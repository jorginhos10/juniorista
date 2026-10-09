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
// Conexión a base de datos
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

// Registrar abono vía AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    if (isset($_POST['id'], $_POST['monto'])) {
        $id = intval($_POST['id']);
        $monto = floatval($_POST['monto']);
        $observacion = trim($_POST['observacion'] ?? '');

        $stmt = $pdo->prepare("SELECT saldo, estado FROM tratamientos WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row && $monto >= 0 && $monto <= $row['saldo'] && $row['estado'] === 'activo') {
            $pdo->prepare("INSERT INTO abonos (id_tratamiento, cantidad, fecha, observacion) VALUES (?, ?, NOW(), ?)")
                ->execute([$id, $monto, $observacion]);
            $nuevoSaldo = $row['saldo'] - $monto;
            $pdo->prepare("UPDATE tratamientos SET saldo = ? WHERE id = ?")->execute([$nuevoSaldo, $id]);

            echo json_encode([
                'status' => 'success',
                'nuevo_saldo' => number_format($nuevoSaldo, 0, '', '.'),
                'abono' => number_format($monto, 0, '', '.'),
                'observacion' => htmlspecialchars($observacion),
                'fecha' => date('Y-m-d H:i:s')
            ]);
            exit;
        }
    }
    echo json_encode(['status' => 'error']);
    exit;
}

// Cambiar estado del tratamiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $id = intval($_POST['id']);
    $estado = $_POST['estado'];

    $pdo->prepare("UPDATE tratamientos SET estado = ? WHERE id = ?")->execute([$estado, $id]);
    echo json_encode(['status' => 'ok']);
    exit;
}

// Obtener datos
$tratamientos = $pdo->query("SELECT * FROM tratamientos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$abonos = $pdo->query("SELECT * FROM abonos")->fetchAll(PDO::FETCH_ASSOC);
$abonosPorTratamiento = [];
foreach ($abonos as $a) {
    $abonosPorTratamiento[$a['id_tratamiento']][] = $a;
}
?>
<style>
.ocultador {
  display: block; /* visible por defecto */
}

@media screen and (min-width: 800px) {
  .ocultador {
    display: none; /* se oculta solo cuando la pantalla es >= 800px */
  }
}
/* Contenedor general */
body {
    font-family: 'Segoe UI', 'Roboto', sans-serif;
    background-color: #f4f6f9;
    color: #2c3e50;
    margin: 0;
    padding: 0;
}
.container {
    max-width: 100%;
    margin: 0 auto;
    padding: 20px;
    box-sizing: border-box;
    overflow-x: hidden;
}
/* Título */
h2 {
    color: #2c3e50;
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 20px;
    text-align: center;
}
/* Scroll horizontal en tablas */
.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin-bottom: 20px;
}
/* Tabla principal */
table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 8px rgba(0,0,0,0.08);
    min-width: 800px;
    
        user-select: none;
    -webkit-user-select: none; /* Safari */
    -moz-user-select: none;    /* Firefox */
    -ms-user-select: none;     /* IE/Edge antiguo */
    cursor:pointer;
}
thead {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
}
th, td {
    padding: 14px;
    text-align: center;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    white-space: nowrap;
}
tbody tr{
    transition: transform 0.3s ease-in-out;
}
tbody tr:hover {
    transform: scale(1.02); /* Aumenta un 2% el tamaño */
    z-index: 2; /* Para que no quede debajo de otras filas */
    position: relative;
}
/* Acordeón */
.acordeon {
    display: none;
    background: #fdfdfd;
}
.acordeon td {
    padding: 20px;
    border-top: 2px solid #e0e0e0;
    text-align: left;
}
/* Caja de abonos */
.abono-box {
    margin-top: 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}
