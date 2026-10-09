<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Bogota'); // Ajusta según tu zona horaria

$conexion = new mysqli("localhost", "jorginho_pradera", "jorginho10.", "jorginho_pradera");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$idTratamiento = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($idTratamiento <= 0) {
    die("ID de tratamiento no válido.");
}

$sqlTratamiento = "SELECT * FROM tratamientos WHERE id = $idTratamiento";
$resTratamiento = $conexion->query($sqlTratamiento);
if ($resTratamiento->num_rows == 0) {
    die("Tratamiento no encontrado.");
}
$tratamiento = $resTratamiento->fetch_assoc();

$sqlAbonos = "SELECT * FROM abonos WHERE id_tratamiento = $idTratamiento ORDER BY fecha ASC";
$resAbonos = $conexion->query($sqlAbonos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte de Tratamiento</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @media print {
      .no-print { display: none; }
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f5f7fa;
      padding: 30px;
    }

    .encabezado {
      display: flex;
      align-items: center;
      border-bottom: 2px solid #0d6efd;
      padding-bottom: 15px;
      margin-bottom: 30px;
    }

    .encabezado img {
      height: 80px;
      margin-right: 20px;
    }

    .encabezado h1 {
      font-size: 28px;
      margin: 0;
      color: #0d6efd;
    }

    .seccion {
      background: #fff;
      border: 1px solid #dee2e6;
      border-radius: 10px;
      padding: 25px;
      margin-bottom: 30px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    .table th, .table td {
      vertical-align: middle;
    }

    .btn-imprimir {
      margin-bottom: 20px;
    }

    .titulo-seccion {
      color: #343a40;
      margin-bottom: 15px;
      border-left: 4px solid #0d6efd;
      padding-left: 10px;
      font-weight: bold;
    }

    .footer {
      text-align: right;
      font-size: 0.9rem;
      color: #6c757d;
      margin-top: 40px;
    }
  </style>
</head>
<body>

<div class="container bg-white rounded">
  <!-- Encabezado con logo -->
  <div class="encabezado">
    <img src="logo.png" alt="Logo Veterinaria">
    <div>
      <h1>Veterinaria San Luis</h1>
      <p class="text-muted mb-0">Reporte de tratamiento y abonos</p>
    </div>
  </div>

  <!-- Botón imprimir -->
  <div class="text-end no-print">
    <button onclick="window.print()" class="btn btn-primary btn-imprimir">🖨️ Imprimir</button>
  </div>

  <!-- Datos del tratamiento -->
  <div class="seccion">
    <h5 class="titulo-seccion">📋 Detalles del Tratamiento</h5>
    <div class="row">
      <div class="col-md-6"><strong>Fecha y Hora:</strong> <?= $tratamiento['fecha_hora'] ?></div>
      <div class="col-md-6"><strong>Paciente:</strong> <?= $tratamiento['paciente'] ?></div>
      <div class="col-md-6"><strong>Propietario:</strong> <?= $tratamiento['propietario'] ?></div>
      <div class="col-md-6"><strong>Teléfono:</strong> <?= $tratamiento['telefono'] ?></div>
            <div class="col-md-6"><strong>cedula:</strong> <?= $tratamiento['cedula'] ?></div>
                  <div class="col-md-6"><strong>direccion:</strong> <?= $tratamiento['direccion'] ?></div>
      <div class="col-md-12"><strong>Tratamiento:</strong> <?= $tratamiento['tratamiento'] ?></div>
      <div class="col-md-4"><strong>Valor:</strong> $<?= number_format($tratamiento['valor']) ?></div>
      <div class="col-md-4"><strong>Abono Inicial:</strong> $<?= number_format($tratamiento['abono']) ?></div>
      <div class="col-md-4"><strong>Saldo Pendiente:</strong> $<?= number_format($tratamiento['saldo']) ?></div>
      <div class="col-md-12"><strong>Estado:</strong> <?= ucfirst($tratamiento['estado']) ?></div>
    </div>
  </div>

  <!-- Lista de abonos -->
  <div class="seccion">
    <h5 class="titulo-seccion">💰 Abonos Realizados</h5>
    <?php if ($resAbonos->num_rows > 0): ?>
      <table class="table table-striped table-hover">
        <thead class="table-primary">
          <tr>
            <th>Fecha</th>
            <th>Cantidad</th>
            <th>Observación</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($ab = $resAbonos->fetch_assoc()): ?>
            <tr>
              <td><?= $ab['fecha'] ?></td>
              <td>$<?= number_format($ab['cantidad']) ?></td>
              <td><?= !empty(trim($ab['observacion'])) ? $ab['observacion'] : 'Ninguno' ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="alert alert-info">No se han registrado abonos para este tratamiento.</div>
    <?php endif; ?>
  </div>

  <!-- Fecha de impresión -->
  <div class="footer">
    <strong>Fecha y hora de impresión:</strong>
    <?= date('Y-m-d H:i:s') ?>
  </div>

</div>

</body>
</html>
