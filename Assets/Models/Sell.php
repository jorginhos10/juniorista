<?php
//incluir la conexion de base de datos
//incluir la conexion de base de datos
require_once "Connect.php";
class Sell
{

    private $tableName = 'venta';
    private $tableNameDetalle = 'detalle_venta';
    private $conexion;

    //implementamos nuestro constructor
    public function __construct()
    {
        $this->conexion = new Conexion();
    }

    //metodo insertar registro
    public function insertar($idcliente, $idusuario, $tipo_comprobante, $fecha_hora, $impuesto, $total_venta, $tipo_pago, $num_transac, $idarticulo, $cantidad, $precio_compra, $precio_venta, $descuento)
    {

        $sql_vSerie = "SELECT serie_comprobante,num_comprobante FROM venta WHERE tipo_comprobante=? ORDER BY idventa DESC limit 1";
        $arrData = array($tipo_comprobante);
        $vNumero = $this->conexion->getData($sql_vSerie, $arrData);

        //NUMERO DE COMPROBANTE
        $serieVenta = isset($vNumero['serie_comprobante']) ? $serieVenta = $vNumero['serie_comprobante'] : null;
        $numeroVenta = isset($vNumero['num_comprobante']) ? $numeroVenta = $vNumero['num_comprobante'] : null;

        if (empty($serieVenta)) {
            $sql_compSerie = "SELECT serie_comprobante,num_comprobante FROM comp_pago WHERE nombre=?";

            $arrData = array($tipo_comprobante);
            $vNumero = $this->conexion->getData($sql_compSerie, $arrData);
            $serieVenta = $vNumero['serie_comprobante'];
            $numeroVenta = $vNumero['num_comprobante'];
        }

        //validamos si el numero de comprobante de la venta ya llego al limite para ir a la siguiente numeracion
        if ($numeroVenta == 9999999 or empty($numeroVenta)) {
            (int)$num_comprobante = '0000001';
            $serie_comprobante = substr(str_repeat(0, 3) . $serieVenta + 1, -3);
        } elseif ($numeroVenta == 9999999) {
            (int)$num_comprobante = '0000001';
            $serie_comprobante = substr(str_repeat(0, 3) . $serieVenta + 1, -3);
        } else {
            $numero = $numeroVenta + 1;
            $num_comprobante = substr(str_repeat(0, 7) . $numero, -7);

            $serie = $serieVenta;
            $serie_comprobante = substr(str_repeat(0, 3) . $serie, -3);
            //$serie_comprobante=$serieVenta;
            //echo json_encode($suma_numero);
        }


        $sql = "INSERT INTO $this->tableName (idcliente,idusuario,tipo_comprobante,serie_comprobante,num_comprobante,fecha_hora,impuesto,total_venta,tipo_pago,num_transac,estado) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
        $arrData = array($idcliente, $idusuario, $tipo_comprobante, $serie_comprobante, $num_comprobante, $fecha_hora, $impuesto, $total_venta, $tipo_pago, $num_transac, 'Aceptado');
        $idventanew = $this->conexion->getReturnId($sql, $arrData);

        $num_elementos = 0;
        $sw = true;
        while ($num_elementos < count($idarticulo)) {

            $sql_detalle = "INSERT INTO $this->tableNameDetalle (idventa,idarticulo,cantidad,precio_compra,precio_venta,descuento,estado) VALUES(?,?,?,?,?,?,?)";
            $arrDatadet = array($idventanew, $idarticulo[$num_elementos], $cantidad[$num_elementos], $precio_compra[$num_elementos], $precio_venta[$num_elementos], $descuento[$num_elementos], '1');
            $this->conexion->setData($sql_detalle, $arrDatadet) or $sw = false;

            $num_elementos = $num_elementos + 1;
        }

        //ACTUALIZAR STOCK DESPUES DE REALIZAR UNA VENTA
        $sql_stock = "SELECT idarticulo, cantidad FROM $this->tableNameDetalle WHERE idventa='$idventanew'";
        $res = $this->conexion->getDataAll($sql_stock);
        $idart = 0;
        foreach ($res as $reg) {
            $cantidad[$idart] = isset($reg['cantidad']) ? $cantidad[$idart] = $reg['cantidad'] : null;
            $idarticulo[$idart] = isset($reg['idarticulo']) ? $idarticulo[$idart] = $reg['idarticulo'] : null;
            $sql_detalle = "UPDATE articulo SET stock= stock-'$cantidad[$idart]' WHERE idarticulo=?";
            $arrData = array($idarticulo[$idart]);
            $this->conexion->setData($sql_detalle, $arrData) or $sw = false;
            $idart = $idart + 1;
        }
        return $sw;
    }

