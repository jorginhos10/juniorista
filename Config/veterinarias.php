<?php
// Bases de datos de las veterinarias que se consultan en el buscador de stock (lupa del header).
// Si un producto no existe en una veterinaria, se muestra con cantidad 0.
// Por defecto usan la misma contraseña que config.php (DB_PASS); si alguna es distinta, cambiar su "pass".
require_once __DIR__ . '/config.php';

return [
    ["nombre" => "Juniorista",    "db" => "jorginho_juniorista", "user" => "jorginho_juniorista", "pass" => DB_PASS],
    ["nombre" => "Pradera",       "db" => "jorginho_pradera",    "user" => "jorginho_pradera",    "pass" => DB_PASS],
    ["nombre" => "Veterinaria 3", "db" => "jorginho_CAMBIAR3",   "user" => "jorginho_CAMBIAR3",   "pass" => DB_PASS],
    ["nombre" => "Veterinaria 4", "db" => "jorginho_CAMBIAR4",   "user" => "jorginho_CAMBIAR4",   "pass" => DB_PASS],
    ["nombre" => "Veterinaria 5", "db" => "jorginho_CAMBIAR5",   "user" => "jorginho_CAMBIAR5",   "pass" => DB_PASS],
    ["nombre" => "Veterinaria 6", "db" => "jorginho_CAMBIAR6",   "user" => "jorginho_CAMBIAR6",   "pass" => DB_PASS],
];
