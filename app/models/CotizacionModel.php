<?php

require_once ROOT . '/config/Database.php';
require_once ROOT . '/app/helpers/Seguridad.php';

/** Cotizaciones del punto de venta: se guardan para consultarlas y reimprimirlas desde el historial. No tocan el inventario. */
class CotizacionModel {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarTablas();
    }

    /** Crea las tablas la primera vez (así basta con subir los archivos al servidor) */
    private function asegurarTablas(): void {
        if ($this->db->query("SHOW TABLES LIKE 'cotizaciones'")->num_rows) return;
        $this->db->query(
            "CREATE TABLE cotizaciones (
                id             INT AUTO_INCREMENT PRIMARY KEY,
                veterinaria_id INT NOT NULL,
                cliente_id     INT DEFAULT NULL,
                usuario_id     INT DEFAULT NULL,
                subtotal       DECIMAL(12,2) NOT NULL DEFAULT 0,
                descuento      DECIMAL(12,2) NOT NULL DEFAULT 0,
                descuento_pct  DECIMAL(5,2)  DEFAULT NULL,
                total          DECIMAL(12,2) NOT NULL DEFAULT 0,
                notas          VARCHAR(500)  DEFAULT NULL,
                created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_cotizaciones_vet_fecha (veterinaria_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
        );
        $this->db->query(
            "CREATE TABLE cotizacion_items (
                id             INT AUTO_INCREMENT PRIMARY KEY,
                cotizacion_id  INT NOT NULL,
                orden          SMALLINT NOT NULL DEFAULT 0,
                producto_id    INT DEFAULT NULL,
                concepto       VARCHAR(200) NOT NULL,
                detalle        VARCHAR(150) DEFAULT NULL,
                cantidad       INT NOT NULL DEFAULT 1,
                precio         DECIMAL(12,2) NOT NULL DEFAULT 0,
                subtotal       DECIMAL(12,2) NOT NULL DEFAULT 0,
                KEY idx_cotizacion_items (cotizacion_id),
                CONSTRAINT fk_cotizacion_items FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
        );
    }

    /** Guarda la cotización con sus líneas ($id > 0: la actualiza, conservando su número y fecha); devuelve el id (0 si falla) */
    public function guardar(array $c, array $lineas, int $id = 0): int {
        $this->db->begin_transaction();
        try {
            if ($id > 0) {
                $stmt = $this->db->prepare(
                    'UPDATE cotizaciones SET cliente_id = ?, subtotal = ?, descuento = ?, descuento_pct = ?, total = ?, notas = ?
                      WHERE id = ? AND veterinaria_id = ?'
                );
                $stmt->bind_param('iddddsii', $c['cliente_id'], $c['subtotal'], $c['descuento'], $c['descuento_pct'],
                    $c['total'], $c['notas'], $id, $c['veterinaria_id']);
                $stmt->execute();
                $stmt->close();
                $del = $this->db->prepare('DELETE FROM cotizacion_items WHERE cotizacion_id = ?');
                $del->bind_param('i', $id);
                $del->execute();
                $del->close();
            } else {
                $stmt = $this->db->prepare(
                    'INSERT INTO cotizaciones (veterinaria_id, cliente_id, usuario_id, subtotal, descuento, descuento_pct, total, notas)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param('iiidddds', $c['veterinaria_id'], $c['cliente_id'], $c['usuario_id'], $c['subtotal'],
                    $c['descuento'], $c['descuento_pct'], $c['total'], $c['notas']);
                $stmt->execute();
                $id = (int)$this->db->insert_id;
                $stmt->close();
            }

            $ins = $this->db->prepare(
                'INSERT INTO cotizacion_items (cotizacion_id, orden, producto_id, concepto, detalle, cantidad, precio, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($lineas as $n => $l) {
                $ins->bind_param('iiissidd', $id, $n, $l['producto_id'], $l['concepto'], $l['detalle'], $l['cantidad'], $l['precio'], $l['subtotal']);
                $ins->execute();
            }
            $ins->close();
            $this->db->commit();
            return $id;
        } catch (Exception $e) {
            $this->db->rollback();
            return 0;
        }
    }

    /** Cotización completa de la sucursal en sesión (con cliente, quien cotizó y líneas) */
    public function getCotizacion(int $id): ?array {
        $vet  = Seguridad::sucursalActual();
        $stmt = $this->db->prepare(
            'SELECT c.*, u.nombre AS usuario_nombre,
                    cl.nombre AS cliente_nombre, cl.apellido AS cliente_apellido, cl.dni AS cliente_dni, cl.telefono AS cliente_telefono
               FROM cotizaciones c
          LEFT JOIN usuarios u  ON u.id = c.usuario_id
          LEFT JOIN clientes cl ON cl.id = c.cliente_id
              WHERE c.id = ? AND c.veterinaria_id = ?'
        );
        $stmt->bind_param('ii', $id, $vet);
        $stmt->execute();
        $c = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$c) return null;

        $stmt = $this->db->prepare('SELECT * FROM cotizacion_items WHERE cotizacion_id = ? ORDER BY orden, id');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $c['lineas'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $c;
    }

    /** Cotizaciones de la sucursal, las más recientes primero */
    public function getAll(int $veterinaria_id, int $limite = 300): array {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.created_at, c.total, c.descuento, c.notas, u.nombre AS usuario_nombre,
                    CONCAT(cl.nombre, ' ', cl.apellido) AS cliente,
                    (SELECT COUNT(*) FROM cotizacion_items i WHERE i.cotizacion_id = c.id) AS lineas,
                    (SELECT GROUP_CONCAT(i.concepto ORDER BY i.orden SEPARATOR ', ') FROM cotizacion_items i WHERE i.cotizacion_id = c.id) AS detalle
               FROM cotizaciones c
          LEFT JOIN usuarios u  ON u.id = c.usuario_id
          LEFT JOIN clientes cl ON cl.id = c.cliente_id
              WHERE c.veterinaria_id = ?
           ORDER BY c.id DESC
              LIMIT ?"
        );
        $stmt->bind_param('ii', $veterinaria_id, $limite);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}
