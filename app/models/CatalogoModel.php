<?php

require_once ROOT . '/config/Database.php';

/**
 * Catálogo administrable (Configuración) de cirugías y tratamientos, por sucursal.
 * Alimenta las listas de sugerencias de Órdenes de cirugía, Tratamientos y del servicio en modo cotización.
 */
class CatalogoModel {

    public const TIPOS = [
        'cirugia'     => ['Cirugías',     'Cirugía',     'bi-bandaid',      '#0ea5e9', '#e0f2fe'],
        'tratamiento' => ['Tratamientos', 'Tratamiento', 'bi-capsule-pill', '#16a34a', '#dcfce7'],
    ];

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarTabla();
    }

    private function asegurarTabla(): void {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS catalogo_servicios (
                id             INT AUTO_INCREMENT PRIMARY KEY,
                veterinaria_id INT NOT NULL,
                tipo           VARCHAR(20) NOT NULL,
                nombre         VARCHAR(150) NOT NULL,
                precio         DECIMAL(10,2) NOT NULL DEFAULT 0,
                activo         TINYINT(1) NOT NULL DEFAULT 1,
                created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_catalogo_vet_tipo (veterinaria_id, tipo, activo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
        );
    }

    /** Primera vez en la sucursal: las cirugías arrancan con la tarifa que ya usaba el sistema (una sola vez) */
    private function sembrar(int $veterinaria_id): void {
        $r = $this->db->query("SELECT COUNT(*) FROM catalogo_servicios WHERE veterinaria_id = $veterinaria_id AND tipo = 'cirugia'");
        if ((int)$r->fetch_row()[0] > 0) return;
        require_once ROOT . '/app/models/OrdenCirugiaModel.php';
        $stmt = $this->db->prepare("INSERT INTO catalogo_servicios (veterinaria_id, tipo, nombre) VALUES (?, 'cirugia', ?)");
        foreach (OrdenCirugiaModel::PROCEDIMIENTOS as $n) {
            $stmt->bind_param('is', $veterinaria_id, $n);
            $stmt->execute();
        }
        $stmt->close();
    }

    public function getAll(int $veterinaria_id, string $tipo): array {
        if ($veterinaria_id <= 0 || !isset(self::TIPOS[$tipo])) return [];
        $this->sembrar($veterinaria_id);
        $stmt = $this->db->prepare(
            'SELECT id, nombre, precio FROM catalogo_servicios WHERE activo = 1 AND veterinaria_id = ? AND tipo = ? ORDER BY nombre'
        );
        $stmt->bind_param('is', $veterinaria_id, $tipo);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /** Solo los nombres, en el orden en que se muestran */
    public function nombres(int $veterinaria_id, string $tipo): array {
        return array_column($this->getAll($veterinaria_id, $tipo), 'nombre');
    }

    public function guardar(int $id, int $veterinaria_id, string $tipo, string $nombre, float $precio): bool {
        if (!isset(self::TIPOS[$tipo])) return false;
        if ($id > 0) {
            $stmt = $this->db->prepare('UPDATE catalogo_servicios SET nombre = ?, precio = ? WHERE id = ? AND veterinaria_id = ? AND tipo = ? AND activo = 1');
            $stmt->bind_param('sdiis', $nombre, $precio, $id, $veterinaria_id, $tipo);
        } else {
            $stmt = $this->db->prepare('INSERT INTO catalogo_servicios (veterinaria_id, tipo, nombre, precio) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('issd', $veterinaria_id, $tipo, $nombre, $precio);
        }
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function find(int $id, int $veterinaria_id): ?array {
        $stmt = $this->db->prepare('SELECT id, tipo, nombre, precio FROM catalogo_servicios WHERE id = ? AND veterinaria_id = ? AND activo = 1');
        $stmt->bind_param('ii', $id, $veterinaria_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /** Aplica el precio a las demás sucursales que tengan ese mismo elemento (mismo tipo y nombre). Devuelve cuántas cambió. */
    public function precioEnOtrasSucursales(int $veterinaria_id, string $tipo, string $nombre, float $precio): int {
        $stmt = $this->db->prepare('UPDATE catalogo_servicios SET precio = ? WHERE activo = 1 AND tipo = ? AND nombre = ? AND veterinaria_id <> ?');
        $stmt->bind_param('dssi', $precio, $tipo, $nombre, $veterinaria_id);
        $stmt->execute();
        $n = $stmt->affected_rows;
        $stmt->close();
        return max(0, $n);
    }

    public function existe(int $veterinaria_id, string $tipo, string $nombre, int $excluirId = 0): bool {
        $stmt = $this->db->prepare('SELECT 1 FROM catalogo_servicios WHERE activo = 1 AND veterinaria_id = ? AND tipo = ? AND nombre = ? AND id <> ? LIMIT 1');
        $stmt->bind_param('issi', $veterinaria_id, $tipo, $nombre, $excluirId);
        $stmt->execute();
        $hay = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $hay;
    }

    /** Se quita del catálogo; lo ya registrado (órdenes, tratamientos) conserva su texto */
    public function eliminar(int $id, int $veterinaria_id): bool {
        $stmt = $this->db->prepare('UPDATE catalogo_servicios SET activo = 0 WHERE id = ? AND veterinaria_id = ?');
        $stmt->bind_param('ii', $id, $veterinaria_id);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }
}
