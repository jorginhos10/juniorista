<?php

require_once ROOT . '/app/models/ProductoModel.php';
require_once ROOT . '/app/models/VeterinariaModel.php';
require_once ROOT . '/app/models/IngresoModel.php';
require_once ROOT . '/app/models/CategoriaModel.php';

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
            'categorias'     => (new CategoriaModel())->getNombres(),
            'relacionados'   => $this->model->getMapaRelacionados(),
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

    // ── DESCARGAS ─────────────────────────────────────────

    /** Productos de la sucursal para exportar; respeta el texto del buscador (?q=) */
    private function datosExportar(): array {
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        $sucursal = '';
        foreach ($this->vetModel->getAll() as $v) {
            if ((int)$v['id'] === $veterinaria_id) { $sucursal = $v['nombre']; break; }
        }

        $q = mb_strtolower(trim($_GET['q'] ?? ''), 'UTF-8');
        $productos = $veterinaria_id > 0 ? $this->model->getAll($veterinaria_id) : [];
        if ($q !== '') {
            $productos = array_values(array_filter($productos, function ($p) use ($q) {
                $texto = implode(' ', [$p['nombre'], $p['descripcion'] ?? '', $p['categoria'] ?? '', $p['codigo'] ?? '']);
                return mb_strpos(mb_strtolower($texto, 'UTF-8'), $q) !== false;
            }));
        }
        return ['sucursal' => $sucursal, 'productos' => $productos, 'filtro' => $q];
    }

    public function excel(): void {
        $d = $this->datosExportar();
        require_once ROOT . '/app/helpers/ExcelExport.php';

        $filas = [];
        $unidades = 0; $valor = 0;
        foreach ($d['productos'] as $i => $p) {
            $stock = (int)$p['stock'];
            $val   = $stock * (float)$p['precio_venta'];   // valor = unidades en stock × precio de venta
            if ((int)$p['activo'] === 1) { $unidades += $stock; $valor += $val; }
            $filas[] = [
                $i + 1, $p['nombre'], (string)($p['descripcion'] ?? ''), (string)($p['categoria'] ?? ''), (string)($p['codigo'] ?? ''),
                (string)($p['unidad'] ?? ''), (float)$p['precio_compra'], (float)$p['precio_venta'],
                $stock, (int)$p['stock_minimo'], $stock <= (int)$p['stock_minimo'] ? 'Sí' : 'No',
                $val, (int)$p['activo'] === 1 ? 'Activo' : 'Inactivo',
            ];
        }
        $filas[] = ['', 'TOTAL (productos activos)', '', '', '', '', '', '', $unidades, '', '', $valor, ''];

        ExcelExport::descargar(
            VeterinariaModel::nombreArchivo($d['sucursal'], 'almacen_' . date('Y-m-d')), 'Almacén',
            ['#', 'Nombre', 'Descripción', 'Categoría', 'Código', 'Unidad', 'P. Compra', 'P. Venta', 'Stock', 'Stock mínimo', 'Stock bajo', 'Valor (stock x venta)', 'Estado'],
            $filas, [6, 46, 24, 14, 20, 10, 12, 12, 9, 13, 11, 20, 10]
        );
        exit;
    }

    public function pdf(): void {
        $d = $this->datosExportar();
        require_once ROOT . '/app/helpers/PdfSimple.php';

        $pdf = new PdfSimple();
        $izq = 36; $der = PdfSimple::ANCHO - 36;
        $col = ['n' => $izq + 4, 'prod' => $izq + 28, 'cat' => 330, 'compra' => 446, 'venta' => 512, 'stock' => $der - 4];
        $dinero = fn($v) => '$' . number_format((float)$v, 2, '.', ',');
        $generado = 'Generado el ' . date('d/m/Y H:i');

        $encabezadoTabla = function (float $y) use ($pdf, $izq, $der, $col): float {
            $pdf->rectangulo($izq, $y, $der - $izq, 16, 0.90);
            $pdf->texto($col['n'], $y + 11, '#', 8, true);
            $pdf->texto($col['prod'], $y + 11, 'PRODUCTO', 8, true);
            $pdf->texto($col['cat'], $y + 11, 'CATEGORÍA', 8, true);
            $pdf->texto($col['compra'], $y + 11, 'P. COMPRA', 8, true, 'R');
            $pdf->texto($col['venta'], $y + 11, 'P. VENTA', 8, true, 'R');
            $pdf->texto($col['stock'], $y + 11, 'STOCK', 8, true, 'R');
            return $y + 16;
        };
        $pie = function () use ($pdf, $izq, $der, $generado, $d): void {
            $pdf->linea($izq, PdfSimple::ALTO - 40, $der, PdfSimple::ALTO - 40);
            $pdf->texto($izq, PdfSimple::ALTO - 28, $d['sucursal'] . '  ·  ' . $generado, 7.5, false, 'L', 0.45);
            $pdf->texto($der, PdfSimple::ALTO - 28, 'Página ' . $pdf->numeroPagina(), 7.5, false, 'R', 0.45);
        };

        $pdf->texto($izq, 50, $d['sucursal'] ?: 'Almacén', 16, true);
        $pdf->texto($der, 46, 'Inventario del almacén', 12, true, 'R');
        $pdf->texto($der, 60, date('d/m/Y H:i'), 10, false, 'R', 0.35);
        $pdf->linea($izq, 70, $der, 70, 0.3);
        $y = 84;
        if ($d['filtro'] !== '') {
            $pdf->texto($izq, $y + 4, 'Filtro aplicado: "' . $d['filtro'] . '"', 9, false, 'L', 0.35);
            $y += 16;
        }

        $y = $encabezadoTabla($y);
        $unidades = 0; $valor = 0; $bajos = 0; $activos = 0;
        foreach ($d['productos'] as $i => $p) {
            if ($y > PdfSimple::ALTO - 70) {
                $pie();
                $pdf->nuevaPagina();
                $y = $encabezadoTabla(40);
            }
            $stock  = (int)$p['stock'];
            $activo = (int)$p['activo'] === 1;
            $bajo   = $stock <= (int)$p['stock_minimo'];
            if ($activo) {
                $activos++; $unidades += $stock; $valor += $stock * (float)$p['precio_venta'];
                if ($bajo) $bajos++;
            }
            $gris = $activo ? 0 : 0.55;

            if ($i % 2 === 1) $pdf->rectangulo($izq, $y, $der - $izq, 15, 0.97);
            $nombre = $p['nombre'] . (!empty($p['codigo']) ? '  (' . $p['codigo'] . ')' : '') . ($activo ? '' : '  [inactivo]');
            $pdf->texto($col['n'], $y + 10.5, (string)($i + 1), 8, false, 'L', 0.45);
            $pdf->texto($col['prod'], $y + 10.5, $pdf->ajustar($nombre, $col['cat'] - $col['prod'] - 8, 8), 8, false, 'L', $gris);
            $pdf->texto($col['cat'], $y + 10.5, $pdf->ajustar((string)($p['categoria'] ?? ''), 62, 8), 8, false, 'L', $gris);
            $pdf->texto($col['compra'], $y + 10.5, $dinero($p['precio_compra']), 8, false, 'R', $gris);
            $pdf->texto($col['venta'], $y + 10.5, $dinero($p['precio_venta']), 8, false, 'R', $gris);
            $pdf->texto($col['stock'], $y + 10.5, $stock . ($bajo && $activo ? ' *' : ''), 8, $bajo && $activo, 'R', $gris);
            $y += 15;
        }

        if ($y > PdfSimple::ALTO - 140) {
            $pie();
            $pdf->nuevaPagina();
            $y = 40;
        }
        $pdf->linea($izq, $y + 2, $der, $y + 2, 0.3);
        $pdf->texto($col['prod'], $y + 17, $activos . ' producto(s) activos', 9, true);
        $pdf->texto($col['stock'], $y + 17, $unidades . ' u.', 9, true, 'R');

        // Totales: valor a precio de venta, repartido en inversión (70%) y ganancias (30%), igual que en la pantalla de Almacén
        $ganancias = round($valor * 0.30, 2);
        $inversion = $valor - $ganancias;
        $yT = $y + 30;
        $pdf->rectangulo($der - 250, $yT, 250, 54, 0.95);
        foreach ([['Total (stock x precio de venta)', $valor], ['Inversión (70%)', $inversion], ['Ganancias (30%)', $ganancias]] as $n => [$etiqueta, $monto]) {
            $pdf->texto($der - 242, $yT + 14 + $n * 16, $etiqueta, 9, $n === 0);
            $pdf->texto($der - 8, $yT + 14 + $n * 16, $dinero($monto), 9, true, 'R');
        }
        $pdf->texto($izq, $yT + 68, '* Stock bajo: igual o menor al stock mínimo (' . $bajos . ' producto(s)).', 7.5, false, 'L', 0.45);
        $pie();

        $pdf->descargar(VeterinariaModel::nombreArchivo($d['sucursal'], 'almacen_' . date('Y-m-d')));
        exit;
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
            $id = $this->model->ultimoCreado;
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Producto agregado correctamente.'
                : 'Error al guardar el producto.';
        }

        // "Relacionar con": productos que hacen lo mismo (se ofrecen como alternativa en ventas)
        if ($ok && $id > 0) {
            $this->model->guardarRelacionados($id, (array)($_POST['relacionados'] ?? []));
        }

        // Foto: se procesa después de guardar; un problema con la imagen no pierde el resto del formulario
        if ($ok && $id > 0) {
            require_once ROOT . '/app/helpers/Imagenes.php';
            [$cambio, $ruta, $errorFoto] = Imagenes::procesar(
                $_FILES['foto'] ?? null, 'assets/img/productos', 'producto_' . $id,
                $this->model->getFoto($id), !empty($_POST['quitar_foto'])
            );
            if ($cambio) {
                $this->model->guardarFoto($id, $ruta);
            }
            if ($errorFoto !== '') {
                $_SESSION['flash_error'] = 'El producto se guardó, pero la foto no: ' . $errorFoto;
            }
        }

        $this->redirect('almacen');
    }

    // ── CATEGORÍAS ────────────────────────────────────────

    public function categorias(): void {
        $veterinarias   = $this->vetModel->getAll();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);

        $datos = [
            'activePage'     => 'almacen',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'categorias'     => (new CategoriaModel())->getAll(),
            'usuario'    => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $this->render('almacen/categorias', $datos);
    }

    /** POST: crea una categoría o renombra una existente (y los productos que la usan) */
    public function guardarCategoria(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('almacen/categorias');
        }

        $modelo = new CategoriaModel();
        $id     = (int)($_POST['id'] ?? 0);
        // Mayúsculas y espacios simples, como las categorías que ya existen
        $nombre = mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string)($_POST['nombre'] ?? ''))));

        if ($nombre === '') {
            $_SESSION['flash_error'] = 'Escribe el nombre de la categoría.';
        } elseif (mb_strlen($nombre) > 80) {
            $_SESSION['flash_error'] = 'El nombre no puede tener más de 80 caracteres.';
        } elseif ($modelo->existe($nombre, $id)) {
            $_SESSION['flash_error'] = 'Ya existe la categoría «' . $nombre . '».';
        } elseif ($id > 0) {
            $anterior = $modelo->findById($id);
            $ok = $anterior && $modelo->renombrar($id, $nombre);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Categoría «' . $anterior['nombre'] . '» renombrada a «' . $nombre . '». Sus productos se actualizaron.'
                : 'No se pudo renombrar la categoría.';
        } else {
            $ok = $modelo->crear($nombre);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Categoría «' . $nombre . '» agregada. Ya aparece al crear o editar un producto.'
                : 'No se pudo agregar la categoría.';
        }
        $this->redirect('almacen/categorias');
    }

    /** POST: elimina una categoría que no tenga productos */
    public function eliminarCategoria(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('almacen/categorias');
        }

        $modelo    = new CategoriaModel();
        $categoria = $modelo->findById((int)($_POST['id'] ?? 0));

        if (!$categoria) {
            $_SESSION['flash_error'] = 'La categoría no existe.';
        } elseif (($n = $modelo->contarProductos($categoria['nombre'])) > 0) {
            $_SESSION['flash_error'] = 'No se puede eliminar «' . $categoria['nombre'] . '»: tiene ' . $n . ' producto(s). Cámbialos de categoría primero.';
        } else {
            $ok = $modelo->eliminar((int)$categoria['id']);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Categoría «' . $categoria['nombre'] . '» eliminada.'
                : 'No se pudo eliminar la categoría.';
        }
        $this->redirect('almacen/categorias');
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

        if ($datos['categoria'] !== '') {
            // Se guarda con el nombre exacto de la categoría registrada
            $registrada = null;
            foreach ((new CategoriaModel())->getNombres() as $cat) {
                if (mb_strtolower($cat) === mb_strtolower($datos['categoria'])) { $registrada = $cat; break; }
            }
            if ($registrada === null) {
                $datos['error'] = 'La categoría «' . $datos['categoria'] . '» no existe. Créala primero en Categorías.';
                return $datos;
            }
            $datos['categoria'] = $registrada;
        }

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
