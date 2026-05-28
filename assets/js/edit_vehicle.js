// assets/js/edit_vehicle.js

document.addEventListener("DOMContentLoaded", () => {
    const params    = new URLSearchParams(window.location.search);
    const vehicleId = params.get('id');

    if (!vehicleId || isNaN(vehicleId)) {
        window.location.href = 'muro.html';
        return;
    }

    cargarVehiculo(vehicleId);

    const form = document.getElementById('edit-vehicle-form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await guardarCambios(vehicleId);
    });
});

async function cargarVehiculo(vehicleId) {
    try {
        const res  = await fetch(`/backend/api/edit_vehicle.php?id=${vehicleId}`);
        const data = await res.json();

        if (!data.success) {
            window.location.href = 'muro.html';
            return;
        }

        const v = data.vehicle;
        document.getElementById('brand').value       = v.brand       || '';
        document.getElementById('model').value       = v.model       || '';
        document.getElementById('year').value        = v.year        || '';
        document.getElementById('km').value          = v.km          || '';
        document.getElementById('potencia_cv').value = v.potencia_cv || '';
        document.getElementById('description').value = v.description || v.descripcion || '';

        const cancelLink = document.getElementById('cancel-link');
        if (cancelLink && data.user_id) {
            cancelLink.href = `profile.html?id=${data.user_id}`;
        }
    } catch (err) {
        console.error('Error al cargar el vehículo:', err);
        mostrarAlerta('Error al cargar los datos del vehículo.', 'error');
    }
}

async function guardarCambios(vehicleId) {
    const btn = document.querySelector('button[type="submit"]');
    btn.disabled    = true;
    btn.textContent = 'Guardando...';
    mostrarAlerta('Guardando cambios...', 'info');

    try {
        const form = document.getElementById('edit-vehicle-form');
        const fd   = new FormData(form);
        fd.append('vehicle_id', vehicleId);

        const res  = await fetch('/backend/api/edit_vehicle.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            mostrarAlerta('¡Vehículo actualizado correctamente!', 'success');
            setTimeout(() => { window.location.href = data.redirect || 'muro.html'; }, 800);
        } else {
            mostrarAlerta(data.message || 'Error al guardar los cambios.', 'error');
            btn.disabled    = false;
            btn.textContent = 'Guardar cambios';
        }
    } catch (err) {
        console.error(err);
        mostrarAlerta('Error de conexión. Inténtalo de nuevo.', 'error');
        btn.disabled    = false;
        btn.textContent = 'Guardar cambios';
    }
}

function mostrarAlerta(mensaje, tipo) {
    const el    = document.getElementById('alert-container');
    if (!el) return;
    const clase = tipo === 'success' ? 'alert-success' : tipo === 'error' ? 'alert-error' : 'alert-info';
    el.innerHTML = `<div class="alert ${clase}">${mensaje}</div>`;
}