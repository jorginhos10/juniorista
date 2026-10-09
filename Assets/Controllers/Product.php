<?php
require_once "../Models/Product.php";

$product = new Product();

$idarticulo = isset($_POST["idarticulo"]) ? $_POST["idarticulo"] : "";
$idcategoria = isset($_POST["idcategoria"]) ? $_POST["idcategoria"] : "";
$codigo = isset($_POST["codigo"]) ? $_POST["codigo"] : "";
$nombre = isset($_POST["nombre"]) ? $_POST["nombre"] : "";
//$stock = isset($_POST["stock"]) ? $_POST["stock"] : "";
$descripcion = isset($_POST["descripcion"]) ? $_POST["descripcion"] : "";
$imagen = isset($_POST["imagen"]) ? $_POST["imagen"] : "";

switch ($_GET["op"]) {
	case 'guardaryeditar':

		if (!file_exists($_FILES['imagen']['tmp_name']) || !is_uploaded_file($_FILES['imagen']['tmp_name'])) {
			(empty($_POST["imagenactual"])) ? $imagen = 'default.png' : $imagen = $_POST["imagenactual"];
		} else {
			if (!empty($_POST["imagenactual"]) && $_POST["imagenactual"] != 'default.png') {
				unlink("../Assets/img/products/" . $_POST["imagenactual"]);
			}
			$ext = explode(".", $_FILES["imagen"]["name"]);
			if ($_FILES['imagen']['type'] == "image/jpg" || $_FILES['imagen']['type'] == "image/jpeg" || $_FILES['imagen']['type'] == "image/png") {
				$imagen = round(microtime(true)) . '.' . end($ext);
				move_uploaded_file($_FILES["imagen"]["tmp_name"], "../Assets/img/products/" . $imagen);
			}
		}
		if (empty($idarticulo)) {
			$rspta = $product->insertar($idcategoria, $codigo, $nombre, $descripcion, $imagen);
			echo $rspta ? "Datos registrados correctamente" : "No se pudo registrar los datos";
		} else {
			$rspta = $product->editar($idarticulo, $idcategoria, $codigo, $nombre, $descripcion, $imagen);
			echo $rspta ? "Datos actualizados correctamente" : "No se pudo actualizar los datos";
		}
		break;


	case 'desactivar':
		$rspta = $product->desactivar($idarticulo);
		echo $rspta ? "Datos desactivados correctamente" : "No se pudo desactivar los datos";
		break;

	case 'activar':
		$rspta = $product->activar($idarticulo);
		echo $rspta ? "Datos activados correctamente" : "No se pudo activar los datos";
		break;

	case 'validarSotck':
		$idarticulo = $_REQUEST['idarticulo'];
		$rspta = $product->validarSotck($idarticulo);
		echo json_encode($rspta);
		break;

	case 'quitarArticulo':
		$idarticulo = $_REQUEST['idarticulo'];
		$cantidad = $_REQUEST['cantidad'];
		$rspta = $product->quitarArticulo($idarticulo, $cantidad);
		echo $rspta ? "Stock actualizado" : "No se pudo quitar el artículo";
		break;

	case 'aumentarArticulo':
		$idarticulo = $_REQUEST['idarticulo'];
		$cantidad = $_REQUEST['cantidad'];
		$rspta = $product->aumentarArticulo($idarticulo, $cantidad);
		echo $rspta ? "Stock actualizado" : "No se pudo quitar el artículo";
		break;

	case 'mostrar':
		$rspta = $product->mostrar($idarticulo);
		echo json_encode($rspta);
		break;

	case 'listar':
		$rspta = $product->listar();
		$data = array();

		foreach ($rspta as $reg) {
			$stockcolor = '';
			if ($reg['stock'] == 0) {
				$stockcolor = '<a href="buy"> <button class="btn btn-info btn-sm"><i class="fas fa-plus"></i> Comprar</button></a>';
			} elseif ($reg['stock'] <= 10) {
				$stockcolor = '<button class="btn btn-danger btn-sm">' . $reg['stock'] . '</button>';
			} elseif ($reg['stock'] > 10 && $reg['stock'] < 30) {
				$stockcolor = '<button class="btn btn-warning btn-sm">' . $reg['stock'] . '</button>';
			} elseif ($reg['stock'] >= 30) {
				$stockcolor = '<button class="btn btn-success btn-sm">' . $reg['stock'] . '</button>';
			}

			$data[] = array(
				"0" => ($reg['condicion']) ? '<button class="btn btn-warning btn-sm" onclick="mostrar(' . $reg['idarticulo'] . ')"><i class="fas fa-pencil-alt"></i></button>' . ' ' . '<button class="btn btn-danger btn-sm" onclick="desactivar(' . $reg['idarticulo'] . ')"><i class="fas fa-times"></i></button>' : '<button class="btn btn-warning btn-sm" onclick="mostrar(' . $reg['idarticulo'] . ')"><i class="fas fa-pencil-alt"></i></button>' . ' ' . '<button class="btn btn-primary btn-sm" onclick="activar(' . $reg['idarticulo'] . ')"><i class="fas fa-check"></i></button>',
				"1" => $reg['nombre'],
				"2" => $reg['categoria'],
				"3" => $reg['codigo'],
				"4" => $stockcolor,
				"5" => "<img src='Assets/img/products/" . $reg['imagen'] . "' height='50px' width='50px'>",
				"6" => $reg['descripcion'],
				"7" => ($reg['precio_compra']) ? $reg['precio_compra'] : '0.00',
				"8" => ($reg['precio_venta']) ? $reg['precio_venta'] : '0.00',
				"9" => ($reg['condicion']) ? '<div class="badge badge-success">Activo</div>' : '<div class="badge badge-danger">Inactivo</div>'
			);
		}
		$results = array(
			"sEcho" => 1, //info para datatables
			"iTotalRecords" => count($data), //enviamos el total de registros al datatable
			"iTotalDisplayRecords" => count($data), //enviamos el total de registros a visualizar
			"aaData" => $data
		);
		echo json_encode($results);
		break;
}
