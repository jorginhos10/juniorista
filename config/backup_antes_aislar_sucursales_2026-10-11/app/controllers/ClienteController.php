<?php

require_once ROOT . '/app/models/ClienteModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';

class ClienteController {

    private ClienteModel     $model;
    private VeterinariaModel $vetModel;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->model    = new ClienteModel();
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
            'activePage'     => 'clientes',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'clientes'       => $this->model->getAll(),
            'totales'        => $this->model->getTotales(),
            'usuario'    => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $this->render('clientes/index', $datos);
    }

    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('clientes');
        }

        $datos = $this->validar($_POST);

        if ($datos['error']) {
            $_SESSION['flash_error'] = $datos['error'];
            $this->redirect('clientes');
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $ok = $this->model->actualizar($id, $datos);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Cliente actualizado correctamente.'
                : 'Error al actualizar el cliente.';
        } else {
            $ok = $this->model->crear($datos);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Cliente registrado correctamente.'
                : 'Error al registrar el cliente.';
        }

        $this->redirect('clientes');
    }

    public function eliminar(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->model->eliminar($id);
            $_SESSION['flash_success'] = 'Cliente eliminado.';
        }
        $this->redirect('clientes');
    }

    private function validar(array $post): array {
        $datos = [
            'nombre'    => trim($post['nombre']    ?? ''),
            'apellido'  => trim($post['apellido']  ?? ''),
            'dni'       => trim($post['dni']       ?? ''),
            'email'     => trim($post['email']     ?? ''),
            'telefono'  => trim($post['telefono']  ?? ''),
            'direccion' => trim($post['direccion'] ?? ''),
            'notas'     => trim($post['notas']     ?? ''),
            'error'     => '',
        ];

        if (empty($datos['nombre']) || empty($datos['apellido'])) {
            $datos['error'] = 'El nombre y apellido son obligatorios.';
        } elseif (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $datos['error'] = 'El correo electrónico no es válido.';
        }

        return $datos;
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
