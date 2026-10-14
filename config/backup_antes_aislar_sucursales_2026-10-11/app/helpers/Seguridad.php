<?php

/**
 * Protecciones transversales que aplica el router (index.php) a todas las peticiones:
 *  - Token anti-CSRF: obligatorio en todo POST y en las acciones que modifican datos por GET.
 *    Se inyecta solo en formularios, enlaces y fetch(), sin tener que tocar cada vista.
 *  - Sucursal: un usuario que no es admin solo opera sobre la sucursal de su sesión.
 */
class Seguridad {

    /** Rutas GET que modifican datos (enlaces existentes): también exigen el token */
    private const GET_QUE_MODIFICAN = [
        'clientes/eliminar', 'almacen/toggle', 'usuarios/toggle', 'proveedores/toggle',
        'ventas/anular', 'ingresos/eliminar', 'perdidas/eliminar', 'configuracion/veterinaria/eliminar',
    ];

    /** Rutas públicas que no requieren token (no hay sesión que proteger) */
    private const SIN_TOKEN = ['', 'login'];

    public static function token(): string {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function verificar(string $ruta): void {
        $esPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        if (in_array($ruta, self::SIN_TOKEN, true) || (!$esPost && !in_array($ruta, self::GET_QUE_MODIFICAN, true))) {
            return;
        }

        $enviado = $_POST['_csrf'] ?? $_GET['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (is_string($enviado) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $enviado)) {
            return;
        }

        // Token ausente o inválido: no se ejecuta la acción
        if (!empty($_SERVER['HTTP_X_CSRF_TOKEN']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'La sesión expiró. Recarga la página e intenta de nuevo.']);
            exit;
        }
        $_SESSION['flash_error'] = 'La acción no se realizó porque la página había expirado. Intenta de nuevo.';
        header('Location: ' . self::volverSeguro());
        exit;
    }

    /**
     * Para usuarios que no son admin, la sucursal enviada por el formulario o la URL se reemplaza por la de su sesión.
     * set_vet queda fuera: ahí «vet» es la sucursal a la que se quiere cambiar y se valida contra las asignadas.
     */
    public static function forzarSucursal(string $ruta): void {
        if ($ruta === 'set_vet' || !isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') === 'admin') {
            return;
        }
        $vet = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($vet <= 0) return;
        foreach (['veterinaria_id', 'vet'] as $campo) {
            if (isset($_POST[$campo])) $_POST[$campo] = (string)$vet;
            if (isset($_GET[$campo]))  $_GET[$campo]  = (string)$vet;
        }
    }

    /** ¿El registro (venta, ingreso o pérdida) es de la sucursal de la sesión? Evita actuar sobre otra sucursal cambiando el id. */
    public static function esDeLaSucursal(string $tabla, int $id): bool {
        if (!in_array($tabla, ['ventas', 'ingresos', 'perdidas'], true) || $id <= 0) {
            return false;
        }
        require_once ROOT . '/config/Database.php';
        $stmt = Database::getInstance()->getConnection()->prepare("SELECT veterinaria_id FROM $tabla WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $vet = $stmt->get_result()->fetch_row()[0] ?? null;
        $stmt->close();
        return $vet !== null && (int)$vet === (int)($_SESSION['veterinaria_id'] ?? 0);
    }

    /** Callback de ob_start: agrega el token a formularios POST, enlaces que modifican datos y peticiones fetch() */
    public static function inyectar(string $html): string {
        if (empty($_SESSION['csrf']) || stripos($html, '<html') === false) {
            return $html;   // JSON, CSV, etc.
        }
        $t = htmlspecialchars($_SESSION['csrf'], ENT_QUOTES);

        // 1. Formularios POST
        $html = preg_replace(
            '/(<form\b[^>]*\bmethod\s*=\s*["\']?post["\']?[^>]*>)/i',
            '$1<input type="hidden" name="_csrf" value="' . $t . '">',
            $html
        );

        // 2. Enlaces GET que modifican datos
        $rutas = implode('|', array_map(fn($r) => preg_quote($r, '/'), self::GET_QUE_MODIFICAN));
        $html = preg_replace_callback(
            '/(href\s*=\s*["\'][^"\']*\/(?:' . $rutas . '))(\?[^"\']*)?(["\'])/i',
            fn($m) => $m[1] . (($m[2] ?? '') !== '' ? $m[2] . '&amp;' : '?') . '_csrf=' . $t . $m[3],
            $html
        );

        // 3. fetch() de la misma app: se agrega la cabecera X-CSRF-Token.
        //    También se define escHtml() para que el JS escape los datos antes de insertarlos con innerHTML.
        $script = '<meta name="csrf-token" content="' . $t . '"><script>(function(){var t="' . $t . '",f=window.fetch;'
            . 'window.fetch=function(u,o){o=o||{};var h=new Headers(o.headers||{});h.set("X-CSRF-Token",t);o.headers=h;return f(u,o);};'
            . 'window.escHtml=function(v){return String(v==null?"":v).replace(/[&<>"\']/g,function(c){return {"&":"&amp;","<":"&lt;",">":"&gt;",\'"\':"&quot;","\'":"&#39;"}[c];});};})();</script>';
        return preg_replace('/<\/head>/i', $script . '</head>', $html, 1);
    }

    /** Vuelve a la página anterior solo si es de esta misma aplicación */
    private static function volverSeguro(): string {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === parse_url('http://' . $host, PHP_URL_HOST)
            && str_starts_with((string)parse_url($ref, PHP_URL_PATH), BASE_URL . '/')) {
            return $ref;
        }
        return BASE_URL . '/dashboard';
    }
}
