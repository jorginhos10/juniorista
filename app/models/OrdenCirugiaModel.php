<?php

require_once ROOT . '/config/Database.php';
require_once ROOT . '/app/helpers/Seguridad.php';

class OrdenCirugiaModel {

    public const ESTADOS = [
        'programada' => ['Programada', 'warning'],
        'realizada'  => ['Realizada',  'success'],
        'cancelada'  => ['Cancelada',  'secondary'],
    ];

    // Cirugías y servicios de la tarifa; además se puede escribir otro procedimiento
    public const PROCEDIMIENTOS = [
        'OVH en perras', 'OVH en gatas', 'Castración en perros', 'Castración en gatos',
        'Cesárea perras', 'Cesárea gatas', 'Piometra perras', 'Piometra gatas',
        'Mastectomía perras unilateral', 'Mastectomía perras bilateral', 'Mastectomía gatos unilateral', 'Mastectomía gatos bilateral',
        'Cistectomía perros', 'Cistectomía gatos', 'Otohematomas unilateral', 'Otohematomas bilateral',
        'Prolapso 3er párpado unilateral', 'Prolapso 3er párpado bilateral', 'Enucleación',
        'Tumor sticker - hembras', 'Tumor sticker - machos', 'Tumor sticker - nasal',
        'Férulas Robert Jhon perro', 'Férulas Robert Jhon gatos', 'Mucocele - unilateral', 'Mucocele - bilateral',
        'Suturación', 'Miasis perro', 'Miasis gato', 'Rx 1 vista', 'Rx 2 vista',
        'Ecografía digital', 'Ecografía análoga', 'Profilaxis',
    ];

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarTabla();
    }

    private function asegurarTabla(): void {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS ordenes_cirugia (
                id int(11) NOT NULL AUTO_INCREMENT,
                veterinaria_id int(11) NOT NULL,
                fecha date NOT NULL,
                mascota_id int(11) DEFAULT NULL,
                cliente_id int(11) DEFAULT NULL,
                mascota_nombre varchar(100) NOT NULL,
                especie varchar(30) NOT NULL DEFAULT '',
                raza varchar(100) NOT NULL DEFAULT '',
                responsable_nombre varchar(200) NOT NULL DEFAULT '',
                responsable_cc varchar(30) NOT NULL DEFAULT '',
                procedimiento varchar(150) NOT NULL,
                veterinario_id int(11) DEFAULT NULL,
                examenes_prequirurgicos tinyint(1) NOT NULL DEFAULT 0,
                notas varchar(255) NOT NULL DEFAULT '',
                estado varchar(20) NOT NULL DEFAULT 'programada',
                usuario_id int(11) DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY idx_oc_vet (veterinaria_id, fecha)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
        );
    }

    public function getAll(): array {
        $r = $this->db->query(
            'SELECT o.*, u.nombre AS veterinario_nombre
               FROM ordenes_cirugia o
          LEFT JOIN usuarios u ON u.id = o.veterinario_id
              WHERE o.veterinaria_id = ' . Seguridad::sucursalActual() . '
           ORDER BY o.fecha DESC, o.id DESC'
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT o.*, u.nombre AS veterinario_nombre
               FROM ordenes_cirugia o
          LEFT JOIN usuarios u ON u.id = o.veterinario_id
              WHERE o.id = ? AND o.veterinaria_id = ?'
        );
        $vet = Seguridad::sucursalActual();
        $stmt->bind_param('ii', $id, $vet);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function crear(array $d): int {
        $vet = Seguridad::sucursalActual();
        if ($vet <= 0) return 0;
        $mascota = $d['mascota_id'] ?: null;
        $cliente = $d['cliente_id'] ?: null;
        $medico  = $d['veterinario_id'] ?: null;
        $stmt = $this->db->prepare(
            'INSERT INTO ordenes_cirugia (veterinaria_id, fecha, mascota_id, cliente_id, mascota_nombre, especie, raza,
                    responsable_nombre, responsable_cc, procedimiento, veterinario_id, examenes_prequirurgicos, notas, usuario_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param('isiissssssiisi',
            $vet, $d['fecha'], $mascota, $cliente, $d['mascota_nombre'], $d['especie'], $d['raza'],
            $d['responsable_nombre'], $d['responsable_cc'], $d['procedimiento'], $medico,
            $d['examenes_prequirurgicos'], $d['notas'], $d['usuario_id']
        );
        $ok = $stmt->execute();
        $id = $ok ? (int)$this->db->insert_id : 0;
        $stmt->close();
        return $id;
    }

    /** Solo se edita mientras la orden sigue programada */
    public function actualizar(int $id, array $d): bool {
        $mascota = $d['mascota_id'] ?: null;
        $cliente = $d['cliente_id'] ?: null;
        $medico  = $d['veterinario_id'] ?: null;
        $stmt = $this->db->prepare(
            "UPDATE ordenes_cirugia SET fecha=?, mascota_id=?, cliente_id=?, mascota_nombre=?, especie=?, raza=?,
                    responsable_nombre=?, responsable_cc=?, procedimiento=?, veterinario_id=?, examenes_prequirurgicos=?, notas=?
              WHERE id=? AND veterinaria_id=? AND estado='programada'"
        );
        $vet = Seguridad::sucursalActual();
        $stmt->bind_param('siissssssiisii',
            $d['fecha'], $mascota, $cliente, $d['mascota_nombre'], $d['especie'], $d['raza'],
            $d['responsable_nombre'], $d['responsable_cc'], $d['procedimiento'], $medico,
            $d['examenes_prequirurgicos'], $d['notas'], $id, $vet
        );
        $ok = $stmt->execute() && $stmt->affected_rows >= 0;
        $stmt->close();
        return $ok;
    }

    public function cambiarEstado(int $id, string $estado): bool {
        if (!isset(self::ESTADOS[$estado])) return false;
        $stmt = $this->db->prepare('UPDATE ordenes_cirugia SET estado = ? WHERE id = ? AND veterinaria_id = ?');
        $vet = Seguridad::sucursalActual();
        $stmt->bind_param('sii', $estado, $id, $vet);
        $ok = $stmt->execute() && $stmt->affected_rows >= 0;
        $stmt->close();
        return $ok;
    }
}
