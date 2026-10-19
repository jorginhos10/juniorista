<?php

require_once ROOT . '/app/models/UsuarioModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';
require_once ROOT . '/app/helpers/Permisos.php';

class UsuarioController {

    private UsuarioModel     $model;
    private VeterinariaModel $vetModel;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->requiereAdmin();
        $this->model    = new UsuarioModel();
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
            'activePage'     => 'usuarios',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'usuarios'       => $this->model->getAll(),
            'modulos'        => Permisos::MODULOS,
            'listaSucursales' => $veterinarias,
            'totales'        => $this->model->getTotales(),
            'usuario'        => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
                'id'     => $_SESSION['usuario_id'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $this->render('usuarios/index', $datos);
    }

    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('usuarios');
        }

        $id         = (int)($_POST['id'] ?? 0);
        $nombre     = trim($_POST['nombre'] ?? '');
        $usuario    = mb_strtolower(trim($_POST['usuario'] ?? ''));
        $rol        = $_POST['rol'] ?? 'recepcion';
        $pass       = $_POST['password'] ?? '';
        // Sucursales asignadas (botones tipo interruptor): ninguna o todas = sin restricción
        $activas     = array_map('intval', array_column($this->vetModel->getAll(), 'id'));
        $sucursales  = Permisos::normalizarSucursales((array)($_POST['sucursales'] ?? []), $activas);
        $lista       = Permisos::decodificarSucursales($sucursales);
        $sucursal_id = count($lista) === 1 ? $lista[0] : 0;   // se mantiene la sucursal fija cuando es una sola

        if (empty($nombre) || $usuario === '') {
            $_SESSION['flash_error'] = 'El nombre y el usuario son obligatorios.';
            $this->redirect('usuarios');
        }

        if (!preg_match('/^[a-z0-9._-]{3,30}$/', $usuario)) {
            $_SESSION['flash_error'] = 'El usuario debe tener de 3 a 30 caracteres: letras, números, punto, guion o guion bajo (sin espacios).';
            $this->redirect('usuarios');
        }

        if (!in_array($rol, ['admin', 'veterinario', 'recepcion', 'vendedor'], true)) {
            $rol = 'recepcion';
        }

        if ($this->model->usuarioExiste($usuario, $id)) {
            $_SESSION['flash_error'] = 'Ya existe ese nombre de usuario.';
            $this->redirect('usuarios');
        }

        if ($id > 0) {
            // Al editar, la contraseña es opcional: si se escribe una, se cambia
            if ($pass !== '' && strlen($pass) < 6) {
                $_SESSION['flash_error'] = 'La contraseña debe tener al menos 6 caracteres.';
                $this->redirect('usuarios');
            }
            $ok = $this->model->actualizar($id, compact('nombre', 'usuario', 'rol', 'sucursal_id', 'sucursales'));
            if ($ok && $pass !== '') {
                $ok = $this->model->cambiarPassword($id, $pass);
            }
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? ($pass !== '' ? 'Usuario y contraseña actualizados correctamente.' : 'Usuario actualizado correctamente.')
                : 'Error al actualizar el usuario.';
        } else {
            if (empty($pass) || strlen($pass) < 6) {
                $_SESSION['flash_error'] = 'La contraseña debe tener al menos 6 caracteres.';
                $this->redirect('usuarios');
            }
            $ok = $this->model->crear(['nombre' => $nombre, 'usuario' => $usuario, 'rol' => $rol,
                                       'password' => $pass, 'sucursal_id' => $sucursal_id, 'sucursales' => $sucursales]);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Usuario creado correctamente.'
                : 'Error al crear el usuario.';
        }

        $this->redirect('usuarios');
    }

    public function cambiarPassword(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('usuarios');
        }

        $id   = (int)($_POST['id'] ?? 0);
        $pass = $_POST['password'] ?? '';

        if ($id <= 0 || strlen($pass) < 6) {
            $_SESSION['flash_error'] = 'La contraseña debe tener al menos 6 caracteres.';
            $this->redirect('usuarios');
        }

        $ok = $this->model->cambiarPassword($id, $pass);
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
            ? 'Contraseña actualizada correctamente.'
            : 'Error al actualizar la contraseña.';

        $this->redirect('usuarios');
    }

    public function toggle(): void {
        $id         = (int)($_GET['id'] ?? 0);
        $sesionId   = (int)($_SESSION['usuario_id'] ?? 0);

        if ($id > 0 && $id !== $sesionId) {
            $this->model->toggleActivo($id);
        }
        $this->redirect('usuarios');
    }

    /** POST: módulos habilitados para un usuario (el admin siempre tiene acceso total) */
    public function permisos(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('usuarios');
        }

        $id      = (int)($_POST['id'] ?? 0);
        $usuario = $id > 0 ? $this->model->findById($id) : null;
        $modulos = array_map('strval', (array)($_POST['modulos'] ?? []));

        if (!$usuario) {
            $_SESSION['flash_error'] = 'El usuario no existe.';
        } elseif ($usuario['rol'] === 'admin') {
            $_SESSION['flash_error'] = 'Un administrador siempre tiene acceso a todos los módulos.';
        } else {
            $valor = Permisos::normalizar($modulos);
            $n     = count(Permisos::decodificar($valor));
            $ok    = $this->model->guardarPermisos($id, $valor);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Permisos de «' . $usuario['nombre'] . '» actualizados: ' . ($valor === null ? 'acceso a todos los módulos.' : "$n de " . count(Permisos::MODULOS) . ' módulos.')
                : 'No se pudieron guardar los permisos.';
        }
        $this->redirect('usuarios');
    }

    public function eliminar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('usuarios');
        }

        $id      = (int)($_POST['id'] ?? 0);
        $usuario = $id > 0 ? $this->model->findById($id) : null;

        if (!$usuario) {
            $_SESSION['flash_error'] = 'El usuario no existe.';
        } elseif ($id === (int)$_SESSION['usuario_id']) {
            $_SESSION['flash_error'] = 'No puedes eliminar tu propio usuario.';
        } elseif ($usuario['rol'] === 'admin' && $this->model->contarAdminsActivos($id) === 0) {
            $_SESSION['flash_error'] = 'No se puede eliminar al único administrador activo.';
        } elseif ($this->model->eliminar($id)) {
            $_SESSION['flash_success'] = 'Usuario «' . $usuario['nombre'] . '» eliminado. Sus registros anteriores conservan su nombre.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo eliminar el usuario.';
        }
        $this->redirect('usuarios');
    }

    private function requiereAutenticacion(): void {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    private function requiereAdmin(): void {
        if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
            $_SESSION['flash_error'] = 'Solo un administrador puede gestionar los usuarios.';
            header('Location: ' . BASE_URL . '/configuracion');
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
