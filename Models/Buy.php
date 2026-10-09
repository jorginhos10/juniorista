<?php
//incluir la conexion de base de datos
require_once "Connect.php";
class Buy
{

    private $tableName = 'ingreso';
    private $tableNameDetalle = 'detalle_ingreso';
    private $conexion;

    //implementamos nuestro constructor
    public function __construct()
    {
        $this->conexion = new Conexion();
    }

    //metodo insertar registro
    public function insertar($idproveedor, $idusuario, $tipo_comprobante, $serie_comprobante, $num_comprobante, $fecha_hora, $impuesto, $total_compra, $idarticulo, $cantidad, $precio_compra, $precio_venta)
    {
        $sql = "INSERT INTO $this->tableName (idproveedor,idusuario,tipo_comprobante,serie_comprobante,num_comprobante,fecha_hora,impuesto,total_compra,estado) VALUES (?,?,?,?,?,?,?,?,?)";
        $arrData = array($idproveedor, $idusuario, $tipo_comprobante, $serie_comprobante, $num_comprobante, $fecha_hora, $impuesto, $total_compra, 'Aceptado');
        $idingresonew = $this->conexion->getReturnId($sql, $arrData);
        $num_elementos = 0;
        $sw = true;
        while ($num_elementos < count($idarticulo)) {
            $sql_detalle = "INSERT INTO $this->tableNameDetalle (idingreso,idarticulo,cantidad,precio_compra,precio_venta,estado) VALUES(?,?,?,?,?,?)";
            $arrDatadet = array($idingresonew, $idarticulo[$num_elementos], $cantidad[$num_elementos], $precio_compra[$num_elementos], $precio_venta[$num_elementos], '1');
            $this->conexion->setData($sql_detalle, $arrDatadet) or $sw = false;

            $num_elementos = $num_elementos + 1;
        }

        //ACTUALIZAR STOCK DESPUES DE INGRESAR
        $sql_stock = "SELECT idarticulo, cantidad FROM $this->tableNameDetalle WHERE idingreso='$idingresonew'";
        $res = $this->conexion->getDataAll($sql_stock);
        $idart = 0;
        foreach ($res as $reg) {

            $cantidad[$idart] = isset($reg['cantidad']) ? $cantidad[$idart] = $reg['cantidad'] : null;
            $idarticulo[$idart] = isset($reg['idarticulo']) ? $idarticulo[$idart] = $reg['idarticulo'] : null;
            $sql_detalle = "UPDATE articulo SET stock= stock+'$cantidad[$idart]' WHERE idarticulo=?";
            //ejecutarConsulta($sql_detalle) or $sw=false;
            $arrData = array($idarticulo[$idart]);
            $this->conexion->setData($sql_detalle, $arrData) or $sw = false;
            $idart = $idart + 1;
        }
        return $sw;
    }

