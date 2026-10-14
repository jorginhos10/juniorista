<?php

require_once ROOT . '/config/Database.php';

class DescargoModel {

    public const MOTIVOS = [
        'vencido' => ['label' => 'Vencido', 'icon' => 'calendar-x-fill',           'color' => 'warning'],
        'perdido' => ['label' => 'Perdido', 'icon' => 'exclamation-triangle-fill', 'color' => 'danger'],
        'otros'   => ['label' => 'Otros',   'icon' => 'three-dots',                'color' => 'secondary'],
    ];

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Productos con stock disponible en la sucursal */
    public function getProductosConStock(int $veterinaria_id): array {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.nombre, p.codigo, p.unidad, p.precio_compra, inv.stock
               FROM inventario inv
               JOIN productos p ON p.id = inv.producto_id
              WHERE inv.veterinaria_id = ? AND inv.stock > 0 AND p.activo = 1
           ORDER BY p.nombre ASC'
        );
        $stmt->bind_param('i', $veterinaria_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getRango(int $veterinaria_id, string $desde, string $hasta, string $motivo = ''): array {
        $sql = 'SELECT d.*, (d.cantidad * d.precio_unitario) AS subtotal,
                       p.nombre AS producto_nombre, p.codigo, p.unidad, u.nombre AS usuario_nombre
                  FROM descargos d
                  JOIN productos p ON p.id = d.producto_id
             LEFT JOIN usuarios u ON u.id = d.usuario_id
                 WHERE d.veterinaria_id = ? AND DATE(d.created_at) BETWEEN ? AND ?';
        if ($motivo !== '') {
            $sql .= ' AND d.motivo = ?';
        }
        $sql .= ' ORDER BY d.created_at DESC, d.id DESC';

        $stmt = $this->db->prepare($sql);
        if ($motivo !== '') {
            $stmt->bind_param('isss', $veterinaria_id, $desde, $hasta, $motivo);
        } else {
            $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Registra el descargo y descuenta el stock en una transacción.
     * Devuelve '' si todo salió bien o el mensaje de error.
     */
    public function crear(array $d): string {
        $this->db->begin_transaction();
        try {
            // Descuenta solo si alcanza el stock (evita negativos aunque dos usuarios descarguen a la vez)
            $upd = $this->db->prepare(
                'UPDATE inventario SET stock = stock - ?
                  WHERE veterinaria_id = ? AND producto_id = ? AND stock >= ?'
            );
            $upd->bind_param('iiii', $d['cantidad'], $d['veterinaria_id'], $d['producto_id'], $d['cantidad']);
            $descontado = $upd->execute() && $upd->affected_rows === 1;
            $upd->close();

            if (!$descontado) {
                $this->db->rollback();
                $disp = $this->getStock($d['veterinaria_id'], $d['producto_id']);
                return "Stock insuficiente: solicitado {$d['cantidad']}, disponible $disp.";
            }

            $ins = $this->db->prepare(
                'INSERT INTO descargos (veterinaria_id, producto_id, cantidad, precio_unitario, motivo, motivo_detalle, usuario_id)
                 SELECT ?, id, ?, precio_compra, ?, NULLIF(?, \'\'), ? FROM productos WHERE id = ?'
            );
            $ins->bind_param('iissii',
                $d['veterinaria_id'], $d['cantidad'], $d['motivo'], $d['motivo_detalle'], $d['usuario_id'], $d['producto_id']
            );
            $insertado = $ins->execute() && $ins->affected_rows === 1;
            $ins->close();
            if (!$insertado) {
                throw new Exception('No se insertó el descargo');
            }

            $this->db->commit();
            return '';
        } catch (Exception $e) {
            $this->db->rollback();
            return 'Error al registrar el descargo.';
        }
    }

    /** Anula el descargo y devuelve las unidades al inventario */
    public function eliminar(int $id, int $veterinaria_id): bool {
        $this->db->begin_transaction();
        try {
            $sel = $this->db->prepare('SELECT producto_id, cantidad FROM descargos WHERE id = ? AND veterinaria_id = ? FOR UPDATE');
            $sel->bind_param('ii', $id, $veterinaria_id);
            $sel->execute();
            $row = $sel->get_result()->fetch_assoc();
            $sel->close();
            if (!$row) {
                $this->db->rollback();
                return false;
            }

            $inv = $this->db->prepare(
                'INSERT INTO inventario (veterinaria_id, producto_id, stock) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE stock = stock + VALUES(stock)'
            );
            $inv->bind_param('iii', $veterinaria_id, $row['producto_id'], $row['cantidad']);
            $okInv = $inv->execute();
            $inv->close();

            $del = $this->db->prepare('DELETE FROM descargos WHERE id = ?');
            $del->bind_param('i', $id);
            $okDel = $del->execute();
            $del->close();
            if (!$okInv || !$okDel) {
                throw new Exception('No se pudo anular el descargo');
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    private function getStock(int $veterinaria_id, int $producto_id): int {
        $stmt = $this->db->prepare('SELECT stock FROM inventario WHERE veterinaria_id = ? AND producto_id = ?');
        $stmt->bind_param('ii', $veterinaria_id, $producto_id);
        $stmt->execute();
        $stock = (int)($stmt->get_result()->fetch_row()[0] ?? 0);
        $stmt->close();
        return $stock;
    }
}
