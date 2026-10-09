<?php
//incluir la conexion de base de datos
require_once "Connect.php";
class Consult
{


  private $tableName = 'categoria';
  private $conexion;

  //implementamos nuestro constructor
  public function __construct()
  {
    $this->conexion = new Conexion();
  }

  //listar registros
  public function comprasfecha($fecha_inicio, $fecha_fin)
  {
    $sql = "SELECT DATE(i.fecha_hora) as fecha, u.nombre as usuario, p.nombre as proveedor, i.tipo_comprobante, i.serie_comprobante, i.num_comprobante, i.total_compra,i.impuesto,i.estado FROM ingreso i INNER JOIN persona p ON i.idproveedor=p.idpersona INNER JOIN usuario u ON i.idusuario=u.idusuario WHERE DATE(i.fecha_hora)>='$fecha_inicio' AND DATE(i.fecha_hora)<='$fecha_fin'";
    return  $this->conexion->getDataAll($sql);
  }

  public function ventasfechacliente($fecha_inicio, $fecha_fin, $idcliente)
  {
    if (empty($idcliente)) {
      $sql = "SELECT DATE(v.fecha_hora) as fecha, u.nombre as usuario, p.nombre as cliente, v.tipo_comprobante,v.serie_comprobante, v.num_comprobante , v.total_venta, v.impuesto, v.estado,v.tipo_pago,v.num_transac FROM venta v INNER JOIN persona p ON v.idcliente=p.idpersona INNER JOIN usuario u ON v.idusuario=u.idusuario WHERE DATE(v.fecha_hora)>='$fecha_inicio' AND DATE(v.fecha_hora)<='$fecha_fin'";
    } else {
      $sql = "SELECT DATE(v.fecha_hora) as fecha, u.nombre as usuario, p.nombre as cliente, v.tipo_comprobante,v.serie_comprobante, v.num_comprobante , v.total_venta, v.impuesto, v.estado,v.tipo_pago,v.num_transac FROM venta v INNER JOIN persona p ON v.idcliente=p.idpersona INNER JOIN usuario u ON v.idusuario=u.idusuario WHERE DATE(v.fecha_hora)>='$fecha_inicio' AND DATE(v.fecha_hora)<='$fecha_fin' AND v.idcliente='$idcliente'";
    }

    return  $this->conexion->getDataAll($sql);
  }

  public function totalcomprahoy()
  {
    $sql = "SELECT IFNULL(SUM(total_compra),0) as total_compra FROM ingreso WHERE DATE(fecha_hora)=curdate() AND estado='Aceptado'";
    return  $this->conexion->getDataAll($sql);
  }

  public function totalventahoy()
  {
    $sql = "SELECT IFNULL(SUM(total_venta),0) as total_venta FROM venta WHERE DATE(fecha_hora)=curdate() AND estado='Aceptado'";
    return  $this->conexion->getDataAll($sql);
  }

  public function comprasultimos_10dias()
  {
    $sql = "SELECT CONCAT(DAY(fecha_hora),'-',MONTH(fecha_hora)) AS fecha, SUM(total_compra) AS total FROM ingreso  WHERE estado='Aceptado' GROUP BY fecha_hora ORDER BY fecha_hora DESC LIMIT 0,10";
    return  $this->conexion->getDataAll($sql);
  }

  public function ventasultimos_10dias()
  {
    $sql = "SELECT CONCAT(DAY(fecha_hora),'-',MONTH(fecha_hora)) AS fecha, SUM(total_venta) AS total FROM venta WHERE estado='Aceptado' GROUP BY fecha_hora ORDER BY fecha_hora DESC LIMIT 0,10";
    return  $this->conexion->getDataAll($sql);
  }

  public function ventasultimos_12meses()
  {
    $sql = "SELECT DATE_FORMAT(fecha_hora,'%M') AS fecha, SUM(total_venta) AS total FROM venta WHERE estado='Aceptado' GROUP BY MONTH(fecha_hora) ORDER BY fecha_hora DESC LIMIT 0,12";
    return  $this->conexion->getDataAll($sql);
  }

  public function ventasultimos_12meses_grafica()
  {

    $sql = " SELECT DATE_FORMAT(fecha_hora,'%M') AS fecha, SUM(total_venta) AS total FROM venta WHERE estado='Aceptado' GROUP BY MONTH(fecha_hora) ORDER BY fecha_hora DESC LIMIT 0,12";

    return  $this->conexion->getDataAll($sql);
  }

