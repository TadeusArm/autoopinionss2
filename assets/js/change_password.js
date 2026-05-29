// assets/js/change_password.js


document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("btn-submit").addEventListener("click", cambiarPassword);
});

async function cambiarPassword() {
    const currentPass = document.getElementById("current_password").value.trim();
    const newPass     = document.getElementById("new_password").value.trim();

    if (!currentPass || !newPass) {
        mostrarAlerta("Por favor rellena todos los campos.", "error");
        return;
    }

    if (newPass.length < 6) {
        mostrarAlerta("La nueva contraseña debe tener al menos 6 caracteres.", "error");
        return;
    }

    mostrarAlerta("Guardando...", "info");

    try {
        const formData = new FormData();
        formData.append("current_password", currentPass);
        formData.append("new_password", newPass);

        const res  = await fetch(`${API}/change_password.php`, {
            method:      "POST",
            body:        formData,
            credentials: 'include'
        });
        const data = await res.json();

        if (data.success) {
            mostrarAlerta(data.message, "success");
            document.getElementById("current_password").value = "";
            document.getElementById("new_password").value     = "";
        } else {
            mostrarAlerta(data.message, "error");
        }
    } catch (err) {
        mostrarAlerta("Error de conexión con el servidor.", "error");
        console.error(err);
    }
}

function mostrarAlerta(mensaje, tipo) {
    const container = document.getElementById("alert-container");
    const clase     = tipo === "success" ? "alert-success"
                    : tipo === "error"   ? "alert-error"
                    : "alert-info";
    container.innerHTML = `<div class="alert ${clase}">${mensaje}</div>`;
}