<?php
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("location: login");
    exit;
}

// header.php / sidebar.php según tu proyecto
require "header.php";
require "sidebar.php";

// permisos
if (!isset($_SESSION['ventas']) || $_SESSION['ventas'] != 1) {
    require "access.php";
    require "footer.php";
    exit;
}

// ======= CONFIG BD =======
$host = 'localhost';
$dbname = 'jorginho_juniorista';
$user = 'jorginho_juniorista';
$pass = 'jorginho10.';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// ======= FUNCIONES =======
function normalize_date_to_iso($dateStr) {
    $dateStr = trim((string)$dateStr);
    if ($dateStr === '') return date('Y-m-d');
    // ya en formato ISO
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) return $dateStr;
    // dd/mm/yyyy o d/m/yyyy
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $m)) {
        $d = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $mo = str_pad($m[2], 2, '0', STR_PAD_LEFT);
        $y = $m[3];
        return "$y-$mo-$d";
    }
    // intento con strtotime (último recurso)
    $ts = strtotime($dateStr);
    if ($ts !== false) return date('Y-m-d', $ts);
    return date('Y-m-d');
}

// Extrae 'fecha' de cualquier sitio donde pueda llegar (GET, POST, REQUEST_URI, url-friendly)
function extract_fecha_from_request() {
    // 1) GET / POST directo
    if (!empty($_GET['fecha'])) return $_GET['fecha'];
    if (!empty($_POST['fecha'])) return $_POST['fecha'];

    // 2) si la query string existe (por seguridad)
    if (!empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $qs);
        if (!empty($qs['fecha'])) return $qs['fecha'];
    }

    // 3) REQUEST_URI (la URL completa que pidió el navegador)
    if (!empty($_SERVER['REQUEST_URI'])) {
        $uri = $_SERVER['REQUEST_URI'];
        // ?fecha=yyyy-mm-dd o ?fecha=dd/mm/yyyy
        if (preg_match('/[?&]fecha=([^&]+)/', $uri, $m)) {
            return urldecode($m[1]);
        }
        // Si usan rutas tipo /gastos/2025-10-12 o /gastos/12-10-2025
        if (preg_match('#/gastos[/\-]([0-9]{4}-[0-9]{2}-[0-9]{2})#', $uri, $m2)) {
            return $m2[1];
        }
        if (preg_match('#/gastos[/\-]([0-9]{1,2}/[0-9]{1,2}/[0-9]{4})#', $uri, $m3)) {
            return $m3[1];
        }
    }

    // 4) Si el router coloca la ruta en $_GET['url'] (ej: url=gastos/2025-10-12)
    if (!empty($_GET['url'])) {
        $u = trim($_GET['url'], '/');
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $u, $m)) return $m[1];
        if (preg_match('/(\d{1,2}\/\d{1,2}\/\d{4})/', $u, $m2)) return $m2[1];
        // como fallback, revisar segmentos finales
        $parts = explode('/', $u);
        $last = end($parts);
        if ($last && preg_match('/^\d{4}-\d{2}-\d{2}$/', $last)) return $last;
        if ($last && preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $last)) return $last;
    }

    return null;
}

// ======= OBTENER FECHA =======
$fecha_raw = extract_fecha_from_request();
$fecha_seleccionada = $fecha_raw ? normalize_date_to_iso($fecha_raw) : date('Y-m-d');

// ======= INSERTAR GASTO (POST) =======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['concepto'], $_POST['valor'])) {
    $concepto = strtoupper(trim($_POST['concepto']));
    // normalizar valor (acepta coma o punto)
    $valor = (float) str_replace(',', '.', $_POST['valor']);
    // la columna 'fecha' es DATE -> insertar YYYY-MM-DD
    $fecha_insert = $fecha_seleccionada;

    $stmt = $pdo->prepare("INSERT INTO gastos (fecha, concepto, valor) VALUES (?, ?, ?)");
    $stmt->execute([$fecha_insert, $concepto, $valor]);

    // Redirigir a la misma ruta pero con ?fecha= para que el filtro se mantenga
    $base = strtok($_SERVER["REQUEST_URI"], '?'); // ruta sin query
    header('Location: ' . $base . '?fecha=' . $fecha_seleccionada);
    exit;
}

