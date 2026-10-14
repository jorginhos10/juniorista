/**
 * initAutocomplete — input predictivo para selección de productos
 *
 * @param {HTMLElement} wrapper   — contenedor .prod-ac
 * @param {Array}       productos — array de productos
 * @param {Function}    onSelect  — callback({ id, stock, precio, unidad, nombre })
 * @param {Function}    onClear   — callback cuando se borra la selección
 * @param {string}      precioKey — 'precio_compra' | 'precio_venta'
 */
function initAutocomplete(wrapper, productos, onSelect, onClear, precioKey = 'precio_compra', allowNoStock = false) {
    const input  = wrapper.querySelector('.prod-ac-input');
    const hidden = wrapper.querySelector('.prod-ac-value');
    const list   = wrapper.querySelector('.prod-ac-list');

    function render(query) {
        const q        = (query || '').toLowerCase().trim();
        const filtered = q
            ? productos.filter(p =>
                p.nombre.toLowerCase().includes(q) ||
                (p.codigo && p.codigo.toLowerCase().includes(q))
              )
            : productos;

        if (!filtered.length) {
            list.innerHTML     = `<div class="prod-ac-empty">Sin resultados</div>`;
            list.style.display = 'block';
            return;
        }

        list.innerHTML = filtered.map(p => {
            const sinStock   = parseInt(p.stock) <= 0;
            const bloqueado  = sinStock && !allowNoStock;
            return `<div class="prod-ac-item ${bloqueado ? 'prod-ac-sin-stock' : ''}"
                         data-id="${p.id}"
                         data-stock="${p.stock}"
                         data-precio="${p[precioKey] ?? 0}"
                         data-unidad="${escHtml(p.unidad)}"
                         data-nombre="${escHtml(p.nombre)}">
                <div class="prod-ac-nombre">
                    ${escHtml(p.nombre)}
                    ${p.codigo ? `<span class="prod-ac-code">${escHtml(p.codigo)}</span>` : ''}
                </div>
                <div class="prod-ac-meta">
                    <span class="prod-ac-stock ${sinStock ? 'sin-stock' : ''}">
                        <i class="bi bi-layers"></i> ${sinStock ? 'Sin stock' : 'Stock: ' + p.stock}
                    </span>
                    <span class="prod-ac-precio">
                        $${parseFloat(p[precioKey] ?? 0).toFixed(2)}
                    </span>
                </div>
            </div>`;
        }).join('');

        list.style.display = 'block';
    }

    function posicionar() {
        const r = input.getBoundingClientRect();
        list.style.top   = (r.bottom + 4) + 'px';
        list.style.left  = r.left + 'px';
        list.style.width = r.width + 'px';
    }

    function abrir() { posicionar(); render(input.value); }
    function cerrar() { list.style.display = 'none'; }

    input.addEventListener('focus', abrir);

    input.addEventListener('input', () => {
        hidden.value = '';
        posicionar();
        render(input.value);
        onClear?.();
    });

    // mousedown para que no se dispare blur antes del click
    list.addEventListener('mousedown', e => {
        const item = e.target.closest('.prod-ac-item');
        if (!item || item.classList.contains('prod-ac-sin-stock')) return;
        e.preventDefault();

        hidden.value = item.dataset.id;
        input.value  = item.dataset.nombre;
        cerrar();

        onSelect?.({
            id:     item.dataset.id,
            stock:  parseInt(item.dataset.stock),
            precio: parseFloat(item.dataset.precio),
            unidad: item.dataset.unidad,
            nombre: item.dataset.nombre,
        });
    });

    input.addEventListener('blur', () => {
        setTimeout(cerrar, 150);
        // Si el texto no corresponde a un producto seleccionado, limpiar
        if (!hidden.value) { input.value = ''; onClear?.(); }
    });

    // Navegación con teclado
    input.addEventListener('keydown', e => {
        const items = [...list.querySelectorAll('.prod-ac-item:not(.prod-ac-sin-stock)')];
        const cur   = list.querySelector('.prod-ac-item.ac-hover');
        let idx     = items.indexOf(cur);

        if (e.key === 'ArrowDown')  { e.preventDefault(); idx = Math.min(idx + 1, items.length - 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); idx = Math.max(idx - 1, 0); }
        else if (e.key === 'Enter' && cur) { e.preventDefault(); cur.dispatchEvent(new MouseEvent('mousedown', { bubbles: true })); return; }
        else if (e.key === 'Escape') { cerrar(); return; }
        else return;

        cur?.classList.remove('ac-hover');
        items[idx]?.classList.add('ac-hover');
        items[idx]?.scrollIntoView({ block: 'nearest' });
    });
}
