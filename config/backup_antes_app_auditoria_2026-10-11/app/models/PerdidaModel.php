<?php

require_once ROOT . '/config/Database.php';

class PerdidaModel {

    private mysqli $db;

    public const MOTIVOS = [
        'perdida'     => ['label' => 'Pérdida',                'icon' => 'exclamation-triangle-fill', 'color' => 'danger'],
        'vencimiento' => ['label' => 'Vencimiento',            'icon' => 'calendar-x-fill',           'color' => 'warning'],
        'a_bodega'    => ['label' => 'A Bodega',               'icon' => 'box-arrow-in-down',          'color' => 'info'],
        'propietario' => ['label' => 'Tomado por propietario', 'icon' => 'person-fill-down',           'color' => 'secondary'],
    ];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── LISTADOS ──────────────────────────────────────────

    public function getAll(int $veterinaria_id = 0): array {
        if ($veterinaria_id <= 0) return [];   // sin sucursal no se lista nada (nunca todas las veterinarias)
        if ($veterinaria_id > 0) {
            $stmt = $this->db->prepare(
                'SELECT p.*, v.nombre AS veterinaria_nombre,
                        COUNT(d.id)     AS total_lineas,
                        SUM(d.cantidad) AS total_unidades
                 FROM perdidas p
                 JOIN veterinarias v ON v.id = p.veterinaria_id
                 LEFT JOIN perdida_detalles d ON d.perdida_id = p.id
                 WHERE p.veterinaria_id = ?
                 GROUP BY p.id ORDER BY p.created_at DESC'
            );
            $stmt->bind_param('i', $veterinaria_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        } else {
            $result = $this->db->query(
                'SELECT p.*, v.nombre AS veterinaria_nombre,
                        COUNT(d.id)     AS total_lineas,
                        SUM(d.cantidad) AS total_unidades
                 FROM perdidas p
                 JOIN veterinarias v ON v.id = p.veterinaria_id
                 LEFT JOIN perdida_detalles d ON d.perdida_id = p.id
                 GROUP BY p.id ORDER BY p.created_at DESC'
            );
        }
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getDetalles(int $perdida_id): array {
        $stmt = $this->db->prepare(
            'SELECT d.*, p.nombre AS producto_nombre, p.codigo, p.unidad
             FROM perdida_detalles d
             JOIN productos p ON p.id = d.producto_id
             WHERE d.perdida_id = ?'
        );
        $stmt->bind_param('i', $perdida_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getTotales(int $veterinaria_id = 0): array {
        $where  = "WHERE p.veterinaria_id = $veterinaria_id";
        $result = $this->db->query(
            "SELECT
                COUNT(DISTINCT p.id)                                             AS total_perdidas,
                COALESCE(SUM(d.cantidad), 0)                                     AS total_unidades,
                COALESCE(SUM(p.total), 0)                                        AS valor_total,
                COALESCE(SUM(CASE WHEN MONTH(p.created_at) = MONTH(NOW())
                                   AND YEAR(p.created_at)  = YEAR(NOW())
                              THEN p.total ELSE 0 END), 0)                       AS valor_mes
             FROM perdidas p
             LEFT JOIN perdida_detalles d ON d.perdida_id = p.id
             $where"
        );
        return $result ? ($result->fetch_assoc() ?? []) : [];
    }

    public function getReporte(int $veterinaria_id, string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            "SELECT p.*, v.nombre AS veterinaria_nombre,
                    COUNT(d.id)     AS total_lineas,
                    SUM(d.cantidad) AS total_unidades,
                    u.nombre        AS responsable_nombre
             FROM perdidas p
             JOIN veterinarias v ON v.id = p.veterinaria_id
             LEFT JOIN perdida_detalles d ON d.perdida_id = p.id
             LEFT JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.veterinaria_id = ?
               AND DATE(p.created_at) BETWEEN ? AND ?
             GROUP BY p.id ORDER BY p.created_at DESC"
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
                COUNT(DISTINCT p.id)                                                        AS total_perdidas,
                COALESCE(SUM(d.cantidad), 0)                                                AS total_unidades,
                COALESCE(SUM(p.total), 0)                                                   AS valor_total,
                SUM(CASE WHEN p.motivo = 'perdida'     THEN 1 ELSE 0 END)                  AS por_perdida,
                SUM(CASE WHEN p.motivo = 'vencimiento' THEN 1 ELSE 0 END)                  AS por_vencimiento,
                SUM(CASE WHEN p.motivo = 'a_bodega'    THEN 1 ELSE 0 END)                  AS por_bodega,
                SUM(CASE WHEN p.motivo = 'propietario' THEN 1 ELSE 0 END)                  AS por_propietario
             FROM perdidas p
             LEFT JOIN perdida_detalles d ON d.perdida_id = p.id
             WHERE p.veterinaria_id = ?
               AND DATE(p.created_at) BETWEEN ? AND ?"
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?? [];
    }

    public function getVeterinarias(): array {
        $result = $this->db->query('SELECT id, nombre FROM veterinarias WHERE activo = 1 ORDER BY nombre ASC');
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
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

    // ── VALIDACIÓN DE STOCK ───────────────────────────────

    public function validarStock(array $lineas, int $veterinaria_id, int $excluirPerdidaId = 0): array {
        $errores = [];
        foreach ($lineas as $l) {
            $pid  = (int)$l['producto_id'];
            $cant = (int)$l['cantidad'];

            // En edición: el stock de las líneas originales ya fue descontado, sumarlo al disponible
            $cantidadOriginal = 0;
            if ($excluirPerdidaId > 0) {
                $r = $this->db->query(
                    "SELECT cantidad FROM perdida_detalles
                     WHERE perdida_id = $excluirPerdidaId AND producto_id = $pid"
                );
                if ($r && $row = $r->fetch_assoc()) {
                    $cantidadOriginal = (int)$row['cantidad'];
                }
            }

            $r = $this->db->query(
                "SELECT pr.nombre, COALESCE(inv.stock, 0) AS stock
                 FROM productos pr
                 LEFT JOIN inventario inv ON inv.producto_id = pr.id
                     AND inv.veterinaria_id = $veterinaria_id
                 WHERE pr.id = $pid"
            );
            if (!$r) continue;
            $prod = $r->fetch_assoc();
            $disp = (int)$prod['stock'] + $cantidadOriginal;

            if ($cant <= 0) {
                $errores[] = "«{$prod['nombre']}»: la cantidad debe ser mayor a 0.";
            } elseif ($disp <= 0) {
                $errores[] = "«{$prod['nombre']}» no tiene stock en esta veterinaria.";
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
                'INSERT INTO perdidas (veterinaria_id, motivo, responsable, notas, total, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('isssdi',
                $cabecera['veterinaria_id'], $cabecera['motivo'],
                $cabecera['responsable'],    $cabecera['notas'],
                $cabecera['total'],          $cabecera['usuario_id']
            );
            $stmt->execute();
            $perdida_id = (int)$this->db->insert_id;
            $stmt->close();

            $ins = $this->db->prepare(
                'INSERT INTO perdida_detalles (perdida_id, producto_id, cantidad, precio_unitario)
                 VALUES (?, ?, ?, ?)'
            );
            foreach ($lineas as $l) {
                $pid  = (int)$l['producto_id'];
                $cant = (int)$l['cantidad'];
                $prec = (float)$l['precio_unitario'];
                $ins->bind_param('iiid', $perdida_id, $pid, $cant, $prec);
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

    public function actualizar(int $id, array $cabecera, array $lineas): bool {
        $this->db->begin_transaction();
        try {
            $r   = $this->db->query("SELECT veterinaria_id FROM perdidas WHERE id = $id");
            $vet = (int)($r->fetch_assoc()['veterinaria_id'] ?? 0);

            // Restaurar stock de las líneas anteriores
            foreach ($this->getDetalles($id) as $d) {
                $this->incrementarStock($vet, $d['producto_id'], $d['cantidad']);
            }

            $del = $this->db->prepare('DELETE FROM perdida_detalles WHERE perdida_id = ?');
            $del->bind_param('i', $id);
            $del->execute();
            $del->close();

            $upd = $this->db->prepare(
                'UPDATE perdidas SET motivo=?, responsable=?, notas=?, total=? WHERE id=?'
            );
            $upd->bind_param('sssdi',
                $cabecera['motivo'], $cabecera['responsable'],
                $cabecera['notas'],  $cabecera['total'], $id
            );
            $upd->execute();
            $upd->close();

            $ins = $this->db->prepare(
                'INSERT INTO perdida_detalles (perdida_id, producto_id, cantidad, precio_unitario) VALUES (?,?,?,?)'
            );
            foreach ($lineas as $l) {
                $pid  = (int)$l['producto_id'];
                $cant = (int)$l['cantidad'];
                $prec = (float)$l['precio_unitario'];
                $ins->bind_param('iiid', $id, $pid, $cant, $prec);
                $ins->execute();
                $this->decrementarStock($vet, $pid, $cant);
            }
            $ins->close();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function eliminar(int $id): bool {
        $r   = $this->db->query("SELECT veterinaria_id FROM perdidas WHERE id = $id");
        $vet = (int)($r->fetch_assoc()['veterinaria_id'] ?? 0);

        foreach ($this->getDetalles($id) as $d) {
            $this->incrementarStock($vet, $d['producto_id'], $d['cantidad']);
        }

        $stmt = $this->db->prepare('DELETE FROM perdidas WHERE id = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
