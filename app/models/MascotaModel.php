<?php

require_once ROOT . '/config/Database.php';
require_once ROOT . '/app/helpers/Seguridad.php';

class MascotaModel {

    public const ESPECIES = ['Perro', 'Gato', 'Ave', 'Conejo', 'Roedor', 'Reptil', 'Otro'];

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array {
        $result = $this->db->query(
            "SELECT m.*, CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre, c.telefono AS cliente_telefono,
                    (SELECT COUNT(*) FROM citas ci WHERE ci.mascota_id = m.id) AS total_citas
               FROM mascotas m
          LEFT JOIN clientes c ON c.id = m.cliente_id AND c.activo = 1
              WHERE m.activo = 1 AND m.veterinaria_id = " . Seguridad::sucursalActual() . "
           ORDER BY m.nombre ASC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getClientes(): array {
        $result = $this->db->query(
            "SELECT id, CONCAT(nombre, ' ', apellido) AS nombre_completo, dni, telefono
               FROM clientes WHERE activo = 1 AND veterinaria_id = " . Seguridad::sucursalActual() . "
              ORDER BY apellido ASC, nombre ASC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function clienteExiste(int $id): bool {
        $stmt = $this->db->prepare('SELECT 1 FROM clientes WHERE id = ? AND activo = 1 AND veterinaria_id = ?');
        $vet  = Seguridad::sucursalActual();
        $stmt->bind_param('ii', $id, $vet);
        $stmt->execute();
        $ok = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $ok;
    }

    public function crear(array $d): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO mascotas (cliente_id, nombre, especie, raza, sexo, fecha_nacimiento, notas, veterinaria_id)
             VALUES (NULLIF(?, 0), ?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), ?)'
        );
        $vet = Seguridad::sucursalActual();
        if ($vet <= 0) return false;
        $stmt->bind_param('issssssi',
            $d['cliente_id'], $d['nombre'], $d['especie'], $d['raza'], $d['sexo'], $d['fecha_nacimiento'], $d['notas'], $vet
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function actualizar(int $id, array $d): bool {
        $stmt = $this->db->prepare(
            'UPDATE mascotas SET cliente_id = NULLIF(?, 0), nombre = ?, especie = ?, raza = NULLIF(?, \'\'),
                                 sexo = NULLIF(?, \'\'), fecha_nacimiento = NULLIF(?, \'\'), notas = NULLIF(?, \'\')
              WHERE id = ? AND activo = 1 AND veterinaria_id = ?'
        );
        $vet = Seguridad::sucursalActual();
        $stmt->bind_param('issssssii',
            $d['cliente_id'], $d['nombre'], $d['especie'], $d['raza'], $d['sexo'], $d['fecha_nacimiento'], $d['notas'], $id, $vet
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare('UPDATE mascotas SET activo = 0 WHERE id = ? AND veterinaria_id = ?');
        $vet  = Seguridad::sucursalActual();
        $stmt->bind_param('ii', $id, $vet);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }
}
