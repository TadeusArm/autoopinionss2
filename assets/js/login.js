document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById('login-form');
    const alertContainer = document.getElementById('alert-container');

    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const loginInputVal = document.getElementById('login_input').value.trim();
            const passwordVal = document.getElementById('password').value;

            if (!loginInputVal || !passwordVal) {
                mostrarAlerta('Por favor, rellena todos los campos.', 'error');
                return;
            }

            // Codificación nativa de formulario para compatibilidad estricta con PHP $_POST
            const params = new URLSearchParams();
            params.append('login_input', loginInputVal);
            params.append('password', passwordVal);

            fetch('/backend/api/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.html';
                } else {
                    mostrarAlerta(data.message, 'error');
                }
            })
            .catch(err => {
                console.error('Error de red:', err);
                mostrarAlerta('Error de conexión con el servidor.', 'error');
            });
        });
    }

    function mostrarAlerta(msg, tipo) {
        if (!alertContainer) return;
        alertContainer.innerHTML = `<div class="alert alert-${tipo}">${msg}</div>`;
    }
});