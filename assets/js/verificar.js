// assets/js/verificar.js
// Este archivo no hace llamadas a la API — usa datos inyectados por PHP (verifData)
// No necesita cambios para la separación frontend/backend

document.addEventListener("DOMContentLoaded", () => {
    const alertBox   = document.getElementById('alert-box');
    const mainBtn    = document.getElementById('main-btn');
    const supportLink = document.getElementById('support-link');

    // Aplicar datos inyectados por el PHP
    alertBox.textContent = verifData.message;
    alertBox.className   = `alert alert-${verifData.status}`;

    if (verifData.status !== 'success') {
        mainBtn.textContent      = "Contactar Soporte";
        mainBtn.style.background = "#ef4444";
        mainBtn.href             = "mailto:notificaciones@autoopinions.es";
    }
    
    supportLink.href = "https://mail.google.com/mail/u/0/?fs=1&to=notificaciones@autoopinions.es&su=Escribenos+dudas+a+AutoOpinions&tf=cm";
});