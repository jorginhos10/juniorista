<?php

require_once ROOT . '/app/models/CitaModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';

class CitaController {

    private CitaModel        $model;
    private VeterinariaModel $vetModel;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->model    = new CitaModel();
        $this->vetModel = new VeterinariaModel();
    }

    public function index(): void {
        $veterinarias   = $this->vetModel->getAll();
        $veterinaria_id = $this->veterinariaActual($veterinarias);

        $hoy   = date('Y-m-d');
        $fecha = $this->fechaValida($_GET['fecha'] ?? '') ?? $hoy;

        // Semana lunes–domingo que contiene la fecha seleccionada
        $ts        = strtotime($fecha);
        $iniSemana = date('Y-m-d', strtotime('monday this week', $ts));
        $finSemana = date('Y-m-d', strtotime('sunday this week', $ts));

        $iniHoy = date('Y-m-d', strtotime('monday this week'));
        $finHoy = date('Y-m-d', strtotime('sunday this week'));

        $datos = [
            'activePage'     => 'citas',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'hoy'            => $hoy,
            'fecha'          => $fecha,
            'iniSemana'      => $iniSemana,
            'finSemana'      => $finSemana,
            'citas'          => $this->model->getRango($veterinaria_id, $iniSemana, $finSemana),
            'totales'        => $this->model->getTotales($veterinaria_id, $hoy, $iniHoy, $finHoy),
            'clientes'       => $this->model->getClientes(),
            'veterinarios'   => $this->model->getVeterinarios(),
            'tipos'          => CitaModel::TIPOS,
            'estados'        => CitaModel::ESTADOS,
            'abrirNueva'     => isset($_GET['nueva']),
            'old'            => $_SESSION['cita_old'] ?? null,
            'usuario'        => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['cita_old']);

        $this->render('citas/index', $datos);
    }

    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('citas');
        }

        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $id    = (int)($_POST['id'] ?? 0);
        $datos = $this->validar($_POST, $id);
        $datos['veterinaria_id'] = $veterinaria_id;
        $volver = 'citas?fecha=' . ($this->fechaValida($_POST['fecha'] ?? '') ?? date('Y-m-d'));

        if ($datos['error'] === '') {
            $cruce = $this->model->buscarCruce($datos, $id);
            if ($cruce) {
                $quien = (int)$cruce['mascota_id'] === $datos['mascota_id']
                    ? $cruce['mascota_nombre'] . ' ya tiene una cita'
                    : ($cruce['veterinario_nombre'] ?? 'El veterinario') . ' ya atiende a ' . $cruce['mascota_nombre'];
                $datos['error'] = sprintf('%s de %s a %s. Elige otro horario.',
                    $quien,
                    substr($cruce['hora'], 0, 5),
                    date('H:i', strtotime($cruce['hora']) + $cruce['duracion'] * 60)
                );
            }
        }

        if ($datos['error'] !== '') {
            $_SESSION['flash_error'] = $datos['error'];
            $_SESSION['cita_old']    = $_POST;
            $this->redirect($volver);
        }

        if ($id > 0) {
            $ok = $this->model->actualizar($id, $datos);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Cita actualizada correctamente.'
                : 'Error al actualizar la cita.';
        } else {
            $datos['usuario_id'] = (int)$_SESSION['usuario_id'];
            $ok = $this->model->crear($datos) > 0;
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Cita agendada para el ' . date('d/m/Y', strtotime($datos['fecha'])) . ' a las ' . substr($datos['hora'], 0, 5) . '.'
                : 'Error al agendar la cita.';
        }

        $this->redirect($volver);
    }

    public function estado(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('citas');
        }

        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $id     = (int)($_POST['id'] ?? 0);
        $estado = $_POST['estado'] ?? '';
        $volver = 'citas?fecha=' . ($this->fechaValida($_POST['fecha'] ?? '') ?? date('Y-m-d'));

        if ($id > 0 && isset(CitaModel::ESTADOS[$estado])
            && $this->model->cambiarEstado($id, $veterinaria_id, $estado)) {
            $_SESSION['flash_success'] = 'Cita marcada como «' . CitaModel::ESTADOS[$estado] . '».';
        } else {
            $_SESSION['flash_error'] = 'No se pudo cambiar el estado de la cita.';
        }

        $this->redirect($volver);
    }

    public function eliminar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('citas');
        }

        $volver = 'citas?fecha=' . ($this->fechaValida($_POST['fecha'] ?? '') ?? date('Y-m-d'));

        if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
            $_SESSION['flash_error'] = 'Solo un administrador puede eliminar citas. Puedes cancelarla.';
            $this->redirect($volver);
        }

        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $ok = $this->model->eliminar((int)($_POST['id'] ?? 0), $veterinaria_id);
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Cita eliminada.' : 'No se pudo eliminar la cita.';
        $this->redirect($volver);
    }

    /** GET JSON: mascotas de un cliente */
    public function mascotas(): void {
        $this->json($this->model->getMascotasCliente((int)($_GET['cliente_id'] ?? 0)));
    }

    /** POST JSON: alta rápida de mascota desde el formulario de cita */
    public function guardarMascota(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido.'], 405);
        }

        $d = [
            'cliente_id' => (int)($_POST['cliente_id'] ?? 0),
            'nombre'     => trim($_POST['nombre']  ?? ''),
            'especie'    => trim($_POST['especie'] ?? '') ?: 'Perro',
            'raza'       => trim($_POST['raza']    ?? ''),
            'sexo'       => in_array($_POST['sexo'] ?? '', ['macho', 'hembra'], true) ? $_POST['sexo'] : '',
        ];

        if ($d['cliente_id'] <= 0 || $d['nombre'] === '') {
            $this->json(['error' => 'Selecciona el propietario e indica el nombre de la mascota.'], 422);
        }

        $id = $this->model->crearMascota($d);
        if ($id <= 0) {
            $this->json(['error' => 'No se pudo registrar la mascota.'], 500);
        }

        $this->json(['id' => $id, 'nombre' => $d['nombre'], 'especie' => $d['especie'], 'raza' => $d['raza']]);
    }

    private function validar(array $post, int $id): array {
        $hora = $post['hora'] ?? '';
        $datos = [
            'cliente_id'     => (int)($post['cliente_id']     ?? 0),
            'mascota_id'     => (int)($post['mascota_id']     ?? 0),
            'veterinario_id' => (int)($post['veterinario_id'] ?? 0),
            'fecha'          => $this->fechaValida($post['fecha'] ?? '') ?? '',
            'hora'           => preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora) ? $hora . ':00' : '',
            'duracion'       => (int)($post['duracion'] ?? 30),
            'tipo'           => $post['tipo']   ?? 'consulta',
            'estado'         => $post['estado'] ?? 'pendiente',
            'motivo'         => mb_substr(trim($post['motivo'] ?? ''), 0, 200),
            'notas'          => trim($post['notas'] ?? ''),
            'error'          => '',
        ];

        if ($datos['cliente_id'] <= 0) {
            $datos['error'] = 'Selecciona el propietario.';
        } elseif ($datos['mascota_id'] <= 0 || !$this->model->mascotaPerteneceA($datos['mascota_id'], $datos['cliente_id'])) {
            $datos['error'] = 'Selecciona una mascota del propietario.';
        } elseif ($datos['fecha'] === '' || $datos['hora'] === '') {
            $datos['error'] = 'Indica una fecha y hora válidas.';
        } elseif ($id === 0 && $datos['fecha'] < date('Y-m-d')) {
            $datos['error'] = 'No se pueden agendar citas en fechas pasadas.';
        } elseif ($datos['duracion'] < 10 || $datos['duracion'] > 480) {
            $datos['error'] = 'La duración debe estar entre 10 y 480 minutos.';
        } elseif (!isset(CitaModel::TIPOS[$datos['tipo']]) || !isset(CitaModel::ESTADOS[$datos['estado']])) {
            $datos['error'] = 'Tipo o estado de cita no válido.';
        } elseif ($datos['veterinario_id'] > 0
            && !in_array($datos['veterinario_id'], array_map('intval', array_column($this->model->getVeterinarios(), 'id')), true)) {
            $datos['error'] = 'El veterinario seleccionado no es válido.';
        }

        return $datos;
    }

    private function fechaValida(string $f): ?string {
        $d = DateTime::createFromFormat('!Y-m-d', $f);
        return ($d && $d->format('Y-m-d') === $f) ? $f : null;
    }

    private function veterinariaActual(array $veterinarias): int {
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }
        return $veterinaria_id;
    }

    private function json($data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
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
