<!-- Consulta de stock en todas las sucursales (lupa junto al título de cada página) -->
<style>
.sg-btn { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; margin-left:.6rem; vertical-align:middle;
          border:1px solid #e2e8f0; border-radius:8px; background:#fff; color:#4f46e5; font-size:.95rem; cursor:pointer; transition:background .15s; }
.sg-btn:hover { background:#eef2ff; }
.sg-overlay { position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:2000; display:none; align-items:flex-start; justify-content:center; padding:4vh 1rem; }
.sg-overlay.abierto { display:flex; }
.sg-modal { background:#fff; border-radius:14px; width:100%; max-width:min(1400px,96vw); max-height:92vh; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,.3); }
.sg-head { display:flex; align-items:center; gap:.75rem; padding:1rem 1.25rem; border-bottom:1px solid #e2e8f0; }
.sg-head h6 { margin:0; font-weight:700; color:#1e293b; flex-shrink:0; }
.sg-head input { flex:1; border:1px solid #cbd5e1; border-radius:8px; padding:.5rem .75rem; font-size:.9rem; outline:none; }
.sg-head input:focus { border-color:#4f46e5; box-shadow:0 0 0 3px rgba(79,70,229,.15); }
.sg-cerrar { border:0; background:none; font-size:1.3rem; color:#64748b; cursor:pointer; line-height:1; }
.sg-body { overflow:auto; }
.sg-tabla { width:100%; border-collapse:collapse; font-size:.85rem; table-layout:fixed; }
.sg-tabla th { position:sticky; top:0; z-index:1; background:#f8fafc; text-align:center; padding:.55rem .35rem; border-bottom:1px solid #e2e8f0; color:#475569; font-weight:600; font-size:.75rem; line-height:1.15; word-break:break-word; }
.sg-tabla th:first-child, .sg-tabla td:first-child { text-align:left; padding-left:1.25rem; width:34%; }
.sg-tabla th.sg-th-total, .sg-tabla td.sg-total { width:64px; }
.sg-tabla td { padding:.45rem .35rem; border-bottom:1px solid #f1f5f9; text-align:center; }
.sg-tabla td:first-child { word-break:break-word; }
.sg-tabla td small { display:block; color:#94a3b8; word-break:break-all; }
.sg-suc { display:none; }
@media (max-width: 768px) {
    .sg-overlay { padding:0; }
    .sg-modal { max-width:none; max-height:100vh; height:100vh; border-radius:0; }
    .sg-head { flex-wrap:wrap; padding:.75rem 1rem; }
    .sg-head input { order:3; flex:1 1 100%; }
    .sg-tabla, .sg-tabla tbody, .sg-tabla tr, .sg-tabla td { display:block; width:auto !important; }
    .sg-tabla thead { display:none; }
    .sg-tabla tr { padding:.6rem 1rem; border-bottom:1px solid #e2e8f0; display:flex; flex-wrap:wrap; gap:.3rem; }
    .sg-tabla td { border:0; padding:0; text-align:left; }
    .sg-tabla td:first-child { flex:1 1 100%; padding:0 0 .25rem; }
    .sg-tabla td.sg-s { display:flex; justify-content:space-between; gap:.5rem; flex:1 1 calc(50% - .3rem); background:#f8fafc; border-radius:6px; padding:.25rem .5rem; font-size:.78rem; }
    .sg-tabla td.sg-s .sg-suc { display:inline; color:#64748b; }
    .sg-tabla td.sg-total { flex:1 1 100%; text-align:right; padding-top:.15rem; }
    .sg-tabla td.sg-total::before { content:'Total: '; font-weight:400; color:#64748b; }
}
.sg-cero { color:#cbd5e1; }
.sg-total { font-weight:700; color:#1e293b; }
.sg-info { padding:.6rem 1.25rem; font-size:.78rem; color:#64748b; border-top:1px solid #e2e8f0; }
</style>
<div class="sg-overlay" id="sg-overlay">
    <div class="sg-modal" role="dialog" aria-label="Stock en todas las sucursales">
        <div class="sg-head">
            <h6><i class="bi bi-search me-1"></i>Stock en todas las sucursales</h6>
            <input type="text" id="sg-buscar" placeholder="Buscar producto por nombre o código..." autocomplete="off">
            <button type="button" class="sg-cerrar" id="sg-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <div class="sg-body" id="sg-body"><div class="p-4 text-center text-muted">Cargando…</div></div>
        <div class="sg-info" id="sg-info"></div>
    </div>
</div>
<script>
(function () {
    var overlay = document.getElementById('sg-overlay'), body = document.getElementById('sg-body'),
        inp = document.getElementById('sg-buscar'), info = document.getElementById('sg-info'), datos = null;

    function esc(t) { return String(t == null ? '' : t).replace(/[&<>"']/g, function (c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function corto(n) { return esc(String(n).replace(/^veterinaria\s+/i, '')); }
    function norm(t) { return String(t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); }

    function pintar() {
        if (!datos) return;
        var q = norm(inp.value).trim(), partes = q ? q.split(/\s+/) : [], filas = [], n = 0;
        datos.productos.forEach(function (p) {
            if (partes.length) {
                var t = norm(p.n + ' ' + (p.c || '') + ' ' + (p.k || ''));
                if (!partes.every(function (x) { return t.indexOf(x) !== -1; })) return;
            }
            n++;
            filas.push('<tr><td>' + esc(p.n) + '<small>' + esc(p.c || '') + '</small></td>' +
                p.s.map(function (v, i) { return '<td class="sg-s ' + (v > 0 ? '' : 'sg-cero') + '"><span class="sg-suc">' + corto(datos.sucursales[i].nombre) + '</span><span>' + v + '</span></td>'; }).join('') +
                '<td class="sg-total">' + p.t + '</td></tr>');
        });
        body.innerHTML = '<table class="sg-tabla"><thead><tr><th>Producto</th>' +
            datos.sucursales.map(function (s) { return '<th title="' + esc(s.nombre) + '">' + corto(s.nombre) + '</th>'; }).join('') +
            '<th class="sg-th-total">Total</th></tr></thead><tbody>' +
            (filas.join('') || '<tr><td colspan="' + (datos.sucursales.length + 2) + '" class="text-muted p-4">Sin resultados</td></tr>') +
            '</tbody></table>';
        info.textContent = n + ' producto' + (n === 1 ? '' : 's');
    }

    function abrir() {
        overlay.classList.add('abierto');
        inp.value = '';
        setTimeout(function () { inp.focus(); }, 50);
        if (datos) { pintar(); return; }
        fetch('<?= BASE_URL ?>/stock-global', {headers: {'Accept': 'application/json'}, credentials: 'same-origin'})
            .then(function (r) { return r.json(); })
            .then(function (d) { if (d.error) throw new Error(d.error); datos = d; pintar(); })
            .catch(function (e) { body.innerHTML = '<div class="p-4 text-center text-danger">No se pudo cargar el stock: ' + esc(e.message) + '</div>'; });
    }
    function cerrar() { overlay.classList.remove('abierto'); }

    document.addEventListener('DOMContentLoaded', function () {
        var titulo = document.querySelector('.topbar-left .topbar-title');
        if (titulo) {
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'sg-btn'; b.title = 'Consultar stock en todas las sucursales';
            b.innerHTML = '<i class="bi bi-search"></i>';
            b.addEventListener('click', abrir);
            titulo.appendChild(b);
        }
        document.getElementById('sg-cerrar').addEventListener('click', cerrar);
        overlay.addEventListener('mousedown', function (e) { if (e.target === overlay) cerrar(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrar(); });
        inp.addEventListener('input', pintar);
    });
})();
</script>
