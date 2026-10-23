<?php

require_once ROOT . '/config/Database.php';

class VentaModel {

    /** Métodos de pago de una venta: clave guardada => texto que se muestra */
    public const METODOS_PAGO = [
        'efectivo'      => 'Efectivo',
        'transferencia' => 'Transferencia',
        'datafono'      => 'Datáfono',
        'mixto'         => 'Mixto',
    ];

    /** Métodos entre los que se reparte un pago mixto */
    public const METODOS_MIXTO = ['efectivo', 'transferencia', 'datafono'];

    /** Texto del método de una venta; en pago mixto incluye cuánto se pagó con cada uno */
    public static function textoMetodoPago(?string $metodo, ?string $detalle = null): string {
        if (!$metodo) return '';
        $texto = self::METODOS_PAGO[$metodo] ?? $metodo;
        $partes = [];
        foreach ((array)json_decode((string)$detalle, true) as $k => $monto) {
            if (isset(self::METODOS_PAGO[$k]) && (float)$monto > 0) {
                $partes[] = self::METODOS_PAGO[$k] . ' $' . number_format((float)$monto, 2);
            }
        }
        return $partes ? $texto . ': ' . implode(' + ', $partes) : $texto;
    }

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarColumnaMetodoPago();
    }

    // Las ventas anteriores a este cambio quedan sin método (NULL)
    private function asegurarColumnaMetodoPago(): void {
        $r = $this->db->query("SHOW COLUMNS FROM ventas LIKE 'metodo_pago'");
        if ($r && $r->num_rows === 0) {
            $this->db->query("ALTER TABLE ventas ADD COLUMN metodo_pago varchar(20) DEFAULT NULL AFTER total");
        }
        // Pago mixto: cuánto se pagó con cada método, en JSON
        $r = $this->db->query("SHOW COLUMNS FROM ventas LIKE 'pago_detalle'");
        if ($r && $r->num_rows === 0) {
            $this->db->query("ALTER TABLE ventas ADD COLUMN pago_detalle varchar(255) DEFAULT NULL AFTER metodo_pago");
        }
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

    /**
     * Productos vendidos en el periodo (sin las ventas anuladas), una sola línea por producto con todas sus
     * cantidades sumadas. Si se vendió a precios distintos, precio_min y precio_max lo indican y el precio
     * que se muestra es el promedio (total / cantidad).
     */
    public function getVentasPorArticulo(int $veterinaria_id, string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.codigo, p.nombre, p.unidad,
                    SUM(d.cantidad) AS cantidad, SUM(d.subtotal) AS total,
                    MIN(d.precio_unitario) AS precio_min, MAX(d.precio_unitario) AS precio_max,
                    COUNT(DISTINCT v.id) AS ventas,
                    CASE WHEN SUM(d.cantidad) > 0 THEN SUM(d.subtotal) / SUM(d.cantidad) ELSE 0 END AS precio_unitario
               FROM venta_detalles d
               JOIN ventas v    ON v.id = d.venta_id
               JOIN productos p ON p.id = d.producto_id
              WHERE v.veterinaria_id = ? AND v.estado != 'anulada'
                AND DATE(v.created_at) BETWEEN ? AND ?
           GROUP BY p.id
           ORDER BY p.nombre ASC"
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Facturas en las que salió cada producto (con quién la vendió)
        $facturas = $this->getFacturasPorArticulo($veterinaria_id, $desde, $hasta);
        foreach ($rows as &$r) {
            $r['facturas'] = $facturas[(int)$r['id']] ?? [];
        }
        unset($r);
        return $rows;
    }

    /** Ventas no anuladas del período agrupadas por producto: [producto_id => [[id, created_at, vendedor, cantidad], …]] */
    public function getFacturasPorArticulo(int $veterinaria_id, string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            "SELECT d.producto_id, v.id, v.created_at, COALESCE(u.nombre, 'Sin usuario') AS vendedor,
                    SUM(d.cantidad) AS cantidad
               FROM venta_detalles d
               JOIN ventas v        ON v.id = d.venta_id
               LEFT JOIN usuarios u ON u.id = v.usuario_id
              WHERE v.veterinaria_id = ? AND v.estado != 'anulada'
                AND DATE(v.created_at) BETWEEN ? AND ?
           GROUP BY d.producto_id, v.id
           ORDER BY v.id DESC"
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($r = $res->fetch_assoc()) {
            $out[(int)$r['producto_id']][] = [
                'id'         => (int)$r['id'],
                'created_at' => $r['created_at'],
                'vendedor'   => $r['vendedor'],
                'cantidad'   => (int)$r['cantidad'],
            ];
        }
        $stmt->close();
        return $out;
    }

    /**
     * Dinero recibido por cada usuario en el período: ventas, abonos de tratamientos, peluquería y laboratorios.
     * Usa las mismas reglas que el balance (sin anulados; los abonos cuentan por su fecha).
     */
    public function getRecaudoPorUsuario(int $veterinaria_id, string $desde, string $hasta): array {
        require_once ROOT . '/app/models/LaboratorioModel.php';
        new LaboratorioModel();   // asegura las tablas de abonos de laboratorios
        $stmt = $this->db->prepare(
            "SELECT m.usuario_id, COALESCE(u.nombre, 'Sin usuario') AS nombre, m.concepto,
                    COUNT(*) AS cantidad, SUM(m.monto) AS total
               FROM (
                    SELECT usuario_id, 'ventas' AS concepto, total AS monto
                      FROM ventas
                     WHERE veterinaria_id = ? AND estado != 'anulada' AND DATE(created_at) BETWEEN ? AND ?
                    UNION ALL
                    SELECT a.usuario_id, 'tratamientos', a.monto
                      FROM tratamiento_abonos a
                      JOIN tratamientos t ON t.id = a.tratamiento_id AND t.activo = 1
                     WHERE a.activo = 1 AND a.veterinaria_id = ? AND a.fecha BETWEEN ? AND ?
                    UNION ALL
                    SELECT usuario_id, 'peluqueria', valor
                      FROM peluquerias
                     WHERE activo = 1 AND veterinaria_id = ? AND fecha BETWEEN ? AND ?
                    UNION ALL
                    SELECT a.usuario_id, 'laboratorios', a.monto
                      FROM laboratorio_abonos a
                      JOIN laboratorios t ON t.id = a.tratamiento_id AND t.activo = 1
                     WHERE a.activo = 1 AND a.veterinaria_id = ? AND a.fecha BETWEEN ? AND ?
               ) m
          LEFT JOIN usuarios u ON u.id = m.usuario_id
           GROUP BY m.usuario_id, m.concepto"
        );
        $stmt->bind_param('ississississ',
            $veterinaria_id, $desde, $hasta, $veterinaria_id, $desde, $hasta,
            $veterinaria_id, $desde, $hasta, $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($r = $res->fetch_assoc()) {
            $k = (int)$r['usuario_id'];
            $out[$k] ??= ['nombre' => $r['nombre'], 'ventas' => 0.0, 'tratamientos' => 0.0, 'peluqueria' => 0.0,
                          'laboratorios' => 0.0, 'movimientos' => 0, 'total' => 0.0];
            $out[$k][$r['concepto']] = (float)$r['total'];
            $out[$k]['movimientos'] += (int)$r['cantidad'];
            $out[$k]['total']       += (float)$r['total'];
        }
        $stmt->close();
        usort($out, fn($a, $b) => $b['total'] <=> $a['total']);
        return $out;
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

    /** Nombre, código, unidad y precio de venta de los productos indicados (para la cotización) */
    public function getProductosPorId(array $ids): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) return [];
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT id, nombre, codigo, unidad, precio_venta FROM productos WHERE activo = 1 AND id IN ($marcas)");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $res = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $p) $res[(int)$p['id']] = $p;
        $stmt->close();
        return $res;
    }

    public function getProductos(int $veterinaria_id = 0): array {
        if ($veterinaria_id > 0) {
            $stmt = $this->db->prepare(
                'SELECT p.id, p.nombre, p.codigo, p.unidad, p.precio_venta, p.foto,
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
                'SELECT id, nombre, codigo, unidad, precio_venta, foto, stock FROM productos WHERE activo = 1 ORDER BY nombre ASC'
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

    /** Id de la última venta creada con crear() (para adjuntarle las fotos del comprobante) */
    public int $ultimaVenta = 0;

    public function crear(array $cabecera, array $lineas): bool {
        $this->ultimaVenta = 0;
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO ventas (veterinaria_id, cliente_id, notas, descuento, subtotal, total, usuario_id, metodo_pago, pago_detalle, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, NOW()))'
            );
            $clienteId = $cabecera['cliente_id'] ?: null;
            $metodo    = $cabecera['metodo_pago'] ?? null;
            $detalle   = $cabecera['pago_detalle'] ?? null;
            $fecha     = $cabecera['fecha'] ?? null;   // null = ahora
            $stmt->bind_param('iisdddisss',
                $cabecera['veterinaria_id'], $clienteId,
                $cabecera['notas'],          $cabecera['descuento'],
                $cabecera['subtotal'],       $cabecera['total'],
                $cabecera['usuario_id'],     $metodo, $detalle,
                $fecha
            );
            $stmt->execute();
            $venta_id = (int)$this->db->insert_id;
            $this->ultimaVenta = $venta_id;
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

    /** Venta activa de la sucursal con sus líneas, para abrirla en el punto de venta en modo edición */
    public function getParaEditar(int $id, int $veterinaria_id): ?array {
        $stmt = $this->db->prepare(
            "SELECT v.id, v.veterinaria_id, v.cliente_id, v.notas, v.descuento, v.metodo_pago, v.pago_detalle, v.created_at,
                    CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre
               FROM ventas v
          LEFT JOIN clientes c ON c.id = v.cliente_id
              WHERE v.id = ? AND v.veterinaria_id = ? AND v.estado <> 'anulada'"
        );
        $stmt->bind_param('ii', $id, $veterinaria_id);
        $stmt->execute();
        $venta = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$venta) return null;

        $venta['lineas'] = array_map(fn($d) => [
            'producto_id' => (int)$d['producto_id'],
            'nombre'      => $d['producto_nombre'],
            'unidad'      => $d['unidad'],
            'cantidad'    => (int)$d['cantidad'],
            'precio'      => (float)$d['precio_unitario'],
        ], $this->getDetalles($id));
        return $venta;
    }

    /**
     * Reemplaza los productos y los datos de una venta. Todo en una transacción: el stock de las líneas
     * anteriores vuelve al inventario y se descuenta el de las nuevas, como si se anulara y se registrara de nuevo.
     */
    public function actualizar(int $id, array $cabecera, array $lineas): bool {
        $this->db->begin_transaction();
        try {
            $r   = $this->db->query("SELECT veterinaria_id, estado FROM ventas WHERE id = $id FOR UPDATE");
            $row = $r ? $r->fetch_assoc() : null;
            if (!$row || $row['estado'] === 'anulada' || (int)$row['veterinaria_id'] !== (int)$cabecera['veterinaria_id']) {
                $this->db->rollback();
                return false;
            }
            $vet = (int)$row['veterinaria_id'];

            foreach ($this->getDetalles($id) as $d) {
                $this->incrementarStock($vet, (int)$d['producto_id'], (int)$d['cantidad']);
            }
            $this->db->query("DELETE FROM venta_detalles WHERE venta_id = $id");

            $ins = $this->db->prepare(
                'INSERT INTO venta_detalles (venta_id, producto_id, cantidad, precio_unitario, descuento)
                 VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($lineas as $l) {
                $pid  = (int)$l['producto_id'];
                $cant = (int)$l['cantidad'];
                $prec = (float)$l['precio_unitario'];
                $desc = (float)($l['descuento'] ?? 0);
                $ins->bind_param('iiidd', $id, $pid, $cant, $prec, $desc);
                $ins->execute();
                $this->decrementarStock($vet, $pid, $cant);
            }
            $ins->close();

            $clienteId = $cabecera['cliente_id'] ?: null;
            $fecha     = $cabecera['fecha'] ?? null;   // null = se conserva la fecha original
            $stmt = $this->db->prepare(
                'UPDATE ventas SET cliente_id = ?, notas = ?, descuento = ?, subtotal = ?, total = ?,
                                   metodo_pago = ?, pago_detalle = ?, created_at = COALESCE(?, created_at)
                 WHERE id = ?'
            );
            $stmt->bind_param('isdddsssi',
                $clienteId, $cabecera['notas'], $cabecera['descuento'], $cabecera['subtotal'], $cabecera['total'],
                $cabecera['metodo_pago'], $cabecera['pago_detalle'], $fecha, $id
            );
            $stmt->execute();
            $stmt->close();

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
