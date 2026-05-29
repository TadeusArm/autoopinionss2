// assets/js/edit_profile.js


document.addEventListener("DOMContentLoaded", () => {
    cargarDatosPerfil();
    iniciarPreviewAvatar();
    document.getElementById("edit-profile-form").addEventListener("submit", guardarPerfil);
    document.getElementById("btn-logout").addEventListener("click", cerrarSesion);
});
 
// --- 1. CARGA LOS DATOS ACTUALES DEL USUARIO ---
async function cargarDatosPerfil() {
    try {
        const res  = await fetch(`${API}/get_session.php`, { credentials: 'include' });
        const data = await res.json();
 
        if (!data.success || !data.user) {
            window.location.href = "login.html";
            return;
        }
 
        const user = data.user;
 
        // Rellenar campos del formulario con lo que viene de sesión
        document.getElementById("username").value = user.username ?? "";
 
        // Estos campos no están en sesión — hacemos una segunda llamada para obtenerlos
        cargarDatosExtendidos(user.id);
 
        // Nombre en la cabecera
        document.getElementById("profile-username").textContent = "@" + user.username;
 
        // Avatar
        const preview     = document.getElementById("avatar-preview");
        const placeholder = document.getElementById("avatar-placeholder");
 
        if (user.profile_pic) {
            const nombreFoto = user.profile_pic.split("/").pop();
            const rutaFoto   = `/assets/img/avatars/${nombreFoto}`;
            const img        = document.createElement("img");
            img.src          = rutaFoto;
            img.alt          = "Avatar";
            preview.appendChild(img);
            placeholder.style.display = "none";
        } else {
            placeholder.textContent = (user.username ?? "U").charAt(0).toUpperCase();
        }
 
        // Navbar
        if (typeof actualizarMenuNavbar === "function") {
            actualizarMenuNavbar(user);
        }
 
    } catch (err) {
        console.error("Error al cargar sesión:", err);
        window.location.href = "login.html";
    }
}
 
// --- 2. CARGA BIO, LOCATION E INSTAGRAM DESDE LA BD ---
async function cargarDatosExtendidos(userId) {
    try {
        const res  = await fetch(`${API}/get_profile.php?id=${userId}`, { credentials: 'include' });
        const data = await res.json();
 
        if (!data.success) return;
 
        const prof = data.profile_data ?? data.user ?? data;
 
        document.getElementById("bio").value            = prof.bio            ?? "";
        document.getElementById("location").value       = prof.location       ?? "";
        document.getElementById("instagram_user").value = prof.instagram_user ?? "";
 
        // Email en la cabecera (si lo devuelve get_profile)
        if (prof.email) {
            document.getElementById("profile-email").textContent = prof.email;
        }
 
    } catch (err) {
        console.error("Error al cargar datos extendidos:", err);
    }
}
 
// --- 3. PREVIEW LOCAL DE LA FOTO ANTES DE SUBIR ---
function iniciarPreviewAvatar() {
    document.getElementById("file-input").addEventListener("change", function (evt) {
        const file = evt.target.files[0];
        if (!file) return;
 
        const fr  = new FileReader();
        fr.onload = function () {
            const preview     = document.getElementById("avatar-preview");
            const placeholder = document.getElementById("avatar-placeholder");
            let img           = preview.querySelector("img");
 
            if (!img) {
                img     = document.createElement("img");
                img.alt = "Avatar";
                preview.appendChild(img);
            }
            img.src = fr.result;
            if (placeholder) placeholder.style.display = "none";
        };
        fr.readAsDataURL(file);
    });
}
 
// --- 4. ENVÍO DEL FORMULARIO VÍA FETCH ---
async function guardarPerfil(evt) {
    evt.preventDefault();
    mostrarAlerta("Guardando...", "info");
 
    try {
        const formData = new FormData(evt.target);
        const res      = await fetch(`${API}/edit_profile.php`, {
            method:      "POST",
            body:        formData,
            credentials: 'include'
        });
        const data = await res.json();
 
        if (data.success) {
            mostrarAlerta(data.message, "success");
        } else {
            mostrarAlerta(data.message ?? "Error al guardar.", "error");
        }
    } catch (err) {
        mostrarAlerta("Error de conexión con el servidor.", "error");
        console.error(err);
    }
}
 
// --- 5. CERRAR SESIÓN ---
async function cerrarSesion(evt) {
    evt.preventDefault();
    try {
        await fetch(`${API}/logout.php`, { method: "POST", credentials: 'include' });
    } catch (err) {
        console.error("Error al cerrar sesión:", err);
    }
    window.location.href = "login.html";
}
 
// --- 6. MOSTRAR ALERTA ---
function mostrarAlerta(mensaje, tipo) {
    const container = document.getElementById("alert-container");
    const claseCSS  = tipo === "success" ? "alert-success"
                    : tipo === "error"   ? "alert-error"
                    : "alert-info";
    container.innerHTML = `<div class="alert ${claseCSS}">${mensaje}</div>`;
}