<?php

require_once ROOT . '/app/models/ProductoModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';
require_once ROOT . '/app/models/IngresoModel.php';

class AlmacenController {

    private ProductoModel    $model;
    private VeterinariaModel $vetModel;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->model    = new ProductoModel();
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
            'activePage'     => 'almacen',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'productos'      => $this->model->getAll($veterinaria_id),
            'totales'        => $this->model->getTotales($veterinaria_id),
            'categorias'     => $this->model->getCategorias($veterinaria_id),
            'usuario'    => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $this->render('almacen/index', $datos);
    }

    public function agregar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('almacen');
        }

        $datos = $this->validarFormulario($_POST);

        if ($datos['error']) {
            $_SESSION['flash_error'] = $datos['error'];
            $this->redirect('almacen');
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $ok = $this->model->actualizar($id, $datos);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Producto actualizado correctamente.'
                : 'Error al actualizar el producto.';
        } else {
            $ok = $this->model->crear($datos);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Producto agregado correctamente.'
                : 'Error al guardar el producto.';
        }

        $this->redirect('almacen');
    }

    public function transferir(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('almacen');
        }

        $origen_id  = (int)($_SESSION['veterinaria_id'] ?? 0);
        $destino_id = (int)($_POST['destino_id'] ?? 0);
        $ids        = $_POST['producto_id']  ?? [];
        $cantidades = $_POST['cantidad']      ?? [];

        if ($destino_id <= 0 || $destino_id === $origen_id) {
            $_SESSION['flash_error'] = 'Selecciona una sucursal destino diferente.';
            $this->redirect('almacen');
        }

        $lineas = [];
        foreach ($ids as $i => $pid) {
            $pid  = (int)$pid;
            $cant = (int)($cantidades[$i] ?? 0);
            if ($pid > 0 && $cant > 0) {
                $lineas[] = ['producto_id' => $pid, 'cantidad' => $cant,
                             'precio_unitario' => 0, 'subtotal' => 0];
            }
        }

        if (empty($lineas)) {
            $_SESSION['flash_error'] = 'Agrega al menos un producto con cantidad mayor a 0.';
            $this->redirect('almacen');
        }

        $ingresoModel = new IngresoModel();
        $ok = $ingresoModel->transferir($origen_id, $destino_id, $lineas, (int)($_SESSION['usuario_id'] ?? 0));

        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
            ? 'Stock transferido correctamente.'
            : 'Error al transferir: stock insuficiente o datos inválidos.';

        $this->redirect('almacen');
    }

    public function toggle(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->model->toggleActivo($id);
        }
        $this->redirect('almacen');
    }

    private function validarFormulario(array $post): array {
        $datos = [
            'nombre'        => trim($post['nombre']        ?? ''),
            'codigo'        => trim($post['codigo']        ?? ''),
            'descripcion'   => trim($post['descripcion']   ?? ''),
            'categoria'     => trim($post['categoria']     ?? ''),
            'unidad'        => trim($post['unidad']        ?? 'unidad'),
            'precio_compra' => (float)($post['precio_compra'] ?? 0),
            'precio_venta'  => (float)($post['precio_venta']  ?? 0),
            'stock_minimo'  => (int)($post['stock_minimo'] ?? 5),
            'error'         => '',
        ];

        if (empty($datos['nombre'])) {
            $datos['error'] = 'El nombre del producto es obligatorio.';
        } elseif ($datos['precio_venta'] <= 0) {
            $datos['error'] = 'El precio de venta debe ser mayor a 0.';
        }

        return $datos;
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
