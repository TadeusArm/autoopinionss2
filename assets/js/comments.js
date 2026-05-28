// assets/js/comments.js

document.addEventListener("DOMContentLoaded", () => {
    const params    = new URLSearchParams(window.location.search);
    const vehicleId = params.get('vehicle_id');

    if (!vehicleId || isNaN(vehicleId)) {
        window.location.href = 'muro.html';
        return;
    }

    cargarPagina(vehicleId);

    if (window.location.hash === '#leer') {
        setTimeout(() => {
            document.getElementById('leer')?.scrollIntoView({ behavior: 'smooth' });
        }, 600);
    }
});

async function cargarPagina(vehicleId) {
    try {
        const res  = await fetch(`/backend/api/comments.php?vehicle_id=${vehicleId}`);
        const data = await res.json();

        if (!data.success) {
            window.location.href = 'muro.html';
            return;
        }

        if (typeof actualizarMenuNavbar === 'function') {
            actualizarMenuNavbar(data.user_header);
        }

        renderCocheInfo(data.coche);
        renderFormulario(data.coche, data.ya_opine, vehicleId);
        renderComentarios(data.comentarios, vehicleId);

    } catch (err) {
        console.error("Error al cargar la página de comentarios:", err);
    }
}

function renderCocheInfo(coche) {
    const srcImg = coche.image
        ? `<a href="${coche.image}" target="_blank">
               <img src="${coche.image}" class="img-preview" alt="Coche">
           </a>`
        : `<img src="assets/img/no-foto.webp" class="img-preview" alt="Sin foto">`;

    document.getElementById('coche-info').innerHTML = `
        <div class="coche-preview-header">
            ${srcImg}
            <div>
                <h2 style="margin:0; color:#60a5fa;">${escapeHTML(coche.brand + ' ' + coche.model)}</h2>
                <p style="color:#9ca3af; margin:5px 0;">Publicado por: <b>@${escapeHTML(coche.username)}</b></p>
            </div>
        </div>`;
}

function renderFormulario(coche, yaOpinó, vehicleId) {
    const bloque = document.getElementById('bloque-form');

    if (yaOpinó) {
        bloque.innerHTML = `<div style="text-align:center; color:#4ade80; font-weight:bold;">✓ Ya has valorado este vehículo.</div>`;
        return;
    }

    bloque.innerHTML = `
        <h3 style="margin-top:0;">Danos tu valoración</h3>
        <div id="alert-form"></div>
        <div class="rating-stars">
            <input type="radio" id="star5" name="nota" value="5"><label for="star5">★</label>
            <input type="radio" id="star4" name="nota" value="4"><label for="star4">★</label>
            <input type="radio" id="star3" name="nota" value="3"><label for="star3">★</label>
            <input type="radio" id="star2" name="nota" value="2"><label for="star2">★</label>
            <input type="radio" id="star1" name="nota" value="1"><label for="star1">★</label>
        </div>
        <textarea id="comentario-principal" placeholder="¿Qué te parece este coche?" style="height:100px;"></textarea>
        <button type="button" class="btn-verde" style="margin-top:15px;" onclick="enviarOpinion(${vehicleId})">
            Publicar Opinión
        </button>`;
}

