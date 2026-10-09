<?php
require_once(dirname(__FILE__, 2) . '/Models/Product.php');

$productModel = new Product();
$items = $productModel->listar(); // obtenemos todos los productos

require_once(dirname(__FILE__, 2) . '/Views/modules/inventario.php');