  public function comparsultimos_12meses_grafica()
  {
    $sql = "SELECT DATE_FORMAT(fecha_hora,'%M') AS fecha, SUM(total_compra) AS total FROM ingreso WHERE estado='Aceptado' GROUP BY MONTH(fecha_hora) ORDER BY fecha_hora DESC LIMIT 0,12";
    return  $this->conexion->getDataAll($sql);
  }

  public function ventas_grafica()
  {
    $sql = "SELECT DATE(fecha_hora) AS fecha, SUM(total_venta) AS total FROM venta WHERE estado='Aceptado' GROUP BY MONTH(fecha_hora) ORDER BY fecha_hora DESC LIMIT 0,12";
    return  $this->conexion->getDataAll($sql);
  }

  public function compras_grafica()
  {
    $sql = "SELECT DATE(fecha_hora) AS fecha, SUM(total_compra) AS total FROM ingreso WHERE estado='Aceptado' GROUP BY MONTH(fecha_hora) ORDER BY fecha_hora DESC LIMIT 0,12";
    return  $this->conexion->getDataAll($sql);
  }

  public function cantidadclientes()
  {
    $sql = "SELECT COUNT(*) totalc FROM persona WHERE tipo_persona='Cliente'";
    return  $this->conexion->getDataAll($sql);
  }

  public function cantidadproveedores()
  {
    $sql = "SELECT COUNT(*) totalp FROM persona WHERE tipo_persona='Proveedor'";
    return  $this->conexion->getDataAll($sql);
  }

  public function cantidadarticulos()
  {
    $sql = "SELECT COUNT(*) totalar FROM articulo WHERE condicion=1";
    return  $this->conexion->getDataAll($sql);
  }
  public function totalstock()
  {
    $sql = "SELECT SUM(stock) AS totalstock FROM articulo WHERE condicion=1";
    return  $this->conexion->getDataAll($sql);
  }

  public function cantidadcategorias()
  {
    $sql = "SELECT COUNT(*) totalca FROM categoria WHERE condicion=1";
    return  $this->conexion->getDataAll($sql);
  }

  public function listaventasarticulos($fecha_inicio, $fecha_fin)
  {
    $sql = "SELECT a.nombre AS articulo, a.codigo, SUM(d.cantidad) AS cantidad, d.precio_venta, d.descuento, SUM(d.cantidad*d.precio_venta-d.descuento) AS subtotal FROM detalle_venta d INNER JOIN articulo a ON d.idarticulo=a.idarticulo INNER JOIN venta v ON v.idventa=d.idventa WHERE DATE(v.fecha_hora)>='$fecha_inicio' AND DATE(v.fecha_hora)<='$fecha_fin' AND d.estado=1 GROUP BY a.codigo";
    return  $this->conexion->getDataAll($sql);
  }

  public function listacomprasarticulos($fecha_inicio, $fecha_fin)
  {
    $sql = "SELECT a.nombre AS articulo, a.codigo, SUM(d.cantidad) AS cantidad, d.precio_compra, SUM(d.cantidad*d.precio_compra) AS subtotal FROM detalle_ingreso d INNER JOIN articulo a ON d.idarticulo=a.idarticulo INNER JOIN ingreso i ON i.idingreso=d.idingreso WHERE DATE(i.fecha_hora)>='$fecha_inicio' AND DATE(i.fecha_hora)<='$fecha_fin' AND d.estado=1 GROUP BY a.codigo";
    return  $this->conexion->getDataAll($sql);
  }

  //VER CANTIDAD DE ARTICULOS MAS VENDIDOS
  public function articuloMasVendidos()
  {
    $sql = "SELECT a.imagen,a.stock, a.codigo, a.nombre, IFNULL(SUM(dv.cantidad),0) AS cantidad_vendidos,IFNULL(SUM(dv.precio_venta*dv.cantidad),0) AS precio_venta FROM detalle_venta dv INNER JOIN articulo a ON dv.idarticulo=a.idarticulo INNER JOIN venta v ON dv.idventa=v.idventa WHERE dv.estado=1 AND MONTH(v.fecha_hora) = MONTH(CURRENT_DATE()) GROUP BY dv.idarticulo LIMIT 0,5";
    return  $this->conexion->getDataAll($sql);
  }

