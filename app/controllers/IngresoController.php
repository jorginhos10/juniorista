<?php

require_once ROOT . '/app/models/IngresoModel.php';
require_once ROOT . '/app/models/ProveedorModel.php';

class IngresoController {

    private IngresoModel $model;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->model = new IngresoModel();
    }

    public function index(): void {
        $veterinarias   = $this->model->getVeterinarias();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);

        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }

        $datos = [
            'activePage'     => 'ingresos',
            'ingresos'       => $this->model->getAll($veterinaria_id),
            'totales'        => $this->model->getTotales($veterinaria_id),
            'productos'      => $this->model->getProductos($veterinaria_id),
            'proveedores'    => (new ProveedorModel())->getAll(),
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

        $this->render('ingresos/index', $datos);
    }

    public function registrar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('ingresos');
        }

        $veterinaria_id = (int)($_POST['veterinaria_id'] ?? 0);
        $vetQuery       = $veterinaria_id > 0 ? '?vet=' . $veterinaria_id : '';

        // Recoger líneas de productos
        $ids      = $_POST['producto_id']     ?? [];
        $cantidades = $_POST['cantidad']      ?? [];
        $precios    = $_POST['precio_unitario'] ?? [];
        $lotes      = $_POST['lote']              ?? [];
        $vencimientos = $_POST['fecha_vencimiento'] ?? [];

        $lineas = [];
        $total  = 0;

        foreach ($ids as $i => $pid) {
            $pid   = (int)$pid;
            $cant  = (int)($cantidades[$i] ?? 0);
            $precio = (float)($precios[$i] ?? 0);

            if ($pid > 0 && $cant > 0) {
                $lineas[] = [
                    'producto_id'     => $pid,
                    'cantidad'        => $cant,
                    'precio_unitario' => $precio,
                ] + $this->datosLote($lotes[$i] ?? '', $vencimientos[$i] ?? '');
                $total += $cant * $precio;
            }
        }

        if ($veterinaria_id <= 0) {
            $_SESSION['flash_error'] = 'Debes seleccionar una veterinaria.';
            $this->redirect('ingresos' . $vetQuery);
        }

        if (empty($lineas)) {
            $_SESSION['flash_error'] = 'Debes agregar al menos un producto al ingreso.';
            $this->redirect('ingresos' . $vetQuery);
        }

        $cabecera = [
            'veterinaria_id' => $veterinaria_id,
            'proveedor'      => trim($_POST['proveedor'] ?? ''),
            'notas'          => trim($_POST['notas']     ?? ''),
            'total'          => $total,
            'usuario_id'     => (int)($_SESSION['usuario_id'] ?? 0),
        ];

        if ($this->model->crear($cabecera, $lineas)) {
            $n = count($lineas);
            $_SESSION['flash_success'] = "Ingreso registrado con $n producto(s). Stock actualizado.";
        } else {
            $_SESSION['flash_error'] = 'Error al registrar el ingreso.';
        }

        $this->redirect('ingresos' . $vetQuery);
    }

    public function editar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('ingresos');
        }

        $id  = (int)($_POST['ingreso_id'] ?? 0);
        $vet = (int)($_POST['veterinaria_id'] ?? 0);
        $vetQuery = $vet > 0 ? '?vet=' . $vet : '';

        if ($id <= 0 || !Seguridad::esDeLaSucursal('ingresos', $id)) {
            $_SESSION['flash_error'] = 'Ingreso no válido.';
            $this->redirect('ingresos' . $vetQuery);
        }

        $ids      = $_POST['producto_id']      ?? [];
        $cantidades = $_POST['cantidad']        ?? [];
        $precios    = $_POST['precio_unitario'] ?? [];
        $lotes      = $_POST['lote']              ?? [];
        $vencimientos = $_POST['fecha_vencimiento'] ?? [];

        $lineas = [];
        $total  = 0;

        foreach ($ids as $i => $pid) {
            $pid  = (int)$pid;
            $cant = (int)($cantidades[$i] ?? 0);
            $prec = (float)($precios[$i]  ?? 0);
            if ($pid > 0 && $cant > 0) {
                $lineas[] = ['producto_id' => $pid, 'cantidad' => $cant, 'precio_unitario' => $prec]
                          + $this->datosLote($lotes[$i] ?? '', $vencimientos[$i] ?? '');
                $total   += $cant * $prec;
            }
        }

        if (empty($lineas)) {
            $_SESSION['flash_error'] = 'Debes agregar al menos un producto.';
            $this->redirect('ingresos' . $vetQuery);
        }

        $cabecera = [
            'proveedor' => trim($_POST['proveedor'] ?? ''),
            'notas'     => trim($_POST['notas']     ?? ''),
            'total'     => $total,
        ];

        if ($this->model->actualizar($id, $cabecera, $lineas)) {
            $_SESSION['flash_success'] = 'Ingreso actualizado correctamente.';
        } else {
            $_SESSION['flash_error'] = 'Error al actualizar el ingreso.';
        }

        $this->redirect('ingresos' . $vetQuery);
    }

    public function detalle(): void {
        $id = (int)($_GET['id'] ?? 0);
        header('Content-Type: application/json');
        echo json_encode(Seguridad::esDeLaSucursal('ingresos', $id) ? $this->model->getDetalles($id) : []);
        exit;
    }

    // ── DESCARGAS ─────────────────────────────────────────

    /** Datos de un ingreso de la sucursal de la sesión, listos para exportar (o null si no es de la sucursal) */
    private function datosExportar(): ?array {
        $id = (int)($_GET['id'] ?? 0);
        if (!Seguridad::esDeLaSucursal('ingresos', $id)) return null;
        $cab = $this->model->getCabecera($id);
        if (!$cab) return null;

        require_once ROOT . '/app/models/VeterinariaModel.php';
        $tipos = ['compra' => 'Ingreso de productos', 'transferencia_salida' => 'Transferencia enviada', 'transferencia_entrada' => 'Transferencia recibida'];
        return [
            'cab'      => $cab,
            'lineas'   => $this->model->getDetalles($id),
            'radicado' => VeterinariaModel::serialIngreso($cab['veterinaria_nombre'], $id, $cab['created_at']),
            'titulo'   => $tipos[$cab['tipo'] ?? 'compra'] ?? 'Ingreso de productos',
        ];
    }

    private function sinAcceso(): void {
        $_SESSION['flash_error'] = 'El ingreso no existe o no pertenece a esta sucursal.';
        $this->redirect('ingresos');
    }

    private static function fechaCorta(?string $fecha): string {
        return $fecha ? date('d/m/Y', strtotime($fecha)) : '';
    }

    public function excel(): void {
        $d = $this->datosExportar();
        if (!$d) $this->sinAcceso();
        require_once ROOT . '/app/helpers/ExcelExport.php';

        $cab = $d['cab'];
        $filas = [
            ['Sucursal', $cab['veterinaria_nombre']],
            ['Radicado', $d['radicado']],
            ['Tipo', $d['titulo']],
            ['Proveedor', $cab['proveedor'] ?: 'Sin proveedor'],
            ['Fecha', date('d/m/Y H:i', strtotime($cab['created_at']))],
            ['Registrado por', $cab['usuario_nombre'] ?? ''],
            ['Notas', $cab['notas'] ?? ''],
            [],
            ['#', 'Producto', 'Código', 'Lote', 'Vencimiento', 'Cantidad', 'Unidad', 'P. Unitario', 'Subtotal'],
        ];
        $total = 0; $unidades = 0;
        foreach ($d['lineas'] as $i => $l) {
            $sub = (float)$l['subtotal'];
            $total += $sub; $unidades += (int)$l['cantidad'];
            $filas[] = [
                $i + 1, $l['producto_nombre'], (string)($l['codigo'] ?? ''), (string)($l['lote'] ?? ''),
                self::fechaCorta($l['fecha_vencimiento'] ?? null), (int)$l['cantidad'], (string)($l['unidad'] ?? ''),
                (float)$l['precio_unitario'], $sub,
            ];
        }
        $filas[] = ['', 'TOTAL', '', '', '', $unidades, '', '', $total];

        ExcelExport::descargar(VeterinariaModel::nombreArchivo($cab['veterinaria_nombre'], 'ingreso_' . $d['radicado']), 'Ingreso', ['INGRESO DE PRODUCTOS', ''], $filas, [16, 48, 20, 16, 14, 11, 10, 13, 14]);
        exit;
    }

    public function pdf(): void {
        $d = $this->datosExportar();
        if (!$d) $this->sinAcceso();
        require_once ROOT . '/app/helpers/PdfSimple.php';

        $cab = $d['cab'];
        $pdf = new PdfSimple();
        $izq = 36; $der = PdfSimple::ANCHO - 36;
        // Columnas: x de inicio (o de fin si va alineada a la derecha)
        $col = ['n' => $izq + 4, 'prod' => $izq + 28, 'lote' => 318, 'vence' => 384, 'cant' => 446, 'precio' => 498, 'sub' => $der - 4];
        $dinero = fn($v) => '$' . number_format((float)$v, 2, '.', ',');
        $generado = 'Generado el ' . date('d/m/Y H:i');

        $encabezadoTabla = function (float $y) use ($pdf, $izq, $der, $col): float {
            $pdf->rectangulo($izq, $y, $der - $izq, 16, 0.90);
            $pdf->texto($col['n'], $y + 11, '#', 8, true);
            $pdf->texto($col['prod'], $y + 11, 'PRODUCTO', 8, true);
            $pdf->texto($col['lote'], $y + 11, 'LOTE', 8, true);
            $pdf->texto($col['vence'], $y + 11, 'VENCE', 8, true);
            $pdf->texto($col['cant'], $y + 11, 'CANT.', 8, true, 'R');
            $pdf->texto($col['precio'], $y + 11, 'P. UNIT.', 8, true, 'R');
            $pdf->texto($col['sub'], $y + 11, 'SUBTOTAL', 8, true, 'R');
            return $y + 16;
        };
        $pie = function () use ($pdf, $izq, $der, $generado, $d): void {
            $pdf->linea($izq, PdfSimple::ALTO - 40, $der, PdfSimple::ALTO - 40);
            $pdf->texto($izq, PdfSimple::ALTO - 28, $d['radicado'] . '  ·  ' . $generado, 7.5, false, 'L', 0.45);
            $pdf->texto($der, PdfSimple::ALTO - 28, 'Página ' . $pdf->numeroPagina(), 7.5, false, 'R', 0.45);
        };

        // Encabezado del documento
        $pdf->texto($izq, 50, $cab['veterinaria_nombre'], 16, true);
        $pdf->texto($der, 46, $d['titulo'], 12, true, 'R');
        $pdf->texto($der, 60, $d['radicado'], 10, false, 'R', 0.35);
        $pdf->linea($izq, 70, $der, 70, 0.3);

        $y = 88;
        $datos = [
            'Proveedor'      => $cab['proveedor'] ?: 'Sin proveedor',
            'Fecha'          => date('d/m/Y H:i', strtotime($cab['created_at'])),
            'Registrado por' => $cab['usuario_nombre'] ?? '—',
        ];
        if (trim((string)($cab['notas'] ?? '')) !== '') $datos['Notas'] = $cab['notas'];
        foreach ($datos as $k => $v) {
            $pdf->texto($izq, $y, $k . ':', 9, true);
            $pdf->texto($izq + 82, $y, $pdf->ajustar((string)$v, $der - $izq - 82, 9), 9);
            $y += 14;
        }

        $y = $encabezadoTabla($y + 6);
        $total = 0; $unidades = 0;
        foreach ($d['lineas'] as $i => $l) {
            if ($y > PdfSimple::ALTO - 70) {
                $pie();
                $pdf->nuevaPagina();
                $y = $encabezadoTabla(40);
            }
            $sub = (float)$l['subtotal'];
            $total += $sub; $unidades += (int)$l['cantidad'];
            $venc = $l['fecha_vencimiento'] ?? null;
            $vencido = $venc && $venc < date('Y-m-d');

            if ($i % 2 === 1) $pdf->rectangulo($izq, $y, $der - $izq, 15, 0.97);
            $nombre = $l['producto_nombre'] . (!empty($l['codigo']) ? '  (' . $l['codigo'] . ')' : '');
            $pdf->texto($col['n'], $y + 10.5, (string)($i + 1), 8, false, 'L', 0.45);
            $pdf->texto($col['prod'], $y + 10.5, $pdf->ajustar($nombre, $col['lote'] - $col['prod'] - 8, 8), 8);
            $pdf->texto($col['lote'], $y + 10.5, $pdf->ajustar((string)($l['lote'] ?? ''), 62, 8), 8);
            $pdf->texto($col['vence'], $y + 10.5, self::fechaCorta($venc) . ($vencido ? ' *' : ''), 8, $vencido);
            $pdf->texto($col['cant'], $y + 10.5, (string)(int)$l['cantidad'], 8, false, 'R');
            $pdf->texto($col['precio'], $y + 10.5, $dinero($l['precio_unitario']), 8, false, 'R');
            $pdf->texto($col['sub'], $y + 10.5, $dinero($sub), 8, false, 'R');
            $y += 15;
        }

        // Totales (en la misma página si caben)
        if ($y > PdfSimple::ALTO - 90) {
            $pie();
            $pdf->nuevaPagina();
            $y = 40;
        }
        $pdf->linea($izq, $y + 2, $der, $y + 2, 0.3);
        $pdf->texto($col['prod'], $y + 17, count($d['lineas']) . ' producto(s)', 9, true);
        $pdf->texto($col['cant'], $y + 17, (string)$unidades, 9, true, 'R');
        $pdf->texto($col['lote'], $y + 17, 'TOTAL', 9, true);
        $pdf->texto($col['sub'], $y + 17, $dinero($total), 10, true, 'R');
        $pdf->texto($izq, $y + 34, '* Producto con fecha de vencimiento ya cumplida.', 7.5, false, 'L', 0.45);
        $pie();

        $pdf->descargar(VeterinariaModel::nombreArchivo($cab['veterinaria_nombre'], 'ingreso_' . $d['radicado']));
        exit;
    }

    public function eliminar(): void {
        $id  = (int)($_GET['id']  ?? 0);
        $vet = (int)($_GET['vet'] ?? 0);

        if ($id > 0 && Seguridad::esDeLaSucursal('ingresos', $id)) {
            $this->model->eliminar($id);
            $_SESSION['flash_success'] = 'Ingreso eliminado y stock revertido.';
        } elseif ($id > 0) {
            $_SESSION['flash_error'] = 'El ingreso no pertenece a esta sucursal.';
        }

        $query = $vet > 0 ? '?vet=' . $vet : '';
        $this->redirect('ingresos' . $query);
    }

    // Lote y fecha de vencimiento de una línea: ambos opcionales; una fecha mal formada se guarda vacía
    private function datosLote($lote, $vence): array {
        $lote  = mb_substr(trim((string)$lote), 0, 60);
        $vence = trim((string)$vence);
        $fecha = DateTime::createFromFormat('Y-m-d', $vence);
        return [
            'lote'              => $lote !== '' ? $lote : null,
            'fecha_vencimiento' => ($fecha && $fecha->format('Y-m-d') === $vence) ? $vence : null,
        ];
    }

    // ── Reporte de ingresos ──────────────────────────────────────────

    public const TIPOS_REPORTE = [
        'compra'                => ['label' => 'Compra',                 'plural' => 'Compras',                  'icon' => 'cart-plus-fill',          'color' => '#4f46e5', 'unidad' => ['compra', 'compras']],
        'transferencia_entrada' => ['label' => 'Transferencia recibida', 'plural' => 'Transferencias recibidas', 'icon' => 'box-arrow-in-down-right', 'color' => '#0891b2', 'unidad' => ['transferencia', 'transferencias']],
        'transferencia_salida'  => ['label' => 'Transferencia enviada',  'plural' => 'Transferencias enviadas',  'icon' => 'box-arrow-up-right',      'color' => '#db2777', 'unidad' => ['transferencia', 'transferencias']],
    ];

    public function reporte(): void {
        $datos = $this->datosReporte();
        $datos['activePage'] = 'reporte-ingresos';
        $this->render('ingresos/reporte', $datos);
    }

    public function imprimirReporte(): void {
        $this->render('ingresos/reporte_print', $this->datosReporte());
    }

    /** Período, ingresos con su radicado, desglose por producto y totales por tipo */
    private function datosReporte(): array {
        require_once ROOT . '/app/models/VeterinariaModel.php';
        $veterinarias   = $this->model->getVeterinarias();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }
        $sucursal = (new VeterinariaModel())->findById($veterinaria_id) ?? [];
        $nombre   = $sucursal['nombre'] ?? '';

        $hoy   = date('Y-m-d');
        $desde = $_GET['desde'] ?? $hoy;
        $hasta = $_GET['hasta'] ?? $hoy;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = $hoy;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = $hoy;
        if ($hasta < $desde) $hasta = $desde;

        $resumen = array_fill_keys(array_keys(self::TIPOS_REPORTE), ['cantidad' => 0, 'unidades' => 0, 'total' => 0.0, 'venta' => 0.0, 'ganancia' => 0.0]);
        $movimientos = $this->model->getReporte($veterinaria_id, $desde, $hasta);
        foreach ($movimientos as &$mv) {
            $mv['comprobante'] = VeterinariaModel::serialIngreso($nombre, (int)$mv['id'], $mv['created_at']);
            $resumen[$mv['tipo']]['cantidad']++;
            $resumen[$mv['tipo']]['unidades'] += (int)$mv['unidades'];
            $resumen[$mv['tipo']]['total']    += (float)$mv['total'];
            $resumen[$mv['tipo']]['venta']    += (float)$mv['venta'];
            $resumen[$mv['tipo']]['ganancia'] += (float)$mv['ganancia'];
        }
        unset($mv);

        $por_articulo = $this->model->getIngresosPorArticulo($veterinaria_id, $desde, $hasta);
        foreach ($por_articulo as &$a) {
            foreach ($a['ingresos'] as &$in) {
                $in['radicado'] = VeterinariaModel::serialIngreso($nombre, $in['id'], $in['created_at']);
            }
            unset($in);
        }
        unset($a);

        return [
            'veterinarias'    => $veterinarias,
            'veterinaria_id'  => $veterinaria_id,
            'sucursal_nombre' => $nombre,
            'sucursal_logo'   => $sucursal['logo'] ?? null,
            'desde'           => $desde,
            'hasta'           => $hasta,
            'resumen'         => $resumen,
            'movimientos'     => $movimientos,
            'por_articulo'    => $por_articulo,
            'tipos'           => self::TIPOS_REPORTE,
            'usuario'         => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
        ];
    }

    private function requiereAutenticacion(): void {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/');
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
