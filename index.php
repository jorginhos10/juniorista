<?php

<<<<<<< HEAD
define('ROOT', __DIR__);
define('BASE_URL', '/petcontrol');

// Hora local de la clínica (UTC-5): «hoy» en citas, cierres y reportes coincide con el reloj del equipo y de MySQL
date_default_timezone_set('America/Bogota');

// Error inesperado: se registra el detalle en el log de PHP/Apache y el usuario ve un mensaje claro
set_exception_handler(function (Throwable $e) {
    error_log('[PetControl] ' . get_class($e) . ': ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(500);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Ocurrió un error inesperado. No se guardaron cambios.']);
    } else {
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>PetControl — Error</title></head>'
           . '<body style="font-family:Segoe UI,Arial,sans-serif;background:#f1f5f9;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0">'
           . '<div style="background:#fff;padding:32px 40px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.08);max-width:460px;text-align:center">'
           . '<h2 style="margin:0 0 8px;color:#1e1b4b">Ocurrió un error inesperado</h2>'
           . '<p style="color:#64748b">La operación no se completó y no se guardaron cambios. Intenta de nuevo; si el problema continúa, avisa al administrador.</p>'
           . '<a href="' . BASE_URL . '/dashboard" style="color:#4f46e5;font-weight:600">Volver al inicio</a></div></body></html>';
    }
});

// Router simple
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$uri = preg_replace('#^petcontrol/?#', '', $uri);

// API de la app de auditoría: no usa la sesión del navegador ni el token anti-CSRF; se autentica con su propio token
if (str_starts_with($uri, 'api/')) {
    require_once ROOT . '/app/controllers/ApiController.php';
    (new ApiController())->despachar(substr($uri, 4));
    exit;
}

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

// Protecciones comunes: token anti-CSRF y sucursal de la sesión para usuarios que no son admin
require_once ROOT . '/app/helpers/Seguridad.php';
Seguridad::token();
Seguridad::verificar($uri);
Seguridad::forzarSucursal($uri);

// Permisos por módulo del usuario (el admin siempre tiene acceso total)
require_once ROOT . '/app/helpers/Permisos.php';
Permisos::asegurarSucursal();
Permisos::verificar($uri);
ob_start([Seguridad::class, 'inyectar']);

