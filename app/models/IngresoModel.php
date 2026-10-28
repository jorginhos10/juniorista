<?php

require_once ROOT . '/config/Database.php';

class IngresoModel {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarColumnasLote();
    }

    // Cada línea de un ingreso es un lote: puede llevar (opcional) número de lote y fecha de vencimiento.
    // Un mismo producto cargado varias veces queda con un lote/vencimiento distinto por cada carga.
    private function asegurarColumnasLote(): void {
        $r = $this->db->query("SHOW COLUMNS FROM ingreso_detalles LIKE 'fecha_vencimiento'");
        if ($r && $r->num_rows === 0) {
            $this->db->query(
                "ALTER TABLE ingreso_detalles
                 ADD COLUMN lote varchar(60) DEFAULT NULL,
                 ADD COLUMN fecha_vencimiento date DEFAULT NULL"
            );
        }
    }

    // ── LISTADOS ──────────────────────────────────────────

    public function getAll(int $veterinaria_id = 0): array {
        if ($veterinaria_id <= 0) return [];   // sin sucursal no se lista nada (nunca todas las veterinarias)
        if ($veterinaria_id > 0) {
            $stmt = $this->db->prepare(
                'SELECT i.*, v.nombre AS veterinaria_nombre,
                        COUNT(d.id)     AS total_lineas,
                        SUM(d.cantidad) AS total_unidades
                 FROM ingresos i
                 JOIN veterinarias v ON v.id = i.veterinaria_id
                 LEFT JOIN ingreso_detalles d ON d.ingreso_id = i.id
                 WHERE i.veterinaria_id = ?
                 GROUP BY i.id ORDER BY i.created_at DESC'
            );
            $stmt->bind_param('i', $veterinaria_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        } else {
            $result = $this->db->query(
                'SELECT i.*, v.nombre AS veterinaria_nombre,
                        COUNT(d.id)     AS total_lineas,
                        SUM(d.cantidad) AS total_unidades
                 FROM ingresos i
                 JOIN veterinarias v ON v.id = i.veterinaria_id
                 LEFT JOIN ingreso_detalles d ON d.ingreso_id = i.id
                 GROUP BY i.id ORDER BY i.created_at DESC'
            );
        }
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Cabecera de un ingreso con el nombre de la sucursal y de quien lo registró */
    public function getCabecera(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT i.*, v.nombre AS veterinaria_nombre, u.nombre AS usuario_nombre
               FROM ingresos i
               JOIN veterinarias v ON v.id = i.veterinaria_id
          LEFT JOIN usuarios u     ON u.id = i.usuario_id
              WHERE i.id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getDetalles(int $ingreso_id): array {
        $stmt = $this->db->prepare(
            'SELECT d.*, p.nombre AS producto_nombre, p.codigo, p.unidad
             FROM ingreso_detalles d
             JOIN productos p ON p.id = d.producto_id
             WHERE d.ingreso_id = ?'
        );
        $stmt->bind_param('i', $ingreso_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getTotales(int $veterinaria_id = 0): array {
        $where  = "WHERE i.veterinaria_id = $veterinaria_id";
        $result = $this->db->query(
            "SELECT
                COUNT(DISTINCT i.id)                                             AS total_ingresos,
                COALESCE(SUM(d.cantidad), 0)                                     AS total_unidades,
                COALESCE(SUM(i.total), 0)                                        AS valor_total,
                COALESCE(SUM(CASE WHEN MONTH(i.created_at) = MONTH(NOW())
                                   AND YEAR(i.created_at)  = YEAR(NOW())
                              THEN i.total ELSE 0 END), 0)                       AS valor_mes
             FROM ingresos i
             LEFT JOIN ingreso_detalles d ON d.ingreso_id = i.id
             $where"
        );
        return $result ? ($result->fetch_assoc() ?? []) : [];
    }

    public function getVeterinarias(): array {
        $result = $this->db->query('SELECT id, nombre FROM veterinarias WHERE activo = 1 ORDER BY nombre ASC');
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getProductos(int $veterinaria_id = 0): array {
        if ($veterinaria_id > 0) {
            $stmt = $this->db->prepare(
                'SELECT p.id, p.nombre, p.codigo, p.unidad, p.precio_compra,
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
                'SELECT id, nombre, codigo, unidad, precio_compra, stock
                 FROM productos WHERE activo = 1 ORDER BY nombre ASC'
            );
        }
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // ── ESCRITURA ─────────────────────────────────────────

    private function upsertStock(int $veterinaria_id, int $producto_id, int $cantidad): void {
        $this->db->query(
            "INSERT INTO inventario (veterinaria_id, producto_id, stock)
             VALUES ($veterinaria_id, $producto_id, $cantidad)
             ON DUPLICATE KEY UPDATE stock = stock + $cantidad"
        );
    }

    private function decrementarStock(int $veterinaria_id, int $producto_id, int $cantidad): void {
        $this->db->query(
            "INSERT INTO inventario (veterinaria_id, producto_id, stock)
             VALUES ($veterinaria_id, $producto_id, 0)
             ON DUPLICATE KEY UPDATE stock = GREATEST(0, stock - $cantidad)"
        );
    }

    public function transferir(int $origen_id, int $destino_id, array $lineas, int $usuario_id): bool {
        if (empty($lineas) || $origen_id === $destino_id) return false;

        $this->db->begin_transaction();
        try {
            $total = array_sum(array_column($lineas, 'subtotal'));

            // Validar stock suficiente en origen
            foreach ($lineas as $l) {
                $pid  = (int)$l['producto_id'];
                $cant = (int)$l['cantidad'];
                $r = $this->db->query(
                    "SELECT COALESCE(stock,0) AS stock FROM inventario
                     WHERE veterinaria_id = $origen_id AND producto_id = $pid"
                );
                $stockDisp = $r ? (int)$r->fetch_assoc()['stock'] : 0;
                if ($cant > $stockDisp) throw new Exception("Stock insuficiente");
            }

            // Ingreso de SALIDA en sucursal origen
            $stmtS = $this->db->prepare(
                'INSERT INTO ingresos (veterinaria_id, proveedor, notas, total, usuario_id, tipo)
                 VALUES (?, NULL, ?, ?, ?, "transferencia_salida")'
            );
            $notaS = "Transferencia enviada a sucursal #$destino_id";
            $stmtS->bind_param('isdi', $origen_id, $notaS, $total, $usuario_id);
            $stmtS->execute();
            $id_salida = (int)$this->db->insert_id;
            $stmtS->close();

            // Ingreso de ENTRADA en sucursal destino
            $stmtE = $this->db->prepare(
                'INSERT INTO ingresos (veterinaria_id, proveedor, notas, total, usuario_id, tipo, transferencia_ref)
                 VALUES (?, NULL, ?, ?, ?, "transferencia_entrada", ?)'
            );
            $notaE = "Transferencia recibida desde sucursal #$origen_id";
            $stmtE->bind_param('isdii', $destino_id, $notaE, $total, $usuario_id, $id_salida);
            $stmtE->execute();
            $id_entrada = (int)$this->db->insert_id;
            $stmtE->close();

            // Actualizar ref cruzada en el registro de salida
            $this->db->query("UPDATE ingresos SET transferencia_ref = $id_entrada WHERE id = $id_salida");

            // Detalles + movimientos de stock
            $ins = $this->db->prepare(
                'INSERT INTO ingreso_detalles (ingreso_id, producto_id, cantidad, precio_unitario) VALUES (?,?,?,?)'
            );
            foreach ($lineas as $l) {
                $pid  = (int)$l['producto_id'];
                $cant = (int)$l['cantidad'];
                $prec = (float)$l['precio_unitario'];

                $ins->bind_param('iiid', $id_salida, $pid, $cant, $prec);
                $ins->execute();
                $ins->bind_param('iiid', $id_entrada, $pid, $cant, $prec);
                $ins->execute();

                $this->decrementarStock($origen_id, $pid, $cant);
                $this->upsertStock($destino_id, $pid, $cant);
            }
            $ins->close();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * El precio de compra del artículo es el último que se aplicó, ya sea desde la edición del producto
     * o desde un ingreso por compra (al crearlo o editarlo): esa acción pisa a la anterior y es el valor
     * por defecto al ingresar de nuevo. Precio 0 (no se escribió) no cambia nada. Si el producto repite
     * línea, manda la última.
     */
    private function aplicarPrecioLineas(array $lineas): void {
        $stmt = $this->db->prepare('UPDATE productos SET precio_compra = ? WHERE id = ?');
        foreach ($lineas as $l) {
            $prec = (float)$l['precio_unitario'];
            $pid  = (int)$l['producto_id'];
            if ($prec <= 0) continue;
            $stmt->bind_param('di', $prec, $pid);
            $stmt->execute();
        }
        $stmt->close();
    }

    // Solo retrocede el precio de los productos cuyo precio vigente era el de la línea que se quitó;
    // si ya tenían otro precio más reciente (producto u otro ingreso), no se toca.
    private function revertirPrecioSiVigente(array $producto_ids, array $precioQuitado): void {
        if (!$producto_ids) return;
        $stmt = $this->db->prepare('SELECT precio_compra FROM productos WHERE id = ?');
        $aRevertir = [];
        foreach (array_unique(array_map('intval', $producto_ids)) as $pid) {
            $stmt->bind_param('i', $pid);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row && abs((float)$row['precio_compra'] - ($precioQuitado[$pid] ?? -1)) <= 0.004) $aRevertir[] = $pid;
        }
        $stmt->close();
        $this->aplicarUltimoPrecio($aRevertir);
    }

    /**
     * Al eliminar un ingreso (o quitarle un producto) el precio vuelve al del último ingreso por compra
     * guardado que quede (por orden de registro); si no queda ninguno, se conserva el actual.
     */
    private function aplicarUltimoPrecio(array $producto_ids): void {
        $stmt = $this->db->prepare(
            "UPDATE productos p
                SET p.precio_compra = (
                        SELECT d.precio_unitario
                          FROM ingreso_detalles d
                          JOIN ingresos i ON i.id = d.ingreso_id
                         WHERE d.producto_id = p.id AND i.tipo = 'compra' AND d.precio_unitario > 0
                      ORDER BY i.id DESC, d.id DESC
                         LIMIT 1
                    )
              WHERE p.id = ?
                AND EXISTS (
                        SELECT 1 FROM ingreso_detalles d2
                          JOIN ingresos i2 ON i2.id = d2.ingreso_id
                         WHERE d2.producto_id = p.id AND i2.tipo = 'compra' AND d2.precio_unitario > 0
                    )"
        );
        foreach (array_unique(array_map('intval', $producto_ids)) as $pid) {
            $stmt->bind_param('i', $pid);
            $stmt->execute();
        }
        $stmt->close();
    }

    public function crear(array $cabecera, array $lineas): bool {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO ingresos (veterinaria_id, proveedor, notas, total, usuario_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $creado = $cabecera['created_at'] ?? date('Y-m-d H:i:s');
            $stmt->bind_param('issdis',
                $cabecera['veterinaria_id'], $cabecera['proveedor'],
                $cabecera['notas'],          $cabecera['total'],
                $cabecera['usuario_id'],     $creado
            );
            $stmt->execute();
            $ingreso_id = (int)$this->db->insert_id;
            $stmt->close();

            $ins = $this->db->prepare(
                'INSERT INTO ingreso_detalles (ingreso_id, producto_id, cantidad, precio_unitario, lote, fecha_vencimiento)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($lineas as $l) {
                $pid  = (int)$l['producto_id'];
                $cant = (int)$l['cantidad'];
                $prec = (float)$l['precio_unitario'];
                $lote = $l['lote'] ?? null;
                $vence = $l['fecha_vencimiento'] ?? null;
                $ins->bind_param('iiidss', $ingreso_id, $pid, $cant, $prec, $lote, $vence);
                $ins->execute();
                $this->upsertStock($cabecera['veterinaria_id'], $pid, $cant);
            }
            $ins->close();
            $this->aplicarPrecioLineas($lineas);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function actualizar(int $id, array $cabecera, array $lineas): bool {
        $this->db->begin_transaction();
        try {
            // Obtener veterinaria_id del ingreso original
            $r   = $this->db->query("SELECT veterinaria_id FROM ingresos WHERE id = $id");
            $vet = (int)$r->fetch_assoc()['veterinaria_id'];

            // Revertir stock de líneas anteriores
            $afectados = [];
            $precioAnterior = [];   // producto => precio que tenía en este ingreso antes de editarlo
            foreach ($this->getDetalles($id) as $d) {
                $this->decrementarStock($vet, $d['producto_id'], $d['cantidad']);
                $afectados[] = $d['producto_id'];
                $precioAnterior[(int)$d['producto_id']] = (float)$d['precio_unitario'];
            }

            // Borrar líneas antiguas
            $del = $this->db->prepare('DELETE FROM ingreso_detalles WHERE ingreso_id = ?');
            $del->bind_param('i', $id);
            $del->execute();
            $del->close();

            // Actualizar cabecera
            $upd = $this->db->prepare('UPDATE ingresos SET proveedor=?, notas=?, total=? WHERE id=?');
            $upd->bind_param('ssdi', $cabecera['proveedor'], $cabecera['notas'], $cabecera['total'], $id);
            $upd->execute();
            $upd->close();

            // Insertar nuevas líneas y actualizar stock
            $ins = $this->db->prepare(
                'INSERT INTO ingreso_detalles (ingreso_id, producto_id, cantidad, precio_unitario, lote, fecha_vencimiento) VALUES (?,?,?,?,?,?)'
            );
            foreach ($lineas as $l) {
                $pid  = (int)$l['producto_id'];
                $cant = (int)$l['cantidad'];
                $prec = (float)$l['precio_unitario'];
                $lote = $l['lote'] ?? null;
                $vence = $l['fecha_vencimiento'] ?? null;
                $ins->bind_param('iiidss', $id, $pid, $cant, $prec, $lote, $vence);
                $ins->execute();
                $this->upsertStock($vet, $pid, $cant);
            }
            $ins->close();
            // Productos que se quitaron de este ingreso: vuelven al precio del último ingreso que quede
            $this->revertirPrecioSiVigente(array_diff($afectados, array_column($lineas, 'producto_id')), $precioAnterior);
            // Solo se aplica el precio de las líneas que cambiaron: guardar un ingreso viejo sin tocar
            // el precio no debe pisar un precio más reciente
            $this->aplicarPrecioLineas(array_filter($lineas, function ($l) use ($precioAnterior) {
                $pid = (int)$l['producto_id'];
                return !isset($precioAnterior[$pid]) || abs($precioAnterior[$pid] - (float)$l['precio_unitario']) > 0.004;
            }));

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function eliminar(int $id): bool {
        $r   = $this->db->query("SELECT veterinaria_id FROM ingresos WHERE id = $id");
        $vet = (int)($r->fetch_assoc()['veterinaria_id'] ?? 0);

        $afectados = [];
        $precioAnterior = [];
        foreach ($this->getDetalles($id) as $d) {
            $this->decrementarStock($vet, $d['producto_id'], $d['cantidad']);
            $afectados[] = $d['producto_id'];
            $precioAnterior[(int)$d['producto_id']] = (float)$d['precio_unitario'];
        }

        $stmt = $this->db->prepare('DELETE FROM ingresos WHERE id = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        // Si el precio vigente era el de este ingreso, vuelve al del ingreso anterior que quede
        if ($ok) $this->revertirPrecioSiVigente($afectados, $precioAnterior);
        return $ok;
    }

    // ── Reporte de ingresos ──────────────────────────────────────────

    /**
     * Ingresos del período (compras y transferencias), con quién los registró y sus unidades.
     * venta = unidades al precio de venta ACTUAL del producto; ganancia = venta − costo registrado en el ingreso.
     */
    public function getReporte(int $veterinaria_id, string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            "SELECT i.id, i.tipo, i.proveedor, i.notas, i.total, i.created_at,
                    COALESCE(u.nombre, 'Sin usuario') AS usuario_nombre,
                    COUNT(d.id) AS lineas, COALESCE(SUM(d.cantidad), 0) AS unidades,
                    COALESCE(SUM(d.cantidad * p.precio_venta), 0) AS venta,
                    COALESCE(SUM(d.cantidad * (p.precio_venta - d.precio_unitario)), 0) AS ganancia
               FROM ingresos i
          LEFT JOIN usuarios u         ON u.id = i.usuario_id
          LEFT JOIN ingreso_detalles d ON d.ingreso_id = i.id
          LEFT JOIN productos p        ON p.id = d.producto_id
              WHERE i.veterinaria_id = ? AND DATE(i.created_at) BETWEEN ? AND ?
           GROUP BY i.id
           ORDER BY i.created_at DESC, i.id DESC"
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Lo que entró al inventario en el período, una línea por producto (compras y transferencias recibidas;
     * las transferencias enviadas son salidas y no cuentan). Cada producto trae los ingresos en que llegó.
     */
    public function getIngresosPorArticulo(int $veterinaria_id, string $desde, string $hasta): array {
        $filtro = "i.veterinaria_id = ? AND i.tipo != 'transferencia_salida' AND DATE(i.created_at) BETWEEN ? AND ?";

        $stmt = $this->db->prepare(
            "SELECT p.id, p.codigo, p.nombre, p.unidad, p.precio_venta,
                    SUM(d.cantidad) * p.precio_venta AS venta,
                    SUM(d.cantidad * (p.precio_venta - d.precio_unitario)) AS ganancia,
                    SUM(d.cantidad) AS cantidad, SUM(d.cantidad * d.precio_unitario) AS total,
                    MIN(d.precio_unitario) AS precio_min, MAX(d.precio_unitario) AS precio_max,
                    CASE WHEN SUM(d.cantidad) > 0 THEN SUM(d.cantidad * d.precio_unitario) / SUM(d.cantidad) ELSE 0 END AS precio_unitario
               FROM ingreso_detalles d
               JOIN ingresos i  ON i.id = d.ingreso_id
               JOIN productos p ON p.id = d.producto_id
              WHERE $filtro
           GROUP BY p.id
           ORDER BY p.nombre ASC"
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $this->db->prepare(
            "SELECT d.producto_id, i.id, i.created_at, COALESCE(u.nombre, 'Sin usuario') AS usuario,
                    SUM(d.cantidad) AS cantidad
               FROM ingreso_detalles d
               JOIN ingresos i      ON i.id = d.ingreso_id
          LEFT JOIN usuarios u      ON u.id = i.usuario_id
              WHERE $filtro
           GROUP BY d.producto_id, i.id
           ORDER BY i.id DESC"
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $res = $stmt->get_result();
        $porProducto = [];
        while ($r = $res->fetch_assoc()) {
            $porProducto[(int)$r['producto_id']][] = [
                'id' => (int)$r['id'], 'created_at' => $r['created_at'], 'usuario' => $r['usuario'], 'cantidad' => (int)$r['cantidad'],
            ];
        }
        $stmt->close();

        foreach ($rows as &$r) {
            $r['ingresos'] = $porProducto[(int)$r['id']] ?? [];
        }
        unset($r);
        return $rows;
    }
}