// ======= CONSULTA =======
$stmt = $pdo->prepare("SELECT * FROM gastos WHERE fecha = ? ORDER BY fecha DESC, id DESC");
$stmt->execute([$fecha_seleccionada]);
$gastos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_gastos = array_sum(array_column($gastos, 'valor'));
?>

<div class="main-content">
<div class="container">
    <h1>Registro de Gastos</h1>

    <!-- FORMULARIO AGREGAR -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="post" class="row g-2" id="formAgregar">
                <input type="hidden" name="fecha" id="hiddenFecha" value="<?= htmlspecialchars($fecha_seleccionada) ?>">
                <div class="col-md-6">
                    <label>Concepto:</label>
                    <input type="text" name="concepto" class="form-control text-uppercase" required>
                </div>
                <div class="col-md-4">
                    <label>Valor:</label>
                    <input type="number" step="0.01" name="valor" class="form-control" required>
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary w-100">Agregar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FILTRO -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="formFiltro" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label>Fecha:</label>
                    <input type="date" name="fecha" id="inputFecha" value="<?= htmlspecialchars($fecha_seleccionada) ?>" class="form-control">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success w-100">Ver Gastos</button>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-primary w-100" onclick="imprimirTabla()">Imprimir Reporte</button>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLA -->
    <div class="card">
        <div class="card-body">
            <h4>Gastos del <?= htmlspecialchars($fecha_seleccionada) ?></h4>
            <div id="tablaGastos">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($gastos): ?>
                            <?php foreach($gastos as $g): ?>
                                <tr>
                                    <td><?= htmlspecialchars($g['fecha']) ?></td>
                                    <td><?= htmlspecialchars($g['concepto']) ?></td>
                                    <td><?= number_format($g['valor'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center">No hay gastos registrados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <h4>Total de Gastos: <?= number_format($total_gastos, 2) ?></h4>
        </div>
    </div>
</div>
</div>

<script>
// convertir dd/mm/yyyy o yyyy-mm-dd a ISO yyyy-mm-dd
function toISODate(s) {
    if (!s) return '';
    s = s.trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
    var m = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (m) {
        var d = m[1].padStart(2,'0'), mo = m[2].padStart(2,'0'), y = m[3];
        return y + '-' + mo + '-' + d;
    }
    var dt = new Date(s);
    if (!isNaN(dt)) {
        var yy = dt.getFullYear();
        var mm = String(dt.getMonth()+1).padStart(2,'0');
        var dd = String(dt.getDate()).padStart(2,'0');
        return yy + '-' + mm + '-' + dd;
    }
    return s;
}

document.addEventListener('DOMContentLoaded', function(){
    var inputFecha = document.getElementById('inputFecha');
    var hiddenFecha = document.getElementById('hiddenFecha');
    var formFiltro = document.getElementById('formFiltro');
    var formAgregar = document.getElementById('formAgregar');

    // Antes de enviar el filtro, redirigir manualmente a la ruta + ?fecha=ISO
    formFiltro.addEventListener('submit', function(e){
        e.preventDefault();
        var iso = toISODate(inputFecha.value);
        // navegar al mismo path (sin query) y añadir ?fecha=
        var base = window.location.pathname; // /pradera/gastos
        window.location.href = base + '?fecha=' + encodeURIComponent(iso);
    });

    // Al enviar agregar, asegurar hidden fecha tenga el ISO correcto
    formAgregar.addEventListener('submit', function(e){
        if (inputFecha && hiddenFecha) {
            hiddenFecha.value = toISODate(inputFecha.value);
        }
    });
});

function imprimirTabla(){
    const original = document.body.innerHTML;
    const fecha = document.getElementById('inputFecha') ? document.getElementById('inputFecha').value : '';
    const content = `<div><h4>Gastos del ${fecha}</h4>${document.getElementById('tablaGastos').outerHTML}</div>`;
    document.body.innerHTML = content;
    window.print();
    setTimeout(()=> document.body.innerHTML = original, 100);
}
</script>

<?php
require "footer.php";
ob_end_flush();
?>
