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
        $this->asegurarFecha();
        $this->asegurarGrupo();
    }

    /** El descargo lleva su propia fecha; en una base sin la columna se crea y se llena con el día de registro */
    /** Los productos descargados juntos comparten «grupo» (el acta de descargo); sin grupo, el acta es el propio descargo */
    private function asegurarGrupo(): void {
        if ($this->db->query("SHOW COLUMNS FROM descargos LIKE 'grupo'")->num_rows) return;
        $this->db->query('ALTER TABLE descargos ADD COLUMN grupo INT DEFAULT NULL AFTER id, ADD KEY idx_descargos_grupo (grupo)');
    }

    /**
     * Descarga varios productos con la misma fecha y motivo, en una sola transacción (todo o nada).
     * $items: [[producto_id, cantidad], …]. Devuelve ['error' => '', 'grupo' => id] o ['error' => mensaje].
     */
    public function crearVarios(array $comun, array $items): array {
        $this->db->begin_transaction();
        try {
            $grupo = 0;
            foreach ($items as [$pid, $cant]) {
                $upd = $this->db->prepare('UPDATE inventario SET stock = stock - ? WHERE veterinaria_id = ? AND producto_id = ? AND stock >= ?');
                $upd->bind_param('iiii', $cant, $comun['veterinaria_id'], $pid, $cant);
                $ok = $upd->execute() && $upd->affected_rows === 1;
                $upd->close();
                if (!$ok) {
                    $this->db->rollback();
                    $disp   = $this->getStock($comun['veterinaria_id'], $pid);
                    $nombre = $this->db->query('SELECT nombre FROM productos WHERE id = ' . (int)$pid)->fetch_row()[0] ?? 'producto';
                    return ['error' => "Stock insuficiente de $nombre: solicitado $cant, disponible $disp. No se descargó nada."];
                }
                $ins = $this->db->prepare(
                    'INSERT INTO descargos (grupo, veterinaria_id, producto_id, fecha, cantidad, precio_unitario, motivo, motivo_detalle, usuario_id)
                     SELECT NULLIF(?, 0), ?, id, ?, ?, precio_compra, ?, NULLIF(?, \'\'), ? FROM productos WHERE id = ?'
                );
                $ins->bind_param('iisissii', $grupo, $comun['veterinaria_id'], $comun['fecha'], $cant, $comun['motivo'],
                    $comun['motivo_detalle'], $comun['usuario_id'], $pid);
                if (!$ins->execute() || $ins->affected_rows !== 1) throw new Exception('No se insertó el descargo');
                $id = (int)$this->db->insert_id;
                $ins->close();
                if ($grupo === 0) {
                    $grupo = $id;   // el primer descargo da el número del acta
                    $this->db->query("UPDATE descargos SET grupo = $grupo WHERE id = $id");
                }
            }
            $this->db->commit();
            return ['error' => '', 'grupo' => $grupo];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['error' => 'Error al registrar el descargo.'];
        }
    }

    /** Productos de un acta de descargo (de la sucursal en sesión) con su producto y quien la registró */
    public function getActa(int $grupo, int $veterinaria_id): array {
        $stmt = $this->db->prepare(
            'SELECT d.*, (d.cantidad * d.precio_unitario) AS subtotal, p.nombre AS producto_nombre, p.codigo, p.unidad,
                    u.nombre AS usuario_nombre
               FROM descargos d
               JOIN productos p ON p.id = d.producto_id
          LEFT JOIN usuarios u  ON u.id = d.usuario_id
              WHERE d.veterinaria_id = ? AND (d.grupo = ? OR (d.grupo IS NULL AND d.id = ?))
           ORDER BY d.id'
        );
        $stmt->bind_param('iii', $veterinaria_id, $grupo, $grupo);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    private function asegurarFecha(): void {
        if ($this->db->query("SHOW COLUMNS FROM descargos LIKE 'fecha'")->num_rows) return;
        $this->db->query('ALTER TABLE descargos ADD COLUMN fecha DATE NULL AFTER producto_id');
        $this->db->query('UPDATE descargos SET fecha = DATE(created_at) WHERE fecha IS NULL');
        $this->db->query('ALTER TABLE descargos MODIFY fecha DATE NOT NULL, ADD KEY idx_descargos_vet_fecha_d (veterinaria_id, fecha)');
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
                 WHERE d.veterinaria_id = ? AND d.fecha BETWEEN ? AND ?';
        if ($motivo !== '') {
            $sql .= ' AND d.motivo = ?';
        }
        $sql .= ' ORDER BY d.fecha DESC, d.created_at DESC, d.id DESC';

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
                'INSERT INTO descargos (veterinaria_id, producto_id, fecha, cantidad, precio_unitario, motivo, motivo_detalle, usuario_id)
                 SELECT ?, id, ?, ?, precio_compra, ?, NULLIF(?, \'\'), ? FROM productos WHERE id = ?'
            );
            $ins->bind_param('isissii',
                $d['veterinaria_id'], $d['fecha'], $d['cantidad'], $d['motivo'], $d['motivo_detalle'], $d['usuario_id'], $d['producto_id']
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

    /**
     * Edita cantidad y motivo de un descargo (el producto no cambia). Si cambia la cantidad, ajusta el stock
     * por la diferencia: más unidades se descuentan (si alcanza el stock), menos vuelven al inventario.
     * Devuelve '' si todo salió bien o el mensaje de error.
     */
    public function actualizar(int $id, int $veterinaria_id, string $fecha, int $cantidad, string $motivo, string $detalle): string {
        $this->db->begin_transaction();
        try {
            $sel = $this->db->prepare('SELECT producto_id, cantidad FROM descargos WHERE id = ? AND veterinaria_id = ? FOR UPDATE');
            $sel->bind_param('ii', $id, $veterinaria_id);
            $sel->execute();
            $row = $sel->get_result()->fetch_assoc();
            $sel->close();
            if (!$row) {
                $this->db->rollback();
                return 'El descargo no existe.';
            }

            $diferencia = $cantidad - (int)$row['cantidad'];
            if ($diferencia > 0) {
                $upd = $this->db->prepare(
                    'UPDATE inventario SET stock = stock - ? WHERE veterinaria_id = ? AND producto_id = ? AND stock >= ?'
                );
                $upd->bind_param('iiii', $diferencia, $veterinaria_id, $row['producto_id'], $diferencia);
                $ok = $upd->execute() && $upd->affected_rows === 1;
                $upd->close();
                if (!$ok) {
                    $this->db->rollback();
                    $disp = $this->getStock($veterinaria_id, (int)$row['producto_id']);
                    return "Stock insuficiente para aumentar el descargo: faltan $diferencia y hay $disp disponible(s).";
                }
            } elseif ($diferencia < 0) {
                $devolver = -$diferencia;
                $inv = $this->db->prepare(
                    'INSERT INTO inventario (veterinaria_id, producto_id, stock) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE stock = stock + VALUES(stock)'
                );
                $inv->bind_param('iii', $veterinaria_id, $row['producto_id'], $devolver);
                if (!$inv->execute()) throw new Exception('No se devolvió el stock');
                $inv->close();
            }

            $up = $this->db->prepare("UPDATE descargos SET fecha = ?, cantidad = ?, motivo = ?, motivo_detalle = NULLIF(?, '') WHERE id = ?");
            $up->bind_param('sissi', $fecha, $cantidad, $motivo, $detalle, $id);
            if (!$up->execute()) throw new Exception('No se actualizó el descargo');
            $up->close();

            $this->db->commit();
            return '';
        } catch (Exception $e) {
            $this->db->rollback();
            return 'Error al editar el descargo.';
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
