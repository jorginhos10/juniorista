<?php
require_once "../Models/Sell.php";
if (strlen(session_id()) < 1)
	session_start();

$sell = new Sell();

$idventa = isset($_POST["idventa"]) ? $_POST["idventa"] : "";
$idcliente = isset($_POST["idcliente"]) ? $_POST["idcliente"] : "";
$idusuario = $_SESSION["idusuario"];
$tipo_comprobante = isset($_POST["tipo_comprobante"]) ? $_POST["tipo_comprobante"] : "";
//$serie_comprobante=isset($_POST["serie_comprobante"])? $_POST["serie_comprobante"]:"";
$fecha_hora = isset($_POST["fecha_hora"]) ? $_POST["fecha_hora"] : "";
$impuesto = isset($_POST["impuesto"]) ? $_POST["impuesto"] : "";
$total_venta = isset($_POST["total_venta"]) ? $_POST["total_venta"] : "";
$tipo_pago = isset($_POST["tipo_pago"]) ? $_POST["tipo_pago"] : "";
$num_transac = isset($_POST["num_transac"]) ? $_POST["num_transac"] : "";

$idarticuloEliminado = isset($_POST["idarticuloEliminado"]) ? $_POST["idarticuloEliminado"] : $idarticuloEliminado = array();
$cantidadEliminado = isset($_POST["cantidadEliminado"]) ? $_POST["cantidadEliminado"] : $cantidadEliminado = array();



