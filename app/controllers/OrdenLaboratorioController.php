<?php

require_once ROOT . '/app/models/LaboratorioModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';

/**
 * Órdenes de laboratorio (módulo del doctor): solo informativo.
 * El doctor solo elige los exámenes del catálogo; no escribe ni ve valores, costos, abonos ni dinero. Caja fija el valor al registrar la orden.
 * La orden es solo informativa: no es un cobro ni entra en reportes. Caja la ve en Laboratorios como «orden por registrar»
 * y es quien la registra (con sus abonos) o la descarta.
 */
class OrdenLaboratorioController {

    private LaboratorioModel $model;
    private VeterinariaModel $vetModel;

    public function __construct() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        $this->model    = new LaboratorioModel();
        $this->vetModel = new VeterinariaModel();
    }

    public function index(): void {
        $veterinarias   = $this->vetModel->getAll();
        $veterinaria_id = $this->veterinariaActual($veterinarias);
        $esAdmin        = ($_SESSION['usuario_rol'] ?? '') === 'admin';
        $usuario_id     = (int)$_SESSION['usuario_id'];

        // Cada doctor ve sus órdenes (el administrador ve todas); sin ningún importe: solo el estado de la orden
        $ordenes = array_map(fn($o) => [
            'id'          => (int)$o['id'],
            'fecha'       => $o['fecha'],
            'nombre'      => $o['nombre'],
            'especie'     => $o['especie'],
            'descripcion' => $o['descripcion'],
            'solicito'    => $o['doctor_nombre'] ?? '',
            'estado'      => $o['estado'],
        ], $this->model->getOrdenes($veterinaria_id, $esAdmin ? null : $usuario_id));

        // Catálogo sin costos: el doctor solo elige exámenes
        $examenes = array_map(fn($x) => [
            'id' => (int)$x['id'], 'codigo' => $x['codigo'], 'nombre' => $x['nombre'], 'combo' => (int)$x['es_combo'], 'incluye_ids' => $x['incluye_ids'],
            'incluye' => $x['incluye_texto'], 'muestra' => (string)($x['tipo_muestra'] ?? ''),
        ], $this->model->getExamenes($veterinaria_id));

        $datos = [
            'activePage'     => 'ordenes-laboratorio',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'hoy'            => date('Y-m-d'),
            'esAdmin'        => $esAdmin,
            'ordenes'        => $ordenes,
            'examenes'       => $examenes,
            'mascotas'       => $this->model->getMascotasSucursal($veterinaria_id),
            'especies'       => TratamientoModel::ESPECIES,
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
        require ROOT . '/app/views/ordenes_laboratorio/index.php';
    }

    /** POST: el doctor manda la orden (exámenes + valor a cobrar) */
    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirect('ordenes-laboratorio');

        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $nombre = mb_substr(trim($_POST['nombre'] ?? ''), 0, 100);
        $especie = $_POST['especie'] ?? '';
        $ids    = array_map('intval', (array)($_POST['examenes'] ?? []));
        $examenes = $this->model->getExamenesPorIds($veterinaria_id, $ids);

        if ($nombre === '') {
            $_SESSION['flash_error'] = 'Indica el nombre de la mascota.';
        } elseif (!isset(TratamientoModel::ESPECIES[$especie])) {
            $_SESSION['flash_error'] = 'Selecciona la especie (perro o gato).';
        } elseif (!$examenes) {
            $_SESSION['flash_error'] = 'Elige al menos un examen.';
        } elseif ($errorSeleccion = $this->model->validarSeleccion($examenes)) {
            $_SESSION['flash_error'] = $errorSeleccion;
        } else {
            // El costo se calcula aquí solo para caja (valor mínimo a cobrar); al doctor no se le muestra nada. La orden no lleva valor.
            $costo = round(array_sum(array_map(fn($e) => (float)$e['costo'], $examenes)), 2);
            [$cliente_id, $mascota_id] = $this->model->validarVinculos($veterinaria_id, (int)($_POST['cliente_id'] ?? 0), (int)($_POST['mascota_id'] ?? 0));
            $descripcion = mb_substr(implode('+', array_column($examenes, 'codigo')), 0, 255);
            $nota = mb_substr(trim($_POST['nota'] ?? ''), 0, 255 - mb_strlen($descripcion) - 3);
            $id = $this->model->crearOrden([
                'veterinaria_id' => $veterinaria_id,
                'fecha'          => date('Y-m-d'),
                'nombre'         => $nombre,
                'especie'        => $especie,
                'examenes'       => array_column($examenes, 'id'),
                'descripcion'    => $nota !== '' ? $descripcion . ' · ' . $nota : $descripcion,
                'valor'          => 0,
                'costo'          => $costo,
                'usuario_id'     => (int)$_SESSION['usuario_id'],
                'cliente_id'     => $cliente_id,
                'mascota_id'     => $mascota_id,
            ]);
            $_SESSION[$id > 0 ? 'flash_success' : 'flash_error'] = $id > 0
                ? 'Orden de ' . $nombre . ' enviada a caja (' . $descripcion . ').'
                : 'No se pudo enviar la orden.';
        }
        $this->redirect('ordenes-laboratorio');
    }

    /** POST: anula una orden que caja todavía no ha registrado */
    public function anular(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirect('ordenes-laboratorio');

        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $id   = (int)($_POST['id'] ?? 0);
        $o    = $this->model->getOrden($id, $veterinaria_id);
        $esAdmin = ($_SESSION['usuario_rol'] ?? '') === 'admin';

        if (!$o || (!$esAdmin && (int)$o['usuario_id'] !== (int)$_SESSION['usuario_id'])) {
            $_SESSION['flash_error'] = 'No se encontró la orden.';
        } elseif ($o['estado'] !== 'pendiente') {
            $_SESSION['flash_error'] = 'Caja ya atendió esta orden; no se puede anular desde aquí.';
        } elseif ($this->model->cambiarEstadoOrden($id, $veterinaria_id, 'anulada')) {
            $_SESSION['flash_success'] = 'Orden anulada.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo anular la orden.';
        }
        $this->redirect('ordenes-laboratorio');
    }

    private function veterinariaActual(array $veterinarias): int {
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }
        return $veterinaria_id;
    }

    private function redirect(string $ruta): void {
        header('Location: ' . BASE_URL . '/' . $ruta);
        exit;
    }
}
