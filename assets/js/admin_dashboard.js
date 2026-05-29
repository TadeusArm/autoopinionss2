// assets/js/admin_dashboard.js


document.addEventListener("DOMContentLoaded", () => {
    verificarAdmin();
});

async function verificarAdmin() {
    try {
        const res  = await fetch(`${API}/get_session.php`, { credentials: 'include' });
        const data = await res.json();

        if (!data.success || data.user.role !== 'admin') {
            window.location.href = 'index.html';
            return;
        }

        cargarDashboard();
    } catch {
        window.location.href = 'index.html';
    }
}

async function cargarDashboard() {
    try {
        const res  = await fetch(`${API}/admin_dashboard.php`, { credentials: 'include' });
        const data = await res.json();

        if (!data.success) {
            window.location.href = 'index.html';
            return;
        }

        // Stats
        document.getElementById('stat-usuarios').textContent = data.nuevos_usuarios;
        document.getElementById('stat-publis').textContent   = data.nuevas_publis;
        document.getElementById('stat-comments').textContent = data.total_comments;

        // Pills
        document.getElementById('pill-primero').textContent = '@' + data.primer_user;
        document.getElementById('pill-ultimo').textContent  = '@' + data.ultimo_user;
        document.getElementById('pill-activo').textContent  = '@' + data.user_activo;

        // Título del gráfico
        document.getElementById('chart-title').textContent =
            `Actividad Mensual — ${data.nombre_mes.toUpperCase()}`;

        // Gráfico
        const ctx          = document.getElementById('bigMonthlyChart').getContext('2d');
        const blueGradient = ctx.createLinearGradient(0, 0, 0, 400);
        blueGradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
        blueGradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels_dias,
                datasets: [{
                    data: data.valores_dias,
                    borderColor: '#3b82f6',
                    backgroundColor: blueGradient,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                events: [],
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, display: false },
                    x: {
                        grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false },
                        ticks: { color: '#475569', font: { size: 9 }, maxTicksLimit: 10 }
                    }
                }
            }
        });

    } catch (err) {
        console.error('Error al cargar dashboard:', err);
    }
}