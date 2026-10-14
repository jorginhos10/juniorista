<?php

require_once ROOT . '/app/models/TratamientoModel.php';

/** Registro diario de peluquería: mismos campos que tratamientos, en su propia tabla. */
class PeluqueriaModel extends TratamientoModel {

    protected string $tabla = 'peluquerias';

    // Peluquería se cobra completa el mismo día
    protected bool $conAbonos = false;

    // % de la peluquería con que se reparte un servicio: el guardado en el registro o, si no tiene, el actual de la sucursal
    private const SQL_PCT = 'COALESCE(t.pct_peluqueria, v.pct_peluqueria, 50)';

    /** Al registrar un servicio se guarda el % vigente de la sucursal, para que un cambio posterior no altere lo ya liquidado */
    public function crear(array $d, float $abonoInicial = 0, string $notaAbono = ''): int {
        $id = parent::crear($d, 0);
        if ($id > 0) {
            $stmt = $this->db->prepare(
                'UPDATE peluquerias t JOIN veterinarias v ON v.id = t.veterinaria_id
                    SET t.pct_peluqueria = v.pct_peluqueria WHERE t.id = ?'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
        return $id;
    }

    /** Servicios del día con su % y lo que le toca a la peluquería y al negocio */
    public function getDia(int $veterinaria_id, string $fecha): array {
        $stmt = $this->db->prepare(
            'SELECT t.*, u.nombre AS usuario_nombre, ' . self::SQL_PCT . ' AS pct,
                    ROUND(t.valor * ' . self::SQL_PCT . ' / 100, 2) AS parte_peluqueria
               FROM peluquerias t
               JOIN veterinarias v ON v.id = t.veterinaria_id
          LEFT JOIN usuarios u ON u.id = t.usuario_id
              WHERE t.activo = 1 AND t.veterinaria_id = ? AND t.fecha = ?
           ORDER BY t.id ASC'
        );
        $stmt->bind_param('is', $veterinaria_id, $fecha);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        foreach ($rows as &$r) {
            $r['parte_negocio'] = round((float)$r['valor'] - (float)$r['parte_peluqueria'], 2);
        }
        unset($r);
        return $rows;
    }

    /** Reparto de un rango: ['total', 'peluqueria', 'negocio', 'cantidad'] */
    public function getReparto(int $veterinaria_id, string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS cantidad, COALESCE(SUM(t.valor), 0) AS total,
                    COALESCE(SUM(ROUND(t.valor * ' . self::SQL_PCT . ' / 100, 2)), 0) AS peluqueria
               FROM peluquerias t
               JOIN veterinarias v ON v.id = t.veterinaria_id
              WHERE t.activo = 1 AND t.veterinaria_id = ? AND t.fecha BETWEEN ? AND ?'
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return [
            'cantidad'   => (int)$r['cantidad'],
            'total'      => (float)$r['total'],
            'peluqueria' => (float)$r['peluqueria'],
            'negocio'    => round((float)$r['total'] - (float)$r['peluqueria'], 2),
        ];
    }

    /** % de la peluquería configurado actualmente en la sucursal */
    public function getPctSucursal(int $veterinaria_id): float {
        $stmt = $this->db->prepare('SELECT pct_peluqueria FROM veterinarias WHERE id = ?');
        $stmt->bind_param('i', $veterinaria_id);
        $stmt->execute();
        $pct = $stmt->get_result()->fetch_row()[0] ?? 50;
        $stmt->close();
        return (float)$pct;
    }
}
