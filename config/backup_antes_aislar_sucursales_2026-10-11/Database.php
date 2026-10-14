<?php

class Database {

    private static ?Database $instance = null;
    private mysqli $connection;

    private string $host     = 'localhost';
    private string $user     = 'root';
    private string $password = '';
    private string $dbname   = 'petcontrol';

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
