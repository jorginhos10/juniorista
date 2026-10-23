<?php

require_once ROOT . '/app/models/HistorialModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';
require_once ROOT . '/app/helpers/AdjuntosHistorial.php';

class HistorialController {

    private HistorialModel   $model;
    private VeterinariaModel $vetModel;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->model    = new HistorialModel();
        $this->vetModel = new VeterinariaModel();
    }

    public function index(): void {
        $veterinarias   = $this->vetModel->getAll();
        $veterinaria_id = $this->veterinariaActual($veterinarias);

        $mascotas   = $this->model->getMascotas();
        $mascota_id = (int)($_GET['mascota'] ?? 0);
        $mascota    = null;
        foreach ($mascotas as $m) {
            if ((int)$m['id'] === $mascota_id) { $mascota = $m; break; }
        }

        $datos = [
            'activePage'     => 'historial',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'hoy'            => date('Y-m-d'),
            'mascotas'       => $mascotas,
            'mascota'        => $mascota,
            'registros'      => $mascota ? $this->model->getRegistros($mascota_id) : [],
            'adjuntosGenerales' => $mascota ? $this->model->getAdjuntosGenerales($mascota_id) : [],
            'proximos'       => $this->model->getProximos(date('Y-m-d')),
            'veterinarios'   => $this->model->getVeterinarios(),
            'tipos'          => HistorialModel::TIPOS,
            'mucosas'        => HistorialModel::MUCOSAS,
            'tllcOpciones'   => HistorialModel::TLLC,
            'hidratacion'    => HistorialModel::HIDRATACION,
            'vacunas'        => HistorialModel::VACUNAS,
            'actitudes'      => HistorialModel::ACTITUD,
            'condiciones'    => HistorialModel::CONDICION,
            'alimentaciones' => HistorialModel::ALIMENTACION,
            'habitats'       => HistorialModel::HABITAT,
            'zonasMucosas'   => HistorialModel::MUCOSAS_ZONAS,
            'sistemas'       => HistorialModel::SISTEMAS,
            'rangos'         => HistorialModel::RANGOS,
            'old'            => $_SESSION['historial_old'] ?? null,
            'usuario'        => [
                'id'     => (int)$_SESSION['usuario_id'],
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['historial_old']);
        $this->render('historial/index', $datos);
    }

    public function imprimir(): void {
        $mascota_id = (int)($_GET['mascota'] ?? 0);
        $mascota    = null;
        foreach ($this->model->getMascotas() as $m) {
            if ((int)$m['id'] === $mascota_id) { $mascota = $m; break; }
        }
        if (!$mascota) {
            $this->redirect('historial');
        }

        $this->render('historial/imprimir', [
            'mascota'   => $mascota,
            'registros' => $this->model->getRegistros($mascota_id),
            'generales' => $this->model->getAdjuntosGenerales($mascota_id),
            'tipos'     => HistorialModel::TIPOS,
            'usuario'   => ['nombre' => $_SESSION['usuario_nombre']],
        ]);
    }

    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('historial');
        }

        $mascota_id = (int)($_POST['mascota_id'] ?? 0);
        $volver     = 'historial?mascota=' . $mascota_id;

        if (!$this->model->mascotaExiste($mascota_id)) {
            $_SESSION['flash_error'] = 'La mascota no existe.';
            $this->redirect('historial');
        }

        [$datos, $error] = $this->validar($_POST);
        if ($error !== '') {
            $_SESSION['flash_error']   = $error;
            $_SESSION['historial_old'] = $_POST;
            $this->redirect($volver);
        }

        $datos['mascota_id'] = $mascota_id;
        $id = (int)($_POST['id'] ?? 0);

        $registro_id = $id;
        if ($id > 0) {
            $ok = $this->model->actualizar($id, $datos);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Registro actualizado.' : 'Error al actualizar el registro.';
        } else {
            $datos['veterinaria_id'] = $this->veterinariaActual($this->vetModel->getAll());
            $datos['usuario_id']     = (int)$_SESSION['usuario_id'];
            $ok = $this->model->crear($datos);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? HistorialModel::TIPOS[$datos['tipo']]['label'] . ' agregada al historial.'
                : 'Error al guardar el registro.';
            $registro_id = $ok ? $this->model->ultimoId : 0;
        }

        // PDF adjuntos: el registro ya quedó guardado; si algún archivo falla se avisa sin perder el resto
        if ($ok && $registro_id > 0 && !empty($_FILES['adjuntos'])) {
            $this->guardarAdjuntos($registro_id, $mascota_id, mb_substr(trim((string)($_POST['adjuntos_descripcion'] ?? '')), 0, 255));
        }

        if (!$ok) {
            $_SESSION['historial_old'] = $_POST;
        }
        $this->redirect($volver);
    }

    /** Guarda los PDF subidos con el formulario y deja el aviso en la sesión */
    private function guardarAdjuntos(int $registro_id, int $mascota_id, string $descripcion = ''): void {
        $sucursal = $this->vetModel->findById($this->veterinariaActual($this->vetModel->getAll()))['nombre'] ?? '';
        [$guardados, $errores] = AdjuntosHistorial::guardarSubidos($_FILES['adjuntos'], $sucursal, $mascota_id);
        foreach ($guardados as $g) {
            if (!$this->model->crearAdjunto($registro_id, $mascota_id, $g['nombre'], $g['archivo'], $g['tamano'], (int)$_SESSION['usuario_id'], $descripcion)) {
                $errores[] = "«{$g['nombre']}» no se pudo registrar.";
            }
        }
        if ($guardados) {
            $_SESSION['flash_success'] = ($_SESSION['flash_success'] ?? '') . ($_SESSION['flash_success'] ?? '' ? ' ' : '')
                . count($guardados) . ' PDF adjunto' . (count($guardados) === 1 ? '' : 's') . '.';
        }
        if ($errores) {
            $_SESSION['flash_error'] = 'El registro se guardó, pero: ' . implode(' ', $errores);
        }
    }

    /** POST: adjunta PDF a la historia de la mascota con su descripción (a un registro o en general) */
    public function adjuntar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('historial');
        }
        $mascota_id   = (int)($_POST['mascota_id'] ?? 0);
        $registro_id  = (int)($_POST['historial_id'] ?? 0);
        $descripcion  = mb_substr(trim((string)($_POST['descripcion'] ?? '')), 0, 255);
        $volver       = 'historial?mascota=' . $mascota_id;

        if (!$this->model->mascotaExiste($mascota_id)) {
            $_SESSION['flash_error'] = 'La mascota no existe.';
            $this->redirect('historial');
        }
        if ($registro_id > 0 && !$this->model->registroExiste($registro_id, $mascota_id)) {
            $_SESSION['flash_error'] = 'El registro elegido no existe en el historial de esta mascota.';
            $this->redirect($volver);
        }
        if ($descripcion === '') {
            $_SESSION['flash_error'] = 'Escribe una descripción del documento.';
            $this->redirect($volver);
        }
        if (empty($_FILES['adjuntos']['name'][0] ?? '')) {
            $_SESSION['flash_error'] = 'Elige al menos un archivo PDF (revisa que no supere el límite de subida del servidor).';
            $this->redirect($volver);
        }

        $_SESSION['flash_success'] = '';
        $this->guardarAdjuntos($registro_id, $mascota_id, $descripcion);
        if (empty($_SESSION['flash_error']) && $_SESSION['flash_success'] === '') {
            $_SESSION['flash_error'] = 'No se adjuntó ningún archivo.';
        }
        $_SESSION['flash_success'] = trim($_SESSION['flash_success']);
        if ($_SESSION['flash_success'] === '') unset($_SESSION['flash_success']);
        $this->redirect($volver);
    }

    /** GET: entrega un PDF adjunto (para verlo y para que la impresión lo dibuje); solo con sesión y de la sucursal actual */
    public function adjunto(): void {
        $a = $this->model->getAdjunto((int)($_GET['id'] ?? 0));
        if (!$a) {
            http_response_code(404);
            exit;
        }
        AdjuntosHistorial::enviar($a['archivo'], $a['nombre']);
    }

    /** POST: quita un PDF adjunto (solo administrador, igual que eliminar registros) */
    public function eliminarAdjunto(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('historial');
        }
        $volver = 'historial?mascota=' . (int)($_POST['mascota_id'] ?? 0);
        if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
            $_SESSION['flash_error'] = 'Solo un administrador puede quitar adjuntos del historial.';
        } else {
            $ok = $this->model->eliminarAdjunto((int)($_POST['id'] ?? 0));
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Adjunto quitado del historial.' : 'No se pudo quitar el adjunto.';
        }
        $this->redirect($volver);
    }

    public function eliminar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('historial');
        }

        $volver = 'historial?mascota=' . (int)($_POST['mascota_id'] ?? 0);
        if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
            $_SESSION['flash_error'] = 'Solo un administrador puede eliminar registros del historial.';
            $this->redirect($volver);
        }

        $ok = $this->model->eliminar((int)($_POST['id'] ?? 0));
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Registro eliminado del historial.' : 'No se pudo eliminar el registro.';
        $this->redirect($volver);
    }

    /** @return array{0: array, 1: string} datos normalizados y mensaje de error ('' si es válido) */
    private function validar(array $p): array {
        $txt = fn(string $k, int $max = 0) => ($v = trim((string)($p[$k] ?? ''))) === '' ? null : ($max ? mb_substr($v, 0, $max) : $v);
        $num = function (string $k, float $min, float $max, string $nombre) use ($p, &$error) {
            $v = trim(str_replace(',', '.', (string)($p[$k] ?? '')));
            if ($v === '') return null;
            if (!is_numeric($v) || (float)$v < $min || (float)$v > $max) {
                $error = $error ?: "$nombre debe estar entre $min y $max.";
                return null;
            }
            return $v;
        };
        $opcion = fn(string $k, array $lista) => in_array($p[$k] ?? '', $lista, true) ? $p[$k] : null;
        $error  = '';

        $vetIds = array_map('intval', array_column($this->model->getVeterinarios(), 'id'));
        $vet    = (int)($p['veterinario_id'] ?? 0);

        $d = [
            'veterinario_id'          => in_array($vet, $vetIds, true) ? $vet : null,
            'fecha'                   => $this->fechaValida($p['fecha'] ?? '') ?? '',
            'tipo'                    => $p['tipo'] ?? '',
            'motivo'                  => $txt('motivo', 200),
            'anamnesis'               => $txt('anamnesis'),
            'peso'                    => $num('peso', 0.01, 200, 'El peso'),
            'temperatura'             => $num('temperatura', 30, 45, 'La temperatura'),
            'frecuencia_cardiaca'     => $num('frecuencia_cardiaca', 10, 400, 'La frecuencia cardíaca'),
            'frecuencia_respiratoria' => $num('frecuencia_respiratoria', 2, 200, 'La frecuencia respiratoria'),
            'mucosas'                 => $opcion('mucosas', HistorialModel::MUCOSAS),
            'tllc'                    => $opcion('tllc', HistorialModel::TLLC),
            'hidratacion'             => $opcion('hidratacion', array_merge(HistorialModel::HIDRATACION, HistorialModel::HIDRATACION_ANTERIOR)),
            'condicion_corporal'      => $num('condicion_corporal', 1, 9, 'La condición corporal'),
            'examen_fisico'           => $txt('examen_fisico'),
            'diagnostico'             => $txt('diagnostico'),
            'tratamiento'             => $txt('tratamiento'),
            'producto_aplicado'       => $txt('producto_aplicado', 150),
            'lote'                    => $txt('lote', 60),
            'proximo_control'         => $this->fechaValida($p['proximo_control'] ?? ''),
            'observaciones'           => $txt('observaciones'),
            'formato'                 => $this->formatoDe($p['f'] ?? null),
        ];

        if ($error === '') {
            if ($d['fecha'] === '') {
                $error = 'La fecha de atención no es válida.';
            } elseif ($d['fecha'] > date('Y-m-d')) {
                $error = 'La fecha de atención no puede ser futura.';
            } elseif (!isset(HistorialModel::TIPOS[$d['tipo']])) {
                $error = 'Selecciona el tipo de registro.';
            } elseif ($d['motivo'] === null) {
                $error = 'Indica el motivo de la atención.';
            } elseif (trim($p['proximo_control'] ?? '') !== '' && $d['proximo_control'] === null) {
                $error = 'La fecha del próximo control no es válida.';
            } elseif ($d['proximo_control'] !== null && $d['proximo_control'] <= $d['fecha']) {
                $error = 'El próximo control debe ser posterior a la fecha de atención.';
            }
        }

        return [$d, $error];
    }

    /** Respuestas del formato de historia clínica: textos recortados, sin vacíos; se guardan como JSON */
    private function formatoDe($f): ?string {
        $limpiar = function ($v, int $nivel) use (&$limpiar) {
            if (is_array($v)) {
                if ($nivel > 4) return null;
                $out = [];
                foreach (array_slice($v, 0, 60, true) as $k => $x) {
                    $x = $limpiar($x, $nivel + 1);
                    if ($x !== null && $x !== '' && $x !== []) $out[mb_substr((string)$k, 0, 40)] = $x;
                }
                return $out;
            }
            return mb_substr(trim((string)$v), 0, 1000);
        };
        $d = is_array($f) ? $limpiar($f, 0) : [];
        // Filas de lista de problemas y plan diagnóstico: solo las que tienen algo escrito, en orden
        foreach (['problemas', 'plan'] as $k) {
            if (isset($d[$k])) $d[$k] = array_values($d[$k]);
        }
        return $d ? json_encode($d, JSON_UNESCAPED_UNICODE) : null;
    }

    /** GET: un registro impreso en el formato de historia clínica de pequeños animales */
    public function formato(): void {
        $r = $this->model->getRegistro((int)($_GET['id'] ?? 0));
        if (!$r) {
            http_response_code(404);
            echo 'Registro no encontrado.';
            exit;
        }
        require_once ROOT . '/app/models/FacturacionModel.php';
        $empresa = (new FacturacionModel())->getSucursal((int)($r['veterinaria_id'] ?: ($_SESSION['veterinaria_id'] ?? 0)));
        $this->render('historial/formato', [
            'r'        => $r,
            'f'        => json_decode((string)$r['formato'], true) ?: [],
            'empresa'  => $empresa,
            'numero'   => VeterinariaModel::serialServicio($empresa['nombre'], 'HC', (int)$r['id'], $r['created_at']),
            'hc'       => 'HC-' . str_pad((string)$r['mascota_id'], 5, '0', STR_PAD_LEFT),
            'vacunas'  => HistorialModel::VACUNAS,
            'actitudes' => HistorialModel::ACTITUD, 'condiciones' => HistorialModel::CONDICION,
            'hidratacion' => HistorialModel::HIDRATACION, 'alimentaciones' => HistorialModel::ALIMENTACION,
            'habitats' => HistorialModel::HABITAT, 'zonasMucosas' => HistorialModel::MUCOSAS_ZONAS, 'sistemas' => HistorialModel::SISTEMAS,
        ]);
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
