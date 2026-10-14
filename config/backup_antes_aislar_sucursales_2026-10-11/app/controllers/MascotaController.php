<?php

require_once ROOT . '/app/models/MascotaModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';

class MascotaController {

    private MascotaModel     $model;
    private VeterinariaModel $vetModel;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->model    = new MascotaModel();
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
            'activePage'     => 'mascotas',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'mascotas'       => $this->model->getAll(),
            'clientes'       => $this->model->getClientes(),
            'especies'       => MascotaModel::ESPECIES,
            'filtroCliente'  => (int)($_GET['cliente'] ?? 0),
            'usuario'        => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $this->render('mascotas/index', $datos);
    }

    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('mascotas');
        }

        $datos = $this->validar($_POST);
        if ($datos['error'] !== '') {
            $_SESSION['flash_error'] = $datos['error'];
            $this->redirect('mascotas');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $ok = $this->model->actualizar($id, $datos);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Mascota actualizada correctamente.'
                : 'Error al actualizar la mascota.';
        } else {
            $ok = $this->model->crear($datos);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? $datos['nombre'] . ' fue registrada correctamente.'
                : 'Error al registrar la mascota.';
        }

        $this->redirect('mascotas');
    }

    public function eliminar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('mascotas');
        }

        $ok = $this->model->eliminar((int)($_POST['id'] ?? 0));
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Mascota eliminada.' : 'No se pudo eliminar la mascota.';
        $this->redirect('mascotas');
    }

    private function validar(array $post): array {
        $nac = trim($post['fecha_nacimiento'] ?? '');
        $d   = $nac !== '' ? DateTime::createFromFormat('!Y-m-d', $nac) : null;

        $datos = [
            'cliente_id'       => (int)($post['cliente_id'] ?? 0),
            'nombre'           => mb_substr(trim($post['nombre'] ?? ''), 0, 100),
            'especie'          => $post['especie'] ?? '',
            'raza'             => mb_substr(trim($post['raza'] ?? ''), 0, 100),
            'sexo'             => in_array($post['sexo'] ?? '', ['macho', 'hembra'], true) ? $post['sexo'] : '',
            'fecha_nacimiento' => ($d && $d->format('Y-m-d') === $nac) ? $nac : '',
            'notas'            => trim($post['notas'] ?? ''),
            'error'            => '',
        ];

        if ($datos['nombre'] === '') {
            $datos['error'] = 'El nombre de la mascota es obligatorio.';
        } elseif (!in_array($datos['especie'], MascotaModel::ESPECIES, true)) {
            $datos['error'] = 'Selecciona una especie válida.';
        } elseif ($nac !== '' && $datos['fecha_nacimiento'] === '') {
            $datos['error'] = 'La fecha de nacimiento no es válida.';
        } elseif ($datos['fecha_nacimiento'] > date('Y-m-d')) {
            $datos['error'] = 'La fecha de nacimiento no puede ser futura.';
        } elseif ($datos['cliente_id'] > 0 && !$this->model->clienteExiste($datos['cliente_id'])) {
            $datos['error'] = 'El dueño seleccionado no existe.';
        }

        return $datos;
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
