// assets/js/main.js
const API = 'https://api.autoopinions.es';

document.addEventListener("DOMContentLoaded", () => {
    inicializarHeader();
});

function inicializarHeader() {
    fetch(`${API}/get_session.php`, { credentials: 'include' })
        .then(r => {
            if (r.status === 401 || !r.ok) throw new Error("Sin sesión");
            return r.json();
        })
        .then(data => {
            if (!data || !data.success) throw new Error("Sesión inválida");
            const user = data.user;

            const nombrePic    = user.profile_pic ? user.profile_pic.split('/').pop() : null;
            const rutaAvatar   = nombrePic ? `/assets/img/avatars/${nombrePic}` : null;
            const bloqueAvatar = rutaAvatar
                ? `<img src="${rutaAvatar}" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`
                : `<span style="font-size:0.9rem;">${escapeHtmlGlobal(user.username).charAt(0).toUpperCase()}</span>`;

            const pastillaPerfil = `
                <a href="profile.html?id=${user.id}" class="profile-pill" title="Mi Perfil">
                    <span class="user-pill-name">${escapeHtmlGlobal(user.username)}</span>
                    <div class="user-avatar-small">${bloqueAvatar}</div>
                </a>`;

            const globalContainer = document.getElementById('global-header-container');
            if (globalContainer) {
                const pag             = window.location.pathname.split('/').pop() || 'muro.html';
                const activeInicio    = (pag === 'muro.html' || pag === '') ? 'active' : '';
                const activeSiguiendo = pag === 'following_feed.html'       ? 'active' : '';
                const activePublicar  = pag === 'add_vehicle.html'          ? 'active' : '';
                const activeAdmin     = (pag === 'admin.html' || pag === 'admin_dashboard.html') ? 'active' : '';

                globalContainer.innerHTML = `
                    <header class="nav-header">
                        <div class="header-container">
                            <div class="nav-left">
                                <a href="muro.html" class="nav-logo">AUTO OPINIONS</a>
                            </div>
                            <nav class="nav-links">
                                <a href="muro.html"           class="${activeInicio}">Inicio</a>
                                <a href="following_feed.html" class="${activeSiguiendo}">Siguiendo</a>
                                <a href="add_vehicle.html"    class="${activePublicar}">Publicar</a>
                                ${user.role === 'admin' ? `<a href="admin.html" class="nav-admin-link ${activeAdmin}">Admin</a>` : ''}
                            </nav>
                            <div class="nav-right">${pastillaPerfil}</div>
                        </div>
                    </header>`;
                return;
            }

            const navContainer = document.getElementById('nav-profile-container');
            if (navContainer) {
                navContainer.innerHTML = pastillaPerfil;
            }
        })
        .catch(err => {
            console.warn("Sin sesión activa:", err);

            const btnLogin = `<a href="login.html" style="background:#3b82f6; color:white; padding:8px 15px; border-radius:8px; text-decoration:none; font-weight:bold; font-size:0.9rem;">Iniciar Sesión</a>`;

            const globalContainer = document.getElementById('global-header-container');
            if (globalContainer) {
                globalContainer.innerHTML = `
                    <header class="nav-header">
                        <div class="header-container">
                            <div class="nav-left">
                                <a href="muro.html" class="nav-logo">AUTO OPINIONS</a>
                            </div>
                            <div class="nav-right">${btnLogin}</div>
                        </div>
                    </header>`;
            }

            // Redirigir a login solo desde páginas que requieren sesión
            // index.html (home), login.html, register.html etc. NO redirigen
            const pag = window.location.pathname.split('/').pop() || 'index.html';
            const sinRedireccion = [
                'index.html',
                'login.html',
                'register.html',
                'comments.html',
                'profile.html',
                'change_password.html',
                'admin.html',
                'admin_dashboard.html'
            ];
            if (!sinRedireccion.some(p => pag.includes(p))) {
                window.location.href = 'login.html';
            }
        });
}

function actualizarMenuNavbar(userHeader) {
    if (!userHeader) return;

    const nombrePic    = userHeader.profile_pic ? userHeader.profile_pic.split('/').pop() : null;
    const rutaAvatar   = nombrePic ? `/assets/img/avatars/${nombrePic}` : null;
    const bloqueAvatar = rutaAvatar
        ? `<img src="${rutaAvatar}" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`
        : `<span style="font-size:0.9rem;">${escapeHtmlGlobal(userHeader.username || 'U').charAt(0).toUpperCase()}</span>`;

    const navContainer = document.getElementById('nav-profile-container');
    if (navContainer) {
        navContainer.innerHTML = `
            <a href="profile.html" class="profile-pill" title="Mi Perfil">
                <span class="user-pill-name">${escapeHtmlGlobal(userHeader.username || '')}</span>
                <div class="user-avatar-small">${bloqueAvatar}</div>
            </a>`;
    }
}

function escapeHtmlGlobal(str) {
    if (!str) return '';
    return String(str).replace(/[&<>'"]/g, t => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[t] || t));
}