input[type="number"], input[type="text"], select {
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
    flex: 1;
    min-width: 150px;
}
input:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 4px rgba(52,152,219,0.3);
    transition: 0.2s;
}
/* Botones */
button {
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    color: white;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
}
.btn-completar { background: #2ecc71; }
.btn-completar:hover { background: #27ae60; transform: translateY(-2px); }
.btn-suspender { background: #f1c40f; color: #2c3e50; }
.btn-suspender:hover { background: #d4ac0d; transform: translateY(-2px); }
.btn-cancelar { background: #e74c3c; }
.btn-cancelar:hover { background: #c0392b; transform: translateY(-2px); }
.btn-mostrar { background: #34495e; }
.btn-mostrar:hover { background: #2c3e50; transform: translateY(-2px); }
/* Filtros */
.filtros {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.filtros input, .filtros select {
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #bbb;
    font-size: 14px;
}
/* Tabla de abonos */
.abono-tabla {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}
.abono-tabla th {
    background: #2980b9;
    color: white;
    font-size: 13px;
    padding: 10px;
}
.abono-tabla td {
    padding: 10px;
    font-size: 13px;
    border: 1px solid #eee;
}
/* Colores por estado */
.estado-completado { background: #e9fce9; }
.estado-activo { background: #e6f0ff; }
.estado-suspendido { background: #fff8e1; }
.estado-cancelado { background: #fdeaea; }
/* Responsive */
@media screen and (max-width: 900px) {
    h2 { font-size: 20px; }
    th, td { padding: 10px; font-size: 12px; }
    .abono-box { flex-direction: column; }
    button { width: 100%; }
}
.btn-llamada{
  display: inline-block;
  font-weight: 600;
  color: #fff;
  background-color: green;
  padding: 0.5rem 1rem;
  font-size: 1rem;
  border-radius: 0.375rem;
  cursor: pointer;
  text-align: center;
  text-decoration: none;
}
/* Se oculta en pantallas grandes */


</style>
<div class="main-content">
<div class="container">
    <h2>Tratamientos registrados</h2>
    <div class="filtros">
        <input type="text" id="buscador" placeholder="Buscar por paciente o propietario" style="width: 300px;">
        <select id="filtroEstado">
            <option value="">Todos los estados</option>
            <option value="activo">Activo</option>
            <option value="completado">Completado</option>
            <option value="suspendido">Suspendido</option>
            <option value="cancelado">Cancelado</option>
        </select>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Paciente</th>
                    <th>Propietario</th>
                    <th>Cédula</th>
                    <th>Teléfono</th>
                    <th class="ocultador">llamada</th>
                    <th>Dirección</th>
                    <th>Tratamiento</th>
                    <th>Valor</th>
                    <th>Abono inicial</th>
                    <th>Saldo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tratamientos as $trat): ?>
                <tr class="estado-<?= $trat['estado'] ?>" > 
                    <td><?= $trat['id'] ?></td>
                    <td><?= htmlspecialchars($trat['paciente']) ?></td>
                    <td><?= htmlspecialchars($trat['propietario']) ?></td>
                    <td><?= htmlspecialchars($trat['cedula']) ?></td>
                    <td><?= htmlspecialchars($trat['telefono']) ?></td>
                    <td class="ocultador"><a class="btn-llamada" onclick="window.location.href=tel:'<?= htmlspecialchars($trat['telefono']) ?>'">LLAMAR</a></td>
                    <td><?= htmlspecialchars($trat['direccion']) ?></td>
                    <td><?= htmlspecialchars($trat['tratamiento']) ?></td>
                    <td>$<?= number_format($trat['valor'], 0, '', '.') ?></td>
                    <td>$<?= number_format($trat['abono'], 0, '', '.') ?></td>
                    <td id="saldo-<?= $trat['id'] ?>">$<?= number_format($trat['saldo'], 0, '', '.') ?></td>

                    <td style="display:flex; gap:5px; justify-content:center; border:none;">
                        <?php if ($trat['estado'] === 'completado'): ?>
                            <button class="btn-completar" disabled>Completado</button>
                        <?php elseif ($trat['estado'] === 'cancelado'): ?>
                            <button class="btn-cancelar" disabled>Cancelado</button>
                        <?php else: ?>
                            <button class="btn-completar" onclick="confirmarCambioEstado(<?= $trat['id'] ?>, 'completado')">Completar</button>
                            <button class="btn-suspender" onclick="confirmarCambioEstado(<?= $trat['id'] ?>, '<?= $trat['estado'] === 'suspendido' ? 'activo' : 'suspendido' ?>')">
                                <?= $trat['estado'] === 'suspendido' ? 'Habilitar' : 'Suspender' ?>
                            </button>
                            <button class="btn-cancelar" onclick="confirmarCambioEstado(<?= $trat['id'] ?>, 'cancelado')">Cancelar</button>
                        <?php endif; ?>
                        <button class="btn-mostrar" onclick="toggleAcordeon(<?= $trat['id'] ?>)">Mostrar</button>
                    </td>
                </tr>
                <tr id="acordeon-<?= $trat['id'] ?>" class="acordeon">
                    <td colspan="6">
                        <strong>Historial de Abonos</strong>
                        <table class="abono-tabla">
                            <thead>
                                <tr>
                                    <th>Cantidad</th>
                                    <th>Fecha</th>
                                    <th>Observación</th>
                                </tr>
                            </thead>
                            <tbody id="lista-abonos-<?= $trat['id'] ?>">
                                <?php foreach ($abonosPorTratamiento[$trat['id']] ?? [] as $a): ?>
                                    <tr>
                                        <td>$<?= number_format($a['cantidad'], 0, '', '.') ?></td>
                                        <td><?= $a['fecha'] ?></td>
                                        <td><?= htmlspecialchars($a['observacion']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if ($trat['estado'] === 'activo'): ?>
                            <div class="abono-box">
                                <input type="number" min="0" max="<?= $trat['saldo'] ?>" id="abono-<?= $trat['id'] ?>" placeholder="Nuevo abono">
                                <input type="text" id="observacion-<?= $trat['id'] ?>" placeholder="Observación">
                                <button class="btn-completar" onclick="confirmarAbono(<?= $trat['id'] ?>)">Abonar</button>
                            </div>
                        <?php else: ?>
                            <em>No disponible para abonos.</em>
                        <?php endif; ?>
                        <form action="../pradera/Reports/reporteAbono.php?id=<?= $trat['id'] ?>" method="POST" target="_blank" style="margin-top: 10px;">
                            <input type="hidden" name="id_tratamiento">
                            <button type="submit" class="btn-mostrar">Descargar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function toggleAcordeon(id) {
    const row = document.getElementById('acordeon-' + id);
    row.style.display = (row.style.display === 'table-row') ? 'none' : 'table-row';
}
function confirmarAbono(id) {
    const monto = document.getElementById('abono-' + id).value;
    const obs = document.getElementById('observacion-' + id).value;
    Swal.fire({
        title: '¿Seguro que deseas registrar este abono?',
        text: `Monto: $${monto}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, abonar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            guardarAbono(id, monto, obs);
        }
    });
}
function guardarAbono(id, monto, observacion) {
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('id', id);
    formData.append('monto', monto);
    formData.append('observacion', observacion);
    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                document.getElementById('saldo-' + id).innerText = "$" + res.nuevo_saldo;
                const tabla = document.getElementById('lista-abonos-' + id);
                const fila = document.createElement('tr');
                fila.innerHTML = `<td>$${res.abono}</td><td>${res.fecha}</td><td>${res.observacion}</td>`;
                tabla.appendChild(fila);
                document.getElementById('abono-' + id).value = '';
                document.getElementById('observacion-' + id).value = '';
                Swal.fire('Abono registrado', '', 'success');
            } else {
                Swal.fire('Error al guardar', 'El monto es inválido o el estado no permite abonar', 'error');
            }
        });
}
function confirmarCambioEstado(id, estado) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `El tratamiento pasará a estado: ${estado}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'No, cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            cambiarEstado(id, estado);
        }
    });
}
function cambiarEstado(id, estado) {
    const formData = new FormData();
    formData.append('cambiar_estado', '1');
    formData.append('id', id);
    formData.append('estado', estado);
    fetch('', { method: 'POST', body: formData }).then(() => location.reload());
}
document.getElementById('buscador').addEventListener('input', filtrarTabla);
document.getElementById('filtroEstado').addEventListener('change', filtrarTabla);
function filtrarTabla() {
    const texto = document.getElementById('buscador').value.toLowerCase();
    const estadoFiltro = document.getElementById('filtroEstado').value;
    const filas = document.querySelectorAll('tbody tr:not(.acordeon)');
    filas.forEach(fila => {
        const paciente = fila.children[1].innerText.toLowerCase();
        const propietario = fila.children[2].innerText.toLowerCase();
        const coincideTexto = paciente.includes(texto) || propietario.includes(texto);
        const coincideEstado = !estadoFiltro || fila.classList.contains("estado-" + estadoFiltro);
        fila.style.display = (coincideTexto && coincideEstado) ? '' : 'none';
        const id = fila.children[0].innerText;
        const acordeon = document.getElementById('acordeon-' + id);
        if (acordeon) acordeon.style.display = 'none';
    });
}

// Scroll horizontal arrastrable en la tabla
const scrollContainer = document.querySelector('.table-responsive');
let isDown = false;
let startX;
let scrollLeft;

scrollContainer.addEventListener('mousedown', (e) => {
    isDown = true;
    scrollContainer.classList.add('active');
    startX = e.pageX - scrollContainer.offsetLeft;
    scrollLeft = scrollContainer.scrollLeft;
});
scrollContainer.addEventListener('mouseleave', () => {
    isDown = false;
    scrollContainer.classList.remove('active');
});
scrollContainer.addEventListener('mouseup', () => {
    isDown = false;
    scrollContainer.classList.remove('active');
});
scrollContainer.addEventListener('mousemove', (e) => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - scrollContainer.offsetLeft;
    const walk = (x - startX) * 1; // velocidad del scroll (puedes ajustar el *1)
    scrollContainer.scrollLeft = scrollLeft - walk;
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
