<?php

require_once ROOT . '/app/models/ProveedorModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';

class ProveedorController {

    private ProveedorModel $model;
    private VeterinariaModel $vetModel;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->model    = new ProveedorModel();
        $this->vetModel = new VeterinariaModel();
    }

    public function index(): void {
        $veterinarias   = $this->vetModel->getAll();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }

        $datos = [
            'activePage'     => 'proveedores',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'proveedores'    => $this->model->getAll(),
            'totales'        => $this->model->getTotales(),
            'usuario'        => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $this->render('proveedores/index', $datos);
    }

    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('proveedores');
        }

        $d = [
            'nombre'    => trim($_POST['nombre']    ?? ''),
            'contacto'  => trim($_POST['contacto']  ?? ''),
            'telefono'  => trim($_POST['telefono']  ?? ''),
            'email'     => trim($_POST['email']     ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'notas'     => trim($_POST['notas']     ?? ''),
        ];

        if (empty($d['nombre'])) {
            $_SESSION['flash_error'] = 'El nombre del proveedor es obligatorio.';
            $this->redirect('proveedores');
        }

        if (!empty($d['email']) && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'El correo electrónico no es válido.';
            $this->redirect('proveedores');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $ok = $this->model->actualizar($id, $d);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Proveedor actualizado correctamente.'
                : 'Error al actualizar el proveedor.';
        } else {
            $ok = $this->model->crear($d);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Proveedor registrado correctamente.'
                : 'Error al registrar el proveedor.';
        }

        $this->redirect('proveedores');
    }

    public function toggle(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) $this->model->toggleActivo($id);
        $this->redirect('proveedores');
    }

    private function requiereAutenticacion(): void {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/login'); exit;
        }
    }

    private function redirect(string $ruta): void {
        header('Location: ' . BASE_URL . '/' . $ruta); exit;
    }

    private function render(string $vista, array $datos = []): void {
        extract($datos);
        require ROOT . '/app/views/' . $vista . '.php';
    }
}
