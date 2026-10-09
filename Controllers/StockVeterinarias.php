<?php
session_start();
require_once dirname(__FILE__, 2) . '/Config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['nombre'])) {
	http_response_code(401);
	echo json_encode(["error" => "Sesión no iniciada"]);
	exit();
}

$termino = isset($_GET["q"]) ? trim($_GET["q"]) : "";
if (mb_strlen($termino) < 2) {
	echo json_encode(["veterinarias" => [], "productos" => []]);
	exit();
}

$veterinarias = require dirname(__FILE__, 2) . '/Config/veterinarias.php';
$nombresVet = array_column($veterinarias, "nombre");

// productos agrupados por nombre normalizado: clave => ["nombre" => ..., "stock" => [vet => cantidad]]
$productos = [];
$errores = [];

foreach ($veterinarias as $vet) {
	try {
		$pdo = new PDO(
			"mysql:host=" . HOST . ";port=" . PORT . ";dbname=" . $vet["db"] . ";charset=" . CHARSET,
			$vet["user"],
			$vet["pass"],
			[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
		);
		$query = $pdo->prepare("SELECT nombre, SUM(stock) AS stock FROM articulo WHERE condicion = 1 AND nombre LIKE ? GROUP BY nombre LIMIT 50");
		$query->execute(["%" . $termino . "%"]);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$clave = mb_strtolower(trim($row["nombre"]));
			if (!isset($productos[$clave])) {
				$productos[$clave] = ["nombre" => trim($row["nombre"]), "stock" => array_fill_keys($nombresVet, 0)];
			}
			$productos[$clave]["stock"][$vet["nombre"]] += (int) $row["stock"];
		}
	} catch (PDOException $e) {
		$errores[] = $vet["nombre"];
	}
}

ksort($productos);

echo json_encode([
	"veterinarias" => $nombresVet,
	"productos" => array_values($productos),
	"errores" => $errores,
]);
