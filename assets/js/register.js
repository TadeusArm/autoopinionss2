// assets/js/register.js
const API = 'https://api.autoopinions.es';

document.addEventListener("DOMContentLoaded", () => {
    const registerForm   = document.getElementById('register-form');
    const alertContainer = document.getElementById('alert-container');

    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(registerForm);

            try {
                const response = await fetch(`${API}/register.php`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });

                // Si la respuesta no es OK, leemos el texto crudo para debugear
                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error("Respuesta del servidor no es JSON:", text);
                    mostrarAlerta('Error interno del servidor. Revisa la consola.', 'error');
                    return;
                }

                if (data.success) {
                    mostrarAlerta(data.message, 'success');
                    registerForm.style.display = 'none';
                } else {
                    mostrarAlerta(data.message, 'error');
                }
            } catch (err) {
                mostrarAlerta('Error de red: ' + err.message, 'error');
            }
        });
    }

    function mostrarAlerta(msg, tipo) {
        if (!alertContainer) return;
        alertContainer.innerHTML = `<div class="alert alert-${tipo}">${msg}</div>`;
    }
});