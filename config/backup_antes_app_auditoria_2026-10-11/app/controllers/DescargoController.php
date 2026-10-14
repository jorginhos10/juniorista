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

    public function registrar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('descargos');
        }

        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $d = [
            'veterinaria_id' => $veterinaria_id,
            'producto_id'    => (int)($_POST['producto_id'] ?? 0),
            'cantidad'       => (int)($_POST['cantidad'] ?? 0),
            'motivo'         => $_POST['motivo'] ?? '',
            'motivo_detalle' => mb_substr(trim($_POST['motivo_detalle'] ?? ''), 0, 255),
            'usuario_id'     => (int)$_SESSION['usuario_id'],
        ];

        $error = '';
        if ($d['producto_id'] <= 0) {
            $error = 'Selecciona un producto.';
        } elseif ($d['cantidad'] <= 0) {
            $error = 'La cantidad debe ser mayor a cero.';
        } elseif (!isset(DescargoModel::MOTIVOS[$d['motivo']])) {
            $error = 'Selecciona el motivo del descargo.';
        } elseif ($d['motivo'] === 'otros' && $d['motivo_detalle'] === '') {
            $error = 'Explica el motivo del descargo.';
        }
        if ($d['motivo'] !== 'otros') {
            $d['motivo_detalle'] = '';   // el detalle solo aplica a «Otros»
        }

        if ($error === '') {
            $error = $this->model->crear($d);
        }

        if ($error !== '') {
            $_SESSION['flash_error']  = $error;
            $_SESSION['descargo_old'] = $_POST;
        } else {
            $_SESSION['flash_success'] = 'Descargo registrado. Se descontaron ' . $d['cantidad'] . ' unidad(es) del inventario.';
        }
        $this->redirect('descargos');
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
