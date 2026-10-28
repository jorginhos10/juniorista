<?php
// $activePage debe ser definida por el controlador antes de incluir este partial
$activePage   = $activePage ?? '';
$veterinarias = $veterinarias ?? [];
$veterinaria_id = $veterinaria_id ?? 0;
$_tema = $_SESSION['tema'] ?? 'original';
?>
<?php if ($_tema === 'halloween'): ?>
<link href="<?= BASE_URL ?>/assets/css/theme-halloween.css" rel="stylesheet">
<script>document.body.classList.add('tema-halloween');</script>
<div class="hw-murcielagos" aria-hidden="true"><span>🦇</span><span>🦇</span><span>🦇</span></div>
<?php endif; ?>
<aside class="sidebar" id="sidebar">

    <!-- Logo -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <i class="bi bi-heart-pulse-fill"></i>
        </div>
        <span class="sidebar-brand-name">PetControl</span>
    </div>

    <!-- Navegación -->
    <nav class="sidebar-nav">
        <ul>

            <?php
            // Menú por categorías: [activePage, ruta, ícono, texto]
            $_menu = [
                'Principal'  => [
                    ['dashboard',        'dashboard',          'speedometer2',          'Dashboard'],
                ],
                'Caja'       => [
                    ['ventas',           'ventas',             'bag-fill',              'Ventas'],
                    ['facturacion',      'facturacion',        'receipt-cutoff',        'Facturación'],
                    ['gastos',           'gastos',             'wallet2',               'Gastos'],
                    ['arqueo',           'arqueo',             'cash-stack',            'Arqueo de caja'],
                ],
                'Atención'   => [
                    ['citas',            'citas',              'calendar2-check',       'Citas'],
                    ['clientes',         'clientes',           'people-fill',           'Clientes'],
                    ['mascotas',         'mascotas',           'heart-fill',            'Mascotas'],
                    ['historial',        'historial',          'clipboard2-pulse-fill', 'Historial médico'],
                    ['formulas',         'formulas',           'prescription2',         'Fórmula médica'],
                    ['hospitalizacion',  'hospitalizacion',    'hospital',              'Hospitalización'],
                ],
                'Servicios'  => [
                    ['tratamiento',      'tratamiento',        'capsule',               'Tratamiento'],
                    ['peluqueria',       'peluqueria',         'scissors',              'Peluquería'],
                    ['laboratorio',      'laboratorio',        'eyedropper',            'Laboratorios'],
                    ['ordenes-laboratorio', 'ordenes-laboratorio', 'clipboard2-pulse',     'Órdenes de laboratorio'],
                    ['ordenes-cirugia',  'ordenes-cirugia',    'bandaid',               'Órdenes de cirugía'],
                ],
                'Inventario' => [
                    ['almacen',          'almacen',            'archive-fill',          'Almacén'],
                    ['ingresos',         'ingresos',           'box-arrow-in-down-right', 'Ingresos'],
                    ['perdidas',         'perdidas',           'box-arrow-up-right',    'Pérdidas'],
                    ['descargos',        'descargos',          'box-arrow-down',        'Descargos'],
                ],
                'Reportes'   => [
                    ['reporte-ventas',   'reportes/ventas',    'bar-chart-fill',        'Reporte de ventas'],
                    ['reporte-unidades', 'reportes/unidades',  'boxes',                 'Ventas por unidad'],
                    ['reporte-ingresos', 'reportes/ingresos',  'box-arrow-in-down-right', 'Reporte de ingresos'],
                    ['reporte-perdidas', 'reportes/perdidas',  'graph-down-arrow',      'Reporte de pérdidas'],
                ],
            ];
            ?>
            <?php
            // Solo los módulos que el usuario tiene permitidos; una categoría sin ítems no se muestra
            require_once ROOT . '/app/helpers/Permisos.php';
            foreach ($_menu as $_seccion => $_items) {
                $_menu[$_seccion] = array_filter($_items, fn($it) => Permisos::puedeMenu($it[0]));
            }
            $_menu = array_filter($_menu);
            ?>
            <?php foreach ($_menu as $_seccion => $_items): ?>
            <li class="nav-section-label"><?= $_seccion ?></li>
            <?php foreach ($_items as [$_clave, $_ruta, $_icono, $_texto]): ?>
            <li class="<?= $activePage === $_clave ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/<?= $_ruta ?>">
                    <i class="bi bi-<?= $_icono ?>"></i>
                    <span><?= $_texto ?></span>
                </a>
            </li>
            <?php endforeach; ?>
            <?php endforeach; ?>

            <li class="nav-section-label">Sistema</li>

            <li class="<?= $activePage === 'configuracion' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/configuracion">
                    <i class="bi bi-gear-fill"></i>
                    <span>Configuración</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/logout" onclick="return confirm('¿Cerrar sesión?')">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </li>

        </ul>
    </nav>

    <!-- Usuario -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="avatar-sm">
                <?= strtoupper(substr($_SESSION['usuario_nombre'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '') ?></div>
                <div class="sidebar-user-rol"><?= htmlspecialchars(ucfirst($_SESSION['usuario_rol'] ?? '')) ?></div>
            </div>
        </div>
    </div>

</aside>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var sel = document.getElementById('vet-selector');
    if (!sel) return;

    sel.addEventListener('change', function () {
        var newVet  = this.value;
        var prevVet = this.dataset.current;
        var vetName = this.options[this.selectedIndex].text;
        var el      = this;

        if (newVet === prevVet) return;

        Swal.fire({
            title: '¿Cambiar de veterinaria?',
            html: '¿Está seguro que desea cambiar a <b>' + vetName + '</b>?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor:  '#6c757d',
            confirmButtonText:  'Sí, cambiar',
            cancelButtonText:   'Cancelar',
            reverseButtons: true
        }).then(function (result) {
            if (result.isConfirmed) {
                window.location.href = '<?= BASE_URL ?>/set_vet?vet=' + newVet
                    + '&back=' + encodeURIComponent(window.location.pathname);
            } else {
                el.value = prevVet;
            }
        });
    });
});
</script>