    //FUNCION PARA EDITAR
    public function editar($idventa, $idcliente, $tipo_comprobante, $fecha_hora, $impuesto, $total_venta, $tipo_pago, $num_transac, $idarticulo, $nuevostock, $cantidad, $precio_compra, $precio_venta, $descuento, $idarticuloEliminado, $cantidadEliminado)
    {
        $sw = true;

        //ACTUALIZAR LA TABLA DE INGRESO
        $sql = "UPDATE $this->tableName SET idcliente=?, tipo_comprobante=?, fecha_hora=?,impuesto=?, total_venta=?, tipo_pago=?, num_transac=? WHERE idventa=?";
        $arrData = array($idcliente, $tipo_comprobante, $fecha_hora, $impuesto, $total_venta, $tipo_pago, $num_transac, $idventa);
        $this->conexion->setData($sql, $arrData) or $sw = false;

        //ELIMINAR DATOS DE DETALLE DE VENTA 
        $sql_del = "DELETE FROM $this->tableNameDetalle WHERE idventa=?";
        $arrDataDel = array($idventa);
        $this->conexion->setData($sql_del, $arrDataDel) or $sw = false;

        $num_elementos = 0;
        while ($num_elementos < count($idarticulo)) {

            //INGRESAR NUEVOS DETALLE VE VENTA
            $sql_detalle = "INSERT INTO $this->tableNameDetalle (idventa,idarticulo,cantidad,precio_venta,descuento,estado) VALUES(?,?,?,?,?,?)";
            $arrDatadet = array($idventa, $idarticulo[$num_elementos], $cantidad[$num_elementos], $precio_venta[$num_elementos], $descuento[$num_elementos], '1');
            $this->conexion->setData($sql_detalle, $arrDatadet) or $sw = false;
            $num_elementos = $num_elementos + 1;
        }

        //ACTUALIZAR STOCK DESPUES DE EDITAR UNA VENTA
        $sql_stock = "SELECT idarticulo, cantidad FROM $this->tableNameDetalle WHERE idventa='$idventa'";
        $res = $this->conexion->getDataAll($sql_stock);
        $idart = 0;
        foreach ($res as $reg) {
            $idarticulo[$idart] = isset($reg['idarticulo']) ? $idarticulo[$idart] = $reg['idarticulo'] : null;
            $sql_detalle = "UPDATE articulo SET stock= stock+'$nuevostock[$idart]' WHERE idarticulo=?";
            $arrData = array($idarticulo[$idart]);
            $this->conexion->setData($sql_detalle, $arrData) or $sw = false;
            $idart = $idart + 1;
        }
        if (count($idarticuloEliminado) > 0) {
            $cant_eliminado = 0;
            while ($cant_eliminado < count($idarticuloEliminado)) {

                $sql_detalle = "UPDATE articulo SET stock= stock+'$cantidadEliminado[$cant_eliminado]' WHERE idarticulo=?";
                $arrData = array($idarticuloEliminado[$cant_eliminado]);
                $this->conexion->setData($sql_detalle, $arrData) or $sw = false;
                $cant_eliminado = $cant_eliminado + 1;
            }
        }
        return $sw;
    }

    //FUNCION PARA ANULAR UNA VENTA
    public function anular($idventa)
    {
        $sw = true;
        $sql = "UPDATE $this->tableName SET estado='Anulado' WHERE idventa=?";
        $arrData = array($idventa);
        $this->conexion->setData($sql, $arrData);
        $sql_detalle = "UPDATE $this->tableNameDetalle SET estado='0' WHERE idventa=?";
        $arrDataDetalle = array($idventa);
        $this->conexion->setData($sql_detalle, $arrDataDetalle) or $sw = false;

        //ACTUALIZAR STOCK DESPUES DE ANULAR UNA VENTA
        $sql_stock = "SELECT idarticulo, cantidad FROM $this->tableNameDetalle WHERE idventa='$idventa'";
        $res = $this->conexion->getDataAll($sql_stock);
        $idart = 0;
        foreach ($res as $reg) {
            $cantidad[$idart] = isset($reg['cantidad']) ? $cantidad[$idart] = $reg['cantidad'] : null;
            $idarticulo[$idart] = isset($reg['idarticulo']) ? $idarticulo[$idart] = $reg['idarticulo'] : null;
            $sql_detalle = "UPDATE articulo SET stock= stock+'$cantidad[$idart]' WHERE idarticulo=?";
            $arrData = array($idarticulo[$idart]);
            $this->conexion->setData($sql_detalle, $arrData) or $sw = false;
            $idart = $idart + 1;
        }
        return $sw;
    }

