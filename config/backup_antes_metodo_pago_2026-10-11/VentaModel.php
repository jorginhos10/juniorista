<?php

require_once ROOT . '/config/Database.php';

class VentaModel {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── LISTADOS ──────────────────────────────────────────

    public function getAll(int $veterinaria_id = 0): array {
        // Sin sucursal no se lista nada (nunca las ventas de todas las veterinarias)
        $where = "WHERE v.veterinaria_id = $veterinaria_id";
        $result = $this->db->query(
            "SELECT v.*, vet.nombre AS veterinaria_nombre,
                    CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
                    COUNT(d.id)     AS total_lineas,
                    SUM(d.cantidad) AS total_unidades,
                    u.nombre        AS vendedor_nombre
             FROM ventas v
             JOIN veterinarias vet ON vet.id = v.veterinaria_id
             LEFT JOIN clientes c  ON c.id  = v.cliente_id
             LEFT JOIN venta_detalles d ON d.venta_id = v.id
             LEFT JOIN usuarios u  ON u.id  = v.usuario_id
             $where
             GROUP BY v.id ORDER BY v.created_at DESC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getDetalles(int $venta_id): array {
        $stmt = $this->db->prepare(
            'SELECT d.*, p.nombre AS producto_nombre, p.codigo, p.unidad
             FROM venta_detalles d
             JOIN productos p ON p.id = d.producto_id
             WHERE d.venta_id = ?'
        );
        $stmt->bind_param('i', $venta_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getTotales(int $veterinaria_id = 0): array {
        $where  = "WHERE v.veterinaria_id = $veterinaria_id AND v.estado != 'anulada'";
        $result = $this->db->query(
            "SELECT
                COUNT(DISTINCT v.id)                                              AS total_ventas,
                COALESCE(SUM(d.cantidad), 0)                                      AS total_unidades,
                COALESCE(SUM(v.total), 0)                                         AS ingresos_total,
                COALESCE(SUM(CASE WHEN MONTH(v.created_at) = MONTH(NOW())
                                   AND YEAR(v.created_at)  = YEAR(NOW())
                              THEN v.total ELSE 0 END), 0)                        AS ingresos_mes,
                COALESCE(SUM(CASE WHEN DATE(v.created_at) = CURDATE()
                              THEN v.total ELSE 0 END), 0)                        AS ingresos_hoy
             FROM ventas v
             LEFT JOIN venta_detalles d ON d.venta_id = v.id
             $where"
        );
        return $result ? ($result->fetch_assoc() ?? []) : [];
    }

    public function getReporteUnidades(int $veterinaria_id, string $desde, string $hasta, int $producto_id = 0): array {
        $filtroProd = $producto_id > 0 ? "AND vd.producto_id = $producto_id" : '';
        $stmt = $this->db->prepare(
            "SELECT
                vd.cantidad,
                vd.precio_unitario,
                vd.descuento        AS desc_linea,
                p.id                AS producto_id,
                p.nombre            AS producto_nombre,
                p.codigo            AS producto_codigo,
                p.unidad,
                v.id                AS venta_id,
                v.created_at,
                v.estado,
                DAYNAME(v.created_at)  AS dia_semana,
                TRIM(CONCAT(COALESCE(c.nombre,''),' ',COALESCE(c.apellido,''))) AS cliente_nombre,
                u.nombre            AS vendedor_nombre
             FROM venta_detalles vd
             JOIN ventas   v ON v.id  = vd.venta_id
             JOIN productos p ON p.id = vd.producto_id
             LEFT JOIN usuarios u  ON u.id = v.usuario_id
             LEFT JOIN clientes c  ON c.id = v.cliente_id
             WHERE v.veterinaria_id = ?
               AND v.estado != 'anulada'
               AND DATE(v.created_at) BETWEEN ? AND ?
               $filtroProd
             ORDER BY v.created_at DESC, p.nombre ASC"
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getResumenUnidades(int $veterinaria_id, string $desde, string $hasta, int $producto_id = 0): array {
        $filtroProd = $producto_id > 0 ? "AND vd.producto_id = $producto_id" : '';
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(DISTINCT v.id)            AS total_ventas,
                SUM(vd.cantidad)                AS total_unidades,
                COUNT(DISTINCT vd.producto_id)  AS productos_distintos,
                SUM(vd.cantidad * vd.precio_unitario - COALESCE(vd.descuento,0)) AS total_ingresos
             FROM venta_detalles vd
             JOIN ventas v ON v.id = vd.venta_id
             WHERE v.veterinaria_id = ?
               AND v.estado != 'anulada'
               AND DATE(v.created_at) BETWEEN ? AND ?
               $filtroProd"
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?? [];
    }

    public function getProductosVendidos(int $veterinaria_id): array {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT p.id, p.nombre, p.codigo
             FROM venta_detalles vd
             JOIN ventas v   ON v.id  = vd.venta_id
             JOIN productos p ON p.id = vd.producto_id
             WHERE v.veterinaria_id = ? AND v.estado != 'anulada'
             ORDER BY p.nombre ASC"
        );
        $stmt->bind_param('i', $veterinaria_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getReporte(int $veterinaria_id, string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            "SELECT v.*, vet.nombre AS veterinaria_nombre,
                    CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
                    COUNT(d.id)     AS total_lineas,
                    SUM(d.cantidad) AS total_unidades,
                    u.nombre        AS vendedor_nombre
             FROM ventas v
             JOIN veterinarias vet ON vet.id = v.veterinaria_id
             LEFT JOIN clientes c  ON c.id  = v.cliente_id
             LEFT JOIN venta_detalles d ON d.venta_id = v.id
             LEFT JOIN usuarios u  ON u.id  = v.usuario_id
             WHERE v.veterinaria_id = ?
               AND DATE(v.created_at) BETWEEN ? AND ?
             GROUP BY v.id ORDER BY v.created_at DESC"
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getResumenReporte(int $veterinaria_id, string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*)                                                                AS total_ventas,
                SUM(CASE WHEN v.estado != 'anulada' THEN 1 ELSE 0 END)                 AS ventas_completadas,
                SUM(CASE WHEN v.estado  = 'anulada' THEN 1 ELSE 0 END)                 AS ventas_anuladas,
                COALESCE(SUM(CASE WHEN v.estado != 'anulada' THEN v.total    ELSE 0 END), 0) AS total_ingresos,
                COALESCE(SUM(CASE WHEN v.estado != 'anulada' THEN v.descuento ELSE 0 END), 0) AS total_descuentos,
                COALESCE(SUM(CASE WHEN v.estado != 'anulada'
                                  THEN (SELECT SUM(d.cantidad) FROM venta_detalles d WHERE d.venta_id = v.id)
                                  ELSE 0 END), 0)                                       AS total_unidades,
                COALESCE(AVG(CASE WHEN v.estado != 'anulada' THEN v.total    ELSE NULL END), 0) AS promedio_venta
             FROM ventas v
             WHERE v.veterinaria_id = ?
               AND DATE(v.created_at) BETWEEN ? AND ?"
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?? [];
    }

    public function getTopProductos(int $veterinaria_id, string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            "SELECT p.nombre, SUM(d.cantidad) AS total_unidades,
                    SUM(d.cantidad * d.precio_unitario) AS total_ingresos
             FROM venta_detalles d
             JOIN ventas v ON v.id = d.venta_id
             JOIN productos p ON p.id = d.producto_id
             WHERE v.veterinaria_id = ? AND v.estado != 'anulada'
               AND DATE(v.created_at) BETWEEN ? AND ?
             GROUP BY d.producto_id
             ORDER BY total_unidades DESC
             LIMIT 5"
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getTopVendedores(int $veterinaria_id, string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            "SELECT u.nombre, COUNT(v.id) AS total_ventas,
                    SUM(v.total) AS total_ingresos
             FROM ventas v
             JOIN usuarios u ON u.id = v.usuario_id
             WHERE v.veterinaria_id = ? AND v.estado != 'anulada'
               AND DATE(v.created_at) BETWEEN ? AND ?
             GROUP BY v.usuario_id
             ORDER BY total_ingresos DESC
             LIMIT 5"
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getVeterinarias(): array {
        $result = $this->db->query('SELECT id, nombre FROM veterinarias WHERE activo = 1 ORDER BY nombre ASC');
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Clientes de la sucursal (los clientes no se comparten entre veterinarias) */
    public function getClientes(int $veterinaria_id): array {
        $result = $this->db->query(
            "SELECT id, CONCAT(nombre, ' ', apellido) AS nombre_completo, telefono
             FROM clientes WHERE activo = 1 AND veterinaria_id = $veterinaria_id
             ORDER BY apellido ASC, nombre ASC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function clienteEsDe(int $cliente_id, int $veterinaria_id): bool {
        $stmt = $this->db->prepare('SELECT 1 FROM clientes WHERE id = ? AND veterinaria_id = ? AND activo = 1');
        $stmt->bind_param('ii', $cliente_id, $veterinaria_id);
        $stmt->execute();
        $ok = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $ok;
    }

    public function getProductos(int $veterinaria_id = 0): array {
        if ($veterinaria_id > 0) {
            $stmt = $this->db->prepare(
                'SELECT p.id, p.nombre, p.codigo, p.unidad, p.precio_venta,
                        COALESCE(inv.stock, 0) AS stock
                 FROM productos p
                 LEFT JOIN inventario inv ON inv.producto_id = p.id
                     AND inv.veterinaria_id = ?
                 WHERE p.activo = 1
                 ORDER BY p.nombre ASC'
            );
            $stmt->bind_param('i', $veterinaria_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        } else {
            $result = $this->db->query(
                'SELECT id, nombre, codigo, unidad, precio_venta, stock FROM productos WHERE activo = 1 ORDER BY nombre ASC'
            );
        }
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // ── VALIDACIÓN ────────────────────────────────────────

    public function validarStock(array $lineas, int $veterinaria_id, int $excluirVentaId = 0): array {
        $errores = [];
        foreach ($lineas as $l) {
            $pid  = (int)$l['producto_id'];
            $cant = (int)$l['cantidad'];

            $cantOriginal = 0;
            if ($excluirVentaId > 0) {
                $r = $this->db->query(
                    "SELECT cantidad FROM venta_detalles WHERE venta_id = $excluirVentaId AND producto_id = $pid"
                );
                if ($r && $row = $r->fetch_assoc()) $cantOriginal = (int)$row['cantidad'];
            }

            $r = $this->db->query(
                "SELECT pr.nombre, COALESCE(inv.stock, 0) AS stock
                 FROM productos pr
                 LEFT JOIN inventario inv ON inv.producto_id = pr.id AND inv.veterinaria_id = $veterinaria_id
                 WHERE pr.id = $pid"
            );
            if (!$r) continue;
            $prod = $r->fetch_assoc();
            $disp = (int)$prod['stock'] + $cantOriginal;

            if ($cant <= 0) {
                $errores[] = "«{$prod['nombre']}»: cantidad inválida.";
            } elseif ($disp <= 0) {
                $errores[] = "«{$prod['nombre']}» sin stock en esta veterinaria.";
            } elseif ($cant > $disp) {
                $errores[] = "«{$prod['nombre']}»: solicitado $cant, disponible $disp.";
            }
        }
        return $errores;
    }

    // ── ESCRITURA ─────────────────────────────────────────

    private function decrementarStock(int $veterinaria_id, int $producto_id, int $cantidad): void {
        $this->db->query(
            "INSERT INTO inventario (veterinaria_id, producto_id, stock)
             VALUES ($veterinaria_id, $producto_id, 0)
             ON DUPLICATE KEY UPDATE stock = GREATEST(0, stock - $cantidad)"
        );
    }

    private function incrementarStock(int $veterinaria_id, int $producto_id, int $cantidad): void {
        $this->db->query(
            "INSERT INTO inventario (veterinaria_id, producto_id, stock)
             VALUES ($veterinaria_id, $producto_id, $cantidad)
             ON DUPLICATE KEY UPDATE stock = stock + $cantidad"
        );
    }

    public function crear(array $cabecera, array $lineas): bool {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO ventas (veterinaria_id, cliente_id, notas, descuento, subtotal, total, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $clienteId = $cabecera['cliente_id'] ?: null;
            $stmt->bind_param('iisdddi',
                $cabecera['veterinaria_id'], $clienteId,
                $cabecera['notas'],          $cabecera['descuento'],
                $cabecera['subtotal'],       $cabecera['total'],
                $cabecera['usuario_id']
            );
            $stmt->execute();
            $venta_id = (int)$this->db->insert_id;
            $stmt->close();

            $ins = $this->db->prepare(
                'INSERT INTO venta_detalles (venta_id, producto_id, cantidad, precio_unitario, descuento)
                 VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($lineas as $l) {
                $pid  = (int)$l['producto_id'];
                $cant = (int)$l['cantidad'];
                $prec = (float)$l['precio_unitario'];
                $desc = (float)($l['descuento'] ?? 0);
                $ins->bind_param('iiids', $venta_id, $pid, $cant, $prec, $desc);
                $ins->execute();
                $this->decrementarStock($cabecera['veterinaria_id'], $pid, $cant);
            }
            $ins->close();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function anular(int $id): bool {
        $r   = $this->db->query("SELECT veterinaria_id, estado FROM ventas WHERE id = $id");
        $row = $r->fetch_assoc();
        if (!$row || $row['estado'] === 'anulada') return false;

        $vet = (int)$row['veterinaria_id'];
        foreach ($this->getDetalles($id) as $d) {
            $this->incrementarStock($vet, $d['producto_id'], $d['cantidad']);
        }

        $stmt = $this->db->prepare("UPDATE ventas SET estado = 'anulada' WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