  //VER EL TOTAL DE VENTAS DE AYER Y HOY
  public function ventasDia($tiempo, $fecha)
  {

    $param = 'Aceptado';
    if ($tiempo == 'Ayer') {
      $sql = "SELECT IFNULL(SUM(total_venta),0) AS total_ventas FROM venta WHERE fecha_hora = DATE_SUB(CONCAT('$fecha', ' 00:00:00'), INTERVAL 1 DAY) AND estado=?";
      $arrData = array($param);
      return  $this->conexion->getData($sql, $arrData);
    } else {
      $sql = "SELECT IFNULL(SUM(total_venta),0) AS total_ventas FROM venta WHERE fecha_hora = '$fecha' AND estado=?";
      $arrData = array($param);
      return  $this->conexion->getData($sql, $arrData);
    }
  }

  //VER EL TOTAL DE VENTAS DE LA SEMANA PASADA Y SEMANA ACTUAL
  public function ventasSemana($tiempo, $fecha)
  {

    $param = 'Aceptado';
    if ($tiempo == 'Ayer') {
      $sql = "SELECT IFNULL(SUM(total_venta),0) AS total_ventas FROM venta WHERE YEARWEEK(fecha_hora) = YEARWEEK(NOW() - INTERVAL 1 WEEK) AND estado=?";
      $arrData = array($param);
      return  $this->conexion->getData($sql, $arrData);
    } else {
      $sql = "SELECT IFNULL(SUM(total_venta),0) AS total_ventas FROM venta WHERE YEARWEEK (fecha_hora) = YEARWEEK ('$fecha') AND estado=?";
      $arrData = array($param);
      return  $this->conexion->getData($sql, $arrData);
    }
  }

  //VER EL TOTAL DE VENTAS DEL MES PASADO Y ACTUAL
  public function ventasMes($tiempo, $fechaActual)
  {

    $param = 'Aceptado';
    if ($tiempo == 'Ayer') {
      $sql = "SELECT IFNULL(SUM(total_venta),0) AS total_ventas FROM venta WHERE YEAR(fecha_hora) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND MONTH(fecha_hora) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) AND estado=?";
      $arrData = array($param);
      return  $this->conexion->getData($sql, $arrData);
    } else {

      $sql = "SELECT IFNULL(SUM(total_venta),0) AS total_ventas FROM venta WHERE MONTH(fecha_hora) = MONTH(CURRENT_DATE()) AND estado=?";
      $arrData = array($param);
      return  $this->conexion->getData($sql, $arrData);
    }
  }


  //TOTAL VENTAS
  public function totalVentas()
  {
    $sql = "SELECT IFNULL(SUM(total_venta),0) as total_venta FROM venta WHERE estado=?";
    $arrData = array('Aceptado');
    return  $this->conexion->getData($sql, $arrData);
  }

  //TOTAL COMPRAS
  public function totalCompras()
  {
    $sql = "SELECT IFNULL(SUM(total_compra),0) as total_compra FROM ingreso WHERE estado=?";
    $arrData = array('Aceptado');
    return  $this->conexion->getData($sql, $arrData);
  }

  //GANANCIAS TOTALES 
  public function totalGanancia()
  {
    $sql = "SELECT IFNULL(SUM(dv.precio_compra*dv.cantidad),0) AS precio_compra, IFNULL(SUM(dv.precio_venta*dv.cantidad),0) AS precio_venta FROM detalle_venta dv INNER JOIN articulo a ON dv.idarticulo=a.idarticulo INNER JOIN venta v ON dv.idventa=v.idventa WHERE dv.estado=? AND v.estado='Aceptado'";
    $arrData = array(1);
    return  $this->conexion->getData($sql, $arrData);
  }


  public function ingresos_grafica()
  {
    $sql = "SELECT IFNULL(SUM(dv.precio_compra*dv.cantidad),0) AS precio_compra, IFNULL(SUM(dv.precio_venta*dv.cantidad),0) AS precio_venta,DATE(v.fecha_hora) AS fecha FROM detalle_venta dv INNER JOIN articulo a ON dv.idarticulo=a.idarticulo INNER JOIN venta v ON dv.idventa=v.idventa WHERE v.estado='Aceptado' GROUP BY v.fecha_hora ORDER BY v.fecha_hora DESC LIMIT 0,10";
    return  $this->conexion->getDataAll($sql);
  }
}
