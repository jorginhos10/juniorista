<?php

require_once ROOT . '/config/Database.php';
require_once ROOT . '/app/helpers/Seguridad.php';

class HistorialModel {

    public const TIPOS = [
        'consulta'        => ['label' => 'Consulta',        'icon' => 'clipboard2-pulse-fill', 'color' => '#4f46e5'],
        'control'         => ['label' => 'Control',         'icon' => 'arrow-repeat',          'color' => '#0891b2'],
        'vacunacion'      => ['label' => 'Vacunación',      'icon' => 'shield-fill-plus',      'color' => '#16a34a'],
        'desparasitacion' => ['label' => 'Desparasitación', 'icon' => 'bug-fill',              'color' => '#65a30d'],
        'cirugia'         => ['label' => 'Cirugía',         'icon' => 'scissors',              'color' => '#dc2626'],
        'laboratorio'     => ['label' => 'Laboratorio',     'icon' => 'eyedropper',            'color' => '#7c3aed'],
        'urgencia'        => ['label' => 'Urgencia',        'icon' => 'heart-pulse-fill',      'color' => '#ea580c'],
        'otro'            => ['label' => 'Otro',            'icon' => 'three-dots',            'color' => '#64748b'],
    ];

    public const MUCOSAS     = ['Rosadas', 'Pálidas', 'Ictéricas', 'Cianóticas', 'Congestivas'];
    public const TLLC        = ['< 2 s', '> 2 s'];
    public const HIDRATACION = ['Normal', '5 %', '7 %', '10 %', '> 12 %'];

    /** Rangos normales de referencia por especie (adultos en reposo) */
    public const RANGOS = [
        'Perro' => ['temperatura' => [37.5, 39.2], 'frecuencia_cardiaca' => [60, 140],  'frecuencia_respiratoria' => [10, 30]],
        'Gato'  => ['temperatura' => [38.0, 39.2], 'frecuencia_cardiaca' => [140, 220], 'frecuencia_respiratoria' => [20, 30]],
    ];

    private const CAMPOS = [
        'veterinario_id', 'fecha', 'tipo', 'motivo', 'anamnesis', 'peso', 'temperatura',
        'frecuencia_cardiaca', 'frecuencia_respiratoria', 'mucosas', 'tllc', 'hidratacion',
        'condicion_corporal', 'examen_fisico', 'diagnostico', 'tratamiento', 'producto_aplicado',
        'lote', 'proximo_control', 'observaciones',
    ];

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Mascotas con datos del dueño y resumen de su historial */
    public function getMascotas(): array {
        $result = $this->db->query(
            "SELECT m.id, m.nombre, m.especie, m.raza, m.sexo, m.fecha_nacimiento, m.notas, m.cliente_id,
                    CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre, c.telefono AS cliente_telefono,
                    COUNT(h.id) AS total_registros, MAX(h.fecha) AS ultima_visita
               FROM mascotas m
          LEFT JOIN clientes c ON c.id = m.cliente_id AND c.activo = 1
          LEFT JOIN historial_medico h ON h.mascota_id = m.id AND h.activo = 1
              WHERE m.activo = 1 AND m.veterinaria_id = " . Seguridad::sucursalActual() . "
           GROUP BY m.id
           ORDER BY MAX(h.fecha) IS NULL, MAX(h.fecha) DESC, m.nombre ASC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getRegistros(int $mascota_id): array {
        $stmt = $this->db->prepare(
            'SELECT h.*, u.nombre AS veterinario_nombre, v.nombre AS veterinaria_nombre
               FROM historial_medico h
          LEFT JOIN usuarios u     ON u.id = h.veterinario_id
          LEFT JOIN veterinarias v ON v.id = h.veterinaria_id
              WHERE h.mascota_id = ? AND h.activo = 1
                AND h.mascota_id IN (SELECT id FROM mascotas WHERE veterinaria_id = ?)
           ORDER BY h.fecha DESC, h.id DESC'
        );
        $vet = Seguridad::sucursalActual();
        $stmt->bind_param('ii', $mascota_id, $vet);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function mascotaExiste(int $id): bool {
        $stmt = $this->db->prepare('SELECT 1 FROM mascotas WHERE id = ? AND activo = 1 AND veterinaria_id = ?');
        $vet  = Seguridad::sucursalActual();
        $stmt->bind_param('ii', $id, $vet);
        $stmt->execute();
        $ok = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $ok;
    }

    public function getVeterinarios(): array {
        $result = $this->db->query(
            "SELECT id, nombre FROM usuarios WHERE activo = 1 AND rol IN ('veterinario','admin')
                AND " . Seguridad::sqlUsuarioEnSucursal() . "
              ORDER BY rol = 'admin', nombre ASC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Controles y refuerzos pendientes (próximo control desde hoy) */
    public function getProximos(string $hoy, int $limite = 8): array {
        $stmt = $this->db->prepare(
            "SELECT h.id, h.mascota_id, h.tipo, h.proximo_control, h.producto_aplicado, h.motivo,
                    m.nombre AS mascota_nombre, m.especie
               FROM historial_medico h
               JOIN mascotas m ON m.id = h.mascota_id AND m.activo = 1 AND m.veterinaria_id = " . Seguridad::sucursalActual() . "
              WHERE h.activo = 1 AND h.proximo_control >= ?
                -- solo el último registro de cada mascota+tipo (si ya se hizo otro después, no está pendiente)
                AND NOT EXISTS (
                    SELECT 1 FROM historial_medico h2
                     WHERE h2.mascota_id = h.mascota_id AND h2.tipo = h.tipo AND h2.activo = 1
                       AND h2.fecha > h.fecha
                )
           ORDER BY h.proximo_control ASC
              LIMIT ?"
        );
        $stmt->bind_param('si', $hoy, $limite);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function crear(array $d): bool {
        $cols = array_merge(['mascota_id', 'veterinaria_id', 'usuario_id'], self::CAMPOS);
        $sql  = 'INSERT INTO historial_medico (' . implode(', ', $cols) . ')
                 VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
        $stmt = $this->db->prepare($sql);
        $vals = array_map(fn($c) => $d[$c], $cols);
        $stmt->bind_param(str_repeat('s', count($vals)), ...$vals);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function actualizar(int $id, array $d): bool {
        $set  = implode(', ', array_map(fn($c) => "$c = ?", self::CAMPOS));
        $stmt = $this->db->prepare(
            "UPDATE historial_medico SET $set WHERE id = ? AND mascota_id = ? AND activo = 1
                AND mascota_id IN (SELECT id FROM mascotas WHERE veterinaria_id = ?)"
        );
        $vals = array_map(fn($c) => $d[$c], self::CAMPOS);
        $vals[] = $id;
        $vals[] = $d['mascota_id'];
        $vals[] = Seguridad::sucursalActual();
        $stmt->bind_param(str_repeat('s', count($vals)), ...$vals);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare(
            'UPDATE historial_medico SET activo = 0
              WHERE id = ? AND mascota_id IN (SELECT id FROM mascotas WHERE veterinaria_id = ?)'
        );
        $vet = Seguridad::sucursalActual();
        $stmt->bind_param('ii', $id, $vet);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }
}
