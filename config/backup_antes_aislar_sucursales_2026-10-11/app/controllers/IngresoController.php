<?php

require_once ROOT . '/app/models/IngresoModel.php';
require_once ROOT . '/app/models/ProveedorModel.php';

class IngresoController {

    private IngresoModel $model;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->model = new IngresoModel();
    }

    public function index(): void {
        $veterinarias   = $this->model->getVeterinarias();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);

        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }

        $datos = [
            'activePage'     => 'ingresos',
            'ingresos'       => $this->model->getAll($veterinaria_id),
            'totales'        => $this->model->getTotales($veterinaria_id),
            'productos'      => $this->model->getProductos($veterinaria_id),
            'proveedores'    => (new ProveedorModel())->getAll(),
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'usuario'        => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $this->render('ingresos/index', $datos);
    }

    public function registrar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('ingresos');
        }

        $veterinaria_id = (int)($_POST['veterinaria_id'] ?? 0);
        $vetQuery       = $veterinaria_id > 0 ? '?vet=' . $veterinaria_id : '';

        // Recoger líneas de productos
        $ids      = $_POST['producto_id']     ?? [];
        $cantidades = $_POST['cantidad']      ?? [];
        $precios    = $_POST['precio_unitario'] ?? [];
        $lotes      = $_POST['lote']              ?? [];
        $vencimientos = $_POST['fecha_vencimiento'] ?? [];

        $lineas = [];
        $total  = 0;

        foreach ($ids as $i => $pid) {
            $pid   = (int)$pid;
            $cant  = (int)($cantidades[$i] ?? 0);
            $precio = (float)($precios[$i] ?? 0);

            if ($pid > 0 && $cant > 0) {
                $lineas[] = [
                    'producto_id'     => $pid,
                    'cantidad'        => $cant,
                    'precio_unitario' => $precio,
                ] + $this->datosLote($lotes[$i] ?? '', $vencimientos[$i] ?? '');
                $total += $cant * $precio;
            }
        }

        if ($veterinaria_id <= 0) {
            $_SESSION['flash_error'] = 'Debes seleccionar una veterinaria.';
            $this->redirect('ingresos' . $vetQuery);
        }

        if (empty($lineas)) {
            $_SESSION['flash_error'] = 'Debes agregar al menos un producto al ingreso.';
            $this->redirect('ingresos' . $vetQuery);
        }

        $cabecera = [
            'veterinaria_id' => $veterinaria_id,
            'proveedor'      => trim($_POST['proveedor'] ?? ''),
            'notas'          => trim($_POST['notas']     ?? ''),
            'total'          => $total,
            'usuario_id'     => (int)($_SESSION['usuario_id'] ?? 0),
        ];

        if ($this->model->crear($cabecera, $lineas)) {
            $n = count($lineas);
            $_SESSION['flash_success'] = "Ingreso registrado con $n producto(s). Stock actualizado.";
        } else {
            $_SESSION['flash_error'] = 'Error al registrar el ingreso.';
        }

        $this->redirect('ingresos' . $vetQuery);
    }

    public function editar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('ingresos');
        }

        $id  = (int)($_POST['ingreso_id'] ?? 0);
        $vet = (int)($_POST['veterinaria_id'] ?? 0);
        $vetQuery = $vet > 0 ? '?vet=' . $vet : '';

        if ($id <= 0 || !Seguridad::esDeLaSucursal('ingresos', $id)) {
            $_SESSION['flash_error'] = 'Ingreso no válido.';
            $this->redirect('ingresos' . $vetQuery);
        }

        $ids      = $_POST['producto_id']      ?? [];
        $cantidades = $_POST['cantidad']        ?? [];
        $precios    = $_POST['precio_unitario'] ?? [];
        $lotes      = $_POST['lote']              ?? [];
        $vencimientos = $_POST['fecha_vencimiento'] ?? [];

        $lineas = [];
        $total  = 0;

        foreach ($ids as $i => $pid) {
            $pid  = (int)$pid;
            $cant = (int)($cantidades[$i] ?? 0);
            $prec = (float)($precios[$i]  ?? 0);
            if ($pid > 0 && $cant > 0) {
                $lineas[] = ['producto_id' => $pid, 'cantidad' => $cant, 'precio_unitario' => $prec]
                          + $this->datosLote($lotes[$i] ?? '', $vencimientos[$i] ?? '');
                $total   += $cant * $prec;
            }
        }

        if (empty($lineas)) {
            $_SESSION['flash_error'] = 'Debes agregar al menos un producto.';
            $this->redirect('ingresos' . $vetQuery);
        }

        $cabecera = [
            'proveedor' => trim($_POST['proveedor'] ?? ''),
            'notas'     => trim($_POST['notas']     ?? ''),
            'total'     => $total,
        ];

        if ($this->model->actualizar($id, $cabecera, $lineas)) {
            $_SESSION['flash_success'] = 'Ingreso actualizado correctamente.';
        } else {
            $_SESSION['flash_error'] = 'Error al actualizar el ingreso.';
        }

        $this->redirect('ingresos' . $vetQuery);
    }

    public function detalle(): void {
        $id = (int)($_GET['id'] ?? 0);
        header('Content-Type: application/json');
        echo json_encode(Seguridad::esDeLaSucursal('ingresos', $id) ? $this->model->getDetalles($id) : []);
        exit;
    }

    public function eliminar(): void {
        $id  = (int)($_GET['id']  ?? 0);
        $vet = (int)($_GET['vet'] ?? 0);

        if ($id > 0 && Seguridad::esDeLaSucursal('ingresos', $id)) {
            $this->model->eliminar($id);
            $_SESSION['flash_success'] = 'Ingreso eliminado y stock revertido.';
        } elseif ($id > 0) {
            $_SESSION['flash_error'] = 'El ingreso no pertenece a esta sucursal.';
        }

        $query = $vet > 0 ? '?vet=' . $vet : '';
        $this->redirect('ingresos' . $query);
    }

    // Lote y fecha de vencimiento de una línea: ambos opcionales; una fecha mal formada se guarda vacía
    private function datosLote($lote, $vence): array {
        $lote  = mb_substr(trim((string)$lote), 0, 60);
        $vence = trim((string)$vence);
        $fecha = DateTime::createFromFormat('Y-m-d', $vence);
        return [
            'lote'              => $lote !== '' ? $lote : null,
            'fecha_vencimiento' => ($fecha && $fecha->format('Y-m-d') === $vence) ? $vence : null,
        ];
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
