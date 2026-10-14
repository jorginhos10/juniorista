<?php

require_once ROOT . '/config/Database.php';
require_once ROOT . '/app/helpers/Seguridad.php';

class ProveedorModel {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array {
        $result = $this->db->query(
            'SELECT * FROM proveedores WHERE veterinaria_id = ' . Seguridad::sucursalActual() . ' ORDER BY activo DESC, nombre ASC'
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getTotales(): array {
        $result = $this->db->query(
            "SELECT
                COUNT(*)            AS total,
                SUM(activo = 1)     AS activos,
                SUM(activo = 0)     AS inactivos
             FROM proveedores WHERE veterinaria_id = " . Seguridad::sucursalActual()
        );
        return $result ? ($result->fetch_assoc() ?? []) : [];
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM proveedores WHERE id = ? AND veterinaria_id = ?');
        $vet  = Seguridad::sucursalActual();
        $stmt->bind_param('ii', $id, $vet);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function crear(array $d): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO proveedores (nombre, contacto, telefono, email, direccion, notas, veterinaria_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $vet = Seguridad::sucursalActual();
        if ($vet <= 0) return false;
        $stmt->bind_param('ssssssi',
            $d['nombre'], $d['contacto'], $d['telefono'],
            $d['email'],  $d['direccion'], $d['notas'], $vet
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function actualizar(int $id, array $d): bool {
        $stmt = $this->db->prepare(
            'UPDATE proveedores SET nombre=?, contacto=?, telefono=?, email=?, direccion=?, notas=?
             WHERE id=? AND veterinaria_id=?'
        );
        $vet = Seguridad::sucursalActual();
        $stmt->bind_param('ssssssii',
            $d['nombre'], $d['contacto'], $d['telefono'],
            $d['email'],  $d['direccion'], $d['notas'], $id, $vet
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function toggleActivo(int $id): bool {
        $stmt = $this->db->prepare('UPDATE proveedores SET activo = 1 - activo WHERE id=? AND veterinaria_id=?');
        $vet  = Seguridad::sucursalActual();
        $stmt->bind_param('ii', $id, $vet);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function tieneIngresos(int $id): bool {
        $prov = $this->findById($id);
        if (!$prov) return false;
        $nombre = $this->db->real_escape_string($prov['nombre']);
        $r = $this->db->query("SELECT COUNT(*) FROM ingresos WHERE proveedor = '$nombre' AND veterinaria_id = " . Seguridad::sucursalActual());
        return $r && (int)$r->fetch_row()[0] > 0;
    }
}
