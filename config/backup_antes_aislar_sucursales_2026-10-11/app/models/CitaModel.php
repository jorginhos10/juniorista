<?php

require_once ROOT . '/config/Database.php';

class CitaModel {

    public const TIPOS = [
        'consulta'        => 'Consulta',
        'vacunacion'      => 'Vacunación',
        'desparasitacion' => 'Desparasitación',
        'control'         => 'Control',
        'cirugia'         => 'Cirugía',
        'estetica'        => 'Estética / Baño',
        'urgencia'        => 'Urgencia',
        'otro'            => 'Otro',
    ];

    public const ESTADOS = [
        'pendiente'  => 'Pendiente',
        'confirmada' => 'Confirmada',
        'en_curso'   => 'En curso',
        'atendida'   => 'Atendida',
        'cancelada'  => 'Cancelada',
        'no_asistio' => 'No asistió',
    ];

    // Estados que ya no ocupan el horario
    private const ESTADOS_LIBRES = "'cancelada','no_asistio'";

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    private const SELECT_BASE =
        "SELECT c.*,
                m.nombre  AS mascota_nombre, m.especie AS mascota_especie, m.raza AS mascota_raza,
                CONCAT(cl.nombre, ' ', cl.apellido) AS cliente_nombre, cl.telefono AS cliente_telefono,
                u.nombre  AS veterinario_nombre
           FROM citas c
           JOIN mascotas m  ON m.id  = c.mascota_id
           JOIN clientes cl ON cl.id = c.cliente_id
      LEFT JOIN usuarios u  ON u.id  = c.veterinario_id";

    public function getRango(int $veterinaria_id, string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            self::SELECT_BASE . ' WHERE c.veterinaria_id = ? AND c.fecha BETWEEN ? AND ?
                                  ORDER BY c.fecha ASC, c.hora ASC'
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(self::SELECT_BASE . ' WHERE c.id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getTotales(int $veterinaria_id, string $hoy, string $iniSemana, string $finSemana): array {
        $stmt = $this->db->prepare(
            "SELECT
                SUM(fecha = ? AND estado NOT IN (" . self::ESTADOS_LIBRES . ")) AS hoy,
                SUM(fecha = ? AND estado IN ('pendiente','confirmada')) AS hoy_por_atender,
                SUM(fecha BETWEEN ? AND ? AND estado NOT IN (" . self::ESTADOS_LIBRES . ")) AS semana,
                SUM(estado = 'atendida' AND YEAR(fecha) = YEAR(?) AND MONTH(fecha) = MONTH(?)) AS atendidas_mes,
                SUM(estado = 'pendiente' AND fecha >= ?) AS por_confirmar
             FROM citas WHERE veterinaria_id = ?"
        );
        $stmt->bind_param('sssssssi', $hoy, $hoy, $iniSemana, $finSemana, $hoy, $hoy, $hoy, $veterinaria_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?? [];
        $stmt->close();
        return array_map('intval', $row);
    }

    /** Devuelve la cita que se cruza con el horario indicado (mismo veterinario o misma mascota), o null. */
    public function buscarCruce(array $d, int $excluirId = 0): ?array {
        $stmt = $this->db->prepare(
            self::SELECT_BASE . "
             WHERE c.fecha = ? AND c.id <> ?
               AND c.estado NOT IN (" . self::ESTADOS_LIBRES . ")
               AND (c.mascota_id = ? OR (? > 0 AND c.veterinario_id = ?))
               AND c.hora < ADDTIME(?, SEC_TO_TIME(? * 60))
               AND ADDTIME(c.hora, SEC_TO_TIME(c.duracion * 60)) > ?
             LIMIT 1"
        );
        $vet = (int)$d['veterinario_id'];
        $stmt->bind_param('siiiisis',
            $d['fecha'], $excluirId, $d['mascota_id'], $vet, $vet,
            $d['hora'], $d['duracion'], $d['hora']
        );
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function crear(array $d): int {
        $stmt = $this->db->prepare(
            'INSERT INTO citas (veterinaria_id, cliente_id, mascota_id, veterinario_id, fecha, hora,
                                duracion, tipo, motivo, estado, notas, usuario_id)
             VALUES (?, ?, ?, NULLIF(?, 0), ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('iiiississssi',
            $d['veterinaria_id'], $d['cliente_id'], $d['mascota_id'], $d['veterinario_id'],
            $d['fecha'], $d['hora'], $d['duracion'], $d['tipo'], $d['motivo'], $d['estado'],
            $d['notas'], $d['usuario_id']
        );
        $ok = $stmt->execute();
        $id = $ok ? $stmt->insert_id : 0;
        $stmt->close();
        return $id;
    }

    public function actualizar(int $id, array $d): bool {
        $stmt = $this->db->prepare(
            'UPDATE citas SET cliente_id=?, mascota_id=?, veterinario_id=NULLIF(?, 0), fecha=?, hora=?,
                              duracion=?, tipo=?, motivo=?, estado=?, notas=?
             WHERE id=? AND veterinaria_id=?'
        );
        $stmt->bind_param('iiississssii',
            $d['cliente_id'], $d['mascota_id'], $d['veterinario_id'], $d['fecha'], $d['hora'],
            $d['duracion'], $d['tipo'], $d['motivo'], $d['estado'], $d['notas'],
            $id, $d['veterinaria_id']
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function cambiarEstado(int $id, int $veterinaria_id, string $estado): bool {
        $stmt = $this->db->prepare('UPDATE citas SET estado=? WHERE id=? AND veterinaria_id=?');
        $stmt->bind_param('sii', $estado, $id, $veterinaria_id);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    public function eliminar(int $id, int $veterinaria_id): bool {
        $stmt = $this->db->prepare('DELETE FROM citas WHERE id=? AND veterinaria_id=?');
        $stmt->bind_param('ii', $id, $veterinaria_id);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    // ── Catálogos ──────────────────────────────────────

    public function getVeterinarios(): array {
        $result = $this->db->query(
            "SELECT id, nombre, rol FROM usuarios
              WHERE activo = 1 AND rol IN ('veterinario','admin')
              ORDER BY rol = 'admin', nombre ASC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getClientes(): array {
        $result = $this->db->query(
            "SELECT id, CONCAT(nombre, ' ', apellido) AS nombre_completo, dni, telefono
               FROM clientes WHERE activo = 1 ORDER BY apellido ASC, nombre ASC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getMascotasCliente(int $cliente_id): array {
        $stmt = $this->db->prepare(
            'SELECT id, nombre, especie, raza, sexo FROM mascotas
              WHERE cliente_id = ? AND activo = 1 ORDER BY nombre ASC'
        );
        $stmt->bind_param('i', $cliente_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function mascotaPerteneceA(int $mascota_id, int $cliente_id): bool {
        $stmt = $this->db->prepare('SELECT 1 FROM mascotas WHERE id = ? AND cliente_id = ? AND activo = 1');
        $stmt->bind_param('ii', $mascota_id, $cliente_id);
        $stmt->execute();
        $ok = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $ok;
    }

    public function crearMascota(array $d): int {
        $stmt = $this->db->prepare(
            'INSERT INTO mascotas (cliente_id, nombre, especie, raza, sexo)
             VALUES (?, ?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'))'
        );
        $stmt->bind_param('issss', $d['cliente_id'], $d['nombre'], $d['especie'], $d['raza'], $d['sexo']);
        $ok = $stmt->execute();
        $id = $ok ? $stmt->insert_id : 0;
        $stmt->close();
        return $id;
    }
}