    //FUNCION PARA EDITAR
    public function editar($idingreso, $idproveedor, $tipo_comprobante, $serie_comprobante, $num_comprobante, $fecha_hora, $impuesto, $total_compra, $idarticulo, $nuevostock, $cantidad, $precio_compra, $precio_venta)
    {
        $sw = true;
        $sql = "UPDATE $this->tableName SET idproveedor=?, tipo_comprobante=?, serie_comprobante=?, num_comprobante=?, fecha_hora=?, impuesto=?, total_compra=? WHERE idingreso=?";

        $arrData = array($idproveedor, $tipo_comprobante, $serie_comprobante, $num_comprobante, $fecha_hora, $impuesto, $total_compra, $idingreso);
        $this->conexion->setData($sql, $arrData) or $sw = false;

        //borramos los articulos de ingreso de la tabla detalle_ingreso
        $sql_del = "DELETE FROM $this->tableNameDetalle WHERE idingreso=?";
        $arrDataDel = array($idingreso);
        $this->conexion->setData($sql_del, $arrDataDel) or $sw = false;

        $num_elementos = 0;
        while ($num_elementos < count($idarticulo)) {
            //nuevo registro de ingreso en la tabla de detalle_ingreso
            $sql_detalle = "INSERT INTO $this->tableNameDetalle (idingreso,idarticulo,cantidad,precio_compra,precio_venta,estado) VALUES(?,?,?,?,?,?)";
            $arrDatadet = array($idingreso, $idarticulo[$num_elementos], $cantidad[$num_elementos], $precio_compra[$num_elementos], $precio_venta[$num_elementos], '1');
            $this->conexion->setData($sql_detalle, $arrDatadet) or $sw = false;

            $num_elementos = $num_elementos + 1;
        }

        //ACTUALIZAR STOCK DESPUES DE EDITAR UN INGRESO
        $sql_stock = "SELECT idarticulo FROM $this->tableNameDetalle WHERE idingreso='$idingreso'";
        $res = $this->conexion->getDataAll($sql_stock);
        $idart = 0;
        foreach ($res as $reg) {
            $idarticulo[$idart] = isset($reg['idarticulo']) ? $idarticulo[$idart] = $reg['idarticulo'] : null;
            $sql_detalle = "UPDATE articulo SET stock= stock+'$nuevostock[$idart]' WHERE idarticulo=?";
            $arrData = array($idarticulo[$idart]);
            $this->conexion->setData($sql_detalle, $arrData) or $sw = false;
            $idart = $idart + 1;
        }

        return $sw;
    }

    //METODO PARA ANULAR UN INGRESO
    public function anular($idingreso)
    {

        $sw = true;
        $sql = "UPDATE $this->tableName SET estado='Anulado' WHERE idingreso=?";
        $arrData = array($idingreso);
        $this->conexion->setData($sql, $arrData);
        $sql_detalle = "UPDATE $this->tableNameDetalle SET estado='0' WHERE idingreso=?";
        $arrDataDetalle = array($idingreso);
        $this->conexion->setData($sql_detalle, $arrDataDetalle) or $sw = false;

        //ACTUALIZAR STOCK DESPUES DE ANULAR UN INGRESO
        if ($sw) {
            $sql_stock = "SELECT idarticulo, cantidad FROM $this->tableNameDetalle WHERE idingreso='$idingreso'";
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
        }
        return $sw;
    }

    //metodo para mostrar registros
    public function mostrar($idingreso)
    {
        $sql = "SELECT i.idingreso,DATE(i.fecha_hora) as fecha,i.idproveedor,p.nombre as proveedor,u.idusuario,u.nombre as usuario, i.tipo_comprobante,i.serie_comprobante,i.num_comprobante,i.total_compra,i.impuesto,i.estado FROM $this->tableName i INNER JOIN persona p ON i.idproveedor=p.idpersona INNER JOIN usuario u ON i.idusuario=u.idusuario WHERE idingreso=?";
        $arrData = array($idingreso);
        return  $this->conexion->getData($sql, $arrData);
    }

    //METODO PARA LISTAR DETALLE DE INGRESO
    public function listarDetalle($idingreso)
    {
        $sql = "SELECT di.idingreso,di.idarticulo,a.stock,a.nombre,di.cantidad,di.precio_compra,di.precio_venta,i.impuesto ,i.total_compra FROM $this->tableNameDetalle di INNER JOIN articulo a ON di.idarticulo=a.idarticulo INNER JOIN $this->tableName i ON i.idingreso=di.idingreso WHERE di.idingreso='$idingreso'";
        return  $this->conexion->getDataAll($sql);
    }

    //metodo para listar registros
    public function listar()
    {
        $sql = "SELECT i.idingreso,DATE(i.fecha_hora) as fecha,i.idproveedor,p.nombre as proveedor,u.idusuario,u.nombre as usuario, i.tipo_comprobante,i.serie_comprobante,i.num_comprobante,i.total_compra,i.impuesto,i.estado FROM $this->tableName i INNER JOIN persona p ON i.idproveedor=p.idpersona INNER JOIN usuario u ON i.idusuario=u.idusuario ORDER BY i.idingreso DESC";
        return  $this->conexion->getDataAll($sql);
    }
}