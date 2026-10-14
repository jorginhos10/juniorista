<?php

require_once ROOT . '/config/Database.php';

/**
 * Datos que declara el vendedor y audita el administrador:
 *  - fotos de comprobantes de las ventas pagadas por transferencia,
 *  - arqueos de caja (conteo de monedas y billetes al final del día),
 *  - descuentos habilitados o no por sucursal,
 *  - tokens de la app de auditoría (solo administradores).
 */
class AuditoriaModel {

    /** Denominaciones del arqueo, en pesos */
    public const MONEDAS  = [50, 100, 200, 500, 1000];
    public const BILLETES = [2000, 5000, 10000, 20000, 50000, 100000];

    public const ESTADOS_ARQUEO = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'observado' => 'Con observación'];

    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarEsquema();
    }

    private function asegurarEsquema(): void {
        $tiene = fn(string $tabla, string $col) => $this->db->query("SHOW COLUMNS FROM $tabla LIKE '$col'")->num_rows > 0;

        if ($tiene('ventas', 'transf_verificada_at')) return;   // última columna que se agrega: todo lo demás ya existe

        if (!$tiene('veterinarias', 'descuentos_habilitados')) {
            $this->db->query('ALTER TABLE veterinarias ADD COLUMN descuentos_habilitados tinyint(1) NOT NULL DEFAULT 0');
        }
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS venta_comprobantes (
                id int(11) NOT NULL AUTO_INCREMENT,
                venta_id int(11) NOT NULL,
                veterinaria_id int(11) NOT NULL,
                archivo varchar(255) NOT NULL,
                usuario_id int(11) DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY venta_id (venta_id),
                CONSTRAINT venta_comprobantes_ibfk_1 FOREIGN KEY (venta_id) REFERENCES ventas (id) ON DELETE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS arqueos (
                id int(11) NOT NULL AUTO_INCREMENT,
                veterinaria_id int(11) NOT NULL,
                usuario_id int(11) DEFAULT NULL,
                fecha date NOT NULL,
                conteo text NOT NULL,
                total_declarado decimal(12,2) NOT NULL DEFAULT 0.00,
                efectivo_sistema decimal(12,2) NOT NULL DEFAULT 0.00,
                notas text DEFAULT NULL,
                estado enum('pendiente','aprobado','observado') NOT NULL DEFAULT 'pendiente',
                auditado_por int(11) DEFAULT NULL,
                auditado_at datetime DEFAULT NULL,
                nota_auditoria text DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY vet_fecha (veterinaria_id, fecha)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS api_tokens (
                id int(11) NOT NULL AUTO_INCREMENT,
                usuario_id int(11) NOT NULL,
                token_hash char(64) NOT NULL,
                expira datetime NOT NULL,
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                UNIQUE KEY token_hash (token_hash)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        // Rol «vendedor»: declara (fotos, arqueo); el administrador audita
        $rol = $this->db->query("SHOW COLUMNS FROM usuarios LIKE 'rol'")->fetch_assoc();
        if ($rol && !str_contains($rol['Type'], "'vendedor'")) {
            $this->db->query("ALTER TABLE usuarios MODIFY rol enum('admin','veterinario','recepcion','vendedor') NOT NULL DEFAULT 'recepcion'");
        }
        if (!$tiene('ventas', 'transf_verificada_por')) {
            $this->db->query('ALTER TABLE ventas ADD COLUMN transf_verificada_por int(11) DEFAULT NULL');
        }
        $this->db->query('ALTER TABLE ventas ADD COLUMN transf_verificada_at datetime DEFAULT NULL');
    }

    // ── DESCUENTOS POR SUCURSAL ───────────────────────────

    public function descuentosHabilitados(int $veterinaria_id): bool {
        $stmt = $this->db->prepare('SELECT descuentos_habilitados FROM veterinarias WHERE id = ?');
        $stmt->bind_param('i', $veterinaria_id);
        $stmt->execute();
        $v = $stmt->get_result()->fetch_row()[0] ?? 0;   // bloqueado mientras el administrador no lo active
        $stmt->close();
        return (int)$v === 1;
    }

    public function setDescuentos(int $veterinaria_id, bool $habilitado): bool {
        $stmt = $this->db->prepare('UPDATE veterinarias SET descuentos_habilitados = ? WHERE id = ? AND activo = 1');
        $h = $habilitado ? 1 : 0;
        $stmt->bind_param('ii', $h, $veterinaria_id);
        $stmt->execute();
        $ok = $stmt->affected_rows >= 0 && $this->db->query("SELECT 1 FROM veterinarias WHERE id = $veterinaria_id AND activo = 1")->num_rows === 1;
        $stmt->close();
        return $ok;
    }

    public function getSucursales(): array {
        $r = $this->db->query('SELECT id, nombre, descuentos_habilitados FROM veterinarias WHERE activo = 1 ORDER BY id');
        return array_map(fn($s) => [
            'id' => (int)$s['id'], 'nombre' => $s['nombre'], 'descuentos_habilitados' => (int)$s['descuentos_habilitados'] === 1,
        ], $r->fetch_all(MYSQLI_ASSOC));
    }

    // ── COMPROBANTES (FOTOS) ──────────────────────────────

    /** Venta de la sucursal dada con el nombre de la sucursal, o null */
    public function getVentaDeSucursal(int $venta_id, int $veterinaria_id): ?array {
        $stmt = $this->db->prepare(
            'SELECT v.id, v.veterinaria_id, v.metodo_pago, v.created_at, vet.nombre AS sucursal
               FROM ventas v JOIN veterinarias vet ON vet.id = v.veterinaria_id
              WHERE v.id = ? AND v.veterinaria_id = ?'
        );
        $stmt->bind_param('ii', $venta_id, $veterinaria_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function contarComprobantes(int $venta_id): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM venta_comprobantes WHERE venta_id = $venta_id")->fetch_row()[0];
    }

    public function agregarComprobante(int $venta_id, int $veterinaria_id, string $archivo, int $usuario_id): bool {
        $stmt = $this->db->prepare('INSERT INTO venta_comprobantes (venta_id, veterinaria_id, archivo, usuario_id) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iisi', $venta_id, $veterinaria_id, $archivo, $usuario_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Fotos de una venta: [id, created_at] */
    public function getComprobantes(int $venta_id): array {
        $stmt = $this->db->prepare('SELECT id, created_at FROM venta_comprobantes WHERE venta_id = ? ORDER BY id');
        $stmt->bind_param('i', $venta_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /** Foto por id; con $veterinaria_id > 0 solo si es de esa sucursal */
    public function getComprobante(int $id, int $veterinaria_id = 0): ?array {
        $sql = 'SELECT id, venta_id, veterinaria_id, archivo FROM venta_comprobantes WHERE id = ?' . ($veterinaria_id > 0 ? ' AND veterinaria_id = ?' : '');
        $stmt = $this->db->prepare($sql);
        $veterinaria_id > 0 ? $stmt->bind_param('ii', $id, $veterinaria_id) : $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    // ── ARQUEOS ───────────────────────────────────────────

    /** Normaliza el conteo recibido ({denominación: cantidad}) y calcula el total */
    public static function normalizarConteo(array $entrada): array {
        $conteo = [];
        $total  = 0;
        foreach (array_merge(self::MONEDAS, self::BILLETES) as $den) {
            $cant = max(0, min(100000, (int)($entrada[$den] ?? $entrada[(string)$den] ?? 0)));
            $conteo[(string)$den] = $cant;
            $total += $den * $cant;
        }
        return ['conteo' => $conteo, 'total' => (float)$total];
    }

    /** Efectivo que registró el sistema ese día en la sucursal: ventas en efectivo + la parte en efectivo de los pagos mixtos */
    public function efectivoSistema(int $veterinaria_id, string $fecha): float {
        $stmt = $this->db->prepare(
            "SELECT metodo_pago, total, pago_detalle FROM ventas
              WHERE veterinaria_id = ? AND DATE(created_at) = ? AND estado <> 'anulada' AND metodo_pago IN ('efectivo', 'mixto')"
        );
        $stmt->bind_param('is', $veterinaria_id, $fecha);
        $stmt->execute();
        $suma = 0;
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $v) {
            $suma += self::partes($v)['efectivo'];
        }
        $stmt->close();
        return round($suma, 2);
    }

    public function crearArqueo(int $veterinaria_id, int $usuario_id, string $fecha, array $conteo, float $total, string $notas): int {
        $sistema = $this->efectivoSistema($veterinaria_id, $fecha);
        $json    = json_encode($conteo);
        $stmt = $this->db->prepare(
            "INSERT INTO arqueos (veterinaria_id, usuario_id, fecha, conteo, total_declarado, efectivo_sistema, notas)
             VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, ''))"
        );
        $stmt->bind_param('iissdds', $veterinaria_id, $usuario_id, $fecha, $json, $total, $sistema, $notas);
        $stmt->execute();
        $id = (int)$stmt->insert_id;
        $stmt->close();
        return $id;
    }

    /** Arqueos; $veterinaria_id = 0 trae los de todas las sucursales (solo para el administrador) */
    public function getArqueos(int $veterinaria_id, string $desde, string $hasta): array {
        $filtro = $veterinaria_id > 0 ? 'AND a.veterinaria_id = ?' : '';
        $stmt = $this->db->prepare(
            "SELECT a.*, v.nombre AS sucursal, u.nombre AS usuario_nombre, au.nombre AS auditor_nombre
               FROM arqueos a
               JOIN veterinarias v ON v.id = a.veterinaria_id
          LEFT JOIN usuarios u  ON u.id  = a.usuario_id
          LEFT JOIN usuarios au ON au.id = a.auditado_por
              WHERE a.fecha BETWEEN ? AND ? $filtro
           ORDER BY a.fecha DESC, a.id DESC"
        );
        $veterinaria_id > 0 ? $stmt->bind_param('ssi', $desde, $hasta, $veterinaria_id) : $stmt->bind_param('ss', $desde, $hasta);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function auditarArqueo(int $id, string $estado, string $nota, int $auditor_id): bool {
        if (!isset(self::ESTADOS_ARQUEO[$estado])) return false;
        $stmt = $this->db->prepare(
            "UPDATE arqueos SET estado = ?, nota_auditoria = NULLIF(?, ''), auditado_por = ?, auditado_at = NOW() WHERE id = ?"
        );
        $stmt->bind_param('ssii', $estado, $nota, $auditor_id, $id);
        $stmt->execute();
        $ok = $this->db->query("SELECT 1 FROM arqueos WHERE id = $id AND estado = '" . $this->db->real_escape_string($estado) . "'")->num_rows === 1;
        $stmt->close();
        return $ok;
    }

    // ── VENTAS POR SUCURSAL Y TRANSFERENCIAS (APP) ────────

    /** Cuánto de una venta se pagó con cada método (el pago mixto se reparte según su detalle) */
    public static function partes(array $venta): array {
        $p = ['efectivo' => 0.0, 'transferencia' => 0.0, 'datafono' => 0.0, 'sin_metodo' => 0.0];
        $metodo = $venta['metodo_pago'] ?? null;
        if ($metodo === 'mixto') {
            foreach ((array)json_decode((string)($venta['pago_detalle'] ?? ''), true) as $k => $monto) {
                if (isset($p[$k])) $p[$k] += (float)$monto;
            }
        } elseif (isset($p[$metodo])) {
            $p[$metodo] = (float)$venta['total'];
        } else {
            $p['sin_metodo'] = (float)$venta['total'];
        }
        return $p;
    }

    /** Cómo va la venta en cada sucursal entre dos fechas */
    public function resumenVentas(string $desde, string $hasta): array {
        $res = [];
        foreach ($this->getSucursales() as $s) {
            $res[$s['id']] = $s + [
                'ventas' => 0, 'anuladas' => 0, 'total' => 0.0, 'descuentos' => 0.0, 'unidades' => 0,
                'efectivo' => 0.0, 'transferencia' => 0.0, 'datafono' => 0.0, 'sin_metodo' => 0.0, 'ultima_venta' => null,
            ];
        }
        $stmt = $this->db->prepare(
            "SELECT v.id, v.veterinaria_id, v.total, v.descuento, v.estado, v.metodo_pago, v.pago_detalle, v.created_at,
                    (SELECT COALESCE(SUM(d.cantidad), 0) FROM venta_detalles d WHERE d.venta_id = v.id) AS unidades
               FROM ventas v WHERE DATE(v.created_at) BETWEEN ? AND ? ORDER BY v.created_at"
        );
        $stmt->bind_param('ss', $desde, $hasta);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $v) {
            $id = (int)$v['veterinaria_id'];
            if (!isset($res[$id])) continue;
            if ($v['estado'] === 'anulada') { $res[$id]['anuladas']++; continue; }
            $res[$id]['ventas']++;
            $res[$id]['total']      += (float)$v['total'];
            $res[$id]['descuentos'] += (float)$v['descuento'];
            $res[$id]['unidades']   += (int)$v['unidades'];
            $res[$id]['ultima_venta'] = $v['created_at'];
            foreach (self::partes($v) as $k => $monto) $res[$id][$k] += $monto;
        }
        $stmt->close();
        return array_values($res);
    }

    /** Ventas pagadas total o parcialmente por transferencia, con sus fotos */
    public function getTransferencias(string $desde, string $hasta, int $veterinaria_id = 0): array {
        $filtro = $veterinaria_id > 0 ? 'AND v.veterinaria_id = ?' : '';
        $stmt = $this->db->prepare(
            "SELECT v.id, v.veterinaria_id, v.total, v.estado, v.metodo_pago, v.pago_detalle, v.notas, v.created_at,
                    v.transf_verificada_at, vet.nombre AS sucursal, u.nombre AS vendedor, au.nombre AS verificador,
                    NULLIF(TRIM(CONCAT(COALESCE(c.nombre, ''), ' ', COALESCE(c.apellido, ''))), '') AS cliente
               FROM ventas v
               JOIN veterinarias vet ON vet.id = v.veterinaria_id
          LEFT JOIN usuarios u  ON u.id  = v.usuario_id
          LEFT JOIN usuarios au ON au.id = v.transf_verificada_por
          LEFT JOIN clientes c  ON c.id  = v.cliente_id
              WHERE DATE(v.created_at) BETWEEN ? AND ? AND v.metodo_pago IN ('transferencia', 'mixto') $filtro
           ORDER BY v.created_at DESC"
        );
        $veterinaria_id > 0 ? $stmt->bind_param('ssi', $desde, $hasta, $veterinaria_id) : $stmt->bind_param('ss', $desde, $hasta);
        $stmt->execute();
        $ventas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $fotos = [];
        if ($ventas) {
            $ids = implode(',', array_map(fn($v) => (int)$v['id'], $ventas));
            foreach ($this->db->query("SELECT id, venta_id FROM venta_comprobantes WHERE venta_id IN ($ids) ORDER BY id")->fetch_all(MYSQLI_ASSOC) as $f) {
                $fotos[(int)$f['venta_id']][] = (int)$f['id'];
            }
        }

        $salida = [];
        foreach ($ventas as $v) {
            $monto = self::partes($v)['transferencia'];
            if ($monto <= 0) continue;   // mixto sin parte por transferencia
            $salida[] = [
                'venta_id' => (int)$v['id'], 'veterinaria_id' => (int)$v['veterinaria_id'], 'sucursal' => $v['sucursal'],
                'fecha' => $v['created_at'], 'monto_transferencia' => $monto, 'total' => (float)$v['total'],
                'metodo_pago' => $v['metodo_pago'], 'anulada' => $v['estado'] === 'anulada',
                'vendedor' => $v['vendedor'], 'cliente' => $v['cliente'], 'notas' => $v['notas'],
                'fotos' => $fotos[(int)$v['id']] ?? [],
                'verificada' => $v['transf_verificada_at'] !== null, 'verificada_at' => $v['transf_verificada_at'], 'verificador' => $v['verificador'],
            ];
        }
        return $salida;
    }

    public function verificarTransferencia(int $venta_id, bool $verificada, int $auditor_id): bool {
        $stmt = $verificada
            ? $this->db->prepare("UPDATE ventas SET transf_verificada_por = ?, transf_verificada_at = NOW() WHERE id = ? AND metodo_pago IN ('transferencia', 'mixto')")
            : $this->db->prepare("UPDATE ventas SET transf_verificada_por = NULL, transf_verificada_at = NULL WHERE (? > 0) AND id = ? AND metodo_pago IN ('transferencia', 'mixto')");
        $stmt->bind_param('ii', $auditor_id, $venta_id);
        $stmt->execute();
        $stmt->close();
        return $this->db->query("SELECT 1 FROM ventas WHERE id = $venta_id AND metodo_pago IN ('transferencia', 'mixto')")->num_rows === 1;
    }

    // ── TOKENS DE LA APP ──────────────────────────────────

    /** Crea un token de 30 días y devuelve el valor en claro (solo se guarda su hash) */
    public function crearToken(int $usuario_id): string {
        $this->db->query('DELETE FROM api_tokens WHERE expira < NOW()');
        $token = bin2hex(random_bytes(32));
        $hash  = hash('sha256', $token);
        $stmt  = $this->db->prepare('INSERT INTO api_tokens (usuario_id, token_hash, expira) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))');
        $stmt->bind_param('is', $usuario_id, $hash);
        $stmt->execute();
        $stmt->close();
        return $token;
    }

    /** Administrador activo dueño del token, o null */
    public function usuarioDeToken(string $token): ?array {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
        $hash = hash('sha256', $token);
        $stmt = $this->db->prepare(
            "SELECT u.id, u.nombre, u.usuario, u.rol
               FROM api_tokens t JOIN usuarios u ON u.id = t.usuario_id
              WHERE t.token_hash = ? AND t.expira > NOW() AND u.activo = 1 AND u.eliminado = 0 AND u.rol = 'admin'"
        );
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function borrarToken(string $token): void {
        $hash = hash('sha256', $token);
        $stmt = $this->db->prepare('DELETE FROM api_tokens WHERE token_hash = ?');
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $stmt->close();
    }
}
