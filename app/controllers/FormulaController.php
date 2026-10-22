<?php

require_once ROOT . '/app/models/FormulaModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';

class FormulaController {

    private FormulaModel     $model;
    private VeterinariaModel $vetModel;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->model    = new FormulaModel();
        $this->vetModel = new VeterinariaModel();
    }

    /** Listado de fórmulas guardadas (por período y, si se indica, de una mascota) */
    public function index(): void {
        $veterinarias = $this->vetModel->getAll();
        $desde   = $this->fechaValida($_GET['desde'] ?? '') ?? date('Y-m-01');
        $hasta   = $this->fechaValida($_GET['hasta'] ?? '') ?? date('Y-m-d');
        if ($hasta < $desde) $hasta = $desde;
        $mascota = (int)($_GET['mascota'] ?? 0);

        $datos = [
            'activePage'     => 'formulas',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => (int)($_SESSION['veterinaria_id'] ?? 0),
            'desde'          => $desde,
            'hasta'          => $hasta,
            'mascotaFiltro'  => $mascota,
            'mascotas'       => $this->model->getMascotas(),
            'formulas'       => $this->model->getRango($mascota ? '2000-01-01' : $desde, $mascota ? date('Y-m-d') : $hasta, $mascota),
            'usuario'        => $this->usuario(),
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $this->render('formulas/index', $datos);
    }

    /** Formulario de una fórmula nueva (?mascota=ID la deja elegida) o de una existente (?id=ID) */
    public function formulario(): void {
        $id      = (int)($_GET['id'] ?? 0);
        $formula = $id > 0 ? $this->model->getFormula($id) : null;
        if ($id > 0 && !$formula) {
            $_SESSION['flash_error'] = 'La fórmula no existe.';
            $this->redirect('formulas');
        }

        $datos = [
            'activePage'     => 'formulas',
            'veterinarias'   => $this->vetModel->getAll(),
            'veterinaria_id' => (int)($_SESSION['veterinaria_id'] ?? 0),
            'formula'        => $formula,
            'old'            => $_SESSION['formula_old'] ?? null,
            'mascotaInicial' => $formula ? (int)$formula['mascota_id'] : (int)($_GET['mascota'] ?? 0),
            'mascotas'       => $this->model->getMascotas(),
            'productos'      => $this->model->getProductos(),
            'veterinarios'   => $this->model->getVeterinarios(),
            'vias'           => FormulaModel::VIAS,
            'usuario'        => $this->usuario(),
            'error'          => $_SESSION['flash_error'] ?? '',
        ];
        unset($_SESSION['flash_error'], $_SESSION['formula_old']);
        $this->render('formulas/form', $datos);
    }

    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('formulas');
        }

        $id  = (int)($_POST['id'] ?? 0);
        $txt = fn(string $k, int $max) => ($v = trim((string)($_POST[$k] ?? ''))) === '' ? null : mb_substr($v, 0, $max);
        $vetIds = array_map('intval', array_column($this->model->getVeterinarios(), 'id'));
        $peso   = trim(str_replace(',', '.', (string)($_POST['peso'] ?? '')));

        $d = [
            'veterinaria_id'      => (int)($_SESSION['veterinaria_id'] ?? 0),
            'mascota_id'          => (int)($_POST['mascota_id'] ?? 0),
            'veterinario_id'      => in_array((int)($_POST['veterinario_id'] ?? 0), $vetIds, true) ? (int)$_POST['veterinario_id'] : null,
            'fecha'               => $this->fechaValida($_POST['fecha'] ?? '') ?? '',
            'peso'                => is_numeric($peso) && (float)$peso > 0 && (float)$peso <= 200 ? round((float)$peso, 2) : null,
            'diagnostico'         => $txt('diagnostico', 2000),
            'indicaciones'        => $txt('indicaciones', 3000),
            'proximo_control'     => $this->fechaValida($_POST['proximo_control'] ?? ''),
            'tarjeta_profesional' => null,
            'usuario_id'          => (int)$_SESSION['usuario_id'],
        ];

        // Medicamentos: del almacén (producto_id) o escritos a mano; se ignoran las filas sin nombre
        $items = [];
        $campos = ['medicamento' => 200, 'presentacion' => 120, 'cantidad' => 60, 'dosis' => 120, 'frecuencia' => 80, 'duracion' => 80, 'indicaciones_item' => 255];
        foreach ((array)($_POST['medicamento'] ?? []) as $i => $nombre) {
            $it = [];
            foreach ($campos as $c => $max) {
                $v = trim((string)($_POST[$c][$i] ?? ''));
                $it[$c] = $v === '' ? null : mb_substr($v, 0, $max);
            }
            if ($it['medicamento'] === null) continue;
            $via = (string)($_POST['via'][$i] ?? '');
            $items[] = [
                'producto_id'  => (int)($_POST['producto_id'][$i] ?? 0) ?: null,
                'medicamento'  => $it['medicamento'],
                'presentacion' => $it['presentacion'],
                'cantidad'     => $it['cantidad'],
                'dosis'        => $it['dosis'],
                'via'          => in_array($via, FormulaModel::VIAS, true) ? $via : null,
                'frecuencia'   => $it['frecuencia'],
                'duracion'     => $it['duracion'],
                'indicaciones' => $it['indicaciones_item'],
            ];
        }

        $error = '';
        if (!$this->model->mascotaEsDeLaSucursal($d['mascota_id'])) {
            $error = 'Selecciona el paciente (mascota).';
        } elseif ($d['fecha'] === '' || $d['fecha'] > date('Y-m-d')) {
            $error = 'La fecha de la fórmula no es válida (hoy o anterior).';
        } elseif ($d['veterinario_id'] === null) {
            $error = 'Selecciona el médico veterinario que firma la fórmula.';
        } elseif (!$items) {
            $error = 'Agrega al menos un medicamento.';
        } elseif (trim((string)($_POST['proximo_control'] ?? '')) !== '' && ($d['proximo_control'] === null || $d['proximo_control'] < $d['fecha'])) {
            $error = 'La fecha del próximo control no es válida.';
        }

        if ($error !== '') {
            $_SESSION['flash_error'] = $error;
            $_SESSION['formula_old'] = $_POST;
            $this->redirect('formulas/nueva' . ($id > 0 ? '?id=' . $id : ''));
        }

        $nuevoId = $this->model->guardar($id, $d, $items);
        if (!$nuevoId) {
            $_SESSION['flash_error'] = 'No se pudo guardar la fórmula.';
            $_SESSION['formula_old'] = $_POST;
            $this->redirect('formulas/nueva' . ($id > 0 ? '?id=' . $id : ''));
        }

        $_SESSION['flash_success'] = $id > 0 ? 'Fórmula actualizada.' : 'Fórmula médica guardada.';
        $_SESSION['formula_imprimir'] = $nuevoId;   // el listado la abre para imprimir
        $this->redirect('formulas?mascota=' . $d['mascota_id']);
    }

    /** Formato imprimible de la fórmula */
    public function imprimir(): void {
        $formula = $this->model->getFormula((int)($_GET['id'] ?? 0));
        if (!$formula) {
            http_response_code(404);
            echo 'Fórmula no encontrada.';
            exit;
        }
        require_once ROOT . '/app/models/FacturacionModel.php';
        $empresa = (new FacturacionModel())->getSucursal((int)$formula['veterinaria_id']);
        $this->render('formulas/imprimir', [
            'f'       => $formula,
            'empresa' => $empresa,
            'numero'  => VeterinariaModel::serialServicio($empresa['nombre'], 'FOR', (int)$formula['id'], $formula['created_at']),
        ]);
    }

    public function anular(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('formulas');
        }
        $ok = $this->model->anular((int)($_POST['id'] ?? 0));
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Fórmula anulada.' : 'No se pudo anular la fórmula.';
        $this->redirect('formulas' . ((int)($_POST['mascota'] ?? 0) ? '?mascota=' . (int)$_POST['mascota'] : ''));
    }

    private function usuario(): array {
        return ['nombre' => $_SESSION['usuario_nombre'], 'email' => $_SESSION['usuario_email'], 'rol' => $_SESSION['usuario_rol']];
    }

    private function fechaValida(string $f): ?string {
        $d = DateTime::createFromFormat('!Y-m-d', $f);
        return ($d && $d->format('Y-m-d') === $f) ? $f : null;
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
