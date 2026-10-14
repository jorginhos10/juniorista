<?php

require_once ROOT . '/app/models/TratamientoModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';

class TratamientoController {

    // Peluquería reutiliza este controlador cambiando esta configuración (ver PeluqueriaController)
    protected array $modulo = [
        'ruta'        => 'tratamiento',
        'titulo'      => 'Tratamiento',
        'singular'    => 'tratamiento',
        'plural'      => 'tratamientos',
        'icono'       => 'capsule',
        'placeholder' => 'Ej: Tratamiento dermatológico, curaciones por 10 días…',
    ];

    private const PESTANAS = ['pendientes', 'completos', 'cancelados'];

    protected TratamientoModel $model;
    private VeterinariaModel   $vetModel;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->model    = $this->crearModelo();
        $this->vetModel = new VeterinariaModel();
    }

    protected function crearModelo(): TratamientoModel {
        return new TratamientoModel();
    }

    public function index(): void {
        $veterinarias   = $this->vetModel->getAll();
        $veterinaria_id = $this->veterinariaActual($veterinarias);

        $datos = [
            'activePage'     => $this->modulo['ruta'],
            'modulo'         => $this->modulo,
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'hoy'            => date('Y-m-d'),
            'especies'       => TratamientoModel::ESPECIES,
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

        if ($this->model->usaAbonos()) {
            // Tratamiento: listado completo con pestañas pendientes / completos / cancelados
            $datos['tratamientos'] = $this->model->getTodos($veterinaria_id);
            $datos['clientes']     = $this->model->getClientesSucursal($veterinaria_id);
            $datos['mascotas']     = $this->model->getMascotasSucursal($veterinaria_id);
            $datos['pestana']      = in_array($_GET['tab'] ?? '', self::PESTANAS, true) ? $_GET['tab'] : 'pendientes';
            $datos['abrir']        = (int)($_GET['ver'] ?? 0);
            $this->render('tratamientos/lista', $datos);
            return;
        }

        // Peluquería: registro por día
        $fecha     = $this->fechaValida($_GET['fecha'] ?? '') ?? $datos['hoy'];
        $iniSemana = date('Y-m-d', strtotime('monday this week', strtotime($fecha)));
        $finSemana = date('Y-m-d', strtotime('sunday this week', strtotime($fecha)));
        $datos += [
            'fecha'         => $fecha,
            'iniSemana'     => $iniSemana,
            'tratamientos'  => $this->model->getDia($veterinaria_id, $fecha),
            'resumenSemana' => $this->model->getResumenDias($veterinaria_id, $iniSemana, $finSemana),
        ];
        $datos += $this->datosExtraDia($veterinaria_id, $fecha, $iniSemana, $finSemana);
        $this->render('tratamientos/index', $datos);
    }

    /** Datos adicionales para la vista por día (Peluquería agrega el reparto negocio / peluquería) */
    protected function datosExtraDia(int $veterinaria_id, string $fecha, string $iniSemana, string $finSemana): array {
        return [];
    }

    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($this->modulo['ruta']);
        }

        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $datos  = $this->validar($_POST);
        $volver = $this->urlVolver($datos['fecha'] ?: date('Y-m-d'));

        if ($datos['error'] !== '') {
            $_SESSION['flash_error'] = $datos['error'];
            $this->redirect($volver);
        }

        $datos['veterinaria_id'] = $veterinaria_id;
        $id = (int)($_POST['id'] ?? 0);

        // Cliente y mascota anclados (opcionales): solo los de esta sucursal. El formulario de peluquería no los envía.
        if (isset($_POST['cliente_id']) || isset($_POST['mascota_id'])) {
            [$datos['cliente_id'], $datos['mascota_id']] = $this->model->validarVinculos(
                $veterinaria_id, (int)($_POST['cliente_id'] ?? 0), (int)($_POST['mascota_id'] ?? 0)
            );
        }

        if ($id > 0) {
            if ($this->model->usaAbonos()) {
                $abonado = $this->model->sumaAbonos($id);
                if ($datos['valor'] < $abonado) {
                    $_SESSION['flash_error'] = 'El valor no puede ser menor a lo ya abonado ($' . number_format($abonado, 2) . ').';
                    $this->redirect($volver);
                }
            }
            $ok = $this->model->actualizar($id, $datos);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? ucfirst($this->modulo['singular']) . ' actualizado.'
                : 'Error al actualizar el ' . $this->modulo['singular'] . '.';
        } else {
            $datos['usuario_id'] = (int)$_SESSION['usuario_id'];
            $nuevoId = $this->model->crear($datos, $datos['abono_inicial'], $datos['nota_abono']);
            $ok  = $nuevoId > 0;
            $msg = ucfirst($this->modulo['singular']) . ' de ' . $datos['nombre'] . ' agregado.';
            if ($ok && $this->model->usaAbonos()) {
                $saldo = $datos['valor'] - $datos['abono_inicial'];
                $msg  .= $saldo > 0
                    ? ($datos['abono_inicial'] > 0 ? ' Abonó $' . number_format($datos['abono_inicial'], 2) . '.' : '')
                      . ' Queda un saldo de $' . number_format($saldo, 2) . '.'
                    : ' Pagado por completo.';
                // Si hubo abono, se abre el detalle para poder imprimir la factura
                $volver = $this->urlVolver('', $saldo > 0 ? 'pendientes' : 'completos', $datos['abono_inicial'] > 0 ? $nuevoId : 0);
            }
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? $msg : 'Error al agregar el ' . $this->modulo['singular'] . '.';
        }

        $this->redirect($volver);
    }

    /** Peluquería: elimina un registro del día */
    public function eliminar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($this->modulo['ruta']);
        }

        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $ok = $this->model->eliminar((int)($_POST['id'] ?? 0), $veterinaria_id);
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? ucfirst($this->modulo['singular']) . ' eliminado.' : 'No se pudo eliminar el ' . $this->modulo['singular'] . '.';
        $this->redirect($this->urlVolver($this->fechaValida($_POST['fecha'] ?? '') ?? date('Y-m-d')));
    }

    // ── Abonos y estado (solo Tratamiento) ────────────────

    /** POST: registra un abono y vuelve con el detalle abierto */
    public function abonar(): void {
        $this->soloConAbonos();
        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());

        $id    = (int)($_POST['tratamiento_id'] ?? 0);
        $fecha = $this->fechaValida($_POST['fecha'] ?? '');
        $monto = round((float)str_replace(',', '.', (string)($_POST['monto'] ?? '0')), 2);
        $nota  = mb_substr(trim($_POST['nota'] ?? ''), 0, 150);

        if ($fecha === null) {
            $res = 'La fecha del abono no es válida.';
        } elseif ($fecha > date('Y-m-d')) {
            $res = 'El abono no puede tener fecha futura.';
        } elseif ($monto <= 0) {
            $res = 'El abono debe ser mayor a cero.';
        } else {
            $res = $this->model->registrarAbono($id, $veterinaria_id, $fecha, $monto, $nota, (int)$_SESSION['usuario_id']);
        }

        $t = $this->model->getConSaldo($id, $veterinaria_id);
        if (is_string($res)) {
            $_SESSION['flash_error'] = $res;
        } else {
            $_SESSION['flash_success'] = 'Abono de $' . number_format($monto, 2) . ' registrado para ' . $t['nombre'] . '. '
                . ($t['saldo'] > 0 ? 'Saldo pendiente: $' . number_format($t['saldo'], 2) . '.' : '¡Tratamiento pagado por completo!');
            $_SESSION['abono_nuevo'] = $res;   // para resaltar e imprimir el abono recién registrado
        }
        $pestana = !$t ? 'pendientes' : ($t['estado'] === 'cancelado' ? 'cancelados' : ($t['saldo'] > 0 ? 'pendientes' : 'completos'));
        $this->redirect($this->urlVolver('', $pestana, $t ? $id : 0));
    }

    /** POST: anula un abono (solo admin) */
    public function eliminarAbono(): void {
        $this->soloConAbonos();
        $tratamiento_id = (int)($_POST['tratamiento_id'] ?? 0);
        if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
            $_SESSION['flash_error'] = 'Solo un administrador puede anular abonos.';
        } else {
            $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
            $ok = $this->model->eliminarAbono((int)($_POST['id'] ?? 0), $veterinaria_id);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Abono anulado; el saldo volvió a quedar pendiente.' : 'No se pudo anular el abono.';
        }
        $this->redirect($this->urlVolver('', $_POST['tab'] ?? '', $tratamiento_id));
    }

    /** GET JSON: tratamiento con saldo e historial de abonos */
    public function abonos(): void {
        $this->soloConAbonos();
        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $id = (int)($_GET['id'] ?? 0);
        $t  = $this->model->getConSaldo($id, $veterinaria_id);

        header('Content-Type: application/json');
        if (!$t) {
            http_response_code(404);
            echo json_encode(['error' => 'Tratamiento no encontrado.']);
            exit;
        }
        // Cada abono con su radicado (mismo patrón que las ventas)
        $sucursal = $this->vetModel->findById($veterinaria_id)['nombre'] ?? '';
        $abonos   = $this->model->getAbonos($id, $veterinaria_id);
        foreach ($abonos as &$a) {
            $a['comprobante'] = VeterinariaModel::serialServicio($sucursal, 'ABO', (int)$a['id'], $a['created_at']);
        }
        unset($a);
        echo json_encode([
            'tratamiento' => $t,
            'abonos'      => $abonos,
            'nuevo'       => $_SESSION['abono_nuevo'] ?? 0,
        ]);
        unset($_SESSION['abono_nuevo']);
        exit;
    }

    /** GET: factura imprimible de un abono */
    public function imprimirAbono(): void {
        $this->soloConAbonos();
        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $recibo = $this->model->getReciboAbono((int)($_GET['id'] ?? 0), $veterinaria_id);
        if (!$recibo) {
            http_response_code(404);
            echo 'Abono no encontrado.';
            exit;
        }
        $this->render('tratamientos/recibo_abono', [
            'r'        => $recibo,
            'especies' => TratamientoModel::ESPECIES,
            'usuario'  => ['nombre' => $_SESSION['usuario_nombre']],
        ]);
    }

    /** POST: cancela un tratamiento (no recibe más abonos; lo abonado sigue contando como cobrado) */
    public function cancelar(): void {
        $this->soloConAbonos();
        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $motivo = mb_substr(trim($_POST['motivo'] ?? ''), 0, 200);
        $ok = $this->model->cancelar((int)($_POST['id'] ?? 0), $veterinaria_id, $motivo, date('Y-m-d'));
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Tratamiento cancelado.' : 'No se pudo cancelar el tratamiento.';
        $this->redirect($this->urlVolver('', $ok ? 'cancelados' : ($_POST['tab'] ?? '')));
    }

    /** POST: reactiva un tratamiento cancelado */
    public function reactivar(): void {
        $this->soloConAbonos();
        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $id = (int)($_POST['id'] ?? 0);
        $ok = $this->model->reactivar($id, $veterinaria_id);
        $t  = $ok ? $this->model->getConSaldo($id, $veterinaria_id) : null;
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Tratamiento reactivado.' : 'No se pudo reactivar el tratamiento.';
        $this->redirect($this->urlVolver('', $t ? ($t['saldo'] > 0 ? 'pendientes' : 'completos') : 'cancelados'));
    }

    /** Las acciones de abonos solo existen en Tratamiento (peluquería y ventas son recaudo directo) */
    private function soloConAbonos(): void {
        if (!$this->model->usaAbonos()) {
            http_response_code(404);
            exit;
        }
    }

    /** Peluquería vuelve al día; Tratamiento vuelve a su pestaña (y opcionalmente abre el detalle de un tratamiento) */
    private function urlVolver(string $fecha = '', string $pestana = '', int $ver = 0): string {
        if (!$this->model->usaAbonos()) {
            return $this->modulo['ruta'] . '?fecha=' . ($fecha ?: date('Y-m-d'));
        }
        $tab = in_array($pestana, self::PESTANAS, true) ? $pestana
             : (in_array($_POST['tab'] ?? '', self::PESTANAS, true) ? $_POST['tab'] : '');
        $q = array_filter(['tab' => $tab, 'ver' => $ver ?: null]);
        return $this->modulo['ruta'] . ($q ? '?' . http_build_query($q) : '');
    }

    private function validar(array $post): array {
        $conAbonos = $this->model->usaAbonos();
        $datos = [
            'fecha'       => $this->fechaValida($post['fecha'] ?? '') ?? '',
            'nombre'      => mb_substr(trim($post['nombre'] ?? ''), 0, 100),
            'especie'     => $post['especie'] ?? '',
            'descripcion' => mb_substr(trim($post['descripcion'] ?? ''), 0, 255),
            'valor'       => round((float)str_replace(',', '.', (string)($post['valor'] ?? '0')), 2),
            // Con abonos (tratamiento): lo que pagó al registrar. Sin abonos (peluquería): recaudo directo
            'abono_inicial' => $conAbonos ? round((float)str_replace(',', '.', (string)($post['abono_inicial'] ?? '0')), 2) : 0.0,
            'nota_abono'    => $conAbonos ? mb_substr(trim($post['nota_abono'] ?? ''), 0, 150) : '',
            'error'       => '',
        ];

        if ($datos['nombre'] === '') {
            $datos['error'] = 'Indica el nombre de la mascota.';
        } elseif (!isset(TratamientoModel::ESPECIES[$datos['especie']])) {
            $datos['error'] = 'Selecciona la especie (perro o gato).';
        } elseif ($datos['descripcion'] === '') {
            $datos['error'] = 'Describe el ' . $this->modulo['singular'] . '.';
        } elseif ($datos['valor'] < 0 || $datos['valor'] > 99999999) {
            $datos['error'] = 'El valor no es válido.';
        } elseif ($datos['fecha'] === '') {
            $datos['error'] = 'La fecha no es válida.';
        } elseif ($datos['abono_inicial'] > 0 && $datos['fecha'] > date('Y-m-d')) {
            $datos['error'] = 'No se puede registrar un abono con fecha futura.';
        } elseif ($datos['abono_inicial'] < 0 || $datos['abono_inicial'] > $datos['valor']) {
            $datos['error'] = 'El abono inicial debe estar entre 0 y el valor del tratamiento.';
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