function renderComentarios(lista, vehicleId) {
    const contenedor = document.getElementById('lista-comentarios');

    if (!lista || lista.length === 0) {
        contenedor.innerHTML = `<p style="color:#94a3b8; text-align:center;">Sé el primero en opinar.</p>`;
        return;
    }

    contenedor.innerHTML = lista.map(l => {
        const esRespuesta = !!l.parent_id;

        const citaHtml = (esRespuesta && l.parent_username)
            ? `<div class="quote-block">
                   <div class="quote-author">↩ @${escapeHTML(l.parent_username)}</div>
                   <div class="quote-text">${escapeHTML((l.parent_content || '').substring(0, 120))}${(l.parent_content || '').length > 120 ? '…' : ''}</div>
               </div>`
            : '';

        const estrellas = (!esRespuesta && l.rating)
            ? `<span style="color:#fbbf24;">${[1,2,3,4,5].map(i => i <= l.rating ? '★' : '☆').join('')}</span>`
            : '';

        return `
        <div class="comment-item">
            ${citaHtml}
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <strong style="color:${esRespuesta ? '#93c5fd' : '#f8fafc'};">@${escapeHTML(l.username)}</strong>
                ${estrellas}
            </div>
            <p style="margin:8px 0; color:#d1d5db;">${escapeHTML(l.content).replace(/\n/g, '<br>')}</p>
            <button class="btn-responder" onclick="toggleReply(${l.id}, '${escapeAttr(l.username)}', '${escapeAttr((l.content || '').substring(0, 100))}')">
                ↩ Responder
            </button>
            <div id="form-${l.id}" class="form-respuesta">
                <div class="reply-preview" id="preview-${l.id}">
                    <div class="preview-author" id="preview-author-${l.id}"></div>
                    <div class="preview-text"  id="preview-text-${l.id}"></div>
                </div>
                <textarea id="textarea-${l.id}" placeholder="Escribe tu respuesta..."></textarea>
                <button type="button" class="btn-respuesta-enviar" onclick="enviarRespuesta(${l.id}, ${vehicleId})">
                    Enviar respuesta
                </button>
            </div>
        </div>`;
    }).join('');
}

async function enviarOpinion(vehicleId) {
    const nota       = document.querySelector('input[name="nota"]:checked')?.value;
    const comentario = document.getElementById('comentario-principal')?.value.trim();

    if (!nota) { mostrarAlertaForm("Selecciona una puntuación.", "error"); return; }
    if (!comentario) { mostrarAlertaForm("Escribe un comentario.", "error"); return; }

    mostrarAlertaForm("Publicando...", "info");

    try {
        const fd = new FormData();
        fd.append('vehicle_id', vehicleId);
        fd.append('nota', nota);
        fd.append('comentario', comentario);

        const res  = await fetch('/backend/api/comments.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            setTimeout(() => window.location.reload(), 300);
        } else {
            mostrarAlertaForm(data.message || "Error al publicar.", "error");
        }
    } catch (err) {
        mostrarAlertaForm("Error de conexión.", "error");
        console.error(err);
    }
}

async function enviarRespuesta(parentId, vehicleId) {
    const comentario = document.getElementById(`textarea-${parentId}`)?.value.trim();
    if (!comentario) return;

    try {
        const fd = new FormData();
        fd.append('vehicle_id', vehicleId);
        fd.append('comentario', comentario);
        fd.append('parent_id', parentId);

        const res  = await fetch('/backend/api/comments.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            setTimeout(() => window.location.reload(), 300);
        } else {
            alert(data.message || "Error al enviar la respuesta.");
        }
    } catch (err) {
        console.error(err);
    }
}

function toggleReply(id, author, text) {
    document.querySelectorAll('.form-respuesta').forEach(f => f.style.display = 'none');

    const form    = document.getElementById(`form-${id}`);
    const preview = document.getElementById(`preview-${id}`);
    const pAuthor = document.getElementById(`preview-author-${id}`);
    const pText   = document.getElementById(`preview-text-${id}`);

    pAuthor.textContent   = `↩ @${author}`;
    pText.textContent     = text + (text.length >= 100 ? '…' : '');
    preview.style.display = 'block';
    form.style.display    = 'block';
    document.getElementById(`textarea-${id}`)?.focus();
}

function mostrarAlertaForm(mensaje, tipo) {
    const el    = document.getElementById('alert-form');
    if (!el) return;
    const clase = tipo === 'success' ? 'alert-success' : tipo === 'error' ? 'alert-error' : 'alert-info';
    el.innerHTML = `<div class="alert ${clase}" style="margin-bottom:12px;">${mensaje}</div>`;
}

function escapeHTML(str) {
    return String(str || '').replace(/[&<>'"]/g, t => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[t]));
}

function escapeAttr(str) {
    return String(str || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}