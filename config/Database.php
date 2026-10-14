<?php

class Database {

    private static ?Database $instance = null;
    private mysqli $connection;

    private string $host     = 'localhost';
    private string $user     = 'jorginho_juniorista';
    private string $password = 'jorginho10.';
    private string $dbname   = 'jorginho_juniorista';

    private function __construct() {
        $this->connection = new mysqli($this->host, $this->user, $this->password, $this->dbname);

        if ($this->connection->connect_error) {
            http_response_code(500);
            die(json_encode(['error' => 'Error de conexión a la base de datos.']));
        }

        $this->connection->set_charset('utf8');

        // Errores de SQL como excepciones: así los try/catch de las transacciones (ventas, ingresos,
        // pérdidas, descargos) hacen rollback de verdad en vez de confirmar operaciones a medias.
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->asegurarColumnasSucursal();
    }

    /**
     * Mascotas y proveedores pertenecen a una sucursal (entre veterinarias solo se comparten los productos).
     * Se agregan las columnas la primera vez y lo ya registrado queda en la sucursal de su dueño o,
     * si no tiene, en la primera sucursal activa.
     */
    private function asegurarColumnasSucursal(): void {
        $r = $this->connection->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'veterinaria_id'
                AND TABLE_NAME IN ('mascotas', 'proveedores')"
        );
        if ((int)$r->fetch_row()[0] === 2) return;

        $r = $this->connection->query('SELECT id FROM veterinarias ORDER BY activo DESC, id ASC LIMIT 1');
        $principal = (int)($r->fetch_row()[0] ?? 1);

        $tiene = fn(string $tabla) => $this->connection
            ->query("SHOW COLUMNS FROM $tabla LIKE 'veterinaria_id'")->num_rows > 0;

        if (!$tiene('mascotas')) {
            $this->connection->query("ALTER TABLE mascotas ADD COLUMN veterinaria_id int(11) NOT NULL DEFAULT $principal, ADD KEY idx_mascotas_vet (veterinaria_id)");
            $this->connection->query('UPDATE mascotas m JOIN clientes c ON c.id = m.cliente_id SET m.veterinaria_id = c.veterinaria_id');
        }
        if (!$tiene('proveedores')) {
            $this->connection->query("ALTER TABLE proveedores ADD COLUMN veterinaria_id int(11) NOT NULL DEFAULT $principal, ADD KEY idx_proveedores_vet (veterinaria_id)");
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): mysqli {
        return $this->connection;
    }

    private function __clone() {}
}
