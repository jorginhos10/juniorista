<?php

require_once ROOT . '/app/models/TratamientoModel.php';

/**
 * Registro de laboratorios (exámenes): mismos campos que tratamientos y también se paga con abonos.
 * Cada examen guarda su costo (lo que cobra el laboratorio externo, sumado del catálogo de la sucursal);
 * el valor que se cobra al cliente es libre pero nunca menor al costo, y la diferencia es la ganancia.
 */
class LaboratorioModel extends TratamientoModel {

    protected string $tabla       = 'laboratorios';
    protected string $tablaAbonos = 'laboratorio_abonos';

    private static bool $esquemaListo = false;

    public function __construct() {
        parent::__construct();
        $this->asegurarEsquema();
    }

    /** Crea las tablas y columnas de abonos, costo y catálogo (una vez por petición) */
    private function asegurarEsquema(): void {
        if (self::$esquemaListo) return;
        self::$esquemaListo = true;

        $abonosNuevos = $this->db->query("SHOW TABLES LIKE 'laboratorio_abonos'")->num_rows === 0;
        if ($abonosNuevos) {
            $this->db->query(
                "CREATE TABLE laboratorio_abonos (
                    id             INT AUTO_INCREMENT PRIMARY KEY,
                    tratamiento_id INT NOT NULL,
                    veterinaria_id INT NOT NULL,
                    fecha          DATE NOT NULL,
                    monto          DECIMAL(10,2) NOT NULL,
                    nota           VARCHAR(150) DEFAULT NULL,
                    usuario_id     INT DEFAULT NULL,
                    activo         TINYINT(1) NOT NULL DEFAULT 1,
                    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_lab_abonos_registro (tratamiento_id),
                    KEY idx_lab_abonos_vet_fecha (veterinaria_id, fecha)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
            );
        }

        $columnas = [
            'estado'             => "ENUM('activo','cancelado') NOT NULL DEFAULT 'activo'",
            'motivo_cancelacion' => 'VARCHAR(200) DEFAULT NULL',
            'fecha_cancelacion'  => 'DATE DEFAULT NULL',
            'costo'              => 'DECIMAL(10,2) NOT NULL DEFAULT 0',
        ];
        foreach ($columnas as $col => $def) {
            if ($this->db->query("SHOW COLUMNS FROM laboratorios LIKE '$col'")->num_rows === 0) {
                $this->db->query("ALTER TABLE laboratorios ADD COLUMN $col $def");
            }
        }

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS laboratorio_examenes (
                id             INT AUTO_INCREMENT PRIMARY KEY,
                veterinaria_id INT NOT NULL,
                codigo         VARCHAR(30) NOT NULL,
                nombre         VARCHAR(100) NOT NULL,
                costo          DECIMAL(10,2) NOT NULL DEFAULT 0,
                activo         TINYINT(1) NOT NULL DEFAULT 1,
                created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_lab_examenes_vet (veterinaria_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
        );

        // Combos (perfiles): un elemento del catálogo con su propio costo y la lista de exámenes que incluye (ids separados por coma)
        $extras = [
            'es_combo'       => 'TINYINT(1) NOT NULL DEFAULT 0',
            'incluye'        => 'VARCHAR(255) DEFAULT NULL',
            'incluye_extra'  => 'VARCHAR(255) DEFAULT NULL',                // exámenes del combo que no están en el catálogo (texto)
            'tipo_muestra'   => 'VARCHAR(100) DEFAULT NULL',                // ej: Tubo tapa lila (EDTA), Heces, Orina
            'tiempo_entrega' => "VARCHAR(50) NOT NULL DEFAULT 'Mismo día'",
        ];
        foreach ($extras as $col => $def) {
            if ($this->db->query("SHOW COLUMNS FROM laboratorio_examenes LIKE '$col'")->num_rows === 0) {
                $this->db->query("ALTER TABLE laboratorio_examenes ADD COLUMN $col $def");
            }
        }

        // Los exámenes que ya estaban registrados se cobraron completos el mismo día: pasan a ser un abono por todo su valor
        if ($abonosNuevos) {
            $this->db->query(
                "INSERT INTO laboratorio_abonos (tratamiento_id, veterinaria_id, fecha, monto, nota, usuario_id, created_at)
                 SELECT id, veterinaria_id, fecha, valor, 'Pago completo', usuario_id, created_at
                   FROM laboratorios WHERE activo = 1 AND valor > 0"
            );
        }
    }

    // ── Costo y ganancia ──────────────────────────────────

    /** Guarda el costo del examen (suma de los exámenes elegidos del catálogo) */
    public function crear(array $d, float $abonoInicial = 0, string $notaAbono = ''): int {
        $id = parent::crear($d, $abonoInicial, $notaAbono);
        if ($id > 0) $this->guardarCosto($id, (int)$d['veterinaria_id'], (float)($d['costo'] ?? 0));
        return $id;
    }

    public function actualizar(int $id, array $d): bool {
        $ok = parent::actualizar($id, $d);
        if ($ok && isset($d['costo'])) $this->guardarCosto($id, (int)$d['veterinaria_id'], (float)$d['costo']);
        return $ok;
    }

    private function guardarCosto(int $id, int $veterinaria_id, float $costo): void {
        $stmt = $this->db->prepare('UPDATE laboratorios SET costo = ? WHERE id = ? AND veterinaria_id = ?');
        $stmt->bind_param('dii', $costo, $id, $veterinaria_id);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Reparto de lo cobrado en un rango: ['cantidad' (exámenes con cobros), 'total' (abonos), 'costo', 'negocio' (ganancia)].
     * El costo se reconoce en proporción a lo abonado: un abono del 40 % del valor cubre el 40 % del costo.
     */
    public function getReparto(int $veterinaria_id, string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            'SELECT COUNT(DISTINCT t.id) AS cantidad, COALESCE(SUM(a.monto), 0) AS total,
                    COALESCE(SUM(ROUND(a.monto * LEAST(t.costo, t.valor) / NULLIF(t.valor, 0), 2)), 0) AS costo
               FROM laboratorio_abonos a
               JOIN laboratorios t ON t.id = a.tratamiento_id AND t.activo = 1
              WHERE a.activo = 1 AND a.veterinaria_id = ? AND a.fecha BETWEEN ? AND ?'
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return [
            'cantidad' => (int)$r['cantidad'],
            'total'    => (float)$r['total'],
            'costo'    => (float)$r['costo'],
            'negocio'  => round((float)$r['total'] - (float)$r['costo'], 2),
        ];
    }

    // ── Catálogo de exámenes (costos fijos por sucursal) ──

    /** Catálogo de la sucursal; cada combo trae 'incluye_ids' y 'incluye_texto' (siglas de los exámenes que lo forman) */
    public function getExamenes(int $veterinaria_id): array {
        $stmt = $this->db->prepare(
            'SELECT id, codigo, nombre, costo, es_combo, incluye, incluye_extra, tipo_muestra, tiempo_entrega FROM laboratorio_examenes
              WHERE activo = 1 AND veterinaria_id = ? ORDER BY es_combo, codigo, nombre'
        );
        $stmt->bind_param('i', $veterinaria_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $siglas = array_column($rows, 'codigo', 'id');
        foreach ($rows as &$r) {
            $r['es_combo']      = (int)$r['es_combo'];
            $r['incluye_ids']   = array_values(array_filter(array_map('intval', explode(',', (string)$r['incluye']))));
            $r['incluye_texto'] = implode(', ', array_filter([
                implode(', ', array_map(fn($i) => $siglas[$i] ?? '?', $r['incluye_ids'])),
                trim((string)$r['incluye_extra']),
            ]));
        }
        unset($r);
        return $rows;
    }

    /** Exámenes del catálogo por id (solo los de la sucursal), para calcular el costo en el servidor */
    public function getExamenesPorIds(int $veterinaria_id, array $ids): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => $i > 0)));
        if (!$ids) return [];
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT id, codigo, nombre, costo FROM laboratorio_examenes
              WHERE activo = 1 AND veterinaria_id = ? AND id IN ($marcas)"
        );
        $stmt->bind_param('i' . str_repeat('i', count($ids)), $veterinaria_id, ...$ids);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /** Guarda un examen o un combo ($incluye = ids de los exámenes del combo, vacío si es un examen suelto) */
    public function guardarExamen(int $id, int $veterinaria_id, string $codigo, string $nombre, float $costo, array $incluye = [],
                                  string $tipoMuestra = '', string $tiempoEntrega = 'Mismo día', string $incluyeExtra = ''): bool {
        $esCombo = ($incluye || $incluyeExtra !== '') ? 1 : 0;
        $lista   = implode(',', array_map('intval', $incluye));
        if ($id > 0) {
            $stmt = $this->db->prepare(
                "UPDATE laboratorio_examenes SET codigo = ?, nombre = ?, costo = ?, es_combo = ?, incluye = NULLIF(?, ''),
                        tipo_muestra = NULLIF(?, ''), tiempo_entrega = ?, incluye_extra = NULLIF(?, '')
                  WHERE id = ? AND veterinaria_id = ? AND activo = 1"
            );
            $stmt->bind_param('ssdissssii', $codigo, $nombre, $costo, $esCombo, $lista, $tipoMuestra, $tiempoEntrega, $incluyeExtra, $id, $veterinaria_id);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO laboratorio_examenes (veterinaria_id, codigo, nombre, costo, es_combo, incluye, tipo_muestra, tiempo_entrega, incluye_extra)
                 VALUES (?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, NULLIF(?, ''))"
            );
            $stmt->bind_param('issdissss', $veterinaria_id, $codigo, $nombre, $costo, $esCombo, $lista, $tipoMuestra, $tiempoEntrega, $incluyeExtra);
        }
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function eliminarExamen(int $id, int $veterinaria_id): bool {
        $stmt = $this->db->prepare('UPDATE laboratorio_examenes SET activo = 0 WHERE id = ? AND veterinaria_id = ?');
        $stmt->bind_param('ii', $id, $veterinaria_id);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    /** Costo guardado de un examen ya registrado (se conserva al editar sin volver a elegir exámenes) */
    public function getCosto(int $id, int $veterinaria_id): float {
        $stmt = $this->db->prepare('SELECT costo FROM laboratorios WHERE id = ? AND veterinaria_id = ?');
        $stmt->bind_param('ii', $id, $veterinaria_id);
        $stmt->execute();
        $costo = (float)($stmt->get_result()->fetch_row()[0] ?? 0);
        $stmt->close();
        return $costo;
    }
}
