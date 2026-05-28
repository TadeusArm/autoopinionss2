// assets/js/home.js

// Si el usuario ya tiene sesión, redirigir al muro
fetch('/backend/api/get_session.php')
    .then(r => r.json())
    .then(data => {
        if (data.success) window.location.href = 'muro.html';
    })
    .catch(() => {});

document.addEventListener("DOMContentLoaded", () => {
    cargarPreview();
});

async function cargarPreview() {
    const grid = document.getElementById('preview-grid');

    try {
        const res  = await fetch('/backend/api/get_public_vehicles.php');
        const data = await res.json();

        if (!data.success || !data.vehicles.length) {
            grid.innerHTML = `<p class="home-loading">No hay publicaciones aún.</p>`;
            return;
        }

        grid.innerHTML = data.vehicles.map(v => {
            const img = v.image
                ? `<img src="${v.image}" alt="${escapeHTML(v.brand)} ${escapeHTML(v.model)}" class="home-card-img">`
                : `<div class="home-card-no-img">Sin foto</div>`;

            const estrellas = v.nota_media
                ? `<span class="home-card-rating">${parseFloat(v.nota_media).toFixed(1)}</span> <span class="home-card-rating-max">/ 5</span>`
                : `<span class="home-card-rating-none">Sin valoraciones</span>`;

            return `
            <div class="home-vehicle-card" onclick="window.location.href='login.html'">
                <div class="home-card-img-wrapper">${img}</div>
                <div class="home-card-body">
                    <h3 class="home-card-title">${escapeHTML(v.brand)} ${escapeHTML(v.model)}</h3>
                    <p class="home-card-meta">${v.year} · ${Number(v.km).toLocaleString('es-ES')} km · ${v.potencia_cv} CV</p>
                    <div class="home-card-footer">
                        <span class="home-card-user">@${escapeHTML(v.username)}</span>
                        <span>${estrellas}</span>
                    </div>
                    <div class="home-card-cta">Inicia sesión para opinar →</div>
                </div>
            </div>`;
        }).join('');

    } catch (err) {
        grid.innerHTML = `<p class="home-loading">No se pudieron cargar las publicaciones.</p>`;
    }
}

function escapeHTML(str) {
    return String(str || '').replace(/[&<>'"]/g, t => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[t]));
}