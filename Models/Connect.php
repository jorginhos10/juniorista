<?php
require_once dirname(__FILE__, 2) . '/Config/config.php';
//require_once "../Config/config.php";

class Conexion
{
	private $conect;

	public function __construct()
	{
		$connectionString = "mysql:host=" . HOST . ";port=" . PORT . ";dbname=" . DB_NAME . ";charset=" . CHARSET;
		try {
			$this->conect = new PDO($connectionString, DB_USER, DB_PASS);
			$this->conect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			echo "ERROR...!: " . $e->getMessage();
			exit();
		}
	}

	public function setData($sql, $arrData)
	{
		$query = $this->conect->prepare($sql);
		return $query->execute($arrData);
	}

	public function getReturnId($sql, $arrData)
	{
		$query = $this->conect->prepare($sql);
		$query->execute($arrData);
		return $this->conect->lastInsertId();
	}

	public function getDataAll($sql)
	{
		$execute = $this->conect->query($sql);
		return $execute->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getData($sql, $arrData)
	{
		$query = $this->conect->prepare($sql);
		$query->execute($arrData);
		return $query->fetch(PDO::FETCH_ASSOC);
	}
}

$conexion = new Conexion();