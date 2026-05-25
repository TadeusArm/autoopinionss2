document.addEventListener("DOMContentLoaded", () => {
    inicializarSelectores();
    cargarMuro();

    // Filtro con debounce al escribir
    let busquedaTimer;
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(busquedaTimer);
            busquedaTimer = setTimeout(cargarMuro, 500);
        });
    }

    // Auto-recarga al cambiar cualquier selector
    document.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', cargarMuro);
    });

    // Evitar recarga de página al enviar el formulario de filtros
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', e => {
            e.preventDefault();
            cargarMuro();
        });
    }

    // Botón scroll to top
    const scrollBtn = document.getElementById("scrollToTop");
    if (scrollBtn) {
        window.onscroll = () => {
            const isMobile = window.innerWidth <= 768;
            const dist     = document.body.scrollTop > 300 || document.documentElement.scrollTop > 300;
            scrollBtn.style.display = (isMobile && dist) ? "block" : "none";
        };
        scrollBtn.onclick = () => window.scrollTo({ top: 0, behavior: "smooth" });
    }
});

// ─── SELECTORES ──────────────────────────────────────────────────────────────

function inicializarSelectores() {
    const llenarSelect = (id, min, max, step = 1, suffix = '') => {
        const el = document.getElementById(id);
        if (!el) return;
        let options = `<option value="">${id.includes('min') ? 'Mín' : 'Máx'}</option>`;
        for (let i = min; i <= max; i += step) {
            options += `<option value="${i}">${i.toLocaleString('es-ES')}${suffix}</option>`;
        }
        el.innerHTML = options;
    };

    llenarSelect('min-year', 1980, 2026);
    llenarSelect('max-year', 1980, 2026);
    llenarSelect('min-km',   0, 300000, 25000, ' km');
    llenarSelect('max-km',   0, 300000, 25000, ' km');
    llenarSelect('min-cv',   50, 500,   25,    ' CV');
    llenarSelect('max-cv',   50, 500,   25,    ' CV');
}

// ─── CARGAR MURO ─────────────────────────────────────────────────────────────

function cargarMuro() {
    const timeline = document.getElementById('vehicles-timeline');
    if (!timeline) return;

    const queryParams = new URLSearchParams({
        search:   document.getElementById('search-input')?.value  || '',
        brand:    document.getElementById('brand-select')?.value  || '',
        min_year: document.getElementById('min-year')?.value      || '',
        max_year: document.getElementById('max-year')?.value      || '',
        min_km:   document.getElementById('min-km')?.value        || '',
        max_km:   document.getElementById('max-km')?.value        || '',
        min_cv:   document.getElementById('min-cv')?.value        || '',
        max_cv:   document.getElementById('max-cv')?.value        || ''
    });

    timeline.innerHTML = `<p class="subtitle text-center" style="margin-top:30px;">Cargando motores...</p>`;

    fetch(`/backend/api/get_vehicles.php?${queryParams.toString()}`)
        .then(r => {
            if (r.status === 401) { window.location.href = 'login.html'; return; }
            return r.json();
        })
        .then(data => {
            if (!data || !data.success) {
                timeline.innerHTML = `<p style="color:#fca5a5; text-align:center;">${data?.message || 'Error al cargar.'}</p>`;
                return;
            }

            // Rellenar marcas (solo la primera vez)
            const brandSelect = document.getElementById('brand-select');
            if (brandSelect && Array.isArray(data.marcas) && brandSelect.options.length <= 1) {
                data.marcas.forEach(marca => {
                    const opt     = document.createElement('option');
                    opt.value     = marca;
                    opt.textContent = marca;
                    brandSelect.appendChild(opt);
                });
            }

            // Sin resultados
            if (!data.vehicles || data.vehicles.length === 0) {
                timeline.innerHTML = `<p style="color:#94a3b8; text-align:center; margin-top:50px;">No se encontraron vehículos.</p>`;
                return;
            }

            // Pintar tarjetas
            timeline.innerHTML = data.vehicles.map(coche => {
                const notaMedia = coche.nota_media
                    ? parseFloat(coche.nota_media).toLocaleString('es-ES', { minimumFractionDigits: 1, maximumFractionDigits: 1 })
                    : '---';

                const bloqueImagen = coche.image
                    ? `<img src="${coche.image}" alt="Vehículo" onerror="this.src='/assets/img/default-car.jpg';">`
                    : `<div style="height:180px; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.2);">Sin foto</div>`;

                const botonAccion = coche.ya_opino
                    ? `<a href="comments.html?vehicle_id=${coche.id}#leer" class="btn-gris">Ver opiniones</a>`
                    : `<a href="comments.html?vehicle_id=${coche.id}" class="btn-azul">Opinar y ver detalles</a>`;

                return `
                <div class="vehicle-card">
                    <div style="display:flex; justify-content:space-between; align-items:baseline;">
                        <div>
                            <span style="color:#9ca3af; font-size:0.8rem;">Publicado por</span>
                            <a href="profile.html?id=${coche.user_id}" style="text-decoration:none;">
                                <strong style="color:#3b82f6; display:block; font-size:1rem;">@${escapeHtmlGlobal(coche.username)}</strong>
                            </a>
                        </div>
                        <div style="text-align:right;">
                            <span style="color:#6b7280; font-size:0.9rem; font-weight:bold; display:block;">${coche.year}</span>
                            <span style="color:#9ca3af; font-size:0.75rem;">${Number(coche.km).toLocaleString('es-ES')} km | ${coche.potencia_cv} CV</span>
                        </div>
                    </div>
                    <h3 style="margin:10px 0; color:#f8fafc;">${escapeHtmlGlobal(coche.brand)} ${escapeHtmlGlobal(coche.model)}</h3>
                    <p style="color:#94a3b8; font-size:0.95rem;">${escapeHtmlGlobal(coche.description)}</p>
                    <div class="img-wrapper">${bloqueImagen}</div>
                    <div class="stats-badge" style="color:#fff; margin:15px 0;">
                        <span style="color:#fbbf24; font-weight:bold;">${notaMedia}</span> | ${coche.total_comentarios} opiniones
                    </div>
                    ${botonAccion}
                </div>`;
            }).join('');
        })
        .catch(err => console.error("Error crítico:", err));
}