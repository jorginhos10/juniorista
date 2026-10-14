<?php

require_once ROOT . '/config/Database.php';

class UsuarioModel {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array {
        $result = $this->db->query(
            'SELECT u.id, u.nombre, u.email, u.rol, u.activo, u.created_at, u.sucursal_id, u.sucursales, u.permisos,
                    v.nombre AS sucursal_nombre
             FROM usuarios u
             LEFT JOIN veterinarias v ON v.id = u.sucursal_id
             WHERE u.eliminado = 0
             ORDER BY u.activo DESC, u.nombre ASC'
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getTotales(): array {
        $result = $this->db->query(
            "SELECT
                COUNT(*)                                                        AS total,
                SUM(activo = 1)                                                 AS activos,
                SUM(rol = 'admin'      AND activo = 1)                         AS admins,
                SUM(rol = 'veterinario' AND activo = 1)                        AS veterinarios,
                SUM(rol = 'recepcion'  AND activo = 1)                         AS recepcionistas
             FROM usuarios WHERE eliminado = 0"
        );
        return $result ? ($result->fetch_assoc() ?? []) : [];
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare(
            'SELECT id, nombre, email, password, rol, ultima_veterinaria, sucursal_id, tema FROM usuarios WHERE email = ? AND activo = 1 AND eliminado = 0'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result  = $stmt->get_result();
        $usuario = $result->num_rows === 1 ? $result->fetch_assoc() : null;
        $stmt->close();
        return $usuario;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT id, nombre, email, rol, activo, created_at FROM usuarios WHERE id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function emailExiste(string $email, int $excluirId = 0): bool {
        $stmt = $this->db->prepare(
            'SELECT id FROM usuarios WHERE email = ? AND id != ?'
        );
        $stmt->bind_param('si', $email, $excluirId);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $existe;
    }

    public function crear(array $d): bool {
        $hash      = password_hash($d['password'], PASSWORD_DEFAULT);
        $sucursal  = $d['sucursal_id'] ?: null;
        $lista     = $d['sucursales'];
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nombre, email, password, rol, sucursal_id, sucursales) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssis', $d['nombre'], $d['email'], $hash, $d['rol'], $sucursal, $lista);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function actualizar(int $id, array $d): bool {
        $sucursal = $d['sucursal_id'] ?: null;
        $lista    = $d['sucursales'];
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET nombre=?, email=?, rol=?, sucursal_id=?, sucursales=? WHERE id=?'
        );
        $stmt->bind_param('sssisi', $d['nombre'], $d['email'], $d['rol'], $sucursal, $lista, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function cambiarPassword(int $id, string $nueva): bool {
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('UPDATE usuarios SET password=? WHERE id=?');
        $stmt->bind_param('si', $hash, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function toggleActivo(int $id): bool {
        $stmt = $this->db->prepare('UPDATE usuarios SET activo = 1 - activo WHERE id=? AND eliminado = 0');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Eliminación lógica: el usuario deja de aparecer y no puede iniciar sesión, pero su nombre se
     * conserva en ventas, abonos, historial, etc. El correo se libera para poder reutilizarlo.
     */
    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare(
            "UPDATE usuarios
                SET eliminado = 1, activo = 0, eliminado_at = NOW(),
                    email = LEFT(CONCAT('eliminado-', id, '-', email), 150)
              WHERE id = ? AND eliminado = 0"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    /** $permisos: JSON con los módulos habilitados, o NULL para acceso total */
    public function guardarPermisos(int $id, ?string $permisos): bool {
        $stmt = $this->db->prepare('UPDATE usuarios SET permisos = ? WHERE id = ? AND eliminado = 0');
        $stmt->bind_param('si', $permisos, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function contarAdminsActivos(int $excluirId = 0): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin' AND activo = 1 AND eliminado = 0 AND id <> ?");
        $stmt->bind_param('i', $excluirId);
        $stmt->execute();
        $n = (int)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
        return $n;
    }

    public function guardarUltimaVeterinaria(int $usuario_id, int $veterinaria_id): void {
        $stmt = $this->db->prepare('UPDATE usuarios SET ultima_veterinaria = ? WHERE id = ?');
        $stmt->bind_param('ii', $veterinaria_id, $usuario_id);
        $stmt->execute();
        $stmt->close();
    }

    public function guardarTema(int $usuario_id, string $tema): bool {
        $stmt = $this->db->prepare('UPDATE usuarios SET tema = ? WHERE id = ?');
        $stmt->bind_param('si', $tema, $usuario_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function verificarPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}
