<?php

require_once ROOT . '/config/Database.php';

class TratamientoModel {

    public const ESPECIES = [
        'perro' => 'Perro',
        'gato'  => 'Gato',
    ];

    // Peluquería reutiliza este modelo con su propia tabla (ver PeluqueriaModel)
    protected string $tabla = 'tratamientos';

    // Los tratamientos se pagan con abonos; peluquería se cobra completa el mismo día
    protected bool $conAbonos = true;

    protected mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarColumnasVinculo();
    }

    // Un tratamiento (o servicio) puede anclarse a una mascota registrada y a un cliente; ambos son opcionales
    private function asegurarColumnasVinculo(): void {
        if ($this->db->query("SHOW COLUMNS FROM peluquerias LIKE 'mascota_id'")->num_rows > 0) return;
        foreach (['tratamientos', 'peluquerias'] as $tabla) {
            if ($this->db->query("SHOW COLUMNS FROM $tabla LIKE 'cliente_id'")->num_rows === 0) {
                $this->db->query("ALTER TABLE $tabla ADD COLUMN cliente_id int(11) DEFAULT NULL");
            }
            if ($this->db->query("SHOW COLUMNS FROM $tabla LIKE 'mascota_id'")->num_rows === 0) {
                $this->db->query("ALTER TABLE $tabla ADD COLUMN mascota_id int(11) DEFAULT NULL");
            }
        }
    }

    /** Clientes de la sucursal para el selector del formulario */
    public function getClientesSucursal(int $veterinaria_id): array {
        $stmt = $this->db->prepare(
            "SELECT id, TRIM(CONCAT(nombre, ' ', apellido)) AS nombre, telefono
               FROM clientes WHERE activo = 1 AND veterinaria_id = ? ORDER BY nombre, apellido"
        );
        $stmt->bind_param('i', $veterinaria_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /** Mascotas de la sucursal con su dueño (si tiene), para anclar el tratamiento */
    public function getMascotasSucursal(int $veterinaria_id): array {
        $stmt = $this->db->prepare(
            "SELECT m.id, m.nombre, m.especie, m.raza, c.id AS cliente_id,
                    NULLIF(TRIM(CONCAT(COALESCE(c.nombre, ''), ' ', COALESCE(c.apellido, ''))), '') AS cliente
               FROM mascotas m
          LEFT JOIN clientes c ON c.id = m.cliente_id AND c.activo = 1 AND c.veterinaria_id = m.veterinaria_id
              WHERE m.activo = 1 AND m.veterinaria_id = ? ORDER BY m.nombre"
        );
        $stmt->bind_param('i', $veterinaria_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /** Deja en 0 el cliente o la mascota que no sean de la sucursal (no se puede anclar a datos de otra veterinaria) */
    public function validarVinculos(int $veterinaria_id, int $cliente_id, int $mascota_id): array {
        $existe = function (string $tabla, int $id) use ($veterinaria_id): bool {
            if ($id <= 0) return false;
            $stmt = $this->db->prepare("SELECT 1 FROM $tabla WHERE id = ? AND veterinaria_id = ? AND activo = 1");
            $stmt->bind_param('ii', $id, $veterinaria_id);
            $stmt->execute();
            $ok = (bool)$stmt->get_result()->fetch_row();
            $stmt->close();
            return $ok;
        };
        return [$existe('clientes', $cliente_id) ? $cliente_id : 0, $existe('mascotas', $mascota_id) ? $mascota_id : 0];
    }

    public function usaAbonos(): bool {
        return $this->conAbonos;
    }

    private const SQL_ABONADO =
        '(SELECT COALESCE(SUM(a.monto), 0) FROM tratamiento_abonos a WHERE a.tratamiento_id = t.id AND a.activo = 1)';

    // ── Registro diario (peluquería) ──────────────────────

    public function getDia(int $veterinaria_id, string $fecha): array {
        $stmt = $this->db->prepare(
            "SELECT t.*, u.nombre AS usuario_nombre
               FROM {$this->tabla} t
          LEFT JOIN usuarios u ON u.id = t.usuario_id
              WHERE t.activo = 1 AND t.veterinaria_id = ? AND t.fecha = ?
           ORDER BY t.id ASC"
        );
        $stmt->bind_param('is', $veterinaria_id, $fecha);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /** Cantidad y total por día dentro de un rango: ['Y-m-d' => ['cantidad' => n, 'total' => x]] */
    public function getResumenDias(int $veterinaria_id, string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            "SELECT fecha, COUNT(*) AS cantidad, SUM(valor) AS total
               FROM {$this->tabla}
              WHERE activo = 1 AND veterinaria_id = ? AND fecha BETWEEN ? AND ?
           GROUP BY fecha"
        );
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $res = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
            $res[$r['fecha']] = ['cantidad' => (int)$r['cantidad'], 'total' => (float)$r['total']];
        }
        $stmt->close();
        return $res;
    }

    /** Cantidad y dinero recibido en un rango (para cierres y reportes): en tratamientos son los abonos */
    public function getTotalRango(int $veterinaria_id, string $desde, string $hasta): array {
        if ($this->conAbonos) {
            $sql = 'SELECT COUNT(*) AS cantidad, COALESCE(SUM(a.monto), 0) AS total
                      FROM tratamiento_abonos a
                      JOIN tratamientos t ON t.id = a.tratamiento_id AND t.activo = 1
                     WHERE a.activo = 1 AND a.veterinaria_id = ? AND a.fecha BETWEEN ? AND ?';
        } else {
            $sql = "SELECT COUNT(*) AS cantidad, COALESCE(SUM(valor), 0) AS total
                      FROM {$this->tabla}
                     WHERE activo = 1 AND veterinaria_id = ? AND fecha BETWEEN ? AND ?";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iss', $veterinaria_id, $desde, $hasta);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return ['cantidad' => (int)$row['cantidad'], 'total' => (float)$row['total']];
    }

    /** Devuelve el id del registro creado (0 si falló). Con abonos, $abonoInicial > 0 registra el primer abono en la misma transacción. */
    public function crear(array $d, float $abonoInicial = 0, string $notaAbono = ''): int {
        $this->db->begin_transaction();
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tabla} (veterinaria_id, fecha, nombre, especie, descripcion, valor, usuario_id, cliente_id, mascota_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, NULLIF(?, 0), NULLIF(?, 0))"
        );
        $cliente = (int)($d['cliente_id'] ?? 0);
        $mascota = (int)($d['mascota_id'] ?? 0);
        $stmt->bind_param('issssdiii',
            $d['veterinaria_id'], $d['fecha'], $d['nombre'], $d['especie'], $d['descripcion'], $d['valor'], $d['usuario_id'], $cliente, $mascota
        );
        $ok = $stmt->execute();
        $id = $ok ? (int)$stmt->insert_id : 0;
        $stmt->close();

        if ($ok && $this->conAbonos && $abonoInicial > 0) {
            $ok = $this->insertarAbono($id, $d['veterinaria_id'], $d['fecha'], $abonoInicial, $notaAbono ?: 'Abono inicial', $d['usuario_id']);
        }

        if ($ok) {
            $this->db->commit();
            return $id;
        }
        $this->db->rollback();
        return 0;
    }

    public function actualizar(int $id, array $d): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tabla} SET fecha=?, nombre=?, especie=?, descripcion=?, valor=?" . (array_key_exists('cliente_id', $d) ? ', cliente_id=NULLIF(?, 0), mascota_id=NULLIF(?, 0)' : '') . "
              WHERE id=? AND veterinaria_id=? AND activo=1"
        );
        if (array_key_exists('cliente_id', $d)) {
            $cliente = (int)$d['cliente_id'];
            $mascota = (int)($d['mascota_id'] ?? 0);
            $stmt->bind_param('ssssdiiii',
                $d['fecha'], $d['nombre'], $d['especie'], $d['descripcion'], $d['valor'], $cliente, $mascota, $id, $d['veterinaria_id']
            );
        } else {
            // Formularios que no manejan cliente ni mascota (peluquería) no tocan el vínculo
            $stmt->bind_param('ssssdii',
                $d['fecha'], $d['nombre'], $d['especie'], $d['descripcion'], $d['valor'], $id, $d['veterinaria_id']
            );
        }
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function eliminar(int $id, int $veterinaria_id): bool {
        $stmt = $this->db->prepare("UPDATE {$this->tabla} SET activo=0 WHERE id=? AND veterinaria_id=?");
        $stmt->bind_param('ii', $id, $veterinaria_id);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    // ── Tratamientos con abonos ───────────────────────────

    /** Todos los tratamientos de la sucursal con lo abonado, el saldo y su situación (pendiente / completo / cancelado) */
    public function getTodos(int $veterinaria_id): array {
        $stmt = $this->db->prepare(
            'SELECT t.*, ' . self::SQL_ABONADO . ' AS abonado,
                    (SELECT COUNT(*) FROM tratamiento_abonos a WHERE a.tratamiento_id = t.id AND a.activo = 1) AS total_abonos,
                    (SELECT MAX(a.fecha) FROM tratamiento_abonos a WHERE a.tratamiento_id = t.id AND a.activo = 1) AS ultimo_abono,
                    NULLIF(TRIM(CONCAT(COALESCE(c.nombre, \'\'), \' \', COALESCE(c.apellido, \'\'))), \'\') AS cliente_nombre
               FROM tratamientos t
          LEFT JOIN clientes c ON c.id = t.cliente_id
              WHERE t.activo = 1 AND t.veterinaria_id = ?
           ORDER BY t.fecha DESC, t.id DESC'
        );
        $stmt->bind_param('i', $veterinaria_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as &$r) {
            $r['saldo']     = round((float)$r['valor'] - (float)$r['abonado'], 2);
            $r['situacion'] = $r['estado'] === 'cancelado' ? 'cancelados' : ($r['saldo'] > 0 ? 'pendientes' : 'completos');
        }
        unset($r);
        return $rows;
    }

    /** Tratamiento con su valor, lo abonado y el saldo */
    public function getConSaldo(int $id, int $veterinaria_id): ?array {
        $stmt = $this->db->prepare(
            'SELECT t.*, ' . self::SQL_ABONADO . ' AS abonado
               FROM tratamientos t WHERE t.id = ? AND t.veterinaria_id = ? AND t.activo = 1'
        );
        $stmt->bind_param('ii', $id, $veterinaria_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return null;
        $row['saldo'] = round((float)$row['valor'] - (float)$row['abonado'], 2);
        return $row;
    }

    /** Historial de abonos de un tratamiento */
    public function getAbonos(int $tratamiento_id, int $veterinaria_id): array {
        $stmt = $this->db->prepare(
            'SELECT a.id, a.fecha, a.monto, a.nota, a.created_at, u.nombre AS usuario_nombre
               FROM tratamiento_abonos a
          LEFT JOIN usuarios u ON u.id = a.usuario_id
              WHERE a.activo = 1 AND a.tratamiento_id = ? AND a.veterinaria_id = ?
           ORDER BY a.fecha ASC, a.id ASC'
        );
        $stmt->bind_param('ii', $tratamiento_id, $veterinaria_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /** Datos para la factura de un abono: abono, tratamiento, sucursal y saldos antes/después */
    public function getReciboAbono(int $abono_id, int $veterinaria_id): ?array {
        $stmt = $this->db->prepare(
            'SELECT a.id, a.fecha, a.monto, a.nota, a.created_at, a.tratamiento_id,
                    t.nombre, t.especie, t.descripcion, t.fecha AS fecha_tratamiento, t.valor, t.estado,
                    u.nombre AS usuario_nombre,
                    v.nombre AS sucursal, v.ruc, v.telefono, v.direccion, v.email, v.logo,
                    (SELECT COALESCE(SUM(p.monto), 0) FROM tratamiento_abonos p
                      WHERE p.tratamiento_id = a.tratamiento_id AND p.activo = 1
                        AND (p.fecha < a.fecha OR (p.fecha = a.fecha AND p.id < a.id))) AS abonado_antes
               FROM tratamiento_abonos a
               JOIN tratamientos t  ON t.id = a.tratamiento_id
          LEFT JOIN usuarios u      ON u.id = a.usuario_id
          LEFT JOIN veterinarias v  ON v.id = a.veterinaria_id
              WHERE a.id = ? AND a.veterinaria_id = ? AND a.activo = 1'
        );
        $stmt->bind_param('ii', $abono_id, $veterinaria_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return null;
        $row['saldo_antes']   = round((float)$row['valor'] - (float)$row['abonado_antes'], 2);
        $row['saldo_despues'] = round($row['saldo_antes'] - (float)$row['monto'], 2);
        return $row;
    }

    /** Registra un abono validando estado y saldo. Devuelve el id del abono o el mensaje de error (string). */
    public function registrarAbono(int $tratamiento_id, int $veterinaria_id, string $fecha, float $monto, string $nota, int $usuario_id): int|string {
        $this->db->begin_transaction();

        // Bloquea el tratamiento para que dos abonos simultáneos no superen el saldo
        $stmt = $this->db->prepare('SELECT valor, fecha, estado FROM tratamientos WHERE id = ? AND veterinaria_id = ? AND activo = 1 FOR UPDATE');
        $stmt->bind_param('ii', $tratamiento_id, $veterinaria_id);
        $stmt->execute();
        $t = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $error = '';
        if (!$t) {
            $error = 'El tratamiento no existe.';
        } elseif ($t['estado'] === 'cancelado') {
            $error = 'El tratamiento está cancelado; no recibe abonos.';
        } elseif ($fecha < $t['fecha']) {
            $error = 'El abono no puede ser anterior a la fecha del tratamiento (' . date('d/m/Y', strtotime($t['fecha'])) . ').';
        } else {
            $saldo = round((float)$t['valor'] - $this->sumaAbonos($tratamiento_id), 2);
            if ($monto > $saldo + 0.001) {
                $error = $saldo <= 0
                    ? 'Este tratamiento ya está pagado por completo.'
                    : 'El abono supera el saldo pendiente ($' . number_format($saldo, 2) . ').';
            }
        }

        if ($error === '' && !$this->insertarAbono($tratamiento_id, $veterinaria_id, $fecha, $monto, $nota, $usuario_id)) {
            $error = 'Error al registrar el abono.';
        }
        if ($error !== '') {
            $this->db->rollback();
            return $error;
        }
        $id = (int)$this->db->insert_id;
        $this->db->commit();
        return $id;
    }

    /** Edita monto, fecha y descripción de un abono sin superar el valor del tratamiento. Devuelve '' o el error. */
    public function actualizarAbono(int $id, int $veterinaria_id, float $monto, string $fecha, string $nota): string {
        $this->db->begin_transaction();

        $stmt = $this->db->prepare(
            'SELECT a.tratamiento_id, t.valor, t.fecha, t.estado
               FROM tratamiento_abonos a JOIN tratamientos t ON t.id = a.tratamiento_id
              WHERE a.id = ? AND a.veterinaria_id = ? AND a.activo = 1 AND t.activo = 1
                FOR UPDATE'
        );
        $stmt->bind_param('ii', $id, $veterinaria_id);
        $stmt->execute();
        $a = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $error = '';
        if (!$a) {
            $error = 'El abono no existe o está anulado.';
        } elseif ($a['estado'] === 'cancelado') {
            $error = 'El tratamiento está cancelado; sus abonos no se pueden modificar.';
        } elseif ($fecha < $a['fecha']) {
            $error = 'El abono no puede ser anterior a la fecha del tratamiento (' . date('d/m/Y', strtotime($a['fecha'])) . ').';
        } else {
            // Lo abonado por los demás abonos + este no puede superar el valor
            $stmt = $this->db->prepare(
                'SELECT COALESCE(SUM(monto), 0) FROM tratamiento_abonos WHERE tratamiento_id = ? AND activo = 1 AND id <> ?'
            );
            $stmt->bind_param('ii', $a['tratamiento_id'], $id);
            $stmt->execute();
            $otros = (float)$stmt->get_result()->fetch_row()[0];
            $stmt->close();

            $maximo = round((float)$a['valor'] - $otros, 2);
            if ($monto > $maximo + 0.001) {
                $error = 'El abono no puede superar $' . number_format($maximo, 2) . ' (saldo del tratamiento).';
            }
        }

        if ($error === '') {
            $stmt = $this->db->prepare('UPDATE tratamiento_abonos SET monto = ?, fecha = ?, nota = NULLIF(?, \'\') WHERE id = ?');
            $stmt->bind_param('dssi', $monto, $fecha, $nota, $id);
            if (!$stmt->execute()) {
                $error = 'Error al actualizar el abono.';
            }
            $stmt->close();
        }

        if ($error !== '') {
            $this->db->rollback();
            return $error;
        }
        $this->db->commit();
        return '';
    }

    public function eliminarAbono(int $id, int $veterinaria_id): bool {
        $stmt = $this->db->prepare('UPDATE tratamiento_abonos SET activo = 0 WHERE id = ? AND veterinaria_id = ?');
        $stmt->bind_param('ii', $id, $veterinaria_id);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    public function sumaAbonos(int $tratamiento_id): float {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(monto), 0) FROM tratamiento_abonos WHERE tratamiento_id = ? AND activo = 1');
        $stmt->bind_param('i', $tratamiento_id);
        $stmt->execute();
        $total = (float)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
        return $total;
    }

    public function cancelar(int $id, int $veterinaria_id, string $motivo, string $fecha): bool {
        $stmt = $this->db->prepare(
            "UPDATE tratamientos SET estado = 'cancelado', motivo_cancelacion = NULLIF(?, ''), fecha_cancelacion = ?
              WHERE id = ? AND veterinaria_id = ? AND activo = 1 AND estado = 'activo'"
        );
        $stmt->bind_param('ssii', $motivo, $fecha, $id, $veterinaria_id);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    public function reactivar(int $id, int $veterinaria_id): bool {
        $stmt = $this->db->prepare(
            "UPDATE tratamientos SET estado = 'activo', motivo_cancelacion = NULL, fecha_cancelacion = NULL
              WHERE id = ? AND veterinaria_id = ? AND activo = 1 AND estado = 'cancelado'"
        );
        $stmt->bind_param('ii', $id, $veterinaria_id);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    private function insertarAbono(int $tratamiento_id, int $veterinaria_id, string $fecha, float $monto, string $nota, int $usuario_id): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO tratamiento_abonos (tratamiento_id, veterinaria_id, fecha, monto, nota, usuario_id)
             VALUES (?, ?, ?, ?, NULLIF(?, \'\'), ?)'
        );
        $stmt->bind_param('iisdsi', $tratamiento_id, $veterinaria_id, $fecha, $monto, $nota, $usuario_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