switch ($uri) {
    case '':
    case 'login':
        require_once ROOT . '/app/controllers/AuthController.php';
        (new AuthController())->login();
        break;

    case 'logout':
        require_once ROOT . '/app/controllers/AuthController.php';
        (new AuthController())->logout();
        break;

    case 'dashboard':
        require_once ROOT . '/app/controllers/DashboardController.php';
        (new DashboardController())->index();
        break;

    case 'almacen':
        require_once ROOT . '/app/controllers/AlmacenController.php';
        (new AlmacenController())->index();
        break;

    case 'almacen/pdf':
        require_once ROOT . '/app/controllers/AlmacenController.php';
        (new AlmacenController())->pdf();
        break;

    case 'almacen/excel':
        require_once ROOT . '/app/controllers/AlmacenController.php';
        (new AlmacenController())->excel();
        break;

    case 'almacen/categorias':
        require_once ROOT . '/app/controllers/AlmacenController.php';
        (new AlmacenController())->categorias();
        break;

    case 'almacen/categorias/guardar':
        require_once ROOT . '/app/controllers/AlmacenController.php';
        (new AlmacenController())->guardarCategoria();
        break;

    case 'almacen/categorias/eliminar':
        require_once ROOT . '/app/controllers/AlmacenController.php';
        (new AlmacenController())->eliminarCategoria();
        break;

    case 'almacen/transferir':
        require_once ROOT . '/app/controllers/AlmacenController.php';
        (new AlmacenController())->transferir();
        break;

    case 'almacen/agregar':
        require_once ROOT . '/app/controllers/AlmacenController.php';
        (new AlmacenController())->agregar();
        break;

    case 'almacen/toggle':
        require_once ROOT . '/app/controllers/AlmacenController.php';
        (new AlmacenController())->toggle();
        break;

    case 'clientes':
        require_once ROOT . '/app/controllers/ClienteController.php';
        (new ClienteController())->index();
        break;

    case 'clientes/guardar':
        require_once ROOT . '/app/controllers/ClienteController.php';
        (new ClienteController())->guardar();
        break;

    case 'clientes/eliminar':
        require_once ROOT . '/app/controllers/ClienteController.php';
        (new ClienteController())->eliminar();
        break;

    case 'citas':
        require_once ROOT . '/app/controllers/CitaController.php';
        (new CitaController())->index();
        break;

    case 'citas/guardar':
        require_once ROOT . '/app/controllers/CitaController.php';
        (new CitaController())->guardar();
        break;

    case 'citas/estado':
        require_once ROOT . '/app/controllers/CitaController.php';
        (new CitaController())->estado();
        break;

    case 'citas/eliminar':
        require_once ROOT . '/app/controllers/CitaController.php';
        (new CitaController())->eliminar();
        break;

    case 'citas/mascotas':
        require_once ROOT . '/app/controllers/CitaController.php';
        (new CitaController())->mascotas();
        break;

    case 'citas/mascota/guardar':
        require_once ROOT . '/app/controllers/CitaController.php';
        (new CitaController())->guardarMascota();
        break;

    case 'gastos':
        require_once ROOT . '/app/controllers/GastoController.php';
        (new GastoController())->index();
        break;

    case 'gastos/guardar':
        require_once ROOT . '/app/controllers/GastoController.php';
        (new GastoController())->guardar();
        break;

    case 'gastos/eliminar':
        require_once ROOT . '/app/controllers/GastoController.php';
        (new GastoController())->eliminar();
        break;

    case 'gastos/imprimir':
        require_once ROOT . '/app/controllers/GastoController.php';
        (new GastoController())->imprimir();
        break;

    case 'tratamiento':
        require_once ROOT . '/app/controllers/TratamientoController.php';
        (new TratamientoController())->index();
        break;

    case 'tratamiento/guardar':
        require_once ROOT . '/app/controllers/TratamientoController.php';
        (new TratamientoController())->guardar();
        break;

    case 'tratamiento/eliminar':
        require_once ROOT . '/app/controllers/TratamientoController.php';
        (new TratamientoController())->eliminar();
        break;

    case 'tratamiento/abonar':
        require_once ROOT . '/app/controllers/TratamientoController.php';
        (new TratamientoController())->abonar();
        break;

    case 'tratamiento/abono/eliminar':
        require_once ROOT . '/app/controllers/TratamientoController.php';
        (new TratamientoController())->eliminarAbono();
        break;

    case 'tratamiento/abonos':
        require_once ROOT . '/app/controllers/TratamientoController.php';
        (new TratamientoController())->abonos();
        break;

    case 'tratamiento/cancelar':
        require_once ROOT . '/app/controllers/TratamientoController.php';
        (new TratamientoController())->cancelar();
        break;

    case 'tratamiento/reactivar':
        require_once ROOT . '/app/controllers/TratamientoController.php';
        (new TratamientoController())->reactivar();
        break;

    case 'tratamiento/abono/imprimir':
        require_once ROOT . '/app/controllers/TratamientoController.php';
        (new TratamientoController())->imprimirAbono();
        break;

    case 'laboratorio':
        require_once ROOT . '/app/controllers/LaboratorioController.php';
        (new LaboratorioController())->index();
        break;

    case 'laboratorio/guardar':
        require_once ROOT . '/app/controllers/LaboratorioController.php';
        (new LaboratorioController())->guardar();
        break;

    case 'laboratorio/eliminar':
        require_once ROOT . '/app/controllers/LaboratorioController.php';
        (new LaboratorioController())->eliminar();
        break;

    case 'peluqueria':
        require_once ROOT . '/app/controllers/PeluqueriaController.php';
        (new PeluqueriaController())->index();
        break;

    case 'peluqueria/guardar':
        require_once ROOT . '/app/controllers/PeluqueriaController.php';
        (new PeluqueriaController())->guardar();
        break;

    case 'peluqueria/eliminar':
        require_once ROOT . '/app/controllers/PeluqueriaController.php';
        (new PeluqueriaController())->eliminar();
        break;

    case 'descargos':
        require_once ROOT . '/app/controllers/DescargoController.php';
        (new DescargoController())->index();
        break;

    case 'descargos/registrar':
        require_once ROOT . '/app/controllers/DescargoController.php';
        (new DescargoController())->registrar();
        break;

    case 'descargos/eliminar':
        require_once ROOT . '/app/controllers/DescargoController.php';
        (new DescargoController())->eliminar();
        break;

    case 'mascotas':
        require_once ROOT . '/app/controllers/MascotaController.php';
        (new MascotaController())->index();
        break;

    case 'mascotas/guardar':
        require_once ROOT . '/app/controllers/MascotaController.php';
        (new MascotaController())->guardar();
        break;

    case 'mascotas/eliminar':
        require_once ROOT . '/app/controllers/MascotaController.php';
        (new MascotaController())->eliminar();
        break;

    case 'historial':
        require_once ROOT . '/app/controllers/HistorialController.php';
        (new HistorialController())->index();
        break;

    case 'historial/guardar':
        require_once ROOT . '/app/controllers/HistorialController.php';
        (new HistorialController())->guardar();
        break;

    case 'historial/eliminar':
        require_once ROOT . '/app/controllers/HistorialController.php';
        (new HistorialController())->eliminar();
        break;

    case 'historial/imprimir':
        require_once ROOT . '/app/controllers/HistorialController.php';
        (new HistorialController())->imprimir();
        break;

    case 'facturacion':
        require_once ROOT . '/app/controllers/FacturacionController.php';
        (new FacturacionController())->index();
        break;

    case 'facturacion/imprimir':
        require_once ROOT . '/app/controllers/FacturacionController.php';
        (new FacturacionController())->imprimir();
        break;

    case 'facturacion/editar':
        require_once ROOT . '/app/controllers/FacturacionController.php';
        (new FacturacionController())->editar();
        break;

    case 'facturacion/cancelar':
        require_once ROOT . '/app/controllers/FacturacionController.php';
        (new FacturacionController())->cancelar();
        break;

    case 'facturacion/exportar':
        require_once ROOT . '/app/controllers/FacturacionController.php';
        (new FacturacionController())->exportar();
        break;

    case 'usuarios':
        require_once ROOT . '/app/controllers/UsuarioController.php';
        (new UsuarioController())->index();
        break;

    case 'usuarios/guardar':
        require_once ROOT . '/app/controllers/UsuarioController.php';
        (new UsuarioController())->guardar();
        break;

    case 'usuarios/permisos':
        require_once ROOT . '/app/controllers/UsuarioController.php';
        (new UsuarioController())->permisos();
        break;

    case 'usuarios/eliminar':
        require_once ROOT . '/app/controllers/UsuarioController.php';
        (new UsuarioController())->eliminar();
        break;

    case 'usuarios/toggle':
        require_once ROOT . '/app/controllers/UsuarioController.php';
        (new UsuarioController())->toggle();
        break;

    case 'usuarios/password':
        require_once ROOT . '/app/controllers/UsuarioController.php';
        (new UsuarioController())->cambiarPassword();
        break;

    case 'configuracion':
        require_once ROOT . '/app/controllers/ConfiguracionController.php';
        (new ConfiguracionController())->index();
        break;

    case 'configuracion/sucursales':
        require_once ROOT . '/app/controllers/ConfiguracionController.php';
        (new ConfiguracionController())->sucursales();
        break;

    case 'configuracion/peluqueria':
        require_once ROOT . '/app/controllers/ConfiguracionController.php';
        (new ConfiguracionController())->negocioPeluqueria();
        break;

    case 'configuracion/peluqueria/guardar':
        require_once ROOT . '/app/controllers/ConfiguracionController.php';
        (new ConfiguracionController())->guardarNegocioPeluqueria();
        break;

    case 'configuracion/apariencia':
        require_once ROOT . '/app/controllers/ConfiguracionController.php';
        (new ConfiguracionController())->apariencia();
        break;

    case 'configuracion/apariencia/guardar':
        require_once ROOT . '/app/controllers/ConfiguracionController.php';
        (new ConfiguracionController())->guardarApariencia();
        break;

    case 'configuracion/veterinaria/guardar':
        require_once ROOT . '/app/controllers/ConfiguracionController.php';
        (new ConfiguracionController())->guardarVeterinaria();
        break;

    case 'configuracion/veterinaria/eliminar':
        require_once ROOT . '/app/controllers/ConfiguracionController.php';
        (new ConfiguracionController())->eliminarVeterinaria();
        break;

    case 'ingresos':
        require_once ROOT . '/app/controllers/IngresoController.php';
        (new IngresoController())->index();
        break;

    case 'ingresos/registrar':
        require_once ROOT . '/app/controllers/IngresoController.php';
        (new IngresoController())->registrar();
        break;

    case 'ingresos/eliminar':
        require_once ROOT . '/app/controllers/IngresoController.php';
        (new IngresoController())->eliminar();
        break;

    case 'ingresos/detalle':
        require_once ROOT . '/app/controllers/IngresoController.php';
        (new IngresoController())->detalle();
        break;

    case 'ingresos/editar':
        require_once ROOT . '/app/controllers/IngresoController.php';
        (new IngresoController())->editar();
        break;

    case 'ingresos/pdf':
        require_once ROOT . '/app/controllers/IngresoController.php';
        (new IngresoController())->pdf();
        break;

    case 'ingresos/excel':
        require_once ROOT . '/app/controllers/IngresoController.php';
        (new IngresoController())->excel();
        break;

    case 'perdidas':
        require_once ROOT . '/app/controllers/PerdidaController.php';
        (new PerdidaController())->index();
        break;

    case 'perdidas/registrar':
        require_once ROOT . '/app/controllers/PerdidaController.php';
        (new PerdidaController())->registrar();
        break;

    case 'perdidas/editar':
        require_once ROOT . '/app/controllers/PerdidaController.php';
        (new PerdidaController())->editar();
        break;

    case 'perdidas/detalle':
        require_once ROOT . '/app/controllers/PerdidaController.php';
        (new PerdidaController())->detalle();
        break;

    case 'perdidas/eliminar':
        require_once ROOT . '/app/controllers/PerdidaController.php';
        (new PerdidaController())->eliminar();
        break;

    case 'ventas':
        require_once ROOT . '/app/controllers/VentaController.php';
        (new VentaController())->index();
        break;

    case 'proveedores':
        require_once ROOT . '/app/controllers/ProveedorController.php';
        (new ProveedorController())->index();
        break;

    case 'proveedores/guardar':
        require_once ROOT . '/app/controllers/ProveedorController.php';
        (new ProveedorController())->guardar();
        break;

    case 'proveedores/toggle':
        require_once ROOT . '/app/controllers/ProveedorController.php';
        (new ProveedorController())->toggle();
        break;

    case 'reportes/perdidas':
        require_once ROOT . '/app/controllers/PerdidaController.php';
        (new PerdidaController())->reporte();
        break;

    case 'reportes/perdidas/imprimir':
        require_once ROOT . '/app/controllers/PerdidaController.php';
        (new PerdidaController())->imprimirReporte();
        break;

    case 'reportes/ingresos':
        require_once ROOT . '/app/controllers/IngresoController.php';
        (new IngresoController())->reporte();
        break;

    case 'reportes/ingresos/imprimir':
        require_once ROOT . '/app/controllers/IngresoController.php';
        (new IngresoController())->imprimirReporte();
        break;

    case 'reportes/ventas':
        require_once ROOT . '/app/controllers/VentaController.php';
        (new VentaController())->reporteVentas();
        break;

    case 'reportes/unidades':
        require_once ROOT . '/app/controllers/VentaController.php';
        (new VentaController())->reporteUnidades();
        break;

    case 'reportes/unidades/imprimir':
        require_once ROOT . '/app/controllers/VentaController.php';
        (new VentaController())->imprimirUnidades();
        break;

    case 'reportes/ventas/imprimir':
        require_once ROOT . '/app/controllers/VentaController.php';
        (new VentaController())->imprimirReporte();
        break;

    case 'ventas/historial':
        require_once ROOT . '/app/controllers/VentaController.php';
        (new VentaController())->historial();
        break;

    case 'ventas/registrar':
        require_once ROOT . '/app/controllers/VentaController.php';
        (new VentaController())->registrar();
        break;

    case 'ventas/detalle':
        require_once ROOT . '/app/controllers/VentaController.php';
        (new VentaController())->detalle();
        break;

    case 'ventas/anular':
        require_once ROOT . '/app/controllers/VentaController.php';
        (new VentaController())->anular();
        break;

    // Fotos de comprobantes de las ventas por transferencia
    case 'ventas/comprobantes':
        require_once ROOT . '/app/controllers/VentaController.php';
        (new VentaController())->comprobantes();
        break;

    case 'ventas/comprobante':
        require_once ROOT . '/app/controllers/VentaController.php';
        (new VentaController())->subirComprobante();
        break;

    case 'ventas/comprobante/ver':
        require_once ROOT . '/app/controllers/VentaController.php';
        (new VentaController())->verComprobante();
        break;

    case 'arqueo':
        require_once ROOT . '/app/controllers/ArqueoController.php';
        (new ArqueoController())->index();
        break;

    case 'arqueo/registrar':
        require_once ROOT . '/app/controllers/ArqueoController.php';
        (new ArqueoController())->registrar();
        break;

    case 'set_vet':
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        // Solo se puede cambiar a una de las sucursales asignadas al usuario (ninguna asignada = todas)
        $vetId = (int)($_GET['vet'] ?? 0);
        if ($vetId > 0 && !Permisos::puedeSucursal($vetId)) {
            $_SESSION['flash_error'] = 'No tienes acceso a esa sucursal.';
        } elseif ($vetId > 0) {
            $_SESSION['veterinaria_id'] = $vetId;
            require_once ROOT . '/app/models/UsuarioModel.php';
            (new UsuarioModel())->guardarUltimaVeterinaria((int)$_SESSION['usuario_id'], $vetId);
        }
        $back = $_GET['back'] ?? (BASE_URL . '/dashboard');
        if (strpos($back, '/') !== 0 || strpos($back, '://') !== false) {
            $back = BASE_URL . '/dashboard';
        }
        header('Location: ' . $back);
        exit;

    default:
        http_response_code(404);
        echo '<h1>404 - Página no encontrada</h1>';
        break;
}
=======
// Sin caché en las páginas, para que cada deploy se vea al recargar.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once "Controllers/Plantilla.php";

$plantilla = new Plantilla();
$plantilla -> mostrarPlantilla();
>>>>>>> parent of c4dd2d6 (eliminado juniorista)
