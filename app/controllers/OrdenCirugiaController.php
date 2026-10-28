<?php

require_once ROOT . '/app/models/OrdenCirugiaModel.php';
require_once ROOT . '/app/models/HistorialModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';
require_once ROOT . '/app/models/FacturacionModel.php';

/** Órdenes de cirugía: programa el procedimiento y imprime el consentimiento informado con los datos ya rellenados */
class OrdenCirugiaController {

    private OrdenCirugiaModel $model;
    private HistorialModel $historial;
    private VeterinariaModel $vetModel;

    public function __construct() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        $this->model     = new OrdenCirugiaModel();
        $this->historial = new HistorialModel();
        $this->vetModel  = new VeterinariaModel();
    }

    public function index(): void {
        $veterinarias = $this->vetModel->getAll();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }

        $datos = [
            'activePage'     => 'ordenes-cirugia',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'hoy'            => date('Y-m-d'),
            'ordenes'        => $this->model->getAll(),
            'estados'        => OrdenCirugiaModel::ESTADOS,
            'mascotas'       => $this->historial->getMascotas(),
            'mascotaInicial' => (int)($_GET['mascota'] ?? 0),   // viene del Historial: abre la orden con la mascota elegida
            'veterinarios'   => $this->historial->getVeterinarios(),
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
        require ROOT . '/app/views/ordenes_cirugia/index.php';
    }

    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirect('ordenes-cirugia');

        $mascota_id = (int)($_POST['mascota_id'] ?? 0);
        $mascota = null;
        foreach ($this->historial->getMascotas() as $m) {   // solo mascotas de esta sucursal
            if ((int)$m['id'] === $mascota_id) { $mascota = $m; break; }
        }

        $fecha = DateTime::createFromFormat('Y-m-d', $_POST['fecha'] ?? '');
        $d = [
            'fecha'          => $fecha ? $fecha->format('Y-m-d') : date('Y-m-d'),
            'mascota_id'     => $mascota ? $mascota_id : 0,
            'cliente_id'     => $mascota ? (int)($mascota['cliente_id'] ?? 0) : 0,
            'mascota_nombre' => mb_substr(trim($_POST['mascota_nombre'] ?? ''), 0, 100),
            'especie'        => mb_substr(trim($_POST['especie'] ?? ''), 0, 30),
            'raza'           => mb_substr(trim($_POST['raza'] ?? ''), 0, 100),
            'responsable_nombre' => mb_substr(trim($_POST['responsable_nombre'] ?? ''), 0, 200),
            'responsable_cc' => mb_substr(trim($_POST['responsable_cc'] ?? ''), 0, 30),
            'procedimiento'  => mb_substr(trim($_POST['procedimiento'] ?? ''), 0, 150),
            'veterinario_id' => (int)($_POST['veterinario_id'] ?? 0),
            'examenes_prequirurgicos' => empty($_POST['examenes_prequirurgicos']) ? 0 : 1,
            'notas'          => mb_substr(trim($_POST['notas'] ?? ''), 0, 255),
            'usuario_id'     => (int)$_SESSION['usuario_id'],
        ];

        if ($d['mascota_nombre'] === '') {
            $_SESSION['flash_error'] = 'Indica el nombre de la mascota.';
        } elseif ($d['procedimiento'] === '') {
            $_SESSION['flash_error'] = 'Indica la cirugía o procedimiento.';
        } elseif (($editar = (int)($_POST['id'] ?? 0)) > 0) {
            $actual = $this->model->find($editar);
            if (!$actual || $actual['estado'] !== 'programada') {
                $_SESSION['flash_error'] = 'Solo se pueden editar órdenes programadas.';
            } elseif ($this->model->actualizar($editar, $d)) {
                $_SESSION['flash_success'] = 'Orden actualizada.';
            } else {
                $_SESSION['flash_error'] = 'No se pudo actualizar la orden.';
            }
        } elseif ($id = $this->model->crear($d)) {
            // Al guardar se abre el consentimiento listo para imprimir
            $this->redirect('ordenes-cirugia/consentimiento?id=' . $id);
        } else {
            $_SESSION['flash_error'] = 'No se pudo registrar la orden de cirugía.';
        }
        $this->redirect('ordenes-cirugia');
    }

    public function estado(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirect('ordenes-cirugia');
        $ok = $this->model->cambiarEstado((int)($_POST['id'] ?? 0), $_POST['estado'] ?? '');
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Estado actualizado.' : 'No se pudo actualizar el estado.';
        $this->redirect('ordenes-cirugia');
    }

    public function consentimiento(): void {
        $orden = $this->model->find((int)($_GET['id'] ?? 0));
        if (!$orden) $this->redirect('ordenes-cirugia');

        extract([
            'orden'       => $orden,
            'empresa'     => (new FacturacionModel())->getSucursal((int)$orden['veterinaria_id']),
        ]);
        require ROOT . '/app/views/ordenes_cirugia/consentimiento.php';
    }

    private function redirect(string $ruta): void {
        header('Location: ' . BASE_URL . '/' . $ruta);
        exit;
    }
}
