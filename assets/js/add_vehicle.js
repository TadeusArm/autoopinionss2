// assets/js/add_vehicle.js

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById('add-vehicle-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await publicarVehiculo();
    });
});

async function publicarVehiculo() {
    const form = document.getElementById('add-vehicle-form');
    const btn  = form.querySelector('button[type="submit"]');

    // Validación mínima del archivo
    const imageInput = document.getElementById('image');
    if (!imageInput.files || imageInput.files.length === 0) {
        mostrarAlerta('Debes seleccionar una imagen.', 'error');
        return;
    }

    btn.disabled    = true;
    btn.textContent = 'Publicando...';
    mostrarAlerta('Subiendo vehículo...', 'info');

    try {
        const fd = new FormData(form);

        const res  = await fetch('/backend/api/add_vehicle.php', {
            method: 'POST',
            body: fd
        });

        const data = await res.json();

        if (data.success) {
            mostrarAlerta('¡Vehículo publicado correctamente!', 'success');
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 800);
        } else {
            mostrarAlerta(data.message || 'Error al publicar el vehículo.', 'error');
            btn.disabled    = false;
            btn.textContent = 'Publicar ahora';
        }

    } catch (err) {
        console.error(err);
        mostrarAlerta('Error de conexión. Inténtalo de nuevo.', 'error');
        btn.disabled    = false;
        btn.textContent = 'Publicar ahora';
    }
}

function mostrarAlerta(mensaje, tipo) {
    const el    = document.getElementById('alert-container');
    if (!el) return;
    const clase = tipo === 'success' ? 'alert-success'
                : tipo === 'error'   ? 'alert-error'
                :                      'alert-info';
    el.innerHTML = `<div class="alert ${clase}">${mensaje}</div>`;
}