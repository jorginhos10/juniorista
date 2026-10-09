<?php

// Sin caché en las páginas, para que cada deploy se vea al recargar.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once "Controllers/Plantilla.php";

$plantilla = new Plantilla();
$plantilla -> mostrarPlantilla();
