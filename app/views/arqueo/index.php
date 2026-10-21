<?php
$pesos = fn($n) => '$' . number_format((float)$n, 0, ',', '.');
$estadoClase = ['pendiente' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                'aprobado'  => 'bg-success-subtle text-success border-success-subtle',
                'observado' => 'bg-danger-subtle text-danger border-danger-subtle'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Arqueo de caja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .arq-grupo-titulo { font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #64748b; margin-bottom: .5rem; }
        .arq-fila { display: grid; grid-template-columns: 76px 90px 1fr 110px; align-items: center; gap: .75rem; padding: .3rem 0; }
        .arq-img { display: flex; justify-content: center; align-items: center; height: 42px; margin: 0; cursor: pointer; }
        .arq-img img { height: 38px; width: 38px; object-fit: contain; filter: drop-shadow(0 1px 2px rgba(15, 23, 42, .25)); }
        .arq-billete .arq-img img { height: 34px; width: 74px; border-radius: 3px; }
        .arq-den { font-weight: 700; font-variant-numeric: tabular-nums; }
        .arq-sub { text-align: right; color: #64748b; font-variant-numeric: tabular-nums; font-size: .9rem; }
        .arq-fila input { text-align: center; }
        .arq-total { display: flex; justify-content: space-between; align-items: baseline; padding: .85rem 1rem; border-radius: 10px;
                     background: #f0fdf4; color: #166534; }
        .arq-total strong { font-size: 1.6rem; font-weight: 800; font-variant-numeric: tabular-nums; }
        .monto { font-variant-numeric: tabular-nums; font-weight: 700; white-space: nowrap; }
        @media (max-width: 576px) {   /* celular: imagen pequeña y columnas que se ajustan al contenido */
            .arq-fila { grid-template-columns: 44px 70px minmax(0, 1fr) 64px; gap: .4rem; }
            .arq-den { font-size: .85rem; }
            .arq-sub { font-size: .75rem; }
            .arq-img img { width: 30px; height: 30px; }
            .arq-billete .arq-img img { width: 44px; height: 21px; }
        }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Arqueo de caja</h5>
                <p class="topbar-date mb-0">Conteo de monedas y billetes al final del día</p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
                <div class="dropdown">
                    <button class="btn btn-link topbar-avatar-btn p-0" data-bs-toggle="dropdown">
                        <div class="avatar-md"><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li>
                            <div class="dropdown-header">
                                <div class="fw-semibold"><?= htmlspecialchars($usuario['nombre']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($usuario['email']) ?></div>
                                <span class="badge badge-rol mt-1"><?= htmlspecialchars(ucfirst($usuario['rol'])) ?></span>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout" onclick="return confirm('¿Cerrar sesión?')">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="main-content">

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
            <?php endif; ?>

            <div class="row g-4">

                <!-- Declarar arqueo -->
                <div class="col-xl-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pt-3 pb-0">
                            <h6 class="card-title fw-semibold mb-0"><i class="bi bi-cash-stack text-success me-2"></i>Declarar arqueo de hoy</h6>
                            <div class="text-muted small"><?= date('d/m/Y') ?> &middot; escribe cuántas unidades hay de cada denominación</div>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= BASE_URL ?>/arqueo/registrar" id="formArqueo" autocomplete="off">

                                <?php
                                // Imagen de cada denominación: una foto real si existe en assets/img/dinero (p. ej. 50000.jpg); si no, la ilustración .svg
                                $imgDen = function (int $den): string {
                                    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                                        if (is_file(ROOT . "/assets/img/dinero/$den.$ext")) return BASE_URL . "/assets/img/dinero/$den.$ext";
                                    }
                                    return BASE_URL . "/assets/img/dinero/$den.svg";
                                };
                                ?>
                                <div class="arq-grupo-titulo">Monedas</div>
                                <?php foreach ($monedas as $den): ?>
                                <div class="arq-fila">
                                    <label class="arq-img" for="den-<?= $den ?>"><img src="<?= $imgDen($den) ?>" alt="<?= $pesos($den) ?>" loading="lazy"></label>
                                    <label class="arq-den" for="den-<?= $den ?>"><?= $pesos($den) ?></label>
                                    <input type="number" class="form-control arq-cant" id="den-<?= $den ?>" name="conteo[<?= $den ?>]"
                                           data-den="<?= $den ?>" min="0" step="1" inputmode="numeric" placeholder="0">
                                    <span class="arq-sub" id="sub-<?= $den ?>">$0</span>
                                </div>
                                <?php endforeach; ?>

                                <div class="arq-grupo-titulo mt-3">Billetes</div>
                                <?php foreach ($billetes as $den): ?>
                                <div class="arq-fila arq-billete">
                                    <label class="arq-img" for="den-<?= $den ?>"><img src="<?= $imgDen($den) ?>" alt="<?= $pesos($den) ?>" loading="lazy"></label>
                                    <label class="arq-den" for="den-<?= $den ?>"><?= $pesos($den) ?></label>
                                    <input type="number" class="form-control arq-cant" id="den-<?= $den ?>" name="conteo[<?= $den ?>]"
                                           data-den="<?= $den ?>" min="0" step="1" inputmode="numeric" placeholder="0">
                                    <span class="arq-sub" id="sub-<?= $den ?>">$0</span>
                                </div>
                                <?php endforeach; ?>

                                <div class="arq-total mt-3">
                                    <span>Total declarado</span>
                                    <strong id="arqTotal">$0</strong>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label fw-semibold" for="arqNotas">Notas</label>
                                    <textarea class="form-control" name="notas" id="arqNotas" rows="2" maxlength="1000"
                                              placeholder="Opcional: algo que el administrador deba saber…"></textarea>
                                </div>

                                <div class="form-check mt-2 d-none" id="cajaVaciaBox">
                                    <input class="form-check-input" type="checkbox" name="caja_vacia" value="1" id="cajaVacia">
                                    <label class="form-check-label" for="cajaVacia">La caja está vacía (declaro $0)</label>
                                </div>

                                <button type="submit" class="btn btn-success w-100 fw-bold mt-3">
                                    <i class="bi bi-check2-circle me-1"></i> Registrar arqueo
                                </button>
                                <div class="form-text">Una vez registrado no se puede modificar; lo revisa el administrador.</div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Arqueos recientes -->
                <div class="col-xl-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pt-3 pb-2">
                            <h6 class="card-title fw-semibold mb-0">Arqueos de los últimos 30 días</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Fecha</th>
                                            <th>Declaró</th>
                                            <th class="text-end">Total declarado</th>
                                            <th class="text-center">Estado</th>
                                            <th>Revisión</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($arqueos)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-5">
                                                <i class="bi bi-cash-stack fs-2 d-block mb-2 opacity-25"></i>
                                                Aún no hay arqueos registrados.
                                            </td>
                                        </tr>
                                        <?php else: foreach ($arqueos as $a): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-semibold"><?= date('d/m/Y', strtotime($a['fecha'])) ?></div>
                                                <div class="text-muted small"><?= date('H:i', strtotime($a['created_at'])) ?></div>
                                            </td>
                                            <td class="small"><?= htmlspecialchars($a['usuario_nombre'] ?? '—') ?></td>
                                            <td class="text-end monto"><?= $pesos($a['total_declarado']) ?></td>
                                            <td class="text-center">
                                                <span class="badge border <?= $estadoClase[$a['estado']] ?? '' ?>"><?= htmlspecialchars($estados[$a['estado']] ?? $a['estado']) ?></span>
                                            </td>
                                            <td class="small">
                                                <?php if ($a['auditado_at']): ?>
                                                <div class="text-muted"><?= htmlspecialchars($a['auditor_nombre'] ?? 'Administrador') ?> &middot; <?= date('d/m/Y H:i', strtotime($a['auditado_at'])) ?></div>
                                                <?php if ($a['nota_auditoria']): ?><div><?= nl2br(htmlspecialchars($a['nota_auditoria'])) ?></div><?php endif; ?>
                                                <?php else: ?>
                                                <span class="text-muted">Sin revisar</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
const fmtPesos = n => '$' + Math.round(n).toLocaleString('es-CO');

function recalcularArqueo() {
    let total = 0;
    document.querySelectorAll('.arq-cant').forEach(inp => {
        const cant = Math.max(0, parseInt(inp.value, 10) || 0);
        const sub  = cant * parseInt(inp.dataset.den, 10);
        document.getElementById('sub-' + inp.dataset.den).textContent = fmtPesos(sub);
        total += sub;
    });
    document.getElementById('arqTotal').textContent = fmtPesos(total);
    // Solo se pide confirmar "caja vacía" cuando el total es 0
    document.getElementById('cajaVaciaBox').classList.toggle('d-none', total > 0);
    return total;
}

document.querySelectorAll('.arq-cant').forEach(inp => {
    inp.addEventListener('input', recalcularArqueo);
    inp.addEventListener('focus', () => inp.select());
    // Enter pasa a la siguiente denominación en vez de enviar el formulario a medias
    inp.addEventListener('keydown', e => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const todos = [...document.querySelectorAll('.arq-cant')];
        const sig = todos[todos.indexOf(inp) + 1];
        sig ? sig.focus() : document.getElementById('arqNotas').focus();
    });
});

document.getElementById('formArqueo').addEventListener('submit', e => {
    const total = recalcularArqueo();
    if (total <= 0 && !document.getElementById('cajaVacia').checked) {
        e.preventDefault();
        alert('Indica cuántas monedas y billetes hay. Si la caja está vacía, marca la casilla para confirmarlo.');
        return;
    }
    if (!confirm('¿Registrar el arqueo por ' + fmtPesos(total) + '? No se podrá modificar después.')) e.preventDefault();
});

recalcularArqueo();
</script>
</body>
</html>
