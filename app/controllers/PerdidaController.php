<?php

require_once ROOT . '/app/models/PerdidaModel.php';

class PerdidaController {

    private PerdidaModel $model;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->model = new PerdidaModel();
    }

    public function index(): void {
        $veterinarias   = $this->model->getVeterinarias();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);

        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }

        $datos = [
            'activePage'     => 'perdidas',
            'perdidas'       => $this->model->getAll($veterinaria_id),
            'totales'        => $this->model->getTotales($veterinaria_id),
            'productos'      => $this->model->getProductos($veterinaria_id),
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'motivos'        => PerdidaModel::MOTIVOS,
            'usuario'        => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $this->render('perdidas/index', $datos);
    }

    public function registrar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('perdidas'); }

        $veterinaria_id = (int)($_POST['veterinaria_id'] ?? 0);
        $vetQuery       = $veterinaria_id > 0 ? '?vet=' . $veterinaria_id : '';
        $lineas         = $this->extraerLineas($_POST);

        if ($veterinaria_id <= 0) {
            $_SESSION['flash_error'] = 'Debes seleccionar una veterinaria.';
            $this->redirect('perdidas' . $vetQuery);
        }
        if (empty($lineas['items'])) {
            $_SESSION['flash_error'] = 'Debes agregar al menos un producto.';
            $this->redirect('perdidas' . $vetQuery);
        }

        $cabecera = [
            'veterinaria_id' => $veterinaria_id,
            'motivo'         => $_POST['motivo']      ?? 'perdida',
            'responsable'    => trim($_POST['responsable'] ?? ''),
            'notas'          => trim($_POST['notas']       ?? ''),
            'total'          => $lineas['total'],
            'usuario_id'     => (int)($_SESSION['usuario_id'] ?? 0),
        ];

        $errores = $this->model->validarStock($lineas['items'], $veterinaria_id);
        if (!empty($errores)) {
            $_SESSION['flash_error'] = 'Stock insuficiente: ' . implode(' / ', $errores);
            $this->redirect('perdidas' . $vetQuery);
        }

        $ok = $this->model->crear($cabecera, $lineas['items']);
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
            ? 'Pérdida registrada y stock descontado correctamente.'
            : 'Error al registrar la pérdida.';

        $this->redirect('perdidas' . $vetQuery);
    }

    public function editar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('perdidas'); }

        $id       = (int)($_POST['perdida_id']     ?? 0);
        $vet      = (int)($_POST['veterinaria_id'] ?? 0);
        $vetQuery = $vet > 0 ? '?vet=' . $vet : '';
        $lineas   = $this->extraerLineas($_POST);

        if ($id <= 0 || empty($lineas['items']) || !Seguridad::esDeLaSucursal('perdidas', $id)) {
            $_SESSION['flash_error'] = 'Datos inválidos.';
            $this->redirect('perdidas' . $vetQuery);
        }

        $cabecera = [
            'motivo'      => $_POST['motivo']      ?? 'perdida',
            'responsable' => trim($_POST['responsable'] ?? ''),
            'notas'       => trim($_POST['notas']       ?? ''),
            'total'       => $lineas['total'],
        ];

        $errores   = $this->model->validarStock($lineas['items'], (int)($_SESSION['veterinaria_id'] ?? 0), $id);
        if (!empty($errores)) {
            $_SESSION['flash_error'] = 'Stock insuficiente: ' . implode(' / ', $errores);
            $this->redirect('perdidas' . $vetQuery);
        }

        $ok = $this->model->actualizar($id, $cabecera, $lineas['items']);
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
            ? 'Pérdida actualizada correctamente.'
            : 'Error al actualizar.';

        $this->redirect('perdidas' . $vetQuery);
    }

    public function detalle(): void {
        $id = (int)($_GET['id'] ?? 0);
        header('Content-Type: application/json');
        echo json_encode(Seguridad::esDeLaSucursal('perdidas', $id) ? $this->model->getDetalles($id) : []);
        exit;
    }

    public function eliminar(): void {
        $id  = (int)($_GET['id']  ?? 0);
        $vet = (int)($_GET['vet'] ?? 0);
        if ($id > 0 && Seguridad::esDeLaSucursal('perdidas', $id)) {
            $this->model->eliminar($id);
            $_SESSION['flash_success'] = 'Pérdida eliminada y stock restaurado.';
        } elseif ($id > 0) {
            $_SESSION['flash_error'] = 'La pérdida no pertenece a esta sucursal.';
        }
        $this->redirect('perdidas' . ($vet > 0 ? '?vet=' . $vet : ''));
    }

    private function extraerLineas(array $post): array {
        $ids      = $post['producto_id']      ?? [];
        $cantidades = $post['cantidad']        ?? [];
        $precios    = $post['precio_unitario'] ?? [];
        $items = [];
        $total = 0;
        foreach ($ids as $i => $pid) {
            $pid  = (int)$pid;
            $cant = (int)($cantidades[$i] ?? 0);
            $prec = (float)($precios[$i]  ?? 0);
            if ($pid > 0 && $cant > 0) {
                $items[] = ['producto_id' => $pid, 'cantidad' => $cant, 'precio_unitario' => $prec];
                $total  += $cant * $prec;
            }
        }
        return ['items' => $items, 'total' => $total];
    }

    private function resolverParams(): array {
        $vets   = $this->model->getVeterinarias();
        $vetId  = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($vetId === 0 && !empty($vets)) $vetId = (int)$vets[0]['id'];
        $nombre = '';
        foreach ($vets as $v) { if ((int)$v['id'] === $vetId) { $nombre = $v['nombre']; break; } }

        $primerDia = date('Y-m-01');
        $ultimoDia = date('Y-m-t');
        $desde = $_GET['desde'] ?? $primerDia;
        $hasta = $_GET['hasta'] ?? $ultimoDia;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = $primerDia;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = $ultimoDia;
        if ($hasta < $desde) $hasta = $desde;
        return [$vets, $vetId, $nombre, $desde, $hasta];
    }

    public function reporte(): void {
        [$veterinarias, $veterinaria_id, $sucursal_nombre, $desde, $hasta] = $this->resolverParams();

        $datos = [
            'activePage'      => 'reporte-perdidas',
            'veterinarias'    => $veterinarias,
            'veterinaria_id'  => $veterinaria_id,
            'sucursal_nombre' => $sucursal_nombre,
            'desde'           => $desde,
            'hasta'           => $hasta,
            'perdidas'        => $this->model->getReporte($veterinaria_id, $desde, $hasta),
            'resumen'         => $this->model->getResumenReporte($veterinaria_id, $desde, $hasta),
            'motivos'         => PerdidaModel::MOTIVOS,
            'usuario'         => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
        ];
        $this->render('perdidas/reporte', $datos);
    }

    public function imprimirReporte(): void {
        [, $veterinaria_id, $sucursal_nombre, $desde, $hasta] = $this->resolverParams();

        $datos = [
            'veterinaria_id'  => $veterinaria_id,
            'sucursal_nombre' => $sucursal_nombre,
            'desde'           => $desde,
            'hasta'           => $hasta,
            'perdidas'        => $this->model->getReporte($veterinaria_id, $desde, $hasta),
            'resumen'         => $this->model->getResumenReporte($veterinaria_id, $desde, $hasta),
            'motivos'         => PerdidaModel::MOTIVOS,
            'usuario'         => ['nombre' => $_SESSION['usuario_nombre']],
        ];
        $this->render('perdidas/reporte_print', $datos);
    }

    private function requiereAutenticacion(): void {
        if (!isset($_SESSION['usuario_id'])) { header('Location: ' . BASE_URL . '/'); exit; }
    }

    private function redirect(string $ruta): void {
        header('Location: ' . BASE_URL . '/' . $ruta); exit;
    }

    private function render(string $vista, array $datos = []): void {
        extract($datos);
        require ROOT . '/app/views/' . $vista . '.php';
    }
}
