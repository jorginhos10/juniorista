<?php

require_once ROOT . '/app/models/VeterinariaModel.php';

class ConfiguracionController {

    private VeterinariaModel $vetModel;

    public function __construct() {
        $this->requiereAutenticacion();
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
            'activePage'     => 'configuracion',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'usuario' => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
            'tab_activa' => $_SESSION['flash_tab']  ?? null,
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['flash_tab']);

        $this->render('configuracion/index', $datos);
    }

    /** Negocio / Peluquería: reparto de lo cobrado en peluquería, por sucursal */
    public function negocioPeluqueria(): void {
        $veterinarias   = $this->vetModel->getAll();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }

        $actual = null;
        foreach ($veterinarias as $v) {
            if ((int)$v['id'] === $veterinaria_id) { $actual = $v; break; }
        }

        $datos = [
            'activePage'     => 'configuracion',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'sucursal'       => $actual,
            'pctPeluqueria'  => (float)($actual['pct_peluqueria'] ?? 50),
            'esAdmin'        => ($_SESSION['usuario_rol'] ?? '') === 'admin',
            'usuario' => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $this->render('configuracion/negocio_peluqueria', $datos);
    }

    public function guardarNegocioPeluqueria(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('configuracion/peluqueria');
        }
        if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
            $_SESSION['flash_error'] = 'Solo un administrador puede cambiar el reparto.';
            $this->redirect('configuracion/peluqueria');
        }

        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        $pctPelu    = round((float)str_replace(',', '.', (string)($_POST['pct_peluqueria'] ?? '')), 2);
        $pctNegocio = round((float)str_replace(',', '.', (string)($_POST['pct_negocio'] ?? '')), 2);

        if ($pctPelu < 0 || $pctPelu > 100 || $pctNegocio < 0 || $pctNegocio > 100) {
            $_SESSION['flash_error'] = 'Los porcentajes deben estar entre 0 y 100.';
        } elseif (abs($pctPelu + $pctNegocio - 100) > 0.001) {
            $_SESSION['flash_error'] = 'Los dos porcentajes deben sumar 100 %.';
        } elseif ($this->vetModel->guardarPctPeluqueria($veterinaria_id, $pctPelu)) {
            $_SESSION['flash_success'] = 'Reparto guardado: negocio ' . rtrim(rtrim(number_format($pctNegocio, 2), '0'), '.') . ' % · peluquería '
                . rtrim(rtrim(number_format($pctPelu, 2), '0'), '.') . ' %. Se aplica a los servicios que se registren desde ahora.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo guardar el reparto.';
        }
        $this->redirect('configuracion/peluqueria');
    }

    public const TEMAS = ['original', 'halloween'];

    public function apariencia(): void {
        $veterinarias   = $this->vetModel->getAll();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }

        $datos = [
            'activePage'     => 'configuracion',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'temaActual'     => $_SESSION['tema'] ?? 'original',
            'usuario' => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $this->render('configuracion/apariencia', $datos);
    }

    public function guardarApariencia(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('configuracion/apariencia');
        }

        $tema = $_POST['tema'] ?? '';
        if (!in_array($tema, self::TEMAS, true)) {
            $_SESSION['flash_error'] = 'Tema no válido.';
            $this->redirect('configuracion/apariencia');
        }

        require_once ROOT . '/app/models/UsuarioModel.php';
        if ((new UsuarioModel())->guardarTema((int)$_SESSION['usuario_id'], $tema)) {
            $_SESSION['tema'] = $tema;
            require_once ROOT . '/app/controllers/AuthController.php';
            AuthController::recordarTema($tema);
            $_SESSION['flash_success'] = $tema === 'halloween'
                ? '🎃 ¡Tema Halloween activado! Feliz noche de brujas.'
                : 'Tema Original activado.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo guardar la apariencia.';
        }
        $this->redirect('configuracion/apariencia');
    }

    public function sucursales(): void {
        $this->soloAdmin();
        $veterinarias   = $this->vetModel->getAll();
        foreach ($veterinarias as &$v) {
            $v['tiene_datos'] = $this->vetModel->tieneDatos((int)$v['id']);
        }
        unset($v);
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }

        $datos = [
            'activePage'     => 'configuracion',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'usuario' => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $this->render('configuracion/sucursales', $datos);
    }

    public function guardarVeterinaria(): void {
        $this->soloAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('configuracion');
        }

        $datos = $this->validarVeterinaria($_POST);
        if ($datos['error']) {
            $_SESSION['flash_error'] = $datos['error'];
            $this->redirect('configuracion');
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $ok = $this->vetModel->actualizar($id, $datos);
            $_SESSION['flash_success'] = $ok ? 'Veterinaria actualizada correctamente.' : 'Error al actualizar.';
            $_SESSION['flash_tab']     = $id;
        } else {
            $nuevoId = $this->vetModel->crear($datos);
            $_SESSION['flash_success'] = $nuevoId ? 'Veterinaria agregada correctamente.' : 'Error al agregar.';
            $_SESSION['flash_tab']     = $nuevoId ?: null;
            $id = (int)$nuevoId;
        }

        // Logo: se procesa después de guardar los datos; un problema con la imagen no pierde el resto del formulario
        if ($id > 0) {
            $errorLogo = $this->procesarLogo($id);
            if ($errorLogo !== '') {
                $_SESSION['flash_error'] = 'Los datos se guardaron, pero el logo no: ' . $errorLogo;
            }
        }

        $this->redirect('configuracion/sucursales');
    }

    public function eliminarVeterinaria(): void {
        $this->soloAdmin();
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            if ($this->vetModel->tieneDatos($id)) {
                $_SESSION['flash_error'] = 'No se puede eliminar: la sucursal tiene ventas, ingresos, stock o usuarios asignados.';
            } else {
                $this->vetModel->eliminar($id);
                $_SESSION['flash_success'] = 'Sucursal eliminada correctamente.';
            }
        }
        $this->redirect('configuracion/sucursales');
    }

    /** Sube, reemplaza o quita el logo de una sucursal. Devuelve '' si no hubo problema o el mensaje de error. */
    private function procesarLogo(int $id): string {
        require_once ROOT . '/app/helpers/Imagenes.php';
        $actual = $this->vetModel->findById($id)['logo'] ?? null;
        [$cambio, $ruta, $error] = Imagenes::procesar(
            $_FILES['logo'] ?? null, 'assets/img/logos', 'sucursal_' . $id, $actual, !empty($_POST['quitar_logo'])
        );
        if ($cambio) {
            $this->vetModel->guardarLogo($id, $ruta);
        }
        return $error;
    }

    private function validarVeterinaria(array $post): array {
        $datos = [
            'nombre'    => trim($post['nombre']    ?? ''),
            'ruc'       => trim($post['ruc']       ?? ''),
            'telefono'  => trim($post['telefono']  ?? ''),
            'email'     => trim($post['email']     ?? ''),
            'direccion' => trim($post['direccion'] ?? ''),
            'horario'   => trim($post['horario']   ?? ''),
            'sitio_web' => trim($post['sitio_web'] ?? ''),
            'error'     => '',
        ];

        if (empty($datos['nombre'])) {
            $datos['error'] = 'El nombre de la veterinaria es obligatorio.';
        } elseif (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $datos['error'] = 'El correo electrónico no es válido.';
        }

        return $datos;
    }

    /** Crear, editar o eliminar sucursales es exclusivo del administrador */
    private function soloAdmin(): void {
        if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
            $_SESSION['flash_error'] = 'Solo un administrador puede gestionar las sucursales.';
            $this->redirect('configuracion');
        }
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
