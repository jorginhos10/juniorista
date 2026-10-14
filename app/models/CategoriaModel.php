<?php

require_once ROOT . '/config/Database.php';

/** Categorías de productos. productos.categoria guarda el nombre, así que renombrar actualiza también los productos. */
class CategoriaModel {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Categorías con la cantidad de productos de cada una */
    public function getAll(): array {
        $result = $this->db->query(
            'SELECT c.id, c.nombre, c.created_at,
                    (SELECT COUNT(*) FROM productos p WHERE p.categoria = c.nombre) AS total_productos
               FROM categorias c
           ORDER BY c.nombre ASC'
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Solo los nombres, para la lista desplegable del producto */
    public function getNombres(): array {
        return array_column($this->getAll(), 'nombre');
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT id, nombre FROM categorias WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function existe(string $nombre, int $excluirId = 0): bool {
        $stmt = $this->db->prepare('SELECT 1 FROM categorias WHERE nombre = ? AND id <> ?');
        $stmt->bind_param('si', $nombre, $excluirId);
        $stmt->execute();
        $ok = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $ok;
    }

    public function crear(string $nombre): bool {
        $stmt = $this->db->prepare('INSERT INTO categorias (nombre) VALUES (?)');
        $stmt->bind_param('s', $nombre);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Renombra la categoría y los productos que la usan, en una sola transacción */
    public function renombrar(int $id, string $nuevo): bool {
        $actual = $this->findById($id);
        if (!$actual) return false;

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare('UPDATE categorias SET nombre = ? WHERE id = ?');
            $stmt->bind_param('si', $nuevo, $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $this->db->prepare('UPDATE productos SET categoria = ? WHERE categoria = ?');
            $stmt->bind_param('ss', $nuevo, $actual['nombre']);
            $stmt->execute();
            $stmt->close();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function contarProductos(string $nombre): int {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM productos WHERE categoria = ?');
        $stmt->bind_param('s', $nombre);
        $stmt->execute();
        $n = (int)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
        return $n;
    }

    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare('DELETE FROM categorias WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }
}
