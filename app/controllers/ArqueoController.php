<?php

require_once ROOT . '/app/models/AuditoriaModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';

/**
 * Arqueo de caja: al final del día el vendedor declara cuántas monedas y billetes tiene.
 * El vendedor no ve lo que el sistema espera (conteo a ciegas); el administrador lo audita desde la app.
 */
class ArqueoController {

    private AuditoriaModel   $model;
    private VeterinariaModel $vetModel;

    public function __construct() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        $this->model    = new AuditoriaModel();
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
            'activePage'     => 'arqueo',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'monedas'        => AuditoriaModel::MONEDAS,
            'billetes'       => AuditoriaModel::BILLETES,
            'estados'        => AuditoriaModel::ESTADOS_ARQUEO,
            'arqueos'        => $veterinaria_id > 0 ? $this->model->getArqueos($veterinaria_id, date('Y-m-d', strtotime('-30 days')), date('Y-m-d')) : [],
            'esAdmin'        => ($_SESSION['usuario_rol'] ?? '') === 'admin',
            'usuario'        => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        extract($datos);
        require ROOT . '/app/views/arqueo/index.php';
    }

    public function registrar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->volver();
        }

        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id <= 0) {
            $_SESSION['flash_error'] = 'Selecciona una sucursal.';
            $this->volver();
        }

        $n = AuditoriaModel::normalizarConteo((array)($_POST['conteo'] ?? []));
        if ($n['total'] <= 0 && empty($_POST['caja_vacia'])) {
            $_SESSION['flash_error'] = 'Indica cuántas monedas y billetes hay. Si la caja está vacía, marca la casilla para confirmarlo.';
            $this->volver();
        }

        $notas = mb_substr(trim($_POST['notas'] ?? ''), 0, 1000);
        $id = $this->model->crearArqueo($veterinaria_id, (int)$_SESSION['usuario_id'], date('Y-m-d'), $n['conteo'], $n['total'], $notas);

        $_SESSION[$id > 0 ? 'flash_success' : 'flash_error'] = $id > 0
            ? 'Arqueo registrado por $' . number_format($n['total'], 0, ',', '.') . '. Queda pendiente de revisión del administrador.'
            : 'No se pudo registrar el arqueo.';
        $this->volver();
    }

    private function volver(): void {
        header('Location: ' . BASE_URL . '/arqueo');
        exit;
    }
}
