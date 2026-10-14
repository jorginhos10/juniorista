<?php

require_once ROOT . '/config/Database.php';

class ClienteModel {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array {
        $result = $this->db->query(
            'SELECT c.*, (SELECT COUNT(*) FROM mascotas m WHERE m.cliente_id = c.id AND m.activo = 1) AS total_mascotas
               FROM clientes c WHERE c.activo = 1 ORDER BY c.apellido ASC, c.nombre ASC'
        );
        return ($result !== false) ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getTotales(): array {
        $result = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) THEN 1 ELSE 0 END) AS nuevos_mes
             FROM clientes WHERE activo = 1"
        );
        return ($result !== false) ? ($result->fetch_assoc() ?? []) : [];
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM clientes WHERE id = ? AND activo = 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function crear(array $d): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO clientes (nombre, apellido, dni, email, telefono, direccion, notas)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssssss',
            $d['nombre'], $d['apellido'], $d['dni'],
            $d['email'],  $d['telefono'], $d['direccion'], $d['notas']
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function actualizar(int $id, array $d): bool {
        $stmt = $this->db->prepare(
            'UPDATE clientes SET nombre=?, apellido=?, dni=?, email=?, telefono=?, direccion=?, notas=?
             WHERE id=?'
        );
        $stmt->bind_param('sssssssi',
            $d['nombre'], $d['apellido'], $d['dni'],
            $d['email'],  $d['telefono'], $d['direccion'], $d['notas'], $id
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare('UPDATE clientes SET activo=0 WHERE id=?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
