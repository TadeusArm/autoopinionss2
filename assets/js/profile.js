document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    let perfilId = urlParams.get('id');

    if (!perfilId || isNaN(perfilId)) {
        // Sin id en URL → intentar cargar el perfil propio desde sesión
        fetch('/backend/api/get_session.php')
            .then(r => r.json())
            .then(sess => {
                if (sess.success && sess.user && sess.user.id) {
                    history.replaceState(null, '', `profile.html?id=${sess.user.id}`);
                    cargarPerfil(sess.user.id);
                    window.onclick = function() {
                        document.querySelectorAll('.menu-dropdown').forEach(m => m.style.display = 'none');
                    };
                } else {
                    window.location.href = 'login.html';
                }
            })
            .catch(() => window.location.href = 'index.html');
        return;
    }

    cargarPerfil(perfilId);

    window.onclick = function() {
        document.querySelectorAll('.menu-dropdown').forEach(m => m.style.display = 'none');
    };
});

function cargarPerfil(perfilId) {
    const headerContainer = document.getElementById('profile-card-header');
    const vehiclesGrid = document.getElementById('user-vehicles-grid');
    const garageTitle = document.getElementById('garage-title');

    fetch(`/backend/api/get_profile.php?id=${perfilId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                window.location.href = data.banned ? 'user_banned.html' : 'index.html';
                return;
            }

            // 1. ACTUALIZAR HEADER NAVBAR
            if (typeof actualizarMenuNavbar === 'function') {
                const userHeader = data.user_header;
                if (userHeader.profile_pic) {
                    const nombrePic = userHeader.profile_pic.split('/').pop();
                    userHeader.profile_pic = `/assets/img/avatars/${nombrePic}`;
                }
                actualizarMenuNavbar(userHeader);
            }

            const prof = data.profile_data;
            document.title = `Perfil de @${escapeHTML(prof.username)} - AutoOpinions`;
            if (garageTitle) garageTitle.textContent = `Garaje de ${escapeHTML(prof.username)}`;

            // 2. AVATAR
            const nombreAvatar = prof.profile_pic ? prof.profile_pic.split('/').pop() : '';
            const rutaAvatar = nombreAvatar ? `/assets/img/avatars/${nombreAvatar}` : null;

            const bloqueAvatar = rutaAvatar
                ? `<img src="${rutaAvatar}" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`
                : prof.username.charAt(0).toUpperCase();

            // 3. LOCATION
            const locationHtml = prof.location
                ? `<div style="margin-top:8px; color:#94a3b8; font-size:0.9rem;">
                       📍 ${escapeHTML(prof.location)}
                   </div>`
                : '';

            // 4. INSTAGRAM 
            const igHtml = prof.instagram_user
                ? `<div style="margin-top:8px;">
                       <a href="https://instagram.com/${escapeHTML(prof.instagram_user)}" target="_blank" rel="noopener noreferrer"
                          title="@${escapeHTML(prof.instagram_user)} en Instagram"
                          style="display:inline-flex; align-items:center; text-decoration:none; transition:transform 0.2s; line-height:0;"
                          onmouseover="this.style.transform='scale(1.15)'" onmouseout="this.style.transform='scale(1)'">
                           <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                               <defs>
                                   <radialGradient id="ig-grad" cx="30%" cy="107%" r="150%">
                                       <stop offset="0%" stop-color="#fdf497"/>
                                       <stop offset="10%" stop-color="#fdf497"/>
                                       <stop offset="30%" stop-color="#fd5949"/>
                                       <stop offset="60%" stop-color="#d6249f"/>
                                       <stop offset="90%" stop-color="#285AEB"/>
                                   </radialGradient>
                               </defs>
                               <rect x="2" y="2" width="20" height="20" rx="5" ry="5" fill="url(#ig-grad)"/>
                               <rect x="2" y="2" width="20" height="20" rx="5" ry="5" fill="none" stroke="none"/>
                               <circle cx="12" cy="12" r="4" fill="none" stroke="white" stroke-width="1.8"/>
                               <circle cx="17.5" cy="6.5" r="1.2" fill="white"/>
                           </svg>
                       </a>
                   </div>`
                : '';

            // 5. BOTÓN DE ACCIÓN
            // Usamos 1/0 en lugar de true/false para pasar correctamente al onclick inline
            const loSigoInt = prof.lo_sigo ? 1 : 0;
            let botonAccionHeader = data.is_owner
                ? `<a href="edit_profile.html" class="btn-editar-perfil">Configuración</a>`
                : `<button type="button" class="btn-follow ${prof.lo_sigo ? 'unfollow-active' : 'follow-active'}" onclick="ejecutarAccionFollow(${prof.id}, ${loSigoInt})">${prof.lo_sigo ? 'Siguiendo' : 'Seguir'}</button>`;

            // 6. CABECERA COMPLETA
            headerContainer.innerHTML = `
    <div class="user-avatar-big">${bloqueAvatar}</div>
    <h1 class="username-title">@${escapeHTML(prof.username)}</h1>

    <div class="header-action-container" style="margin:15px 0; display:block;">
        ${botonAccionHeader}
    </div>

    <div class="bio-text" style="margin-top:10px; color:#cbd5e1;">${escapeHTML(prof.bio).replace(/\n/g, '<br>')}</div>

    ${locationHtml}
    ${igHtml}

    <div class="stats-bar">
        <div class="stat-item"><span class="stat-num">${data.garage.length}</span><span class="stat-label">Coches</span></div>
        <div class="stat-item"><span class="stat-num">${prof.total_seguidores}</span><span class="stat-label">Seguidores</span></div>
        <div class="stat-item"><span class="stat-num">${prof.total_seguidos}</span><span class="stat-label">Seguidos</span></div>
    </div>
`;

            // 7. GARAJE
            vehiclesGrid.innerHTML = data.garage.length === 0
                ? `<div style="grid-column:1/-1; text-align:center; padding:40px;">Este usuario aún no ha subido ningún coche.</div>`
                : data.garage.map(v => {
                    const nombreImg = v.image ? v.image.split('/').pop() : '';
                    const srcCoche = nombreImg ? `/assets/img/vehicles/${nombreImg}` : '';

                    return `
                    <div style="position:relative; overflow:visible;">
                        ${data.is_owner ? `
                            <div style="position:absolute; top:10px; right:10px; z-index:50;">
                                <button type="button" onclick="menuContextualToggle(event, ${v.id})"
                                    style="background:rgba(0,0,0,0.7); border:none; color:white; border-radius:50%; width:32px; height:32px; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                                    &#8942;
                                </button>
                                <div id="menu-${v.id}" class="menu-dropdown" style="display:none; position:absolute; right:0; top:40px; background:#1f2937; border:1px solid #374151; border-radius:8px; z-index:100; min-width:120px; box-shadow:0 4px 15px rgba(0,0,0,0.5);">
                                    <a href="edit_vehicle.html?id=${v.id}" style="display:block; padding:10px; color:white; text-decoration:none; font-size:0.9rem;" onclick="event.stopPropagation()">Editar</a>
                                    <button type="button" style="display:block; width:100%; padding:10px; color:#ef4444; background:none; border:none; text-align:left; cursor:pointer; font-size:0.9rem;" onclick="eliminarPublicacionCoche(event, ${v.id})">Borrar</button>
                                </div>
                            </div>` : ''}
                        <a href="comments.html?vehicle_id=${v.id}" class="mini-card-coche" style="display:block; text-decoration:none;">
                            <div style="height:180px; background:#111; overflow:hidden;">${srcCoche ? `<img src="${srcCoche}" style="width:100%; height:100%; object-fit:cover;">` : '<div style="padding:20px;">Sin foto</div>'}</div>
                            <div style="padding:15px;"><h4 style="margin:0; color:white;">${escapeHTML(v.brand)} ${escapeHTML(v.model)}</h4></div>
                        </a>
                    </div>`;
                }).join('');
        })
        .catch(err => console.error("Error al cargar perfil:", err));
}

// Funciones globales
window.menuContextualToggle = (e, id) => {
    e.stopPropagation();
    document.querySelectorAll('.menu-dropdown').forEach(m => m.style.display = 'none');
    const m = document.getElementById('menu-' + id);
    if (m) m.style.display = 'block';
};

window.ejecutarAccionFollow = (id, loSigoInt) => {
    const accion = loSigoInt ? 'unfollow' : 'follow';
    const fd = new FormData();
    fd.append('followed_id', id);
    fd.append('accion', accion);
    fetch('/backend/api/follow_action.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else console.error('Error follow:', data.message);
        })
        .catch(err => console.error('Error fetch:', err));
};

window.eliminarPublicacionCoche = (e, id) => {
    e.stopPropagation();
    if (confirm('¿Borrar publicación?')) {
        const fd = new FormData();
        fd.append('vehicle_id', id);
        fetch('/backend/api/delete_vehicle.php', { method: 'POST', body: fd })
            .then(() => location.reload());
    }
};

function escapeHTML(str) {
    return String(str).replace(/[&<>'"]/g, tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag]));
}