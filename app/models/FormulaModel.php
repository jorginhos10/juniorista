<?php

require_once ROOT . '/config/Database.php';
require_once ROOT . '/app/helpers/Seguridad.php';

/**
 * Fórmulas médicas veterinarias (recetas). Cada fórmula es de una mascota, la firma un médico veterinario
 * y lleva uno o varios medicamentos: productos del almacén o escritos a mano.
 * No descuenta inventario: solo prescribe.
 */
class FormulaModel {

    /** Vías de administración habituales */
    public const ESPECIES = ['Perro', 'Gato', 'Ave', 'Conejo', 'Roedor', 'Reptil', 'Otro'];

    /** Datos del paciente cuando no es una mascota registrada (se guardan en la propia fórmula) */
    private const LIBRES = ['paciente_nombre', 'paciente_especie', 'paciente_raza', 'paciente_sexo', 'paciente_edad', 'propietario_nombre', 'propietario_telefono'];

    public const VIAS = ['Oral', 'Tópica', 'Oftálmica', 'Ótica', 'Subcutánea', 'Intramuscular', 'Intravenosa', 'Rectal', 'Inhalada', 'Otra'];

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarTablas();
    }

    /** Crea las tablas la primera vez (así basta con subir los archivos al servidor) */
    private function asegurarTablas(): void {
        if ($this->db->query("SHOW TABLES LIKE 'formulas_medicas'")->num_rows) { $this->asegurarPacienteLibre(); return; }
        $this->db->query(
            "CREATE TABLE formulas_medicas (
                id                  INT AUTO_INCREMENT PRIMARY KEY,
                veterinaria_id      INT NOT NULL,
                mascota_id          INT NOT NULL,
                veterinario_id      INT DEFAULT NULL,
                fecha               DATE NOT NULL,
                peso                DECIMAL(6,2) DEFAULT NULL,
                diagnostico         TEXT DEFAULT NULL,
                indicaciones        TEXT DEFAULT NULL,
                proximo_control     DATE DEFAULT NULL,
                tarjeta_profesional VARCHAR(40) DEFAULT NULL,
                usuario_id          INT DEFAULT NULL,
                activo              TINYINT(1) NOT NULL DEFAULT 1,
                created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_formulas_vet_fecha (veterinaria_id, fecha),
                KEY idx_formulas_mascota (mascota_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
        );
        $this->db->query(
            "CREATE TABLE formula_items (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                formula_id   INT NOT NULL,
                orden        SMALLINT NOT NULL DEFAULT 0,
                producto_id  INT DEFAULT NULL,
                medicamento  VARCHAR(200) NOT NULL,
                presentacion VARCHAR(120) DEFAULT NULL,
                cantidad     VARCHAR(60)  DEFAULT NULL,
                dosis        VARCHAR(120) DEFAULT NULL,
                via          VARCHAR(30)  DEFAULT NULL,
                frecuencia   VARCHAR(80)  DEFAULT NULL,
                duracion     VARCHAR(80)  DEFAULT NULL,
                indicaciones VARCHAR(255) DEFAULT NULL,
                KEY idx_formula_items_formula (formula_id),
                CONSTRAINT fk_formula_items_formula FOREIGN KEY (formula_id) REFERENCES formulas_medicas (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
        );
    }

    /** Columnas para recetar a un paciente que no está registrado como mascota */
    private function asegurarPacienteLibre(): void {
        if ($this->db->query("SHOW COLUMNS FROM formulas_medicas LIKE 'paciente_nombre'")->num_rows) return;
        $this->db->query(
            "ALTER TABLE formulas_medicas
                MODIFY mascota_id INT DEFAULT NULL,
                ADD COLUMN paciente_nombre      VARCHAR(100) DEFAULT NULL AFTER mascota_id,
                ADD COLUMN paciente_especie     VARCHAR(50)  DEFAULT NULL AFTER paciente_nombre,
                ADD COLUMN paciente_raza        VARCHAR(100) DEFAULT NULL AFTER paciente_especie,
                ADD COLUMN paciente_sexo        VARCHAR(10)  DEFAULT NULL AFTER paciente_raza,
                ADD COLUMN paciente_edad        VARCHAR(40)  DEFAULT NULL AFTER paciente_sexo,
                ADD COLUMN propietario_nombre   VARCHAR(150) DEFAULT NULL AFTER paciente_edad,
                ADD COLUMN propietario_telefono VARCHAR(40)  DEFAULT NULL AFTER propietario_nombre"
        );
    }

    /** Fórmulas de la sucursal en un rango de fechas (opcional: de una mascota) */
    public function getRango(string $desde, string $hasta, int $mascota_id = 0): array {
        $vet = Seguridad::sucursalActual();
        $sql = "SELECT f.id, f.fecha, f.diagnostico, f.created_at, f.mascota_id,
                       COALESCE(m.nombre, f.paciente_nombre) AS mascota, COALESCE(m.especie, f.paciente_especie) AS especie,
                       COALESCE(CONCAT(c.nombre, ' ', c.apellido), f.propietario_nombre) AS cliente,
                       u.nombre AS veterinario,
                       (SELECT COUNT(*) FROM formula_items i WHERE i.formula_id = f.id) AS medicamentos,
                       (SELECT GROUP_CONCAT(i.medicamento ORDER BY i.orden SEPARATOR ', ') FROM formula_items i WHERE i.formula_id = f.id) AS lista
                  FROM formulas_medicas f
             LEFT JOIN mascotas m      ON m.id = f.mascota_id
             LEFT JOIN clientes c      ON c.id = m.cliente_id
             LEFT JOIN usuarios u      ON u.id = f.veterinario_id
                 WHERE f.activo = 1 AND f.veterinaria_id = ? AND f.fecha BETWEEN ? AND ?";
        if ($mascota_id > 0) $sql .= ' AND f.mascota_id = ' . (int)$mascota_id;
        $sql .= ' ORDER BY f.fecha DESC, f.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iss', $vet, $desde, $hasta);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /** Una fórmula completa (cabecera, paciente, propietario, médico e ítems) de la sucursal */
    public function getFormula(int $id): ?array {
        $vet  = Seguridad::sucursalActual();
        $stmt = $this->db->prepare(
            "SELECT f.*, COALESCE(m.nombre, f.paciente_nombre) AS mascota, COALESCE(m.especie, f.paciente_especie) AS especie,
                    COALESCE(m.raza, f.paciente_raza) AS raza, COALESCE(m.sexo, f.paciente_sexo) AS sexo, m.fecha_nacimiento,
                    c.id AS cliente_id, COALESCE(CONCAT(c.nombre, ' ', c.apellido), f.propietario_nombre) AS cliente, c.dni AS cliente_dni,
                    COALESCE(c.telefono, f.propietario_telefono) AS cliente_telefono, c.direccion AS cliente_direccion,
                    u.nombre AS veterinario
               FROM formulas_medicas f
          LEFT JOIN mascotas m ON m.id = f.mascota_id
          LEFT JOIN clientes c ON c.id = m.cliente_id
          LEFT JOIN usuarios u ON u.id = f.veterinario_id
              WHERE f.id = ? AND f.activo = 1 AND f.veterinaria_id = ?"
        );
        $stmt->bind_param('ii', $id, $vet);
        $stmt->execute();
        $f = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$f) return null;

        $stmt = $this->db->prepare('SELECT * FROM formula_items WHERE formula_id = ? ORDER BY orden, id');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $f['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $f;
    }

    /** Mascotas activas de la sucursal con su propietario */
    public function getMascotas(): array {
        $r = $this->db->query(
            "SELECT m.id, m.nombre, m.especie, m.raza, m.sexo, m.fecha_nacimiento,
                    CONCAT(c.nombre, ' ', c.apellido) AS cliente, c.telefono AS cliente_telefono,
                    (SELECT h.peso FROM historial_medico h WHERE h.mascota_id = m.id AND h.activo = 1 AND h.peso IS NOT NULL
                      ORDER BY h.fecha DESC, h.id DESC LIMIT 1) AS ultimo_peso
               FROM mascotas m
          LEFT JOIN clientes c ON c.id = m.cliente_id AND c.activo = 1
              WHERE m.activo = 1 AND m.veterinaria_id = " . Seguridad::sucursalActual() . "
           ORDER BY m.nombre ASC"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Productos del almacén para recetar (con el stock de la sucursal, solo informativo) */
    public function getProductos(): array {
        $vet = Seguridad::sucursalActual();
        $r = $this->db->query(
            "SELECT p.id, p.nombre, p.codigo, p.descripcion, p.unidad, p.categoria, COALESCE(inv.stock, 0) AS stock
               FROM productos p
          LEFT JOIN inventario inv ON inv.producto_id = p.id AND inv.veterinaria_id = $vet
              WHERE p.activo = 1
           ORDER BY p.nombre ASC"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Médicos veterinarios que pueden firmar (veterinarios y administradores de la sucursal) */
    public function getVeterinarios(): array {
        $r = $this->db->query(
            "SELECT id, nombre FROM usuarios
              WHERE activo = 1 AND eliminado = 0 AND rol IN ('veterinario','admin')
                AND " . Seguridad::sqlUsuarioEnSucursal() . "
           ORDER BY rol = 'admin', nombre ASC"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Última tarjeta profesional usada por cada médico (para proponerla en la siguiente fórmula) */
    public function getTarjetas(): array {
        $r = $this->db->query(
            "SELECT f.veterinario_id, f.tarjeta_profesional
               FROM formulas_medicas f
               JOIN (SELECT veterinario_id, MAX(id) AS id FROM formulas_medicas
                      WHERE tarjeta_profesional IS NOT NULL AND tarjeta_profesional <> '' GROUP BY veterinario_id) u ON u.id = f.id"
        );
        $out = [];
        foreach ($r ? $r->fetch_all(MYSQLI_ASSOC) : [] as $t) $out[(int)$t['veterinario_id']] = $t['tarjeta_profesional'];
        return $out;
    }

    public function mascotaEsDeLaSucursal(int $id): bool {
        $stmt = $this->db->prepare('SELECT 1 FROM mascotas WHERE id = ? AND activo = 1 AND veterinaria_id = ?');
        $vet  = Seguridad::sucursalActual();
        $stmt->bind_param('ii', $id, $vet);
        $stmt->execute();
        $ok = $stmt->get_result()->num_rows === 1;
        $stmt->close();
        return $ok;
    }

    /** Crea o actualiza la fórmula con sus ítems (en una transacción). Devuelve el id o 0 si falla. */
    public function guardar(int $id, array $d, array $items): int {
        $this->db->begin_transaction();
        try {
            if ($id > 0) {
                $stmt = $this->db->prepare(
                    'UPDATE formulas_medicas SET mascota_id = ?, veterinario_id = ?, fecha = ?, peso = ?, diagnostico = ?,
                            indicaciones = ?, proximo_control = ?, tarjeta_profesional = ?,
                            paciente_nombre = ?, paciente_especie = ?, paciente_raza = ?, paciente_sexo = ?, paciente_edad = ?,
                            propietario_nombre = ?, propietario_telefono = ?
                      WHERE id = ? AND activo = 1 AND veterinaria_id = ?'
                );
                $stmt->bind_param('iisdsssssssssssii', $d['mascota_id'], $d['veterinario_id'], $d['fecha'], $d['peso'], $d['diagnostico'],
                    $d['indicaciones'], $d['proximo_control'], $d['tarjeta_profesional'],
                    $d['paciente_nombre'], $d['paciente_especie'], $d['paciente_raza'], $d['paciente_sexo'], $d['paciente_edad'],
                    $d['propietario_nombre'], $d['propietario_telefono'], $id, $d['veterinaria_id']);
                $stmt->execute();
                $stmt->close();
                $del = $this->db->prepare('DELETE FROM formula_items WHERE formula_id = ?');
                $del->bind_param('i', $id);
                $del->execute();
                $del->close();
            } else {
                $stmt = $this->db->prepare(
                    'INSERT INTO formulas_medicas (veterinaria_id, mascota_id, veterinario_id, fecha, peso, diagnostico,
                                                   indicaciones, proximo_control, tarjeta_profesional, usuario_id,
                                                   paciente_nombre, paciente_especie, paciente_raza, paciente_sexo, paciente_edad,
                                                   propietario_nombre, propietario_telefono)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param('iiisdssssisssssss', $d['veterinaria_id'], $d['mascota_id'], $d['veterinario_id'], $d['fecha'], $d['peso'],
                    $d['diagnostico'], $d['indicaciones'], $d['proximo_control'], $d['tarjeta_profesional'], $d['usuario_id'],
                    $d['paciente_nombre'], $d['paciente_especie'], $d['paciente_raza'], $d['paciente_sexo'], $d['paciente_edad'],
                    $d['propietario_nombre'], $d['propietario_telefono']);
                $stmt->execute();
                $id = (int)$this->db->insert_id;
                $stmt->close();
            }

            $ins = $this->db->prepare(
                'INSERT INTO formula_items (formula_id, orden, producto_id, medicamento, presentacion, cantidad, dosis, via, frecuencia, duracion, indicaciones)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($items as $n => $it) {
                $ins->bind_param('iiissssssss', $id, $n, $it['producto_id'], $it['medicamento'], $it['presentacion'], $it['cantidad'],
                    $it['dosis'], $it['via'], $it['frecuencia'], $it['duracion'], $it['indicaciones']);
                $ins->execute();
            }
            $ins->close();

            $this->db->commit();
            return $id;
        } catch (Exception $e) {
            $this->db->rollback();
            return 0;
        }
    }

    /** Anula la fórmula (se conserva en la base, deja de aparecer) */
    public function anular(int $id): bool {
        $stmt = $this->db->prepare('UPDATE formulas_medicas SET activo = 0 WHERE id = ? AND veterinaria_id = ?');
        $vet  = Seguridad::sucursalActual();
        $stmt->bind_param('ii', $id, $vet);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }
}