switch ($_GET["op"]) {
	case 'guardaryeditar':
		if (empty($idventa)) {
			$rspta = $sell->insertar($idcliente, $idusuario, $tipo_comprobante, $fecha_hora, $impuesto, $total_venta, $tipo_pago, $num_transac, $_POST["idarticulo"], $_POST["cantidad"], $_POST["precio_compra"], $_POST["precio_venta"], $_POST["descuento"]);
			echo $rspta ? "Datos registrados correctamente" : "No se pudo registrar los datos";
		} else {
			$rspta = $sell->editar($idventa, $idcliente, $tipo_comprobante, $fecha_hora, $impuesto, $total_venta, $tipo_pago, $num_transac, $_POST["idarticulo"], $_POST["nuevostock"], $_POST["cantidad"], $_POST["precio_compra"], $_POST["precio_venta"], $_POST["descuento"], $idarticuloEliminado, $cantidadEliminado);
			echo $rspta ? "Datos registrados correctamente" : "No se pudo registrar los datos";
		}
		break;

	case 'anular':
		$rspta = $sell->anular($idventa);
		echo $rspta ? "Ingreso anulado correctamente" : "No se pudo anular el ingreso";
		break;

	case 'mostrar':
		$rspta = $sell->mostrar($idventa);
		echo json_encode($rspta);
		break;


	case 'listarDetalle':
		require_once "../Models/Company.php";
		$cnegocio = new Company();
		$rsptan = $cnegocio->listar();
		//$regn=$rsptan->fetch_object();
		if (empty($rsptan)) {
			$smoneda = 'Simbolo de moneda';
		} else {
			$smoneda = $rsptan[0]['simbolo'];
			$nom_imp = $rsptan[0]['nombre_impuesto'];
		};
		//recibimos el idventa
		$id = $_GET['id'];

		$rspta = $sell->listarDetalle($id);
		$total = 0;

		echo ' <thead style="background-color:#A9D0F5">
        <th>Opciones</th>
        <th>Articulo</th>
        <th>Cantidad</th>
        <th>Precio Venta</th>
        <th>Descuento</th>
        <th>Subtotal</th>
       </thead>';
		foreach ($rspta as $reg) {
			echo '<tr class="filas">
			<td></td>
			<td>' . $reg['nombre'] . '</td>
			<td>' . $reg['cantidad'] . '</td> 
			<td>' . $reg['precio_venta'] . '</td>
			<td>' . $reg['descuento'] . '</td>
			<td>' . $reg['subtotal'] . '</td></tr>';
			$total = $total + ($reg['precio_venta'] * $reg['cantidad'] - $reg['descuento']);
			$t_venta = $reg['total_venta'];
			$imp = $reg['impuesto'];
			$most_igv = $t_venta - $total;
		}
		echo '<tfoot>
        <th><span>SubTotal</span><br><span id="valor_impuestoc">' . $nom_imp . ' ' . $imp . ' %</span><br><span>TOTAL</span></th>
         <th></th>
         <th></th>
         <th></th>
         <th></th>
         <th><span id="total">' . $smoneda . ' ' . number_format((float)$total, 2, '.', '') . '</span><br><span id="most_imp">' . $smoneda . ' ' . number_format((float)$most_igv, 2, '.', '') . '</span><br><span id="most_total" maxlength="4">' . $smoneda . ' ' . $t_venta . '</span></th>
       </tfoot>';
		break;

	case 'listarDetalle_editar':
		require_once "../Models/Company.php";
		$cnegocio = new Company();
		$rsptan = $cnegocio->listar();
		$regn = $rsptan[0];
		if (empty($regn)) {
			$smoneda = 'Simbolo de moneda';
		} else {
			$smoneda = $regn['simbolo'];
			$nom_imp = $regn['nombre_impuesto'];
		};
		//recibimos el idventa
		$id = $_GET['id'];

		$rspta = $sell->listarDetalle($id);
		$total = 0;
		$data = array();

		foreach ($rspta as $reg) {
			$data[] = array(
				"Idarticulo" => $reg['idarticulo'],
				"Articulo" => $reg['nombre'],
				"Pcompra" => $reg['precio_compra'],
				"Pventa" => $reg['precio_venta'],
				"Cantidad" => $reg['cantidad'],
				"Stock" => $reg['stock'],
			);
		}
		$results = array(
			"Datos" => $data
		);
		echo json_encode($data);
		break;

	case 'listar':
		$rspta = $sell->listar();
		$data = array();

		foreach ($rspta as $reg) {

			$op = $reg['num_transac'] ? '<strong> N°:' . $reg['num_transac'] . '</strong>' : '';
			$urlt = 'Reports/ticket.php?id=';
			$url = 'Reports/exFactura.php?id=';

			$data[] = array(
				"0" => (($reg['estado'] == 'Aceptado') ? '<button class="btn btn-info btn-sm" onclick="mostrar(' . $reg['idventa'] . ')"><i class="fas fa-eye"></i></button>' . ' ' . '<a href="editsale?op=new&id=' . $reg['idventa'] . '"> <button class="btn btn-warning btn-sm"><i class="fas fa-pen"></i></button></a>' . ' ' . '<button class="btn btn-danger btn-sm" onclick="anular(' . $reg['idventa'] . ')"><i class="fas fa-times"></i></button>' : '<button class="btn btn-info btn-sm" onclick="mostrar(' . $reg['idventa'] . ')"><i class="fas fa-eye"></i></button>') .
					'<a target="_blank" href="' . $url . $reg['idventa'] . '"> <button class="btn btn-primary btn-sm"><i class="fas fa-file-pdf"></i></button></a>' . ' ' . '<a target="_blank" href="' . $urlt . $reg['idventa'] . '"> <button class="btn btn-success btn-sm"><i class="fas fa-print"></i></button></a>',
				"1" => $reg['fecha'],
				"2" => $reg['cliente'],
				"3" => $reg['usuario'],
				"4" => '<strong>' . $reg['tipo_comprobante'] . ': </strong>' . '' . $reg['serie_comprobante'] . '-' . $reg['num_comprobante'],
				"5" => $reg['tipo_pago'] . ' ' . $op,
				"6" => $reg['total_venta'],
				"7" => ($reg['estado'] == 'Aceptado') ? '<div class="badge badge-success">Aceptado</div>' : '<div class="badge badge-danger">Anulado</div>'
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


	case 'selectCliente':
		require_once "../Models/Person.php";
		$persona = new Person();

		$rspta = $persona->listarc();
		echo '<option value="">seleccione...</option>';
		foreach ($rspta as $reg) {

			echo '<option value=' . $reg['idpersona'] . '>' . $reg['nombre'] . '</option>';
		}
		break;

	case 'cantidad_articulos':
		require_once "../Models/Product.php";
		$articulo = new Product();
		$rsptav = $articulo->cantidadarticulos();

		echo json_encode($rsptav);
		break;

	case 'listarArticulos':
		require_once "../Models/Product.php";
		$articulo = new Product();

		$rspta = $articulo->listarActivosVenta();
		$data = array();

		foreach ($rspta as $reg) {
			$btncolor = '';
			if ($reg['stock'] <= 10) {
				$btncolor = '<button class="btn btn-danger btn-sm">' . $reg['stock'] . '</button>';
			} elseif ($reg['stock'] > 10 && $reg['stock'] < 30) {
				$btncolor = '<button class="btn btn-warning btn-sm">' . $reg['stock'] . '</button>';
			} elseif ($reg['stock'] >= 30) {
				$btncolor = '<button class="btn btn-success btn-sm">' . $reg['stock'] . '</button>';
			}
			$data[] = array(
				"0" => '<button class="btn btn-success btn-sm" id="addetalle" name="' . $reg['idarticulo'] . '" onclick="agregarDetalle(' . $reg['idarticulo'] . ',\'' . $reg['nombre'] . '\',' . $reg['precio_compra'] . ',' . $reg['precio_venta'] . ',' . $reg['stock'] . ')"><span class="fas fa-cart-plus"></span></button>',
				"1" => $reg['nombre'],
				"2" => $reg['codigo'],
				"3" => $btncolor,
				"4" => "<img src='Assets/img/products/" . $reg['imagen'] . "' height='40px' width='40px'>"

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


	case 'selectComprobante':
		require_once "../Models/Voucher.php";
		$comprobantes = new Voucher();

		$rspta = $comprobantes->select();
		echo '<option value="">Seleccione...</option>';
		foreach ($rspta as $reg) {
			echo '<option value="' . $reg['nombre'] . '">' . $reg['nombre'] . '</option>';
		}
		break;
}
