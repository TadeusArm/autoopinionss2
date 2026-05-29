// assets/js/admin.js

document.addEventListener("DOMContentLoaded", () => {
    cargarUsuarios();
});

async function cargarUsuarios() {
    try {
        const res  = await fetch(`${API}/admin.php`, { credentials: 'include' });
        const data = await res.json();

        if (!data.success) {
            window.location.href = 'muro.html';
            return;
        }

        const tbody = document.getElementById('users-tbody');
        tbody.innerHTML = data.usuarios.map(u => {
            const avatar = u.profile_pic
                ? `<a href="profile.html?id=${u.id}"><img src="/assets/img/avatars/${u.profile_pic.split('/').pop()}" class="mini-avatar"></a>`
                : `<a href="profile.html?id=${u.id}"><div class="mini-avatar" style="background:#4b5563; display:grid; place-items:center; font-size:12px;">${u.username.charAt(0).toUpperCase()}</div></a>`;

            const badge = u.role === 'admin'
                ? `<span class="badge badge-admin">ADMIN</span>`
                : `<span class="badge badge-user">USER</span>`;

            const accion = u.is_current
                ? `<span style="font-size:0.8rem; color:#6b7280; font-style:italic;">Cuenta actual</span>`
                : `<button type="button" class="btn-delete" onclick="banearUsuario(${u.id}, this)">Bannear</button>`;

            return `
            <tr class="user-row">
                <td>
                    <div class="user-info">
                        ${avatar}
                        <span class="username-cell">${u.username}</span>
                    </div>
                </td>
                <td style="color:#9ca3af; font-size:0.9rem;">${u.email}</td>
                <td>${badge}</td>
                <td style="text-align:right;">${accion}</td>
            </tr>`;
        }).join('');

    } catch (err) {
        console.error('Error al cargar usuarios:', err);
    }
}

window.banearUsuario = async function(userId, btn) {
    const username = btn.closest('tr').querySelector('.username-cell').textContent;
    if (!confirm(`¿Baneamos a @${username}? Se eliminarán todos sus coches y comentarios y su correo quedará bloqueado.`)) return;

    btn.disabled    = true;
    btn.textContent = 'Baneando...';

    try {
        const fd = new FormData();
        fd.append('user_id', userId);

        const res  = await fetch(`${API}/admin.php`, { method: 'POST', body: fd, credentials: 'include' });
        const data = await res.json();

        if (data.success) {
            mostrarAlerta(`@${username} ha sido baneado correctamente.`, 'success');
            cargarUsuarios();
        } else {
            mostrarAlerta(data.message || 'Error al banear.', 'error');
            btn.disabled    = false;
            btn.textContent = 'Bannear';
        }
    } catch (err) {
        mostrarAlerta('Error de conexión.', 'error');
        btn.disabled    = false;
        btn.textContent = 'Bannear';
    }
};

function mostrarAlerta(mensaje, tipo) {
    const el = document.getElementById('alert-container');
    if (!el) return;
    const clase = tipo === 'success' ? 'alert-success' : 'alert-error';
    el.innerHTML = `<div class="alert ${clase}" style="margin-bottom:20px;">${mensaje}</div>`;
    setTimeout(() => el.innerHTML = '', 4000);
}