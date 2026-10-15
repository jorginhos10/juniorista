<?php

require_once ROOT . '/app/models/FacturacionModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';
require_once ROOT . '/app/models/TratamientoModel.php';   // base de peluquería y laboratorios (especies, edición)

class FacturacionController {

    private FacturacionModel $model;
    private VeterinariaModel $vetModel;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->model    = new FacturacionModel();
        $this->vetModel = new VeterinariaModel();
    }

    public function index(): void {
        [$veterinarias, $veterinaria_id, $sucursal, $desde, $hasta, $tipo] = $this->resolverParams();

        // Siempre se traen los tres tipos: los totales por tipo no dependen del filtro, la tabla sí
        $todos       = $this->model->getMovimientos($veterinaria_id, $desde, $hasta);
        $movimientos = $tipo === '' ? $todos : array_values(array_filter($todos, fn($m) => $m['tipo'] === $tipo));

        $datos = [
            'activePage'     => 'facturacion',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'sucursal'       => $sucursal,
            'desde'          => $desde,
            'hasta'          => $hasta,
            'tipo'           => $tipo,
            'movimientos'    => $movimientos,
            'totalesTipo'    => $this->totalesPorTipo($todos),
            'tipos'          => FacturacionModel::TIPOS,
            'esAdmin'        => ($_SESSION['usuario_rol'] ?? '') === 'admin',
            'hoy'            => date('Y-m-d'),
            'usuario'        => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $this->render('facturacion/index', $datos);
    }

    /** GET: factura imprimible de un movimiento (venta o peluquería; los abonos usan su propia factura) */
    public function imprimir(): void {
        [, $veterinaria_id, $sucursal] = $this->resolverParams();
        $tipo = $_GET['tipo'] ?? '';
        $id   = (int)($_GET['id'] ?? 0);

        if ($tipo === 'tratamiento') {
            $this->redirect('tratamiento/abono/imprimir?id=' . $id);
        }

        $doc = null;
        if ($tipo === 'venta' && ($v = $this->model->getVenta($id, $veterinaria_id))) {
            $doc = [
                'numero'   => VeterinariaModel::serialVenta($sucursal, $id, $v['created_at']),
                'creado'   => $v['created_at'],
                'cliente'  => $v['cliente'] ?: 'Cliente general',
                'cliente_extra' => trim(($v['cliente_dni'] ? 'Doc: ' . $v['cliente_dni'] : '') . ($v['cliente_telefono'] ? ' · Tel: ' . $v['cliente_telefono'] : ''), ' ·'),
                'lineas'   => array_map(fn($l) => [
                    'cantidad' => (int)$l['cantidad'],
                    'concepto' => $l['nombre'] . ($l['codigo'] ? ' (' . $l['codigo'] . ')' : ''),
                    'precio'   => (float)$l['precio_unitario'],
                    'descuento'=> (float)$l['descuento'],
                    'subtotal' => (float)$l['subtotal'],
                ], $v['lineas']),
                'subtotal' => (float)$v['subtotal'],
                'descuento'=> (float)$v['descuento'],
                'total'    => (float)$v['total'],
                'metodo_pago' => self::textoMetodoPago($v['metodo_pago'] ?? null, $v['pago_detalle'] ?? null),
                'notas'    => $v['notas'],
                'usuario'  => $v['usuario_nombre'],
                'anulada'  => $v['estado'] === 'anulada',
            ];
        } elseif ($p = $this->model->getServicio($tipo, $id, $veterinaria_id)) {
            // Peluquería o laboratorio: un solo concepto
            $doc = [
                'numero'   => self::comprobante(['tipo' => $tipo, 'id' => $id, 'creado' => $p['created_at']], $sucursal),
                'creado'   => $p['fecha'] . ' ' . date('H:i:s', strtotime($p['created_at'])),
                'cliente'  => $p['nombre'] . ' (' . ($p['especie'] === 'gato' ? 'Gato' : 'Perro') . ')',
                'cliente_extra' => 'Mascota',
                'lineas'   => [[
                    'cantidad' => 1, 'concepto' => FacturacionModel::TIPOS[$tipo]['label'] . ': ' . $p['descripcion'],
                    'precio' => (float)$p['valor'], 'descuento' => 0.0, 'subtotal' => (float)$p['valor'],
                ]],
                'subtotal' => (float)$p['valor'],
                'descuento'=> 0.0,
                'total'    => (float)$p['valor'],
                'notas'    => null,
                'usuario'  => $p['usuario_nombre'],
                'anulada'  => !(int)$p['activo'],
            ];
        }

        if (!$doc) {
            http_response_code(404);
            echo 'Movimiento no encontrado.';
            exit;
        }
        $this->render('facturacion/factura', [
            'doc'      => $doc,
            'tipo'     => FacturacionModel::TIPOS[$tipo]['label'],
            'empresa'  => $this->model->getSucursal($veterinaria_id),
            'emisor'   => $_SESSION['usuario_nombre'],
        ]);
    }

    /** POST: edita un movimiento (peluquería o abono). Las ventas no se editan: se anulan y se registran de nuevo. */
    public function editar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('facturacion');
        }
        [, $veterinaria_id] = $this->resolverParams();
        $tipo  = $_POST['tipo'] ?? '';
        $id    = (int)($_POST['id'] ?? 0);
        $fecha = $this->fechaValida($_POST['fecha'] ?? '');
        $monto = round((float)str_replace(',', '.', (string)($_POST['monto'] ?? '0')), 2);
        $error = '';

        if ($fecha === null) {
            $error = 'La fecha no es válida.';
        } elseif ($fecha > date('Y-m-d')) {
            $error = 'La fecha no puede ser futura.';
        } elseif ($tipo === 'peluqueria' || $tipo === 'laboratorio') {
            $d = [
                'fecha'          => $fecha,
                'nombre'         => mb_substr(trim($_POST['nombre'] ?? ''), 0, 100),
                'especie'        => $_POST['especie'] ?? '',
                'descripcion'    => mb_substr(trim($_POST['descripcion'] ?? ''), 0, 255),
                'valor'          => $monto,
                'veterinaria_id' => $veterinaria_id,
            ];
            if ($d['nombre'] === '' || $d['descripcion'] === '') {
                $error = 'Indica la mascota y el servicio.';
            } elseif (!isset(TratamientoModel::ESPECIES[$d['especie']])) {
                $error = 'Selecciona la especie (perro o gato).';
            } elseif ($monto < 0) {
                $error = 'El valor no es válido.';
            } elseif (!$this->modeloServicio($tipo)->actualizar($id, $d)) {
                $error = 'No se pudo actualizar el servicio.';
            }
        } elseif ($tipo === 'tratamiento') {
            require_once ROOT . '/app/models/TratamientoModel.php';
            $error = $monto <= 0
                ? 'El abono debe ser mayor a cero.'
                : (new TratamientoModel())->actualizarAbono($id, $veterinaria_id, $monto, $fecha, mb_substr(trim($_POST['nota'] ?? ''), 0, 150));
        } else {
            $error = 'Este movimiento no se puede editar aquí.';
        }

        $_SESSION[$error === '' ? 'flash_success' : 'flash_error'] = $error === '' ? 'Movimiento actualizado.' : $error;
        $this->redirect($this->volver());
    }

    /** POST: cancela (anula) un movimiento */
    public function cancelar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('facturacion');
        }
        [, $veterinaria_id] = $this->resolverParams();
        $tipo = $_POST['tipo'] ?? '';
        $id   = (int)($_POST['id'] ?? 0);
        $msg  = '';
        $ok   = false;

        if ($tipo === 'venta') {
            require_once ROOT . '/app/models/VentaModel.php';
            // Se verifica que la venta sea de la sucursal actual antes de anularla
            $ok  = $this->model->getVenta($id, $veterinaria_id) !== null && (new VentaModel())->anular($id);
            $msg = 'Venta anulada. El stock de sus productos volvió al inventario.';
        } elseif ($tipo === 'peluqueria' || $tipo === 'laboratorio') {
            $ok  = $this->modeloServicio($tipo)->eliminar($id, $veterinaria_id);
            $msg = $tipo === 'peluqueria' ? 'Servicio de peluquería anulado.' : 'Examen de laboratorio anulado.';
        } elseif ($tipo === 'tratamiento') {
            if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
                $_SESSION['flash_error'] = 'Solo un administrador puede anular abonos.';
                $this->redirect($this->volver());
            }
            require_once ROOT . '/app/models/TratamientoModel.php';
            $ok  = (new TratamientoModel())->eliminarAbono($id, $veterinaria_id);
            $msg = 'Abono anulado. El saldo del tratamiento volvió a quedar pendiente.';
        }

        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? $msg : 'No se pudo cancelar (ya estaba anulado o no existe).';
        $this->redirect($this->volver());
    }

    /** Modelo del servicio de recaudo directo según el tipo de movimiento */
    private function modeloServicio(string $tipo): TratamientoModel {
        if ($tipo === 'laboratorio') {
            require_once ROOT . '/app/models/LaboratorioModel.php';
            return new LaboratorioModel();
        }
        require_once ROOT . '/app/models/PeluqueriaModel.php';
        return new PeluqueriaModel();
    }

    /** Vuelve a facturación conservando los filtros que traía el formulario */
    private function volver(): string {
        $q = array_filter([
            'desde' => $this->fechaValida($_POST['f_desde'] ?? '') ?? '',
            'hasta' => $this->fechaValida($_POST['f_hasta'] ?? '') ?? '',
            'tipo'  => isset(FacturacionModel::TIPOS[$_POST['f_tipo'] ?? '']) ? $_POST['f_tipo'] : '',
        ]);
        return 'facturacion' . ($q ? '?' . http_build_query($q) : '');
    }

    /** Método de pago de una venta para la factura (el pago mixto incluye el monto de cada método) */
    private static function textoMetodoPago(?string $metodo, ?string $detalle): string {
        require_once ROOT . '/app/models/VentaModel.php';
        return VentaModel::textoMetodoPago($metodo, $detalle);
    }

    /** Descarga CSV (abre en Excel) con los movimientos filtrados */
    public function exportar(): void {
        [, $veterinaria_id, $sucursal, $desde, $hasta, $tipo] = $this->resolverParams();
        $movimientos = $this->model->getMovimientos($veterinaria_id, $desde, $hasta, $tipo);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . VeterinariaModel::nombreArchivo((string)$sucursal, 'facturacion_' . $desde . '_' . $hasta) . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");   // BOM para que Excel respete las tildes
        fputcsv($out, ['Fecha', 'Hora', 'Tipo', 'Comprobante', 'Detalle', 'Cliente', 'Mascota', 'Estado', 'Monto', 'Registrado por'], ';');
        foreach ($movimientos as $m) {
            fputcsv($out, [
                date('d/m/Y', strtotime($m['fecha'])),
                date('H:i', strtotime($m['creado'])),
                FacturacionModel::TIPOS[$m['tipo']]['label'],
                self::comprobante($m, $sucursal),
                $m['detalle'],
                $m['cliente'] ?? '',
                $m['mascota'] ? $m['mascota'] . ' (' . ucfirst($m['especie']) . ')' : '',
                $m['anulada'] ? 'Anulada' : 'Válida',
                number_format((float)$m['monto'], 2, ',', ''),
                $m['usuario_nombre'] ?? '',
            ], ';');
        }
        fclose($out);
        exit;
    }

    /** Número de comprobante: mismo patrón para todo (iniciales-fecha-número); los servicios llevan además su sigla (ABO / PEL / LAB) */
    public static function comprobante(array $m, string $sucursal): string {
        if ($m['tipo'] === 'venta') {
            return VeterinariaModel::serialVenta($sucursal, (int)$m['id'], $m['creado']);
        }
        return VeterinariaModel::serialServicio($sucursal, FacturacionModel::TIPOS[$m['tipo']]['prefijo'], (int)$m['id'], $m['creado'] ?? '');
    }

    private function totalesPorTipo(array $movs): array {
        $t = array_fill_keys(array_keys(FacturacionModel::TIPOS), ['cantidad' => 0, 'total' => 0.0]);
        $t['anuladas'] = ['cantidad' => 0, 'total' => 0.0];
        foreach ($movs as $m) {
            $k = $m['anulada'] ? 'anuladas' : $m['tipo'];
            $t[$k]['cantidad']++;
            $t[$k]['total'] += (float)$m['monto'];
        }
        return $t;
    }

    /** [veterinarias, veterinaria_id, nombre sucursal, desde, hasta, tipo] */
    private function resolverParams(): array {
        $veterinarias   = $this->vetModel->getAll();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }
        $sucursal = '';
        foreach ($veterinarias as $v) {
            if ((int)$v['id'] === $veterinaria_id) { $sucursal = $v['nombre']; break; }
        }

        $desde = $this->fechaValida($_GET['desde'] ?? '') ?? date('Y-m-01');
        $hasta = $this->fechaValida($_GET['hasta'] ?? '') ?? date('Y-m-d');
        if ($hasta < $desde) $hasta = $desde;
        $tipo = isset(FacturacionModel::TIPOS[$_GET['tipo'] ?? '']) ? $_GET['tipo'] : '';

        return [$veterinarias, $veterinaria_id, $sucursal, $desde, $hasta, $tipo];
    }

    private function fechaValida(string $f): ?string {
        $d = DateTime::createFromFormat('!Y-m-d', $f);
        return ($d && $d->format('Y-m-d') === $f) ? $f : null;
    }

    private function requiereAutenticacion(): void {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    private function redirect(string $ruta): void {
        header('Location: ' . BASE_URL . '/' . $ruta);
        exit;
    }

    private function render(string $vista, array $datos = []): void {
        extract($datos);
        require ROOT . '/app/views/' . $vista . '.php';
    }
}
