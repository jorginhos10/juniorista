<?php
// Conexión a la base de datos
$servername = "localhost";
$username = "jorginho_pradera";
$password = "jorginho10.";
$dbname = "jorginho_pradera";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Función para agregar un peluquero
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add'])) {
    $nombre_peluquero = $_POST['nombre_peluquero'];
    $porcentaje_pago = $_POST['porcentaje_pago'];

    $sql = "INSERT INTO config_peluqueria (nombre_peluquero, porcentaje_pago, fecha_creacion) 
            VALUES ('$nombre_peluquero', '$porcentaje_pago', NOW())";

    if ($conn->query($sql) === TRUE) {
        // Redirigir después de la inserción para evitar resubmit en el refresh
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "<script>alert('Error al agregar peluquero: " . $conn->error . "');</script>";
    }
}

// Función para eliminar un peluquero
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];

    $sql = "DELETE FROM config_peluqueria WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        // Redirigir después de eliminar
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "<script>alert('Error al eliminar peluquero: " . $conn->error . "');</script>";
    }
}

// Función para editar un peluquero
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nombre_peluquero = $_POST['nombre_peluquero'];
    $porcentaje_pago = $_POST['porcentaje_pago'];

    $sql = "UPDATE config_peluqueria 
            SET nombre_peluquero = '$nombre_peluquero', porcentaje_pago = '$porcentaje_pago' 
            WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        // Redirigir después de actualizar
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "<script>alert('Error al actualizar peluquero: " . $conn->error . "');</script>";
    }
}

// Función para obtener todos los peluqueros
function obtener_peluqueros() {
    global $conn;
    $sql = "SELECT * FROM config_peluqueria";
    $result = $conn->query($sql);

    if ($result === FALSE) {
        die("Error en la consulta: " . $conn->error);
    }

    return $result;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Peluqueros</title>
    <style>
        /* Estilos básicos */
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .form-container, .list-container {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: center;
        }
        button {
            margin: 5px;
        }
    </style>
</head>
<body>

    <h1>Gestión de Peluqueros</h1>

    <!-- Formulario para agregar un peluquero -->
    <div class="form-container">
        <form action="" method="POST">
            <label for="nombre_peluquero">Nombre del peluquero:</label>
            <input type="text" name="nombre_peluquero" required><br><br>
            <label for="porcentaje_pago">Porcentaje de pago:</label>
            <input type="number" name="porcentaje_pago" required><br><br>
            <button type="submit" name="add">Agregar Peluquero</button>
        </form>
    </div>

    <!-- Mostrar lista de peluqueros -->
    <div class="list-container">
        <h2>Lista de Peluqueros</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Porcentaje de Pago</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Mostrar los peluqueros desde la base de datos
                $peluqueros = obtener_peluqueros();
                if ($peluqueros->num_rows > 0) {
                    while ($row = $peluqueros->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['nombre_peluquero']}</td>
                                <td>{$row['porcentaje_pago']}</td>
                                <td>
                                    <a href='?eliminar={$row['id']}'>Eliminar</a>
                                    <button onclick='editar({$row['id']}, \"{$row['nombre_peluquero']}\", {$row['porcentaje_pago']})'>Editar</button>
                                </td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No hay peluqueros registrados.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Modal para editar -->
    <div id="modal-editar" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 20px; border: 1px solid #ddd;">
        <h3>Editar Peluquero</h3>
        <form action="" method="POST">
            <input type="hidden" name="id" id="edit-id">
            <label for="edit-nombre">Nombre del peluquero:</label>
            <input type="text" name="nombre_peluquero" id="edit-nombre" required><br><br>
            <label for="edit-porcentaje">Porcentaje de pago:</label>
            <input type="number" name="porcentaje_pago" id="edit-porcentaje" required><br><br>
            <button type="submit" name="editar">Actualizar Peluquero</button>
            <button type="button" onclick="cerrarModal()">Cancelar</button>
        </form>
    </div>

    <script>
        // Función para abrir el modal de edición
        function editar(id, nombre, porcentaje) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-nombre').value = nombre;
            document.getElementById('edit-porcentaje').value = porcentaje;
            document.getElementById('modal-editar').style.display = 'block';
        }

        // Función para cerrar el modal
        function cerrarModal() {
            document.getElementById('modal-editar').style.display = 'none';
        }
    </script>

</body>
</html>
