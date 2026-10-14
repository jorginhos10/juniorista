<?php

require_once ROOT . '/config/Database.php';

class ProductoModel {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarTablaRelacionados();
    }

    // ── PRODUCTOS RELACIONADOS (hacen lo mismo que otro: se ofrecen como alternativa al vender) ──

    private function asegurarTablaRelacionados(): void {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS producto_relacionados (
                producto_id    int(11) NOT NULL,
                relacionado_id int(11) NOT NULL,
                PRIMARY KEY (producto_id, relacionado_id),
                KEY relacionado_id (relacionado_id),
                CONSTRAINT producto_relacionados_ibfk_1 FOREIGN KEY (producto_id)    REFERENCES productos (id) ON DELETE CASCADE,
                CONSTRAINT producto_relacionados_ibfk_2 FOREIGN KEY (relacionado_id) REFERENCES productos (id) ON DELETE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
    }

    /** Mapa id => [ids relacionados]. La relación vale en los dos sentidos. */
    public function getMapaRelacionados(): array {
        $mapa = [];
        $r = $this->db->query('SELECT producto_id, relacionado_id FROM producto_relacionados');
        foreach ($r ? $r->fetch_all(MYSQLI_NUM) : [] as [$a, $b]) {
            $mapa[(int)$a][] = (int)$b;
            $mapa[(int)$b][] = (int)$a;
        }
        return $mapa;
    }

    /** Reemplaza los relacionados de un producto. Cada pareja se guarda una sola vez (menor id, mayor id). */
    public function guardarRelacionados(int $id, array $ids): bool {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($x) => $x > 0 && $x !== $id)));

        $this->db->begin_transaction();
        try {
            $del = $this->db->prepare('DELETE FROM producto_relacionados WHERE producto_id = ? OR relacionado_id = ?');
            $del->bind_param('ii', $id, $id);
            $del->execute();
            $del->close();

            $ins = $this->db->prepare(
                'INSERT IGNORE INTO producto_relacionados (producto_id, relacionado_id)
                 SELECT ?, ? FROM productos WHERE id = ?'
            );
            foreach ($ids as $otro) {
                $a = min($id, $otro);
                $b = max($id, $otro);
                $ins->bind_param('iii', $a, $b, $otro);   // solo si el otro producto existe
                $ins->execute();
            }
            $ins->close();
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    // Catálogo global; stock por veterinaria desde inventario
    public function getAll(int $veterinaria_id = 0): array {
        if ($veterinaria_id > 0) {
            $stmt = $this->db->prepare(
                'SELECT p.*, COALESCE(inv.stock, 0) AS stock
                 FROM productos p
                 LEFT JOIN inventario inv ON inv.producto_id = p.id AND inv.veterinaria_id = ?
                 ORDER BY p.activo DESC, p.nombre ASC'
            );
            $stmt->bind_param('i', $veterinaria_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        } else {
            $result = $this->db->query(
                'SELECT * FROM productos ORDER BY activo DESC, nombre ASC'
            );
        }
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Totales usando el stock de inventario de la veterinaria seleccionada
    public function getTotales(int $veterinaria_id = 0): array {
        $joinVet = $veterinaria_id > 0 ? "AND inv.veterinaria_id = $veterinaria_id" : '';
        $result = $this->db->query(
            "SELECT
                COUNT(p.id)                                                                AS total_productos,
                COALESCE(SUM(COALESCE(inv.stock, 0)), 0)                                   AS total_stock,
                COUNT(DISTINCT p.categoria)                                                AS total_categorias,
                COALESCE(SUM(COALESCE(inv.stock, 0) * p.precio_compra), 0)                AS valor_inventario,
                SUM(CASE WHEN COALESCE(inv.stock, 0) <= p.stock_minimo THEN 1 ELSE 0 END) AS stock_bajo
             FROM productos p
             LEFT JOIN inventario inv ON inv.producto_id = p.id $joinVet
             WHERE p.activo = 1"
        );
        return $result ? ($result->fetch_assoc() ?? []) : [];
    }

    public function getCategorias(): array {
        $result = $this->db->query(
            'SELECT DISTINCT categoria FROM productos WHERE activo = 1 AND categoria IS NOT NULL ORDER BY categoria'
        );
        return $result ? array_column($result->fetch_all(MYSQLI_ASSOC), 'categoria') : [];
    }

    public function crear(array $datos): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO productos (nombre, codigo, descripcion, categoria, unidad, precio_compra, precio_venta, stock, stock_minimo)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)'
        );
        $stmt->bind_param(
            'sssssddi',
            $datos['nombre'],
            $datos['codigo'],
            $datos['descripcion'],
            $datos['categoria'],
            $datos['unidad'],
            $datos['precio_compra'],
            $datos['precio_venta'],
            $datos['stock_minimo']
        );
        $ok = $stmt->execute();
        $this->ultimoCreado = $ok ? (int)$stmt->insert_id : 0;
        $stmt->close();
        return $ok;
    }

    /** Id del último producto creado con crear() */
    public int $ultimoCreado = 0;

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM productos WHERE id = ? AND activo = 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function actualizar(int $id, array $datos): bool {
        $stmt = $this->db->prepare(
            'UPDATE productos SET nombre=?, codigo=?, descripcion=?, categoria=?, unidad=?, precio_compra=?, precio_venta=?, stock_minimo=?
             WHERE id=?'
        );
        $stmt->bind_param(
            'sssssddii',
            $datos['nombre'],
            $datos['codigo'],
            $datos['descripcion'],
            $datos['categoria'],
            $datos['unidad'],
            $datos['precio_compra'],
            $datos['precio_venta'],
            $datos['stock_minimo'],
            $id
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function toggleActivo(int $id): bool {
        $stmt = $this->db->prepare('UPDATE productos SET activo = 1 - activo WHERE id=?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