    //implementar un metodopara mostrar los datos de unregistro a modificar
    public function mostrar($idventa)
    {
        $sql = "SELECT v.idventa,DATE(v.fecha_hora) as fecha,v.idcliente,p.nombre as cliente,u.idusuario,u.nombre as usuario, v.tipo_comprobante,v.serie_comprobante,v.num_comprobante,v.total_venta,v.impuesto,v.tipo_pago,v.num_transac,v.estado FROM $this->tableName v INNER JOIN persona p ON v.idcliente=p.idpersona INNER JOIN usuario u ON v.idusuario=u.idusuario WHERE idventa=?";
        $arrData = array($idventa);
        return  $this->conexion->getData($sql, $arrData);
    }

    public function listarDetalle($idventa)
    {
        $sql = "SELECT dv.idventa,dv.idarticulo,a.nombre,a.stock, dv.cantidad,dv.precio_compra,dv.precio_venta,dv.descuento,(dv.cantidad*dv.precio_venta-dv.descuento) as subtotal, v.total_venta, v.impuesto FROM detalle_venta dv INNER JOIN articulo a ON dv.idarticulo=a.idarticulo INNER JOIN venta v ON v.idventa=dv.idventa WHERE dv.idventa='$idventa'";
        return  $this->conexion->getDataAll($sql);
    }

    //listar registros
    public function listar()
    {
        $sql = "SELECT v.idventa,DATE(v.fecha_hora) as fecha,v.idcliente,p.nombre as cliente,u.idusuario,u.nombre as usuario, v.tipo_comprobante,v.serie_comprobante,v.num_comprobante,v.total_venta,v.impuesto,v.tipo_pago,v.num_transac,v.estado FROM $this->tableName v INNER JOIN persona p ON v.idcliente=p.idpersona INNER JOIN usuario u ON v.idusuario=u.idusuario ORDER BY v.idventa DESC";
        return  $this->conexion->getDataAll($sql);
    }


    public function ventacabecera($idventa)
    {
        $sql = "SELECT v.idventa, v.idcliente, p.nombre AS cliente, p.direccion, p.tipo_documento, p.num_documento, p.email, p.telefono, v.idusuario,v.estado, u.nombre AS usuario, v.tipo_comprobante, v.serie_comprobante, v.num_comprobante, DATE(v.fecha_hora) AS fecha, v.impuesto, v.total_venta FROM $this->tableName v INNER JOIN persona p ON v.idcliente=p.idpersona INNER JOIN usuario u ON v.idusuario=u.idusuario WHERE v.idventa='$idventa'";
        return  $this->conexion->getDataAll($sql);
    }

    public function ventadetalles($idventa)
    {
        $sql = "SELECT a.nombre AS articulo, a.codigo, d.cantidad, d.precio_venta, d.descuento, (d.cantidad*d.precio_venta-d.descuento) AS subtotal FROM $this->tableNameDetalle d INNER JOIN articulo a ON d.idarticulo=a.idarticulo WHERE d.idventa='$idventa'";
        return  $this->conexion->getDataAll($sql);
    }



    //funcion para selecciolnar el numero de factura
    public function numero_venta($tipo_comprobante)
    {

        $sql = "SELECT num_comprobante FROM $this->tableName WHERE tipo_comprobante='$tipo_comprobante' ORDER BY idventa DESC limit 1 ";
        return  $this->conexion->getDataAll($sql);
    }
    //funcion para seleccionar la serie de la factura
    public function numero_serie($tipo_comprobante)
    {

        $sql = "SELECT serie_comprobante ,num_comprobante FROM $this->tableName WHERE tipo_comprobante='$tipo_comprobante' ORDER BY idventa DESC limit 1";

        return  $this->conexion->getDataAll($sql);
    }
}
