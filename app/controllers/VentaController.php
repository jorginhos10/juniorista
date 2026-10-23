<?php

require_once ROOT . '/app/models/VentaModel.php';

class VentaController {

    private VentaModel $model;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->model = new VentaModel();
    }

    public function index(): void {
        $veterinarias   = $this->model->getVeterinarias();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);

        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }

        // Modo edición (?editar=ID, desde Facturación): el punto de venta se abre con la venta cargada
        $editar    = null;
        $productos = $this->model->getProductos($veterinaria_id);
        if (isset($_GET['editar'])) {
            $editar = $this->model->getParaEditar((int)$_GET['editar'], $veterinaria_id);
            if (!$editar) {
                $_SESSION['flash_error'] = 'La venta no existe, está anulada o es de otra sucursal.';
                $this->redirect('facturacion');
            }
            // Lo que ya tiene la venta vuelve al inventario al guardar: cuenta como disponible
            $cantOriginal = array_column($editar['lineas'], 'cantidad', 'producto_id');
            foreach ($productos as &$p) {
                $p['stock'] = (int)$p['stock'] + ($cantOriginal[(int)$p['id']] ?? 0);
            }
            unset($p);
        }

        // Editar una cotización (?cotizacion=ID, desde su historial): se abre en modo cotización con sus líneas
        $cotizacion = null;
        if (!$editar && isset($_GET['cotizacion'])) {
            require_once ROOT . '/app/models/CotizacionModel.php';
            require_once ROOT . '/app/models/VeterinariaModel.php';
            $cotizacion = (new CotizacionModel())->getCotizacion((int)$_GET['cotizacion']);
            if (!$cotizacion) {
                $_SESSION['flash_error'] = 'La cotización no existe o es de otra sucursal.';
                $this->redirect('ventas/cotizaciones');
            }
            $sucursal = '';
            foreach ($veterinarias as $v) { if ((int)$v['id'] === $veterinaria_id) { $sucursal = $v['nombre']; break; } }
            $cotizacion['numero'] = VeterinariaModel::serialServicio($sucursal, 'COT', (int)$cotizacion['id'], $cotizacion['created_at']);
        }

        $datos = [
            'activePage'     => 'ventas',
            'editar'         => $editar,
            'cotizacion'     => $cotizacion,
            'ventas'         => $this->model->getAll($veterinaria_id),
            'totales'        => $this->model->getTotales($veterinaria_id),
            'productos'      => $productos,
            'clientes'       => $this->model->getClientes($veterinaria_id),
            'relacionados'   => $this->mapaRelacionados(),
            // El administrador siempre puede dar descuento; los demás, solo con la autorización de la app
            'descuentos'     => $this->esAdmin() || $this->auditoria()->descuentosHabilitados($veterinaria_id),
            'esAdmin'        => $this->esAdmin(),
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'usuario'        => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $this->render('ventas/index', $datos);
    }

    /** Productos relacionados (hacen lo mismo): el buscador de ventas los muestra aparte como alternativa */
    private function mapaRelacionados(): array {
        require_once ROOT . '/app/models/ProductoModel.php';
        return (new ProductoModel())->getMapaRelacionados();
    }

    public function registrar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('ventas');
        }

        $veterinaria_id = (int)($_POST['veterinaria_id'] ?? 0);
        $vetQuery       = $veterinaria_id > 0 ? '?vet=' . $veterinaria_id : '';
        $lineas         = $this->extraerLineas($_POST);

        // Edición: venta_id > 0 reemplaza esa venta (misma sucursal y no anulada) en vez de crear una nueva
        $venta_id = (int)($_POST['venta_id'] ?? 0);
        $original = null;
        if ($venta_id > 0) {
            $original = $this->model->getParaEditar($venta_id, $veterinaria_id);
            if (!$original) {
                $_SESSION['flash_error'] = 'La venta no existe, está anulada o es de otra sucursal.';
                $this->redirect('facturacion');
            }
        }
        $volver = $venta_id > 0 ? 'ventas?editar=' . $venta_id : 'ventas' . $vetQuery;

        if ($veterinaria_id <= 0) {
            $_SESSION['flash_error'] = 'Selecciona una veterinaria.';
            $this->redirect($volver);
        }

        if (empty($lineas['items'])) {
            $_SESSION['flash_error'] = 'Agrega al menos un producto a la venta.';
            $this->redirect($volver);
        }

        // Al editar, lo que ya tenía la venta cuenta como disponible (vuelve al inventario antes de descontar)
        $errores = $this->model->validarStock($lineas['items'], $veterinaria_id, $venta_id);
        if (!empty($errores)) {
            $_SESSION['flash_error'] = 'Stock insuficiente: ' . implode(' / ', $errores);
            $this->redirect($volver);
        }

        $metodo_pago = $_POST['metodo_pago'] ?? '';
        if (!isset(VentaModel::METODOS_PAGO[$metodo_pago])) {
            $_SESSION['flash_error'] = 'Selecciona el método de pago de la venta.';
            $this->redirect($volver);
        }

        // El cliente debe ser de la misma sucursal de la venta
        $cliente_id = (int)($_POST['cliente_id'] ?? 0);
        if ($cliente_id > 0 && !$this->model->clienteEsDe($cliente_id, $veterinaria_id)) {
            $_SESSION['flash_error'] = 'El cliente seleccionado no pertenece a esta sucursal.';
            $this->redirect($volver);
        }

        // El descuento lo autoriza el administrador desde la app, por sucursal y para UNA sola venta:
        // al registrar una venta con descuento, la autorización se consume y vuelve a quedar desactivado.
        // El administrador siempre puede dar descuento y no consume esa autorización.
        // Al editar sin autorización se conserva el descuento que ya tenía la venta.
        $subtotal         = $lineas['subtotal'];
        $usaAutorizacion  = !$this->esAdmin();
        $descOriginal     = $original ? (float)$original['descuento'] : 0.0;
        $descPedido       = max(0, (float)($_POST['descuento_global'] ?? 0));
        $puedeDescontar   = !$usaAutorizacion || $this->auditoria()->descuentosHabilitados($veterinaria_id);
        $descuento        = min($subtotal, $puedeDescontar ? $descPedido : $descOriginal);
        // Solo se gasta la autorización si se aplicó un descuento nuevo o distinto al que ya tenía
        $consumeAutorizacion = $usaAutorizacion && $puedeDescontar && $descuento > 0 && abs($descuento - $descOriginal) > 0.009;
        $total     = max(0, $subtotal - $descuento);

        // Pago mixto: los montos por método deben ser al menos dos y sumar el total de la venta
        $pago_detalle = null;
        if ($metodo_pago === 'mixto') {
            $montos = [];
            foreach (VentaModel::METODOS_MIXTO as $m) {
                $v = round((float)($_POST['pago_' . $m] ?? 0), 2);
                if ($v > 0) $montos[$m] = $v;
            }
            if (count($montos) < 2 || abs(array_sum($montos) - $total) > 0.009) {
                $_SESSION['flash_error'] = 'En un pago mixto indica el monto de al menos dos métodos y que sumen el total de la venta.';
                $this->redirect($volver);
            }
            $pago_detalle = json_encode($montos);
        }

        // Fecha de la venta: vacía = ahora. Se puede registrar con fecha pasada, nunca futura.
        $fecha    = null;
        $fechaTxt = trim($_POST['fecha'] ?? '');
        if ($fechaTxt !== '') {
            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $fechaTxt);
            if (!$dt || $dt->format('Y-m-d\TH:i') !== $fechaTxt) {
                $_SESSION['flash_error'] = 'La fecha de la venta no es válida.';
                $this->redirect($volver);
            }
            if ($dt > new DateTime('+5 minutes')) {
                $_SESSION['flash_error'] = 'La fecha de la venta no puede ser futura.';
                $this->redirect($volver);
            }
            $fecha = $dt->format('Y-m-d H:i:s');
        }

        $cabecera = [
            'veterinaria_id' => $veterinaria_id,
            'fecha'          => $fecha,
            'cliente_id'     => (int)($_POST['cliente_id'] ?? 0),
            'metodo_pago'    => $metodo_pago,
            'pago_detalle'   => $pago_detalle,
            'notas'          => trim($_POST['notas'] ?? ''),
            'descuento'      => $descuento,
            'subtotal'       => $subtotal,
            'total'          => $total,
            'usuario_id'     => (int)($_SESSION['usuario_id'] ?? 0),
        ];

        if ($venta_id > 0) {
            $ok = $this->model->actualizar($venta_id, $cabecera, $lineas['items']);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Venta actualizada. El stock se ajustó con los cambios.'
                : 'Error al actualizar la venta. No se guardaron cambios.';
        } else {
            $ok = $this->model->crear($cabecera, $lineas['items']);
            $venta_id = $ok ? $this->model->ultimaVenta : 0;
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Venta registrada correctamente. Stock actualizado.'
                : 'Error al registrar la venta.';
        }

        if ($ok && $consumeAutorizacion) {
            $this->auditoria()->setDescuentos($veterinaria_id, false);
            $_SESSION['flash_success'] .= ' Se usó el descuento autorizado: queda desactivado hasta que el administrador lo active de nuevo.';
        }

        // Fotos del comprobante tomadas al cobrar (solo en pagos con transferencia)
        if ($ok && in_array($metodo_pago, ['transferencia', 'mixto'], true)) {
            $fotos    = array_slice(array_filter((array)($_POST['comprobante'] ?? []), 'is_string'), 0, Comprobantes::MAX_POR_VENTA);
            $fallidas = $this->guardarComprobantes($venta_id, $veterinaria_id, $fotos);
            if ($fallidas > 0) {
                $_SESSION['flash_success'] .= " No se pudo guardar $fallidas foto(s) del comprobante: puedes subirlas desde el historial.";
            }
        }

        // Tras editar se vuelve a facturación; si falló, a la misma edición
        $this->redirect($original ? ($ok ? 'facturacion' : $volver) : 'ventas' . $vetQuery);
    }

    public function reporteVentas(): void {
        $veterinarias   = $this->model->getVeterinarias();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }

        $hoy  = date('Y-m-d');
        $desde = $_GET['desde'] ?? $hoy;
        $hasta = $_GET['hasta'] ?? $hoy;

        // Sanitizar fechas
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = $hoy;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = $hoy;
        if ($hasta < $desde) $hasta = $desde;

        $datos = [
            'activePage'     => 'reporte-ventas',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'desde'          => $desde,
            'hasta'          => $hasta,
            'resumen'        => $this->model->getResumenReporte($veterinaria_id, $desde, $hasta),
            'balance'        => $this->balancePeriodo($veterinaria_id, $desde, $hasta),
            'por_articulo'   => $this->conRadicados($this->model->getVentasPorArticulo($veterinaria_id, $desde, $hasta), $veterinaria_id),
            'movimientos' => $this->movimientosPeriodo($veterinaria_id, $desde, $hasta),
            'usuario'        => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
        ];
        $this->render('ventas/reporte', $datos);
    }

    private function resolverReporteParams(): array {
        $vets = $this->model->getVeterinarias();
        $vet_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($vet_id === 0 && !empty($vets)) {
            $vet_id = (int)$vets[0]['id'];
        }
        $nombre = '';
        foreach ($vets as $v) { if ((int)$v['id'] === $vet_id) { $nombre = $v['nombre']; break; } }

        $hoy   = date('Y-m-d');
        $desde = $_GET['desde'] ?? $hoy;
        $hasta = $_GET['hasta'] ?? $hoy;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = $hoy;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = $hoy;
        if ($hasta < $desde) $hasta = $desde;

        return [$vets, $vet_id, $nombre, $desde, $hasta];
    }

    public function reporteUnidades(): void {
        [$veterinarias, $veterinaria_id, $sucursal_nombre, $desde, $hasta] = $this->resolverReporteParams();
        $producto_id   = (int)($_GET['producto_id'] ?? 0);
        $producto_nombre = '';
        $todosProductos = $this->model->getProductos($veterinaria_id);
        if ($producto_id > 0) {
            foreach ($todosProductos as $pv) {
                if ((int)$pv['id'] === $producto_id) { $producto_nombre = $pv['nombre']; break; }
            }
        }

        $datos = [
            'activePage'        => 'reporte-unidades',
            'veterinarias'      => $veterinarias,
            'veterinaria_id'    => $veterinaria_id,
            'sucursal_nombre'   => $sucursal_nombre,
            'desde'             => $desde,
            'hasta'             => $hasta,
            'producto_id'       => $producto_id,
            'producto_nombre'   => $producto_nombre,
            'productosVendidos' => $todosProductos,
            'lineas'            => $this->model->getReporteUnidades($veterinaria_id, $desde, $hasta, $producto_id),
            'resumen'           => $this->model->getResumenUnidades($veterinaria_id, $desde, $hasta, $producto_id),
            'usuario'           => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
        ];
        $this->render('ventas/reporte_unidades', $datos);
    }

    public function imprimirUnidades(): void {
        [, $veterinaria_id, $sucursal_nombre, $desde, $hasta] = $this->resolverReporteParams();
        $producto_id   = (int)($_GET['producto_id'] ?? 0);
        $producto_nombre = '';
        $todosProductos = $this->model->getProductos($veterinaria_id);
        foreach ($todosProductos as $pv) {
            if ((int)$pv['id'] === $producto_id) { $producto_nombre = $pv['nombre']; break; }
        }

        $datos = [
            'veterinaria_id'   => $veterinaria_id,
            'sucursal_nombre'  => $sucursal_nombre,
            'desde'            => $desde,
            'hasta'            => $hasta,
            'producto_id'      => $producto_id,
            'producto_nombre'  => $producto_nombre,
            'lineas'           => $this->model->getReporteUnidades($veterinaria_id, $desde, $hasta, $producto_id),
            'resumen'          => $this->model->getResumenUnidades($veterinaria_id, $desde, $hasta, $producto_id),
            'usuario'          => ['nombre' => $_SESSION['usuario_nombre']],
        ];
        $this->render('ventas/reporte_unidades_print', $datos);
    }

    public function imprimirReporte(): void {
        $veterinarias   = $this->model->getVeterinarias();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
        }

        $hoy  = date('Y-m-d');
        $desde = $_GET['desde'] ?? $hoy;
        $hasta = $_GET['hasta'] ?? $hoy;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = $hoy;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = $hoy;
        if ($hasta < $desde) $hasta = $desde;

        // Nombre de la sucursal
        $sucursal_nombre = '';
        foreach ($veterinarias as $v) {
            if ((int)$v['id'] === $veterinaria_id) { $sucursal_nombre = $v['nombre']; break; }
        }

        $datos = [
            'veterinaria_id'  => $veterinaria_id,
            'sucursal_nombre' => $sucursal_nombre,
            'desde'           => $desde,
            'hasta'           => $hasta,
            'sucursal_logo'   => $this->logoSucursal($veterinaria_id),
            'resumen'         => $this->model->getResumenReporte($veterinaria_id, $desde, $hasta),
            'balance'         => $this->balancePeriodo($veterinaria_id, $desde, $hasta),
            'por_articulo'    => $this->conRadicados($this->model->getVentasPorArticulo($veterinaria_id, $desde, $hasta), $veterinaria_id),
            'movimientos' => $this->movimientosPeriodo($veterinaria_id, $desde, $hasta),
            'usuario'         => [
                'nombre' => $_SESSION['usuario_nombre'],
            ],
        ];
        $this->render('ventas/reporte_print', $datos);
    }

    /** Ingresos del período (ventas, peluquería y lo ABONADO a tratamientos) menos los gastos */
    /** Todo lo cobrado en el período, movimiento por movimiento (ventas, abonos, peluquería y laboratorios), con su comprobante */
    private function movimientosPeriodo(int $veterinaria_id, string $desde, string $hasta): array {
        require_once ROOT . '/app/models/VeterinariaModel.php';
        require_once ROOT . '/app/models/FacturacionModel.php';
        $sucursal = (new VeterinariaModel())->findById($veterinaria_id)['nombre'] ?? '';
        $movs = (new FacturacionModel())->getMovimientos($veterinaria_id, $desde, $hasta);
        foreach ($movs as &$m) {
            $m['comprobante'] = $m['tipo'] === 'venta'
                ? VeterinariaModel::serialVenta($sucursal, (int)$m['id'], $m['creado'])
                : VeterinariaModel::serialServicio($sucursal, FacturacionModel::TIPOS[$m['tipo']]['prefijo'], (int)$m['id'], $m['creado']);
        }
        unset($m);
        return $movs;
    }

    /** Agrega a cada factura de los artículos su radicado (el mismo número que imprime la factura) */
    private function conRadicados(array $articulos, int $veterinaria_id): array {
        require_once ROOT . '/app/models/VeterinariaModel.php';
        $sucursal = (new VeterinariaModel())->findById($veterinaria_id)['nombre'] ?? '';
        foreach ($articulos as &$a) {
            foreach ($a['facturas'] as &$fa) {
                $fa['radicado'] = VeterinariaModel::serialVenta($sucursal, $fa['id'], $fa['created_at']);
            }
            unset($fa);
        }
        unset($a);
        return $articulos;
    }

    /** Ruta del logo de la sucursal (o null) para los reportes imprimibles */
    private function logoSucursal(int $veterinaria_id): ?string {
        require_once ROOT . '/app/models/VeterinariaModel.php';
        return (new VeterinariaModel())->findById($veterinaria_id)['logo'] ?? null;
    }

    private function balancePeriodo(int $veterinaria_id, string $desde, string $hasta): array {
        require_once ROOT . '/app/models/PeluqueriaModel.php';
        require_once ROOT . '/app/models/LaboratorioModel.php';
        require_once ROOT . '/app/models/GastoModel.php';

        $ventas       = (float)($this->model->getResumenReporte($veterinaria_id, $desde, $hasta)['total_ingresos'] ?? 0);
        $peluqueria   = (new PeluqueriaModel())->getTotalRango($veterinaria_id, $desde, $hasta);
        $tratamientos = (new TratamientoModel())->getTotalRango($veterinaria_id, $desde, $hasta);
        $laboratorios = (new LaboratorioModel())->getTotalRango($veterinaria_id, $desde, $hasta);
        $gastos       = (new GastoModel())->getTotalRango($veterinaria_id, $desde, $hasta);

        $ingresos = $ventas + $peluqueria['negocio'] +$tratamientos['total'] + $laboratorios['total'];
        return [
            'ventas'       => $ventas,
            'peluqueria'   => $peluqueria,
            'tratamientos' => $tratamientos,
            'laboratorios' => $laboratorios,
            'ingresos'     => $ingresos,
            'gastos'       => $gastos,
            'utilidad'     => $ingresos - $gastos['total'],
        ];
    }

    public function historial(): void {
        $veterinarias   = $this->model->getVeterinarias();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);

        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }

        $datos = [
            'activePage'     => 'historial-ventas',
            'ventas'         => $this->model->getAll($veterinaria_id),
            'totales'        => $this->model->getTotales($veterinaria_id),
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'usuario'        => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $this->render('ventas/historial', $datos);
    }

    public function detalle(): void {
        $id = (int)($_GET['id'] ?? 0);
        header('Content-Type: application/json');
        echo json_encode(Seguridad::esDeLaSucursal('ventas', $id) ? $this->model->getDetalles($id) : []);
        exit;
    }

    public function anular(): void {
        $id  = (int)($_GET['id']  ?? 0);
        $vet = (int)($_GET['vet'] ?? 0);

        if ($id > 0) {
            $ok = Seguridad::esDeLaSucursal('ventas', $id) && $this->model->anular($id);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Venta anulada y stock restaurado.'
                : 'No se pudo anular (ya estaba anulada o no existe).';
        }

        $this->redirect('ventas' . ($vet > 0 ? '?vet=' . $vet : ''));
    }

    // ── COMPROBANTES (fotos de las transferencias) ────────

    private ?AuditoriaModel $auditoria = null;

    private function auditoria(): AuditoriaModel {
        require_once ROOT . '/app/models/AuditoriaModel.php';
        require_once ROOT . '/app/helpers/Comprobantes.php';
        return $this->auditoria ??= new AuditoriaModel();
    }

    /** Guarda las fotos de una venta en la carpeta sistebendey/<fecha>/<sucursal>/. Devuelve cuántas no se pudieron guardar. */
    private function guardarComprobantes(int $venta_id, int $veterinaria_id, array $fotos): int {
        $venta = $venta_id > 0 ? $this->auditoria()->getVentaDeSucursal($venta_id, $veterinaria_id) : null;
        if (!$venta) return count($fotos);

        $fallidas = 0;
        foreach ($fotos as $foto) {
            $archivo = Comprobantes::guardar($venta_id, $venta['sucursal'], $foto, date('Y-m-d', strtotime($venta['created_at'])));
            if (!$archivo || !$this->auditoria()->agregarComprobante($venta_id, $veterinaria_id, $archivo, (int)($_SESSION['usuario_id'] ?? 0))) {
                $fallidas++;
            }
        }
        return $fallidas;
    }

    /** GET JSON: fotos de una venta de la sucursal */
    public function comprobantes(): void {
        $id = (int)($_GET['id'] ?? 0);
        header('Content-Type: application/json');
        $fotos = Seguridad::esDeLaSucursal('ventas', $id) ? $this->auditoria()->getComprobantes($id) : [];
        echo json_encode(array_map(fn($f) => ['id' => (int)$f['id'], 'fecha' => $f['created_at']], $fotos));
        exit;
    }

    /** POST JSON: agrega una foto a una venta ya registrada (desde el historial) */
    public function subirComprobante(): void {
        header('Content-Type: application/json');
        $d  = json_decode((string)file_get_contents('php://input'), true) ?: $_POST;
        $id = (int)($d['venta_id'] ?? 0);
        $vet = (int)($_SESSION['veterinaria_id'] ?? 0);

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Seguridad::esDeLaSucursal('ventas', $id)) {
            $error = 'La venta no pertenece a esta sucursal.';
        } elseif ($this->auditoria()->contarComprobantes($id) >= Comprobantes::MAX_POR_VENTA) {
            $error = 'Esta venta ya tiene el máximo de ' . Comprobantes::MAX_POR_VENTA . ' fotos.';
        } elseif (!is_string($d['foto'] ?? null) || $this->guardarComprobantes($id, $vet, [$d['foto']]) > 0) {
            $error = 'No se pudo guardar la foto. Debe ser una imagen de hasta 6 MB.';
        }

        if ($error !== '') http_response_code(422);
        echo json_encode($error !== '' ? ['error' => $error] : ['ok' => true]);
        exit;
    }

    /** GET: entrega la imagen de un comprobante de la sucursal */
    public function verComprobante(): void {
        $foto = $this->auditoria()->getComprobante((int)($_GET['id'] ?? 0), (int)($_SESSION['veterinaria_id'] ?? 0));
        if (!$foto) {
            http_response_code(404);
            exit;
        }
        Comprobantes::enviar($foto['archivo']);
    }

    private function extraerLineas(array $post): array {
        $ids      = $post['producto_id']      ?? [];
        $cantidades = $post['cantidad']        ?? [];
        $precios    = $post['precio_unitario'] ?? [];
        $descuentos = $post['desc_linea']      ?? [];

        $items    = [];
        $subtotal = 0;

        foreach ($ids as $i => $pid) {
            $pid  = (int)$pid;
            $cant = (int)($cantidades[$i]  ?? 0);
            $prec = (float)($precios[$i]   ?? 0);
            $desc = (float)($descuentos[$i] ?? 0);
            if ($pid > 0 && $cant > 0) {
                $items[]   = ['producto_id' => $pid, 'cantidad' => $cant, 'precio_unitario' => $prec, 'descuento' => $desc];
                $subtotal += ($cant * $prec) - $desc;
            }
        }
        return ['items' => $items, 'subtotal' => $subtotal];
    }

    private function requiereAutenticacion(): void {
        if (!isset($_SESSION['usuario_id'])) { header('Location: ' . BASE_URL . '/'); exit; }
    }

    /**
     * POST (desde el modo cotización del punto de venta): guarda la cotización y abre su formato para imprimir.
     * No registra venta ni toca el inventario. Los productos se cotizan con su precio de venta actual;
     * los servicios llevan la descripción y el precio escritos a mano.
     */
    public function cotizacion(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('ventas');
        }
        require_once ROOT . '/app/models/CotizacionModel.php';
        require_once ROOT . '/app/models/ClienteModel.php';

        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        $tipos      = (array)($_POST['tipo'] ?? []);
        $ids        = (array)($_POST['producto_id'] ?? []);
        $textos     = (array)($_POST['descripcion'] ?? []);
        $cantidades = (array)($_POST['cantidad'] ?? []);
        $precios    = (array)($_POST['precio'] ?? []);
        $productos  = $this->model->getProductosPorId($ids);

        $lineas = [];
        foreach ($tipos as $i => $tipo) {
            $cant = max(1, (int)($cantidades[$i] ?? 1));
            if ($tipo === 'servicio') {
                $texto  = mb_substr(trim((string)($textos[$i] ?? '')), 0, 200);
                $precio = round(max(0, (float)($precios[$i] ?? 0)), 2);
                if ($texto === '') continue;
                $lineas[] = ['producto_id' => null, 'cantidad' => $cant, 'concepto' => $texto, 'detalle' => 'Servicio', 'precio' => $precio];
            } elseif (isset($productos[(int)($ids[$i] ?? 0)])) {
                $p = $productos[(int)$ids[$i]];
                $lineas[] = ['producto_id' => (int)$p['id'], 'cantidad' => $cant, 'concepto' => $p['nombre'],
                             'detalle' => trim(($p['codigo'] ? 'Cód. ' . $p['codigo'] : '') . ' · ' . $p['unidad'], ' ·'),
                             'precio' => (float)$p['precio_venta']];
            }
        }
        foreach ($lineas as &$l) $l['subtotal'] = round($l['cantidad'] * $l['precio'], 2);
        unset($l);

        if (!$lineas) {
            http_response_code(422);
            echo 'La cotización no tiene productos ni servicios.';
            exit;
        }

        $subtotal = array_sum(array_column($lineas, 'subtotal'));
        // En la cotización el descuento siempre se permite (no mueve dinero ni consume la autorización de descuentos de ventas)
        $descuento = min($subtotal, max(0, round((float)($_POST['descuento'] ?? 0), 2)));
        $pct = $descuento > 0 && is_numeric($_POST['descuento_pct'] ?? '') ? min(100, max(0, (float)$_POST['descuento_pct'])) : null;

        $cid = (int)($_POST['cliente_id'] ?? 0);
        $cliente_id = $cid > 0 && (new ClienteModel())->findById($cid) ? $cid : null;

        $modelo = new CotizacionModel();
        $editarId = (int)($_POST['cotizacion_id'] ?? 0);
        if ($editarId > 0 && !$modelo->getCotizacion($editarId)) $editarId = 0;   // de otra sucursal o inexistente: se crea nueva
        $id = $modelo->guardar([
            'veterinaria_id' => $veterinaria_id,
            'cliente_id'     => $cliente_id,
            'usuario_id'     => (int)$_SESSION['usuario_id'],
            'subtotal'       => $subtotal,
            'descuento'      => $descuento,
            'descuento_pct'  => $pct,
            'total'          => max(0, $subtotal - $descuento),
            'notas'          => mb_substr(trim((string)($_POST['notas'] ?? '')), 0, 500) ?: null,
        ], $lineas, $editarId);

        if (!$id) {
            http_response_code(500);
            echo 'No se pudo guardar la cotización.';
            exit;
        }
        // Se abre por GET: recargar la pestaña no crea otra cotización
        $this->redirect('ventas/cotizacion/ver?id=' . $id);
    }

    /** GET: historial de cotizaciones (solo cotizaciones) */
    public function cotizaciones(): void {
        require_once ROOT . '/app/models/CotizacionModel.php';
        $veterinarias   = $this->model->getVeterinarias();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        $datos = [
            'activePage'     => 'ventas',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'cotizaciones'   => (new CotizacionModel())->getAll($veterinaria_id),
            'usuario'        => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $this->render('ventas/cotizaciones', $datos);
    }

    /** GET: formato imprimible de una cotización guardada */
    public function verCotizacion(): void {
        require_once ROOT . '/app/models/CotizacionModel.php';
        require_once ROOT . '/app/models/FacturacionModel.php';
        require_once ROOT . '/app/models/VeterinariaModel.php';

        $c = (new CotizacionModel())->getCotizacion((int)($_GET['id'] ?? 0));
        if (!$c) {
            http_response_code(404);
            echo 'Cotización no encontrada.';
            exit;
        }
        $empresa = (new FacturacionModel())->getSucursal((int)$c['veterinaria_id']);
        $fecha   = strtotime($c['created_at']);
        $this->render('ventas/cotizacion', [
            'empresa'      => $empresa,
            'numero'       => VeterinariaModel::serialServicio($empresa['nombre'], 'COT', (int)$c['id'], $c['created_at']),
            'fecha'        => $fecha,
            'vence'        => strtotime('+15 days', $fecha),
            'cliente'      => $c['cliente_id'] ? ['nombre' => $c['cliente_nombre'], 'apellido' => $c['cliente_apellido'],
                                                  'dni' => $c['cliente_dni'], 'telefono' => $c['cliente_telefono']] : null,
            'lineas'       => $c['lineas'],
            'subtotal'     => (float)$c['subtotal'],
            'descuento'    => (float)$c['descuento'],
            'descuentoPct' => $c['descuento_pct'] !== null ? (float)$c['descuento_pct'] : null,
            'total'        => (float)$c['total'],
            'notas'        => (string)$c['notas'],
            'usuario'      => (string)$c['usuario_nombre'],
        ]);
    }

    private function esAdmin(): bool {
        return ($_SESSION['usuario_rol'] ?? '') === 'admin';
    }

    private function redirect(string $ruta): void {
        header('Location: ' . BASE_URL . '/' . $ruta); exit;
    }

    private function render(string $vista, array $datos = []): void {
        extract($datos);
        require ROOT . '/app/views/' . $vista . '.php';
    }
}
