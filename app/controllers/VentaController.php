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

        $datos = [
            'activePage'     => 'ventas',
            'ventas'         => $this->model->getAll($veterinaria_id),
            'totales'        => $this->model->getTotales($veterinaria_id),
            'productos'      => $this->model->getProductos($veterinaria_id),
            'clientes'       => $this->model->getClientes($veterinaria_id),
            'relacionados'   => $this->mapaRelacionados(),
            'descuentos'     => $this->auditoria()->descuentosHabilitados($veterinaria_id),
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

        if ($veterinaria_id <= 0) {
            $_SESSION['flash_error'] = 'Selecciona una veterinaria.';
            $this->redirect('ventas' . $vetQuery);
        }

        if (empty($lineas['items'])) {
            $_SESSION['flash_error'] = 'Agrega al menos un producto a la venta.';
            $this->redirect('ventas' . $vetQuery);
        }

        $errores = $this->model->validarStock($lineas['items'], $veterinaria_id);
        if (!empty($errores)) {
            $_SESSION['flash_error'] = 'Stock insuficiente: ' . implode(' / ', $errores);
            $this->redirect('ventas' . $vetQuery);
        }

        $metodo_pago = $_POST['metodo_pago'] ?? '';
        if (!isset(VentaModel::METODOS_PAGO[$metodo_pago])) {
            $_SESSION['flash_error'] = 'Selecciona el método de pago de la venta.';
            $this->redirect('ventas' . $vetQuery);
        }

        // El cliente debe ser de la misma sucursal de la venta
        $cliente_id = (int)($_POST['cliente_id'] ?? 0);
        if ($cliente_id > 0 && !$this->model->clienteEsDe($cliente_id, $veterinaria_id)) {
            $_SESSION['flash_error'] = 'El cliente seleccionado no pertenece a esta sucursal.';
            $this->redirect('ventas' . $vetQuery);
        }

        // El descuento lo autoriza el administrador desde la app, por sucursal y para UNA sola venta:
        // al registrar una venta con descuento, la autorización se consume y vuelve a quedar desactivado.
        $subtotal  = $lineas['subtotal'];
        $descuento = $this->auditoria()->descuentosHabilitados($veterinaria_id)
            ? min($subtotal, max(0, (float)($_POST['descuento_global'] ?? 0)))
            : 0.0;
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
                $this->redirect('ventas' . $vetQuery);
            }
            $pago_detalle = json_encode($montos);
        }

        $cabecera = [
            'veterinaria_id' => $veterinaria_id,
            'cliente_id'     => (int)($_POST['cliente_id'] ?? 0),
            'metodo_pago'    => $metodo_pago,
            'pago_detalle'   => $pago_detalle,
            'notas'          => trim($_POST['notas'] ?? ''),
            'descuento'      => $descuento,
            'subtotal'       => $subtotal,
            'total'          => $total,
            'usuario_id'     => (int)($_SESSION['usuario_id'] ?? 0),
        ];

        $ok = $this->model->crear($cabecera, $lineas['items']);
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
            ? 'Venta registrada correctamente. Stock actualizado.'
            : 'Error al registrar la venta.';

        if ($ok && $descuento > 0) {
            $this->auditoria()->setDescuentos($veterinaria_id, false);
            $_SESSION['flash_success'] .= ' Se usó el descuento autorizado: queda desactivado hasta que el administrador lo active de nuevo.';
        }

        // Fotos del comprobante tomadas al cobrar (solo en pagos con transferencia)
        if ($ok && in_array($metodo_pago, ['transferencia', 'mixto'], true)) {
            $fotos    = array_slice(array_filter((array)($_POST['comprobante'] ?? []), 'is_string'), 0, Comprobantes::MAX_POR_VENTA);
            $fallidas = $this->guardarComprobantes($this->model->ultimaVenta, $veterinaria_id, $fotos);
            if ($fallidas > 0) {
                $_SESSION['flash_success'] .= " No se pudo guardar $fallidas foto(s) del comprobante: puedes subirlas desde el historial.";
            }
        }

        $this->redirect('ventas' . $vetQuery);
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

        $ingresos = $ventas + $peluqueria['total'] + $tratamientos['total'] + $laboratorios['total'];
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

    private function redirect(string $ruta): void {
        header('Location: ' . BASE_URL . '/' . $ruta); exit;
    }

    private function render(string $vista, array $datos = []): void {
        extract($datos);
        require ROOT . '/app/views/' . $vista . '.php';
    }
}
