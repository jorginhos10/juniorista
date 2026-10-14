<?php

require_once ROOT . '/config/Database.php';

class ProductoModel {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
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
        $stmt->close();
        return $ok;
    }

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
