<?php

require_once ROOT . '/app/models/HospitalizacionModel.php';
require_once ROOT . '/app/models/HistorialModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';
require_once ROOT . '/app/models/FacturacionModel.php';

/** Hospitalización: tablero de pacientes internados y hoja ECOP de cada uno */
class HospitalizacionController {

    private HospitalizacionModel $model;
    private HistorialModel $historial;
    private VeterinariaModel $vetModel;

    public function __construct() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        $this->model     = new HospitalizacionModel();
        $this->historial = new HistorialModel();
        $this->vetModel  = new VeterinariaModel();
    }

    private function datosBase(string $pagina): array {
        $veterinarias = $this->vetModel->getAll();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }
        $datos = [
            'activePage'     => 'hospitalizacion',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'estados'        => HospitalizacionModel::ESTADOS,
            'usuario'        => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        return $datos;
    }

    public function index(): void {
        $datos = $this->datosBase('hospitalizacion') + [
            'internados'     => $this->model->getInternados(),
            'egresos'        => $this->model->getEgresos(),
            'mascotas'       => $this->historial->getMascotas(),
            'veterinarios'   => $this->historial->getVeterinarios(),
            'mascotaInicial' => (int)($_GET['mascota'] ?? 0),   // viene de la historia médica
            'ahora'          => date('Y-m-d\TH:i'),
        ];
        extract($datos);
        require ROOT . '/app/views/hospitalizacion/index.php';
    }

    /** Hoja ECOP del paciente */
    public function ver(): void {
        $h = $this->model->find((int)($_GET['id'] ?? 0));
        if (!$h) $this->redirect('hospitalizacion');

        $datos = $this->datosBase('hospitalizacion') + [
            'h'             => $h,
            'aplicaciones'  => $this->model->getAplicaciones((int)$h['id']),
            'constancias'   => $this->model->getConstancias((int)$h['id']),
            'rangos'        => HistorialModel::RANGOS[$h['especie']] ?? null,
            'medicamentos'  => $this->model->sugerenciasMedicamentos(),
            'vias'          => HospitalizacionModel::VIAS,
            'frecuencias'   => HospitalizacionModel::FRECUENCIAS,
            'veterinarios'  => $this->historial->getVeterinarios(),
            'editable'      => $h['estado'] === 'internado',
        ];
        extract($datos);
        require ROOT . '/app/views/hospitalizacion/ver.php';
    }

    public function imprimir(): void {
        $h = $this->model->find((int)($_GET['id'] ?? 0));
        if (!$h) $this->redirect('hospitalizacion');
        $empresa      = (new FacturacionModel())->getSucursal((int)$h['veterinaria_id']);
        $aplicaciones = $this->model->getAplicaciones((int)$h['id']);
        $constancias  = $this->model->getConstancias((int)$h['id']);
        require ROOT . '/app/views/hospitalizacion/imprimir.php';
    }

    /** POST: ingresa un paciente (sin id) o corrige los datos de la cabecera (con id) */
    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirect('hospitalizacion');

        $mascota_id = (int)($_POST['mascota_id'] ?? 0);
        $mascota = null;
        foreach ($this->historial->getMascotas() as $m) {   // solo mascotas de la sucursal
            if ((int)$m['id'] === $mascota_id) { $mascota = $m; break; }
        }
        $id = (int)($_POST['id'] ?? 0);

        $ingreso = DateTime::createFromFormat('Y-m-d\TH:i', $_POST['fecha_ingreso'] ?? '');
        $peso = trim($_POST['peso'] ?? '');
        $d = [
            'mascota_id'     => $mascota_id,
            'diagnostico'    => mb_substr(trim($_POST['diagnostico'] ?? ''), 0, 255),
            'fecha_ingreso'  => ($ingreso ?: new DateTime())->format('Y-m-d H:i:s'),
            'peso'           => $peso !== '' ? max(0, (float)$peso) : null,
            'veterinario_id' => (int)($_POST['veterinario_id'] ?? 0),
            'notas'          => mb_substr(trim($_POST['notas'] ?? ''), 0, 255),
            'usuario_id'     => (int)$_SESSION['usuario_id'],
        ];

        if ($id > 0) {
            $actual = $this->model->find($id);
            if (!$actual) {
                $_SESSION['flash_error'] = 'No se encontró la hospitalización.';
            } elseif ($this->model->actualizar($id, $d)) {
                $_SESSION['flash_success'] = 'Datos de la hoja actualizados.';
            } else {
                $_SESSION['flash_error'] = 'No se pudo actualizar.';
            }
            $this->redirect('hospitalizacion/ver?id=' . $id);
        }

        if (!$mascota) {
            $_SESSION['flash_error'] = 'Elige una mascota registrada de la lista.';
        } elseif ($d['diagnostico'] === '') {
            $_SESSION['flash_error'] = 'Indica el diagnóstico o motivo de la hospitalización.';
        } elseif ($abierta = $this->model->internadoDeMascota($mascota_id)) {
            $_SESSION['flash_error'] = $mascota['nombre'] . ' ya está internado.';
            $this->redirect('hospitalizacion/ver?id=' . (int)$abierta['id']);
        } elseif ($nuevo = $this->model->crear($d)) {
            $this->redirect('hospitalizacion/ver?id=' . $nuevo);   // directo a la hoja ECOP
        } else {
            $_SESSION['flash_error'] = 'No se pudo registrar la hospitalización.';
        }
        $this->redirect('hospitalizacion');
    }

    /** POST: alta, referido, fallecido o reabrir la hoja */
    public function estado(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirect('hospitalizacion');
        $id = (int)($_POST['id'] ?? 0);
        $h  = $this->model->find($id);
        $salida = DateTime::createFromFormat('Y-m-d\TH:i', $_POST['fecha_salida'] ?? '');
        if ($h && $this->model->cambiarEstado($id, $_POST['estado'] ?? '', $salida ? $salida->format('Y-m-d H:i:s') : null)) {
            $_SESSION['flash_success'] = ($_POST['estado'] ?? '') === 'internado' ? 'Hoja reabierta.' : 'Hospitalización cerrada.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo cambiar el estado.';
        }
        $this->redirect('hospitalizacion/ver?id=' . $id);
    }

    /** POST (AJAX): agrega, edita o elimina una fila de la hoja y devuelve la hoja completa */
    public function aplicacion(): void {
        header('Content-Type: application/json; charset=utf-8');
        $h = $this->model->find((int)($_POST['hospitalizacion_id'] ?? 0));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$h) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'No se encontró la hoja.']);
            exit;
        }
        if ($h['estado'] !== 'internado') {
            echo json_encode(['ok' => false, 'error' => 'La hoja está cerrada. Reábrela para registrar más aplicaciones.']);
            exit;
        }

        $hid = (int)$h['id'];
        $id  = (int)($_POST['id'] ?? 0);
        $accion = $_POST['accion'] ?? 'guardar';

        if ($accion === 'eliminar') {
            $ok = $id > 0 && $this->model->eliminarAplicacion($hid, $id);
        } else {
            $fecha = DateTime::createFromFormat('Y-m-d', $_POST['fecha'] ?? '');
            $hora  = DateTime::createFromFormat('H:i', $_POST['hora'] ?? '');
            $d = [
                'fecha'         => $fecha ? $fecha->format('Y-m-d') : date('Y-m-d'),
                'hora'          => $hora ? $hora->format('H:i:s') : date('H:i:s'),
                'medicamento'   => mb_substr(trim($_POST['medicamento'] ?? ''), 0, 150),
                'dosis'         => mb_substr(trim($_POST['dosis'] ?? ''), 0, 60),
                'via'           => mb_substr(trim($_POST['via'] ?? ''), 0, 30),
                'frecuencia'    => mb_substr(trim($_POST['frecuencia'] ?? ''), 0, 60),
                'observaciones' => mb_substr(trim($_POST['observaciones'] ?? ''), 0, 255),
                'firma'         => mb_substr((string)$_SESSION['usuario_nombre'], 0, 100),   // firma = quien registra
                'usuario_id'    => (int)$_SESSION['usuario_id'],
            ];
            if ($d['medicamento'] === '') {
                echo json_encode(['ok' => false, 'error' => 'Indica el medicamento.']);
                exit;
            }
            $ok = $this->model->guardarAplicacion($hid, $id, $d);
        }

        echo json_encode(['ok' => (bool)$ok, 'error' => $ok ? '' : 'No se pudo guardar.', 'filas' => $this->model->getAplicaciones($hid)]);
        exit;
    }

    /** POST (AJAX): agrega, edita o elimina una fila de constancias fisiológicas y devuelve la tabla completa */
    public function constancia(): void {
        header('Content-Type: application/json; charset=utf-8');
        $h = $this->model->find((int)($_POST['hospitalizacion_id'] ?? 0));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$h) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'No se encontró la hoja.']);
            exit;
        }
        if ($h['estado'] !== 'internado') {
            echo json_encode(['ok' => false, 'error' => 'La hoja está cerrada. Reábrela para registrar más constancias.']);
            exit;
        }

        $hid = (int)$h['id'];
        $id  = (int)($_POST['id'] ?? 0);

        if (($_POST['accion'] ?? '') === 'eliminar') {
            $ok = $id > 0 && $this->model->eliminarConstancia($hid, $id);
        } else {
            $fecha = DateTime::createFromFormat('Y-m-d', $_POST['fecha'] ?? '');
            $hora  = DateTime::createFromFormat('H:i', $_POST['hora'] ?? '');
            $d = [
                'fecha' => $fecha ? $fecha->format('Y-m-d') : date('Y-m-d'),
                'hora'  => $hora ? $hora->format('H:i:s') : date('H:i:s'),
                'firma' => mb_substr((string)$_SESSION['usuario_nombre'], 0, 100),
                'usuario_id' => (int)$_SESSION['usuario_id'],
            ];
            $largos = ['temp' => 12, 'fc' => 12, 'fr' => 12, 'psd' => 16, 'so' => 12, 'glu' => 12, 'vom' => 30, 'dep' => 30, 'orina' => 30];
            $alguno = false;
            foreach ($largos as $campo => $max) {
                $d[$campo] = mb_substr(trim($_POST[$campo] ?? ''), 0, $max);
                if ($d[$campo] !== '') $alguno = true;
            }
            if (!$alguno) {
                echo json_encode(['ok' => false, 'error' => 'Anota al menos una constancia.']);
                exit;
            }
            $ok = $this->model->guardarConstancia($hid, $id, $d);
        }

        echo json_encode(['ok' => (bool)$ok, 'error' => $ok ? '' : 'No se pudo guardar.', 'filas' => $this->model->getConstancias($hid)]);
        exit;
    }

    private function redirect(string $ruta): void {
        header('Location: ' . BASE_URL . '/' . $ruta);
        exit;
    }
}
