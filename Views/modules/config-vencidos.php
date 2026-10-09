
<div>

<?php


$usuario = 'jorginho_pradera';
$contraseña = 'jorginho10.';
$servidor = 'localhost';
$nombre_bd = 'jorginho_pradera';

$conexion = mysqli_connect($servidor,$usuario,$contraseña,$nombre_bd);

if ($conexion) {
    echo 'conectado';
}else{
    echo 'no conectado';
}

?>



<div>
    <div></div>
    <div>
        <button>guardar</button><button>limpiar</button>
    </div>
</div>


</div>
