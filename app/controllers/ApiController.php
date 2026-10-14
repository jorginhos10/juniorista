<?php

require_once ROOT . '/app/models/AuditoriaModel.php';
require_once ROOT . '/app/models/UsuarioModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';
require_once ROOT . '/app/helpers/Comprobantes.php';

/**
 * API de la app de auditoría (Flutter). Solo para administradores.
 * No usa la sesión del navegador: cada petición lleva el token en la cabecera X-Api-Token
 * (o Authorization: Bearer). Todas las respuestas son JSON, salvo api/foto que devuelve la imagen.
 */
class ApiController {

    private AuditoriaModel $model;
    private ?array $admin = null;

    public function __construct() {
        $this->model = new AuditoriaModel();
    }

    public function despachar(string $ruta): void {
        // La app puede ejecutarse también en el navegador (Flutter web)
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, X-Api-Token, Authorization');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        if ($ruta === 'login') {
            $this->login();
        }

        $this->admin = $this->model->usuarioDeToken($this->token());
        if (!$this->admin) {
            $this->json(['error' => 'Sesión no válida o vencida. Inicia sesión de nuevo.'], 401);
        }

        switch ($ruta) {
            case 'logout':                   $this->logout(); break;
            case 'sucursales':               $this->json(['sucursales' => $this->model->getSucursales()]); break;
            case 'sucursales/descuentos':    $this->setDescuentos(); break;
            case 'resumen':                  $this->resumen(); break;
            case 'transferencias':           $this->transferencias(); break;
            case 'transferencias/verificar': $this->verificarTransferencia(); break;
            case 'foto':                     $this->foto(); break;
            case 'arqueos':                  $this->arqueos(); break;
            case 'arqueos/auditar':          $this->auditarArqueo(); break;
            default:                         $this->json(['error' => 'Ruta no encontrada.'], 404);
        }
    }

    // ── SESIÓN ────────────────────────────────────────────

    private function login(): void {
        $this->soloPost();
        $d        = $this->cuerpo();
        // La app instalada envía el campo «email»; se toma como nombre de usuario (si trae @, lo que va antes)
        $login    = trim((string)($d['usuario'] ?? $d['email'] ?? ''));
        $login    = explode('@', $login)[0];
        $password = (string)($d['password'] ?? '');

        $usuarios = new UsuarioModel();
        $u = $login !== '' ? $usuarios->findByUsuario($login) : null;
        if (!$u || !$usuarios->verificarPassword($password, $u['password'])) {
            sleep(1);   // frena los intentos repetidos de adivinar la contraseña
            $this->json(['error' => 'Usuario o contraseña incorrectos.'], 401);
        }
        if ($u['rol'] !== 'admin') {
            $this->json(['error' => 'Esta aplicación es solo para administradores.'], 403);
        }

        $this->json([
            'token'   => $this->model->crearToken((int)$u['id']),
            'usuario' => ['id' => (int)$u['id'], 'nombre' => $u['nombre'], 'usuario' => $u['usuario'], 'email' => $u['usuario']],
        ]);
    }

    private function logout(): void {
        $this->model->borrarToken($this->token());
        $this->json(['ok' => true]);
    }

    // ── VENTAS POR SUCURSAL ───────────────────────────────

    private function resumen(): void {
        [$desde, $hasta] = $this->rango();
        $sucursales = $this->model->resumenVentas($desde, $hasta);
        $totales = ['ventas' => 0, 'anuladas' => 0, 'total' => 0.0, 'descuentos' => 0.0, 'unidades' => 0,
                    'efectivo' => 0.0, 'transferencia' => 0.0, 'datafono' => 0.0, 'sin_metodo' => 0.0];
        foreach ($sucursales as $s) {
            foreach ($totales as $k => $_) $totales[$k] += $s[$k];
        }
        $this->json(['desde' => $desde, 'hasta' => $hasta, 'sucursales' => $sucursales, 'totales' => $totales, 'generado' => date('Y-m-d H:i:s')]);
    }

    // ── DESCUENTOS ────────────────────────────────────────

    private function setDescuentos(): void {
        $this->soloPost();
        $d  = $this->cuerpo();
        $id = (int)($d['veterinaria_id'] ?? 0);
        if ($id <= 0 || !$this->model->setDescuentos($id, !empty($d['habilitado']))) {
            $this->json(['error' => 'Sucursal no válida.'], 422);
        }
        $this->json(['ok' => true, 'sucursales' => $this->model->getSucursales()]);
    }

    // ── TRANSFERENCIAS ────────────────────────────────────

    private function transferencias(): void {
        [$desde, $hasta] = $this->rango();
        $lista = $this->model->getTransferencias($desde, $hasta, (int)($_GET['veterinaria_id'] ?? 0));
        foreach ($lista as &$t) {
            $t['radicado'] = VeterinariaModel::serialVenta($t['sucursal'], $t['venta_id'], $t['fecha']);
        }
        unset($t);
        $activas = array_filter($lista, fn($t) => !$t['anulada']);
        $this->json([
            'desde' => $desde, 'hasta' => $hasta, 'transferencias' => $lista,
            'total' => array_sum(array_column($activas, 'monto_transferencia')),
            'sin_foto' => count(array_filter($activas, fn($t) => !$t['fotos'])),
            'sin_verificar' => count(array_filter($activas, fn($t) => !$t['verificada'])),
        ]);
    }

    private function verificarTransferencia(): void {
        $this->soloPost();
        $d = $this->cuerpo();
        if (!$this->model->verificarTransferencia((int)($d['venta_id'] ?? 0), !empty($d['verificada']), (int)$this->admin['id'])) {
            $this->json(['error' => 'La venta no existe o no se pagó por transferencia.'], 422);
        }
        $this->json(['ok' => true]);
    }

    private function foto(): void {
        $foto = $this->model->getComprobante((int)($_GET['id'] ?? 0));
        if (!$foto) {
            $this->json(['error' => 'Foto no encontrada.'], 404);
        }
        Comprobantes::enviar($foto['archivo']);
    }

    // ── ARQUEOS ───────────────────────────────────────────

    private function arqueos(): void {
        [$desde, $hasta] = $this->rango();
        $lista = array_map(function ($a) {
            $declarado = (float)$a['total_declarado'];
            $sistema   = (float)$a['efectivo_sistema'];
            return [
                'id' => (int)$a['id'], 'veterinaria_id' => (int)$a['veterinaria_id'], 'sucursal' => $a['sucursal'],
                'fecha' => $a['fecha'], 'registrado' => $a['created_at'], 'vendedor' => $a['usuario_nombre'],
                'conteo' => (object)(json_decode($a['conteo'], true) ?: []),
                'total_declarado' => $declarado, 'efectivo_sistema' => $sistema, 'diferencia' => round($declarado - $sistema, 2),
                'notas' => $a['notas'], 'estado' => $a['estado'],
                'auditor' => $a['auditor_nombre'], 'auditado_at' => $a['auditado_at'], 'nota_auditoria' => $a['nota_auditoria'],
            ];
        }, $this->model->getArqueos((int)($_GET['veterinaria_id'] ?? 0), $desde, $hasta));

        $this->json([
            'desde' => $desde, 'hasta' => $hasta, 'arqueos' => $lista,
            'monedas' => AuditoriaModel::MONEDAS, 'billetes' => AuditoriaModel::BILLETES,
            'pendientes' => count(array_filter($lista, fn($a) => $a['estado'] === 'pendiente')),
        ]);
    }

    private function auditarArqueo(): void {
        $this->soloPost();
        $d      = $this->cuerpo();
        $estado = (string)($d['estado'] ?? '');
        $nota   = mb_substr(trim((string)($d['nota'] ?? '')), 0, 1000);
        if ($estado === 'observado' && $nota === '') {
            $this->json(['error' => 'Escribe la observación para el vendedor.'], 422);
        }
        if (!$this->model->auditarArqueo((int)($d['id'] ?? 0), $estado, $nota, (int)$this->admin['id'])) {
            $this->json(['error' => 'No se pudo actualizar el arqueo.'], 422);
        }
        $this->json(['ok' => true]);
    }

    // ── UTILIDADES ────────────────────────────────────────

    private function token(): string {
        $t = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
        if ($t === '' && preg_match('/^Bearer\s+(\S+)$/i', $_SERVER['HTTP_AUTHORIZATION'] ?? '', $m)) $t = $m[1];
        return trim($t);   // nunca por la URL: quedaría en los registros del servidor
    }

    /** Datos enviados como JSON o como formulario */
    private function cuerpo(): array {
        $json = json_decode((string)file_get_contents('php://input'), true);
        return is_array($json) ? $json : $_POST;
    }

    /** desde/hasta válidos (por defecto hoy); como mucho un año de rango */
    private function rango(): array {
        $ok = function ($f) {
            $d = DateTime::createFromFormat('!Y-m-d', (string)$f);
            return $d && $d->format('Y-m-d') === $f ? $f : null;
        };
        $desde = $ok($_GET['desde'] ?? '') ?? date('Y-m-d');
        $hasta = $ok($_GET['hasta'] ?? '') ?? $desde;
        if ($hasta < $desde) $hasta = $desde;
        if ((strtotime($hasta) - strtotime($desde)) > 366 * 86400) $desde = date('Y-m-d', strtotime($hasta . ' -366 days'));
        return [$desde, $hasta];
    }

    private function soloPost(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json(['error' => 'Método no permitido.'], 405);
        }
    }

    private function json(array $datos, int $codigo = 200): void {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
