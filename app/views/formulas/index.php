<?php
$iconos = ['Perro' => '🐶', 'Gato' => '🐱', 'Ave' => '🐦', 'Conejo' => '🐰', 'Roedor' => '🐹', 'Reptil' => '🦎', 'Otro' => '🐾'];
$mascotaSel = null;
foreach ($mascotas as $m) { if ((int)$m['id'] === $mascotaFiltro) { $mascotaSel = $m; break; } }
$imprimir = $_SESSION['formula_imprimir'] ?? 0;   // recién guardada: se abre para imprimir
unset($_SESSION['formula_imprimir']);
$hoyF = date('Y-m-d');
// Nombre de la sucursal para el número de cada fórmula
$vetNombreActual = '';
foreach ($veterinarias as $v) { if ((int)$v['id'] === $veterinaria_id) { $vetNombreActual = $v['nombre']; break; } }
$atajos = [
    'Hoy'          => [$hoyF, $hoyF],
    'Este mes'     => [date('Y-m-01'), $hoyF],
    'Mes anterior' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Fórmula médica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .rx-num { font-family: Consolas, monospace; font-size: .8rem; color: #4338ca; background: #eef2ff; border-radius: 999px; padding: .1rem .55rem; white-space: nowrap; }
        .rx-lista { max-width: 380px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .filtro-bar { display: flex; flex-wrap: wrap; gap: .5rem; align-items: end; }
        .atajo { font-size: .75rem; }
    </style>
</head>
<body class="dashboard-body">
<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Fórmula médica</h5>
                <p class="topbar-date mb-0">Recetas veterinarias firmadas por el médico</p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
                <a href="<?= BASE_URL ?>/formulas/nueva<?= $mascotaFiltro ? '?mascota=' . $mascotaFiltro : '' ?>" class="btn btn-primary-custom btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Nueva fórmula
                </a>
                <div class="dropdown">
                    <button class="btn btn-link topbar-avatar-btn p-0" data-bs-toggle="dropdown">
                        <div class="avatar-md"><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><div class="dropdown-header">
                            <div class="fw-semibold"><?= htmlspecialchars($usuario['nombre']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($usuario['email']) ?></div>
                        </div></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout" onclick="return confirm('¿Cerrar sesión?')">
                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="main-content">
            <?php if (!empty($success)): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-3">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i><span><?= htmlspecialchars($success) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i><span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 pb-2">
                    <form method="GET" action="<?= BASE_URL ?>/formulas" class="filtro-bar">
                        <h6 class="card-title mb-0 fw-semibold me-auto align-self-center">
                            <?= $mascotaSel ? 'Fórmulas de ' . htmlspecialchars($mascotaSel['nombre']) : 'Fórmulas guardadas' ?>
                        </h6>
                        <div>
                            <label class="form-label small fw-semibold mb-1">Paciente</label>
                            <select class="form-select form-select-sm" name="mascota" onchange="this.form.submit()" style="min-width:220px;">
                                <option value="0">Todos (por fecha)</option>
                                <?php foreach ($mascotas as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= (int)$m['id'] === $mascotaFiltro ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['nombre'] . ($m['cliente'] ? ' — ' . $m['cliente'] : '')) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (!$mascotaFiltro): ?>
                        <div>
                            <label class="form-label small fw-semibold mb-1">Desde</label>
                            <input type="date" class="form-control form-control-sm" name="desde" value="<?= $desde ?>" max="<?= $hoyF ?>">
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">Hasta</label>
                            <input type="date" class="form-control form-control-sm" name="hasta" value="<?= $hasta ?>" max="<?= $hoyF ?>">
                        </div>
                        <button class="btn btn-sm btn-light"><i class="bi bi-search"></i></button>
                        <div class="d-flex gap-1">
                            <?php foreach ($atajos as $lbl => [$d1, $h1]): ?>
                            <a href="<?= BASE_URL ?>/formulas?desde=<?= $d1 ?>&hasta=<?= $h1 ?>"
                               class="btn btn-sm atajo <?= ($d1 === $desde && $h1 === $hasta) ? 'btn-secondary' : 'btn-light' ?>"><?= $lbl ?></a>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <a href="<?= BASE_URL ?>/formulas" class="btn btn-sm btn-light">Ver todas</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Fecha</th>
                                    <th>N.º</th>
                                    <th>Paciente</th>
                                    <th>Diagnóstico</th>
                                    <th>Medicamentos</th>
                                    <th>Médico</th>
                                    <th class="text-center pe-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$formulas): ?>
                                <tr><td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-prescription2 fs-2 d-block mb-2 opacity-25"></i>
                                    No hay fórmulas <?= $mascotaSel ? 'para este paciente' : 'en este período' ?>.
                                </td></tr>
                                <?php endif; ?>
                                <?php foreach ($formulas as $f): ?>
                                <tr>
                                    <td class="ps-3 text-nowrap"><?= date('d/m/Y', strtotime($f['fecha'])) ?></td>
                                    <td><span class="rx-num"><?= htmlspecialchars(VeterinariaModel::serialServicio($vetNombreActual, 'FOR', (int)$f['id'], $f['created_at'])) ?></span></td>
                                    <td>
                                        <div class="fw-semibold"><?= $iconos[$f['especie']] ?? '🐾' ?> <?= htmlspecialchars($f['mascota']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($f['cliente'] ?: 'Sin propietario') ?></div>
                                    </td>
                                    <td class="small"><?= htmlspecialchars(mb_strimwidth((string)$f['diagnostico'], 0, 80, '…')) ?: '<span class="text-muted">—</span>' ?></td>
                                    <td class="small">
                                        <span class="badge bg-primary-soft text-primary"><?= (int)$f['medicamentos'] ?></span>
                                        <span class="rx-lista d-inline-block align-middle text-muted" title="<?= htmlspecialchars((string)$f['lista']) ?>"><?= htmlspecialchars((string)$f['lista']) ?></span>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($f['veterinario'] ?? '—') ?></td>
                                    <td class="text-center text-nowrap pe-3">
                                        <a href="<?= BASE_URL ?>/formulas/imprimir?id=<?= $f['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary btn-accion" title="Imprimir"><i class="bi bi-printer"></i></a>
                                        <a href="<?= BASE_URL ?>/formulas/nueva?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary btn-accion" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-accion" title="Anular"
                                                onclick="anularFormula(<?= (int)$f['id'] ?>, <?= htmlspecialchars(json_encode($f['mascota'])) ?>)"><i class="bi bi-x-lg"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>/formulas/anular" id="formAnular" class="d-none">
    <input type="hidden" name="id">
    <input type="hidden" name="mascota" value="<?= $mascotaFiltro ?>">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function anularFormula(id, mascota) {
    if (!confirm('¿Anular la fórmula de ' + mascota + '? Dejará de aparecer en el listado.')) return;
    const f = document.getElementById('formAnular');
    f.id.value = id;
    f.submit();
}
<?php if ($imprimir): ?>
// Recién guardada: se abre el formato para imprimir
window.open('<?= BASE_URL ?>/formulas/imprimir?id=<?= (int)$imprimir ?>', '_blank');
<?php endif; ?>
</script>
</body>
</html>
