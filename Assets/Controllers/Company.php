<?php
require_once "../Models/Company.php";

$company = new Company();

$id_negocio = 1;
$nombre = isset($_POST["nombre"]) ? $_POST["nombre"] : "";
$ndocumento = isset($_POST["ndocumento"]) ? $_POST["ndocumento"] : "";
$documento = isset($_POST["documento"]) ? $_POST["documento"] : "";
$direccion = isset($_POST["direccion"]) ? $_POST["direccion"] : "";
$telefono = isset($_POST["telefono"]) ? $_POST["telefono"] : "";
$email = isset($_POST["email"]) ? $_POST["email"] : "";
$pais = isset($_POST["pais"]) ? $_POST["pais"] : "";
$ciudad = isset($_POST["ciudad"]) ? $_POST["ciudad"] : "";
$nombre_impuesto = isset($_POST["nombre_impuesto"]) ? $_POST["nombre_impuesto"] : "";
$monto_impuesto = isset($_POST["monto_impuesto"]) ? $_POST["monto_impuesto"] : "";
$moneda = isset($_POST["moneda"]) ? $_POST["moneda"] : "";
$simbolo = isset($_POST["simbolo"]) ? $_POST["simbolo"] : "";
$imagen = isset($_POST["imagen"]) ? $_POST["imagen"] : "";

switch ($_GET["op"]) {
	case 'guardaryeditar':
		if (!file_exists($_FILES['imagen']['tmp_name']) || !is_uploaded_file($_FILES['imagen']['tmp_name'])) {
			$imagen = $_POST["imagenactual"];
		} else {
			$ext = explode(".", $_FILES["imagen"]["name"]);
			if ($_FILES['imagen']['type'] == "image/jpg" || $_FILES['imagen']['type'] == "image/jpeg" || $_FILES['imagen']['type'] == "image/png") {
				$imagen = round(microtime(true)) . '.' . end($ext);
				move_uploaded_file($_FILES["imagen"]["tmp_name"], "../Assets/img/company/" . $imagen);
			}
		}

		$rspta = $company->editar($id_negocio, $nombre, $ndocumento, $documento, $direccion, $telefono, $email, $imagen, $pais, $ciudad, $nombre_impuesto, $monto_impuesto, $moneda, $simbolo);
		echo $rspta ? "Datos actualizados correctamente" : "No se pudo actualizar los datos";
		break;

	case 'mostrar':
		$rspta = $company->mostrar($id_negocio);
		echo json_encode($rspta);
		break;

	case 'mostrar_impuesto':
		$rspta = $company->mostrar_impuesto();
		$data = array();

		foreach ($rspta as $reg) {
			$data[] = array(
				$numeroimp = $reg['monto_impuesto']

			);
		}
		$impuesto = (floatval($numeroimp));
		echo json_encode($impuesto);
		break;

	case 'nombre_impuesto':
		$rspta = $company->nombre_impuesto();
		$data = array();

		foreach ($rspta as $reg) {
			$data[] = array(
				$nombreimp = $reg['nombre_impuesto']

			);
		}
		echo json_encode($nombreimp);
		break;

	case 'mostrar_simbolo':
		$rspta = $company->mostrar_simbolo();
		$data = array();

		foreach ($rspta as $reg) {
			$data[] = array(
				$simbolo = $reg['simbolo']
			);
		}
		echo json_encode($simbolo);
		break;

	case 'mostrar_datos':
		$id_negocio = 1;
		$rspta = $company->mostrar($id_negocio);
		echo json_encode($rspta);
		break;

		/*case 'listar':
		$rspta = $company->listar();
		$data = array();

		foreach ($rspta as $reg) {
			$data[] = array(
				"0" => '<button class="btn btn-warning btn-xs" id="btn_lista" onclick="mostrar(' . $reg['id_negocio'] . ')"><i class="fas fa-edit"></i></button>',
				"1" => "<img src='Assets/img/company/" . $reg['logo'] . "' height='50px' width='50px'>",
				"2" => $reg['nombre'],
				"3" => $reg['ndocumento'] . '-' . $reg['documento'],
				"4" => $reg['direccion'],
				"5" => $reg['telefono'],
				"6" => $reg['email'],
				"7" => $reg['ciudad'] . '-' . $reg['pais'],
				"8" => $reg['nombre_impuesto'] . ' ' . $reg['monto_impuesto'] . ' %',
				"9" => $reg['simbolo'] . '- ' . $reg['moneda'],
				"10" => ($reg['condicion']) ? '<span class="label bg-green">Activado</span>' : '<span class="label bg-red">Desactivado</span>'
			);
		}
		$results = array(
			"sEcho" => 1, //info para datatables
			"iTotalRecords" => count($data), //enviamos el total de registros al datatable
			"iTotalDisplayRecords" => count($data), //enviamos el total de registros a visualizar
			"aaData" => $data
		);
		echo json_encode($results);
		break;*/
}
