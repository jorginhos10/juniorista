<?php

require_once ROOT . '/config/Database.php';
require_once ROOT . '/app/helpers/Seguridad.php';

/**
 * Hospitalización: cada paciente internado tiene su hoja ECOP (registro de medicamentos aplicados).
 * La hoja se referencia desde la historia médica de la mascota.
 */
class HospitalizacionModel {

    public const ESTADOS = [
        'internado' => ['Internado',  'warning'],
        'alta'      => ['Alta',       'success'],
        'referido'  => ['Referido',   'info'],
        'fallecido' => ['Fallecido',  'secondary'],
    ];

    public const VIAS = ['IV', 'IM', 'SC', 'VO', 'Tópica', 'Oftálmica', 'Ótica', 'Rectal', 'Inhalada', 'Otra'];

    public const FRECUENCIAS = [
        'Dosis única', 'SID (cada 24 h)', 'BID (cada 12 h)', 'TID (cada 8 h)', 'QID (cada 6 h)',
        'Cada 4 h', 'Cada 2 h', 'Infusión continua', 'PRN (si es necesario)',
    ];

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarTablas();
    }

    private function asegurarTablas(): void {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS hospitalizaciones (
                id int(11) NOT NULL AUTO_INCREMENT,
                veterinaria_id int(11) NOT NULL,
                mascota_id int(11) NOT NULL,
                diagnostico varchar(255) NOT NULL DEFAULT '',
                fecha_ingreso datetime NOT NULL,
                fecha_salida datetime DEFAULT NULL,
                peso decimal(6,2) DEFAULT NULL,
                veterinario_id int(11) DEFAULT NULL,
                estado varchar(20) NOT NULL DEFAULT 'internado',
                notas varchar(255) NOT NULL DEFAULT '',
                usuario_id int(11) DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY idx_hosp_vet (veterinaria_id, estado),
                KEY idx_hosp_mascota (mascota_id)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
        );
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS hospitalizacion_aplicaciones (
                id int(11) NOT NULL AUTO_INCREMENT,
                hospitalizacion_id int(11) NOT NULL,
                fecha date NOT NULL,
                hora time NOT NULL,
                medicamento varchar(150) NOT NULL,
                dosis varchar(60) NOT NULL DEFAULT '',
                via varchar(30) NOT NULL DEFAULT '',
                frecuencia varchar(60) NOT NULL DEFAULT '',
                firma varchar(100) NOT NULL DEFAULT '',
                observaciones varchar(255) NOT NULL DEFAULT '',
                usuario_id int(11) DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY idx_hapl_hosp (hospitalizacion_id, fecha, hora)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
        );
        // Segunda página de la hoja: constancias fisiológicas (TEMP, F.C., F.R., P.S.D., S.O., GLU., VOM., DEP., OR.)
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS hospitalizacion_constancias (
                id int(11) NOT NULL AUTO_INCREMENT,
                hospitalizacion_id int(11) NOT NULL,
                fecha date NOT NULL,
                hora time NOT NULL,
                temp varchar(12) NOT NULL DEFAULT '',
                fc varchar(12) NOT NULL DEFAULT '',
                fr varchar(12) NOT NULL DEFAULT '',
                psd varchar(16) NOT NULL DEFAULT '',
                so varchar(12) NOT NULL DEFAULT '',
                glu varchar(12) NOT NULL DEFAULT '',
                vom varchar(30) NOT NULL DEFAULT '',
                dep varchar(30) NOT NULL DEFAULT '',
                orina varchar(30) NOT NULL DEFAULT '',
                firma varchar(100) NOT NULL DEFAULT '',
                usuario_id int(11) DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY idx_hcon_hosp (hospitalizacion_id, fecha, hora)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
        );
    }

    private const SELECT_BASE =
        "SELECT h.*, m.nombre AS mascota_nombre, m.especie, m.raza, m.sexo, m.fecha_nacimiento,
                CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre, c.telefono AS cliente_telefono,
                u.nombre AS veterinario_nombre,
                (SELECT COUNT(*) FROM hospitalizacion_aplicaciones a WHERE a.hospitalizacion_id = h.id) AS total_aplicaciones,
                (SELECT COUNT(*) FROM hospitalizacion_aplicaciones a WHERE a.hospitalizacion_id = h.id AND a.fecha = CURDATE()) AS aplicaciones_hoy,
                (SELECT CONCAT(a.medicamento, '|', TIME_FORMAT(a.hora, '%H:%i'), '|', DATE_FORMAT(a.fecha, '%d/%m'))
                   FROM hospitalizacion_aplicaciones a WHERE a.hospitalizacion_id = h.id
                  ORDER BY a.fecha DESC, a.hora DESC, a.id DESC LIMIT 1) AS ultima_aplicacion
           FROM hospitalizaciones h
           JOIN mascotas m ON m.id = h.mascota_id
      LEFT JOIN clientes c ON c.id = m.cliente_id
      LEFT JOIN usuarios u ON u.id = h.veterinario_id";

    public function getInternados(): array {
        $r = $this->db->query(
            self::SELECT_BASE . " WHERE h.veterinaria_id = " . Seguridad::sucursalActual() . " AND h.estado = 'internado'
             ORDER BY h.fecha_ingreso ASC"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getEgresos(int $limite = 60): array {
        $r = $this->db->query(
            self::SELECT_BASE . " WHERE h.veterinaria_id = " . Seguridad::sucursalActual() . " AND h.estado <> 'internado'
             ORDER BY h.fecha_salida DESC, h.id DESC LIMIT " . (int)$limite
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare(self::SELECT_BASE . ' WHERE h.id = ? AND h.veterinaria_id = ?');
        $vet = Seguridad::sucursalActual();
        $stmt->bind_param('ii', $id, $vet);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /** Hospitalizaciones de una mascota (para la historia médica) */
    public function getPorMascota(int $mascota_id): array {
        $stmt = $this->db->prepare(
            self::SELECT_BASE . ' WHERE h.mascota_id = ? AND h.veterinaria_id = ? ORDER BY h.fecha_ingreso DESC'
        );
        $vet = Seguridad::sucursalActual();
        $stmt->bind_param('ii', $mascota_id, $vet);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function internadoDeMascota(int $mascota_id): ?array {
        foreach ($this->getPorMascota($mascota_id) as $h) {
            if ($h['estado'] === 'internado') return $h;
        }
        return null;
    }

    public function crear(array $d): int {
        $vet = Seguridad::sucursalActual();
        if ($vet <= 0) return 0;
        $peso = $d['peso'];
        $medico = $d['veterinario_id'] ?: null;
        $stmt = $this->db->prepare(
            'INSERT INTO hospitalizaciones (veterinaria_id, mascota_id, diagnostico, fecha_ingreso, peso, veterinario_id, notas, usuario_id)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param('iissdisi', $vet, $d['mascota_id'], $d['diagnostico'], $d['fecha_ingreso'], $peso, $medico, $d['notas'], $d['usuario_id']);
        $ok = $stmt->execute();
        $id = $ok ? (int)$this->db->insert_id : 0;
        $stmt->close();
        return $id;
    }

    public function actualizar(int $id, array $d): bool {
        $medico = $d['veterinario_id'] ?: null;
        $peso = $d['peso'];
        $stmt = $this->db->prepare(
            'UPDATE hospitalizaciones SET diagnostico=?, fecha_ingreso=?, peso=?, veterinario_id=?, notas=? WHERE id=? AND veterinaria_id=?'
        );
        $vet = Seguridad::sucursalActual();
        $stmt->bind_param('ssdisii', $d['diagnostico'], $d['fecha_ingreso'], $peso, $medico, $d['notas'], $id, $vet);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Cambia el estado: 'internado' reabre la hoja; los demás la cierran con fecha de salida */
    public function cambiarEstado(int $id, string $estado, ?string $fecha_salida): bool {
        if (!isset(self::ESTADOS[$estado])) return false;
        $salida = $estado === 'internado' ? null : ($fecha_salida ?: date('Y-m-d H:i:s'));
        $stmt = $this->db->prepare('UPDATE hospitalizaciones SET estado = ?, fecha_salida = ? WHERE id = ? AND veterinaria_id = ?');
        $vet = Seguridad::sucursalActual();
        $stmt->bind_param('ssii', $estado, $salida, $id, $vet);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // ── Aplicaciones (filas de la hoja ECOP) ────────────────

    public function getAplicaciones(int $hospitalizacion_id): array {
        $stmt = $this->db->prepare(
            "SELECT id, DATE_FORMAT(fecha, '%Y-%m-%d') AS fecha, TIME_FORMAT(hora, '%H:%i') AS hora, medicamento, dosis, via, frecuencia, firma, observaciones
               FROM hospitalizacion_aplicaciones WHERE hospitalizacion_id = ? ORDER BY fecha ASC, hora ASC, id ASC"
        );
        $stmt->bind_param('i', $hospitalizacion_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function guardarAplicacion(int $hospitalizacion_id, int $id, array $d): bool {
        if ($id > 0) {
            $stmt = $this->db->prepare(
                'UPDATE hospitalizacion_aplicaciones SET fecha=?, hora=?, medicamento=?, dosis=?, via=?, frecuencia=?, observaciones=?
                  WHERE id=? AND hospitalizacion_id=?'
            );
            $stmt->bind_param('sssssssii', $d['fecha'], $d['hora'], $d['medicamento'], $d['dosis'], $d['via'], $d['frecuencia'], $d['observaciones'], $id, $hospitalizacion_id);
        } else {
            $stmt = $this->db->prepare(
                'INSERT INTO hospitalizacion_aplicaciones (hospitalizacion_id, fecha, hora, medicamento, dosis, via, frecuencia, firma, observaciones, usuario_id)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->bind_param('issssssssi', $hospitalizacion_id, $d['fecha'], $d['hora'], $d['medicamento'], $d['dosis'], $d['via'], $d['frecuencia'], $d['firma'], $d['observaciones'], $d['usuario_id']);
        }
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function eliminarAplicacion(int $hospitalizacion_id, int $id): bool {
        $stmt = $this->db->prepare('DELETE FROM hospitalizacion_aplicaciones WHERE id = ? AND hospitalizacion_id = ?');
        $stmt->bind_param('ii', $id, $hospitalizacion_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Medicamentos para sugerir: los más usados en hojas anteriores y los productos del almacén */
    public function sugerenciasMedicamentos(): array {
        $vet = Seguridad::sucursalActual();
        $out = [];
        $r = $this->db->query(
            "SELECT a.medicamento FROM hospitalizacion_aplicaciones a
               JOIN hospitalizaciones h ON h.id = a.hospitalizacion_id
              WHERE h.veterinaria_id = $vet GROUP BY a.medicamento ORDER BY COUNT(*) DESC LIMIT 100"
        );
        while ($r && ($row = $r->fetch_row())) $out[$row[0]] = true;
        $r = $this->db->query("SELECT nombre FROM productos WHERE activo = 1 ORDER BY nombre ASC LIMIT 500");
        while ($r && ($row = $r->fetch_row())) $out[$row[0]] = true;
        return array_keys($out);
    }

    // ── Constancias fisiológicas (segunda página de la hoja) ─

    public function getConstancias(int $hospitalizacion_id): array {
        $stmt = $this->db->prepare(
            "SELECT id, DATE_FORMAT(fecha, '%Y-%m-%d') AS fecha, TIME_FORMAT(hora, '%H:%i') AS hora,
                    temp, fc, fr, psd, so, glu, vom, dep, orina, firma
               FROM hospitalizacion_constancias WHERE hospitalizacion_id = ? ORDER BY fecha ASC, hora ASC, id ASC"
        );
        $stmt->bind_param('i', $hospitalizacion_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function guardarConstancia(int $hospitalizacion_id, int $id, array $d): bool {
        if ($id > 0) {
            $stmt = $this->db->prepare(
                'UPDATE hospitalizacion_constancias SET fecha=?, hora=?, temp=?, fc=?, fr=?, psd=?, so=?, glu=?, vom=?, dep=?, orina=?
                  WHERE id=? AND hospitalizacion_id=?'
            );
            $stmt->bind_param('sssssssssssii', $d['fecha'], $d['hora'], $d['temp'], $d['fc'], $d['fr'], $d['psd'], $d['so'], $d['glu'], $d['vom'], $d['dep'], $d['orina'], $id, $hospitalizacion_id);
        } else {
            $stmt = $this->db->prepare(
                'INSERT INTO hospitalizacion_constancias (hospitalizacion_id, fecha, hora, temp, fc, fr, psd, so, glu, vom, dep, orina, firma, usuario_id)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->bind_param('issssssssssssi', $hospitalizacion_id, $d['fecha'], $d['hora'], $d['temp'], $d['fc'], $d['fr'], $d['psd'], $d['so'], $d['glu'], $d['vom'], $d['dep'], $d['orina'], $d['firma'], $d['usuario_id']);
        }
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function eliminarConstancia(int $hospitalizacion_id, int $id): bool {
        $stmt = $this->db->prepare('DELETE FROM hospitalizacion_constancias WHERE id = ? AND hospitalizacion_id = ?');
        $stmt->bind_param('ii', $id, $hospitalizacion_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
