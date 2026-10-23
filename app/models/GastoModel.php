<?php

require_once ROOT . '/config/Database.php';

class GastoModel {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getRango(int $veterinaria_id, string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            "SELECT g.*, u.nombre AS usuario_nombre
               FROM gastos g
          LEFT JOIN usuarios u ON u.id = g.usuario_id
              WHERE g.activo = 1 AND g.veterinaria_id = ? AND g.fecha BETWEEN ? AND ?
           ORDER BY g.fecha DESC, g.id DESC"
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /** Totales fijos (hoy / mes actual / mes anterior) independientes del filtro */
    public function getTotales(int $veterinaria_id, string $hoy): array {
        $iniMes     = date('Y-m-01', strtotime($hoy));
        $iniMesAnt  = date('Y-m-01', strtotime("$iniMes -1 month"));
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN fecha = ? THEN monto END), 0) AS hoy,
                COALESCE(SUM(CASE WHEN fecha >= ? AND fecha <= ? THEN monto END), 0) AS mes,
                COALESCE(SUM(CASE WHEN fecha >= ? AND fecha < ? THEN monto END), 0) AS mes_anterior
               FROM gastos WHERE activo = 1 AND veterinaria_id = ?"
        );
        $stmt->bind_param('sssssi', $hoy, $iniMes, $hoy, $iniMesAnt, $iniMes, $veterinaria_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?? [];
        $stmt->close();
        return array_map('floatval', $row);
    }

    /** Ingresos del período (ventas no anuladas + parte del negocio en peluquería + ganancia de laboratorios + abonos de tratamientos), para comparar con los gastos */
    public function getIngresosPeriodo(int $veterinaria_id, string $desde, string $hasta): float {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM ventas
              WHERE veterinaria_id = ? AND estado <> 'anulada' AND DATE(created_at) BETWEEN ? AND ?"
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $total = (float)($stmt->get_result()->fetch_row()[0] ?? 0);
        $stmt->close();

        require_once ROOT . '/app/models/PeluqueriaModel.php';
        $total += (new PeluqueriaModel())->getReparto($veterinaria_id, $desde, $hasta)['negocio'];
        require_once ROOT . '/app/models/LaboratorioModel.php';
        $total += (new LaboratorioModel())->getReparto($veterinaria_id, $desde, $hasta)['negocio'];
        $total += (new TratamientoModel())->getTotalRango($veterinaria_id, $desde, $hasta)['total'];
        return $total;
    }

    /** Cantidad y monto total de gastos en un rango de fechas */
    public function getTotalRango(int $veterinaria_id, string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS cantidad, COALESCE(SUM(monto), 0) AS total
               FROM gastos WHERE activo = 1 AND veterinaria_id = ? AND fecha BETWEEN ? AND ?'
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return ['cantidad' => (int)$row['cantidad'], 'total' => (float)$row['total']];
    }

    public function crear(array $d): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO gastos (veterinaria_id, fecha, descripcion, monto, notas, usuario_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('issdsi',
            $d['veterinaria_id'], $d['fecha'], $d['descripcion'], $d['monto'], $d['notas'], $d['usuario_id']
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function actualizar(int $id, array $d): bool {
        $stmt = $this->db->prepare(
            'UPDATE gastos SET fecha=?, descripcion=?, monto=?, notas=?
              WHERE id=? AND veterinaria_id=? AND activo=1'
        );
        $stmt->bind_param('ssdsii',
            $d['fecha'], $d['descripcion'], $d['monto'], $d['notas'], $id, $d['veterinaria_id']
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function eliminar(int $id, int $veterinaria_id): bool {
        $stmt = $this->db->prepare('UPDATE gastos SET activo=0 WHERE id=? AND veterinaria_id=?');
        $stmt->bind_param('ii', $id, $veterinaria_id);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }
}
