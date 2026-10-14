<?php

require_once ROOT . '/app/models/GastoModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';

class GastoController {

    private GastoModel       $model;
    private VeterinariaModel $vetModel;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->model    = new GastoModel();
        $this->vetModel = new VeterinariaModel();
    }

    public function index(): void {
        [$veterinarias, $veterinaria_id, , $desde, $hasta] = $this->resolverParams();

        $datos = [
            'activePage'     => 'gastos',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'desde'          => $desde,
            'hasta'          => $hasta,
            'gastos'         => $this->model->getRango($veterinaria_id, $desde, $hasta),
            'ventasPeriodo'  => $this->model->getIngresosPeriodo($veterinaria_id, $desde, $hasta),
            'totales'        => $this->model->getTotales($veterinaria_id, date('Y-m-d')),
            'usuario'        => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $this->render('gastos/index', $datos);
    }

    public function imprimir(): void {
        [, $veterinaria_id, $sucursal_nombre, $desde, $hasta] = $this->resolverParams();

        $datos = [
            'sucursal_nombre' => $sucursal_nombre,
            'desde'           => $desde,
            'hasta'           => $hasta,
            'gastos'          => $this->model->getRango($veterinaria_id, $desde, $hasta),
            'ventasPeriodo'   => $this->model->getIngresosPeriodo($veterinaria_id, $desde, $hasta),
            'usuario'         => ['nombre' => $_SESSION['usuario_nombre']],
        ];
        $this->render('gastos/reporte_print', $datos);
    }

    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('gastos');
        }

        [, $veterinaria_id] = $this->resolverParams();
        $volver = 'gastos' . $this->queryVolver($_POST);
        $datos  = $this->validar($_POST);

        if ($datos['error'] !== '') {
            $_SESSION['flash_error'] = $datos['error'];
            $this->redirect($volver);
        }

        $datos['veterinaria_id'] = $veterinaria_id;
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $ok = $this->model->actualizar($id, $datos);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Gasto actualizado correctamente.'
                : 'Error al actualizar el gasto.';
        } else {
            $datos['usuario_id'] = (int)$_SESSION['usuario_id'];
            $ok = $this->model->crear($datos);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Gasto de $' . number_format($datos['monto'], 2) . ' registrado.'
                : 'Error al registrar el gasto.';
        }

        $this->redirect($volver);
    }

    public function eliminar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('gastos');
        }

        [, $veterinaria_id] = $this->resolverParams();
        $ok = $this->model->eliminar((int)($_POST['id'] ?? 0), $veterinaria_id);
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Gasto eliminado.' : 'No se pudo eliminar el gasto.';
        $this->redirect('gastos' . $this->queryVolver($_POST));
    }

    private function validar(array $post): array {
        $fecha = $this->fechaValida($post['fecha'] ?? '');
        $monto = round((float)str_replace(',', '.', (string)($post['monto'] ?? '0')), 2);

        $datos = [
            'fecha'        => $fecha ?? '',
            'descripcion'  => mb_substr(trim($post['descripcion'] ?? ''), 0, 200),
            'monto'        => $monto,
            'notas'      => trim($post['notas'] ?? ''),
            'error'        => '',
        ];

        if ($datos['descripcion'] === '') {
            $datos['error'] = 'La descripción del gasto es obligatoria.';
        } elseif ($monto <= 0) {
            $datos['error'] = 'El monto debe ser mayor a cero.';
        } elseif ($monto > 99999999) {
            $datos['error'] = 'El monto es demasiado alto.';
        } elseif ($fecha === null) {
            $datos['error'] = 'La fecha no es válida.';
        } elseif ($fecha > date('Y-m-d')) {
            $datos['error'] = 'No se pueden registrar gastos con fecha futura.';
        }

        return $datos;
    }

    /** [veterinarias, veterinaria_id, nombre sucursal, desde, hasta] */
    private function resolverParams(): array {
        $veterinarias   = $this->vetModel->getAll();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }
        $nombre = '';
        foreach ($veterinarias as $v) {
            if ((int)$v['id'] === $veterinaria_id) { $nombre = $v['nombre']; break; }
        }

        // Por defecto: mes en curso
        $desde = $this->fechaValida($_GET['desde'] ?? '') ?? date('Y-m-01');
        $hasta = $this->fechaValida($_GET['hasta'] ?? '') ?? date('Y-m-d');
        if ($hasta < $desde) $hasta = $desde;

        return [$veterinarias, $veterinaria_id, $nombre, $desde, $hasta];
    }

    private function queryVolver(array $post): string {
        $q = array_filter([
            'desde'     => $this->fechaValida($post['f_desde'] ?? '') ?? '',
            'hasta'     => $this->fechaValida($post['f_hasta'] ?? '') ?? '',
        ]);
        return $q ? '?' . http_build_query($q) : '';
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
