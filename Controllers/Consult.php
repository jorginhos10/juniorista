<?php
require_once "../Models/Consult.php";

$consult = new Consult();

switch ($_GET["op"]) {


  case 'comprasfecha':
    $fecha_inicio = $_REQUEST["fecha_inicio"];
    $fecha_fin = $_REQUEST["fecha_fin"];

    $rspta = $consult->comprasfecha($fecha_inicio, $fecha_fin);
    $data = array();

    foreach ($rspta as $reg) {
      $data[] = array(
        "0" => $reg['fecha'],
        "1" => $reg['usuario'],
        "2" => $reg['proveedor'],
        "3" => $reg['tipo_comprobante'],
        "4" => $reg['serie_comprobante'] . ' ' . $reg['num_comprobante'],
        "5" => $reg['total_compra'],
        "6" => $reg['impuesto'],
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

  case 'ventasfechacliente':
    $fecha_inicio = $_REQUEST["fecha_inicio"];
    $fecha_fin = $_REQUEST["fecha_fin"];
    $idcliente = $_REQUEST["idcliente"];

    $rspta = $consult->ventasfechacliente($fecha_inicio, $fecha_fin, $idcliente);
    $data = array();

    foreach ($rspta as $reg) {
      $op = $reg['num_transac'] ? '<strong> N°:' . $reg['num_transac'] . '</strong>' : '';
      $data[] = array(
        "0" => $reg['fecha'],
        "1" => $reg['usuario'],
        "2" => $reg['cliente'],
        "3" => '<strong>' . $reg['tipo_comprobante'] . ': </strong>' . '' . $reg['serie_comprobante'] . '-' . $reg['num_comprobante'],
        "4" => $reg['tipo_pago'] . ' ' . $op,
        "5" => $reg['total_venta'],
        "6" => $reg['impuesto'],
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

  case 'listaventasarticulos':
    $fecha_inicio = $_REQUEST["fecha_inicio"];
    $fecha_fin = $_REQUEST["fecha_fin"];
    $rspta = $consult->listaventasarticulos($fecha_inicio, $fecha_fin);
    $data = array();
    $item = 1;
    foreach ($rspta as $reg) {

      $data[] = array(
        "0" => $item++,
        "1" => $reg['codigo'],
        "2" => $reg['articulo'],
        "3" => $reg['precio_venta'],
        "4" => $reg['cantidad'],
        "5" => $reg['subtotal']
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

  case 'listacomprasarticulos':
    $fecha_inicio = $_REQUEST["fecha_inicio"];
    $fecha_fin = $_REQUEST["fecha_fin"];
    $rspta = $consult->listacomprasarticulos($fecha_inicio, $fecha_fin);
    $data = array();
    $item = 1;
    foreach ($rspta as $reg) {

      $data[] = array(
        "0" => $item++,
        "1" => $reg['codigo'],
        "2" => $reg['articulo'],
        "3" => $reg['precio_compra'],
        "4" => $reg['cantidad'],
        "5" => $reg['subtotal']
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
