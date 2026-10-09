<?php
//incluir la conexion de base de datos
require_once "Connect.php";
class Product
{

	private $tableName = 'articulo';
	private $conexion;

	//implementamos nuestro constructor
	public function __construct()
	{
		$this->conexion = new Conexion();
	}

	//metodo insertar registro
	public function insertar($idcategoria, $codigo, $nombre, $descripcion, $imagen)
	{
		$sql = "INSERT INTO $this->tableName (idcategoria,codigo,nombre,descripcion,imagen,condicion) VALUES (?,?,?,?,?,?)";
		$arrData = array($idcategoria, $codigo, $nombre, $descripcion, $imagen, 1);
		return $this->conexion->setData($sql, $arrData);
	}

	//metodo para editar registro
	public function editar($idarticulo, $idcategoria, $codigo, $nombre, $descripcion, $imagen)
	{
		$sql = "UPDATE $this->tableName SET idcategoria=?, codigo=?, nombre=?, descripcion=?, imagen=? WHERE idarticulo=?";
		$arrData = array($idcategoria, $codigo, $nombre, $descripcion, $imagen, $idarticulo);
		return $this->conexion->setData($sql, $arrData);
	}

	//metodo para desactivar registro
	public function desactivar($idarticulo)
	{
		$sql = "UPDATE $this->tableName SET condicion='0' WHERE idarticulo=?";
		$arrData = array($idarticulo);
		return $this->conexion->setData($sql, $arrData);
	}

	//metodo para activar registro
	public function activar($idarticulo)
	{
		$sql = "UPDATE $this->tableName SET condicion='1' WHERE idarticulo=?";
		$arrData = array($idarticulo);
		return $this->conexion->setData($sql, $arrData);
	}

	//metodo para mostrar registros
	public function mostrar(string $idarticulo)
	{
		$sql = "SELECT * FROM $this->tableName WHERE idarticulo=?";
		$arrData = array($idarticulo);
		return  $this->conexion->getData($sql, $arrData);
	}

	//listar registros
	public function listar()
	{
		$sql = "SELECT a.idarticulo,a.idcategoria,c.nombre as categoria,a.codigo, a.nombre,a.stock,a.descripcion,a.imagen,a.condicion,(SELECT precio_compra FROM detalle_ingreso WHERE idarticulo=a.idarticulo AND estado=1 ORDER BY iddetalle_ingreso DESC LIMIT 0,1) AS precio_compra,(SELECT precio_venta FROM detalle_ingreso WHERE idarticulo=a.idarticulo AND estado=1 ORDER BY iddetalle_ingreso DESC LIMIT 0,1) AS precio_venta FROM $this->tableName a INNER JOIN categoria c ON a.idcategoria=c.idcategoria";
		return  $this->conexion->getDataAll($sql);
	}

	//listar registros activos
	public function listarActivos()
	{
		$sql = "SELECT a.idarticulo,a.idcategoria,c.nombre as categoria,a.codigo, a.nombre,a.stock,a.descripcion,a.imagen,a.condicion FROM $this->tableName a INNER JOIN categoria c ON a.idcategoria=c.idcategoria WHERE a.condicion='1'";
		return  $this->conexion->getDataAll($sql);
	}

	//listar y mostrar registros activos
	public function listarActivosVenta()
	{
		$sql = "SELECT a.idarticulo,a.idcategoria,c.nombre as categoria,a.codigo, a.nombre,a.stock,(SELECT precio_venta FROM detalle_ingreso WHERE idarticulo=a.idarticulo ORDER BY iddetalle_ingreso DESC LIMIT 0,1) AS precio_venta,(SELECT precio_compra FROM detalle_ingreso WHERE idarticulo=a.idarticulo ORDER BY iddetalle_ingreso ASC LIMIT 0,1) AS precio_compra,a.descripcion,a.imagen,a.condicion FROM $this->tableName a INNER JOIN categoria c ON a.idcategoria=c.idcategoria WHERE a.condicion='1'AND a.stock > 0";
		return  $this->conexion->getDataAll($sql);
	}

	//metodo para contar articulos
	public function cantidadarticulos()
	{
		$sql = "SELECT COUNT(*) totalar FROM $this->tableName WHERE condicion=? AND stock>?";
		$arrData = array(1, 0);
		return  $this->conexion->getData($sql, $arrData);
	}

	//metodo para quitar y actulaizar stock al momento de editar el ingreso
	public function quitarArticulo($idarticulo, $cantidad)
	{
		$sql = "UPDATE $this->tableName SET stock=stock-'$cantidad' WHERE idarticulo=?";
		$arrData = array($idarticulo);
		return $this->conexion->setData($sql, $arrData);
	}

	//metodo para quitar y actulaizar stock al momento de editar una venta
	public function aumentarArticulo($idarticulo, $cantidad)
	{
		$sql = "UPDATE $this->tableName SET stock=stock+'$cantidad' WHERE idarticulo=?";
		$arrData = array($idarticulo);
		return $this->conexion->setData($sql, $arrData);
	}


	public function validarSotck($idarticulo)
	{
		$sql = "SELECT stock FROM $this->tableName WHERE idarticulo=?";
		$arrData = array($idarticulo);
		return $this->conexion->getData($sql, $arrData);
	}
}
