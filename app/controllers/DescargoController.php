<?php

require_once ROOT . '/app/models/DescargoModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';

class DescargoController {

    private DescargoModel    $model;
    private VeterinariaModel $vetModel;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->model    = new DescargoModel();
        $this->vetModel = new VeterinariaModel();
    }

    public function index(): void {
        $veterinarias   = $this->vetModel->getAll();
        $veterinaria_id = $this->veterinariaActual($veterinarias);

        // Por defecto: mes en curso
        $desde = $this->fechaValida($_GET['desde'] ?? '') ?? date('Y-m-01');
        $hasta = $this->fechaValida($_GET['hasta'] ?? '') ?? date('Y-m-d');
        if ($hasta < $desde) $hasta = $desde;
        $motivo = isset(DescargoModel::MOTIVOS[$_GET['motivo'] ?? '']) ? $_GET['motivo'] : '';

        $datos = [
            'activePage'     => 'descargos',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'desde'          => $desde,
            'hasta'          => $hasta,
            'motivo'         => $motivo,
            'descargos'      => $this->model->getRango($veterinaria_id, $desde, $hasta, $motivo),
            'productos'      => $this->model->getProductosConStock($veterinaria_id),
            'motivos'        => DescargoModel::MOTIVOS,
            'old'            => $_SESSION['descargo_old'] ?? null,
            'usuario'        => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['descargo_old']);
        $this->render('descargos/index', $datos);
    }

    /** POST: descarga uno o varios productos con la misma fecha y motivo; queda un acta de descargo */
    public function registrar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('descargos');
        }

        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $comun = [
            'veterinaria_id' => $veterinaria_id,
            'fecha'          => $this->fechaValida($_POST['fecha'] ?? '') ?? '',
            'motivo'         => $_POST['motivo'] ?? '',
            'motivo_detalle' => mb_substr(trim($_POST['motivo_detalle'] ?? ''), 0, 255),
            'usuario_id'     => (int)$_SESSION['usuario_id'],
        ];

        // Productos: el mismo producto repetido se suma
        $items = [];
        $ids   = (array)($_POST['producto_id'] ?? []);
        $cants = (array)($_POST['cantidad'] ?? []);
        foreach ($ids as $i => $pid) {
            $pid = (int)$pid;
            $cant = (int)($cants[$i] ?? 0);
            if ($pid <= 0) continue;
            $items[$pid] = ($items[$pid] ?? 0) + $cant;
        }

        $error = '';
        if (!$items) {
            $error = 'Agrega al menos un producto.';
        } elseif (min($items) <= 0) {
            $error = 'Todas las cantidades deben ser mayores a cero.';
        } elseif ($comun['fecha'] === '') {
            $error = 'Indica una fecha válida para el descargo.';
        } elseif ($comun['fecha'] > date('Y-m-d')) {
            $error = 'La fecha del descargo no puede ser futura.';
        } elseif (!isset(DescargoModel::MOTIVOS[$comun['motivo']])) {
            $error = 'Selecciona el motivo del descargo.';
        } elseif ($comun['motivo'] === 'otros' && $comun['motivo_detalle'] === '') {
            $error = 'Explica el motivo del descargo.';
        }
        if ($comun['motivo'] !== 'otros') {
            $comun['motivo_detalle'] = '';   // el detalle solo aplica a «Otros»
        }

        if ($error === '') {
            $res   = $this->model->crearVarios($comun, array_map(null, array_keys($items), array_values($items)));
            $error = $res['error'];
        }

        if ($error !== '') {
            $_SESSION['flash_error']  = $error;
            $_SESSION['descargo_old'] = $_POST;
        } else {
            $n = count($items);
            $_SESSION['flash_success']   = $n === 1
                ? 'Descargo registrado. Se descontaron ' . array_sum($items) . ' unidad(es) del inventario.'
                : "Descargo de $n productos registrado (" . array_sum($items) . ' unidades).';
            $_SESSION['descargo_acta'] = $res['grupo'];   // el listado abre el acta para imprimir
        }
        $this->redirect('descargos');
    }

    /** GET: acta de descargo imprimible (todos los productos descargados juntos) */
    public function acta(): void {
        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        if (isset($_GET['ids'])) {
            $items = $this->model->getPorIds(explode(',', (string)$_GET['ids']), $veterinaria_id);
            $grupo = $items ? (int)min(array_column($items, 'id')) : 0;
        } else {
            $grupo = (int)($_GET['grupo'] ?? 0);
            $items = $this->model->getActa($grupo, $veterinaria_id);
        }
        if (!$items) {
            http_response_code(404);
            echo 'Acta de descargo no encontrada.';
            exit;
        }
        require_once ROOT . '/app/models/FacturacionModel.php';
        $empresa = (new FacturacionModel())->getSucursal($veterinaria_id);
        $this->render('descargos/acta', [
            'items'   => $items,
            'empresa' => $empresa,
            'motivos' => DescargoModel::MOTIVOS,
            'numero'  => VeterinariaModel::serialServicio($empresa['nombre'], 'DES', $grupo, $items[0]['created_at']),
        ]);
    }

    /** POST: cambia cantidad y motivo de un descargo; el stock se ajusta por la diferencia */
    public function editar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('descargos');
        }

        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $id       = (int)($_POST['id'] ?? 0);
        $fecha    = $this->fechaValida($_POST['fecha'] ?? '') ?? '';
        $cantidad = (int)($_POST['cantidad'] ?? 0);
        $motivo   = $_POST['motivo'] ?? '';
        $detalle  = mb_substr(trim($_POST['motivo_detalle'] ?? ''), 0, 255);

        $error = '';
        if ($fecha === '' || $fecha > date('Y-m-d')) {
            $error = 'Indica una fecha válida para el descargo (hoy o anterior).';
        } elseif ($cantidad <= 0) {
            $error = 'La cantidad debe ser mayor a cero.';
        } elseif (!isset(DescargoModel::MOTIVOS[$motivo])) {
            $error = 'Selecciona el motivo del descargo.';
        } elseif ($motivo === 'otros' && $detalle === '') {
            $error = 'Explica el motivo del descargo.';
        }
        if ($motivo !== 'otros') {
            $detalle = '';   // el detalle solo aplica a «Otros»
        }
        if ($error === '') {
            $error = $this->model->actualizar($id, $veterinaria_id, $fecha, $cantidad, $motivo, $detalle);
        }

        $_SESSION[$error === '' ? 'flash_success' : 'flash_error'] = $error === '' ? 'Descargo actualizado.' : $error;
        $this->redirect('descargos' . (isset($_POST['volver']) && str_starts_with((string)$_POST['volver'], '?') ? $_POST['volver'] : ''));
    }

    public function eliminar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('descargos');
        }

        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $ok = $this->model->eliminar((int)($_POST['id'] ?? 0), $veterinaria_id);
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
            ? 'Descargo anulado. Las unidades volvieron al inventario.'
            : 'No se pudo anular el descargo.';
        $this->redirect('descargos');
    }

    private function fechaValida(string $f): ?string {
        $d = DateTime::createFromFormat('!Y-m-d', $f);
        return ($d && $d->format('Y-m-d') === $f) ? $f : null;
    }

    private function veterinariaActual(array $veterinarias): int {
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }
        return $veterinaria_id;
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
