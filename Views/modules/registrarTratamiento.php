<?php

ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("location: login");
} else {
    require "header.php";
    require "sidebar.php";

    if ($_SESSION['ventas'] == 1) {
?>

<?php
$host = 'localhost';
$dbname = 'jorginho_pradera';
$user = 'jorginho_pradera';
$pass = 'jorginho10.';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$exito = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'fecha_hora'  => $_POST['fecha_hora'],
        'paciente'    => $_POST['paciente'],
        'propietario' => $_POST['propietario'],
        'cedula'      => $_POST['cedula'],
        'telefono'    => $_POST['telefono'],
        'direccion'   => $_POST['direccion'],
        'tratamiento' => $_POST['tratamiento'],
        'valor'       => $_POST['valor'],
        'abono'       => $_POST['abono'],
        'saldo'       => $_POST['valor'] - $_POST['abono']
    ];

    $stmt = $pdo->prepare("INSERT INTO tratamientos 
        (fecha_hora, paciente, propietario, cedula, telefono, direccion, tratamiento, valor, abono, saldo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $exito = $stmt->execute(array_values($data));
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(to right, #e8f0f8, #fefefe);
    padding: 40px;
    margin: 0;
}

.container {
    max-width: 700px;
    margin: auto;
    background-color: white;
    border-radius: 15px;
    padding: 30px 40px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

h2 {
    text-align: center;
    color: #333;
    margin-bottom: 25px;
}

form label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
    color: #444;
}

form input, form textarea {
    width: 100%;
    padding: 12px;
    margin-top: 6px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
    transition: border-color 0.3s;
}

form input:focus, form textarea:focus {
    border-color: #28a745;
    outline: none;
}

form textarea {
    resize: vertical;
    min-height: 80px;
}

button {
    margin-top: 25px;
    width: 100%;
    padding: 14px;
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s ease;
}

button:hover {
    background-color: #218838;
}
</style>
<div class="main-content">
<div class="container">
    <h2>Registrar Tratamiento</h2>
    <form method="POST">
        <label>Fecha y hora</label>
        <input type="datetime-local" name="fecha_hora" value="<?= date('Y-m-d\TH:i') ?>" required>

        <label>Paciente</label>
        <input type="text" name="paciente" required>

        <label>Propietario</label>
        <input type="text" name="propietario" required>

        <label>Cédula</label>
        <input type="text" name="cedula" required>

        <label>Teléfono</label>
        <input type="text" name="telefono" required>

        <label>Dirección</label>
        <input type="text" name="direccion" required>

        <label>Nombre y control del tratamiento</label>
        <textarea name="tratamiento" required></textarea>

        <label>Valor del tratamiento</label>
        <input type="number" step="0.01" name="valor" id="valor" required>

        <label>Abono</label>
        <input type="number" step="0.01" name="abono" id="abono" required>

        <label>Saldo pendiente</label>
        <input type="number" step="0.01" name="saldo" id="saldo" readonly>

        <button type="submit">Guardar Tratamiento</button>
    </form>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const valor = document.getElementById('valor');
    const abono = document.getElementById('abono');
    const saldo = document.getElementById('saldo');

    function calcularSaldo() {
        const v = parseFloat(valor.value) || 0;
        const a = parseFloat(abono.value) || 0;
        saldo.value = (v - a).toFixed(2);
    }

    valor.addEventListener('input', calcularSaldo);
    abono.addEventListener('input', calcularSaldo);

    <?php if ($exito): ?>
    Swal.fire({
        icon: 'success',
        title: '¡Guardado!',
        text: 'El tratamiento se registró correctamente.',
        confirmButtonColor: '#28a745'
    });
    <?php endif; ?>
});
</script>
<?php
    } else {
        require "access.php";
    }
    require "footer.php";
    ?>
<script src="Views/modules/scripts/generaldata.js"></script>
<script src="Views/modules/scripts/editsale.js"></script>
<?php
}
ob_end_flush();
?>
