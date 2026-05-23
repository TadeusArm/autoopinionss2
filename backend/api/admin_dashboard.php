<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); exit;
}

//  ESTADÍSTICAS RÁPIDAS (7 DÍAS)
$nuevosUsuarios = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$nuevasPublis = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$totalComments = $pdo->query("SELECT COUNT(*) FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

//  PERFILES DESTACADOS 
$primerUser = $pdo->query("SELECT username FROM users ORDER BY id ASC LIMIT 1")->fetchColumn();
$ultimoUser = $pdo->query("SELECT username FROM users ORDER BY id DESC LIMIT 1")->fetchColumn();
$userActivo = $pdo->query("SELECT u.username FROM users u JOIN vehicles v ON u.id = v.user_id GROUP BY u.id ORDER BY COUNT(v.id) DESC LIMIT 1")->fetchColumn();

//  LÓGICA MENSUAL PARA EL GRÁFICO GRANDE 
$mesActual = date('m');
$anioActual = date('Y');
$nombreMes = date('F'); 

$queryGraph = $pdo->prepare("
    SELECT DAY(created_at) as dia, COUNT(*) as total 
    FROM vehicles 
    WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?
    GROUP BY DAY(created_at) 
    ORDER BY dia ASC
");
$queryGraph->execute([$mesActual, $anioActual]);
$resultados = $queryGraph->fetchAll(PDO::FETCH_ASSOC);

$diasDelMes = cal_days_in_month(CAL_GREGORIAN, $mesActual, $anioActual);
$datosMensuales = array_fill(1, $diasDelMes, 0);

foreach ($resultados as $row) {
    $datosMensuales[$row['dia']] = (int)$row['total'];
}

$labelsDias = array_keys($datosMensuales);
$valoresDias = array_values($datosMensuales);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor de Sistema - AutoOpinions</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.jpg">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: url('assets/img/dashboard.webp') no-repeat center center fixed;
            background-size: cover;
            color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .overlay {
            background: rgba(10, 15, 25, 0.9);
            min-height: 100vh;
            padding: 20px;
        }

        .container { 
            max-width: 1400px; 
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
        }

        h1 { font-weight: 200; font-size: 1.2rem; letter-spacing: 2px; text-transform: uppercase; }
        h1 b { color: #3b82f6; font-weight: 900; }

        /* Layout Superior: En móvil es una sola columna */
        .top-stats-layout {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* En Desktop vuelve a ser 2 columnas */
        @media (min-width: 992px) {
            .top-stats-layout {
                display: grid;
                grid-template-columns: 2fr 1fr;
            }
        }

        /* Rejilla de cartas de números */
        .grid-main-stats {
            display: grid;
            grid-template-columns: 1fr; 
            gap: 15px;
        }

        @media (min-width: 480px) {
            .grid-main-stats { grid-template-columns: repeat(3, 1fr); } 
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-number { font-size: 2.2rem; font-weight: 800; color: #3b82f6; display: block; }
        .stat-label { color: #64748b; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; }

        .pills-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .user-pill {
            background: rgba(59, 130, 246, 0.08);
            padding: 15px 20px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid #3b82f6;
        }

        .user-pill small { color: #94a3b8; font-size: 0.65rem; text-transform: uppercase; }
        .user-pill strong { color: #fff; font-size: 0.9rem; }

        /* Gráfico Mensual */
        .main-chart-section {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(15px);
            padding: 20px;
            border-radius: 20px;
            min-height: 350px;
            height: auto;
        }

        @media (min-width: 768px) {
            .main-chart-section { padding: 40px; height: 500px; }
        }

        .chart-title {
            color: #475569;
            font-size: 0.75rem;
            letter-spacing: 2px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        canvas { pointer-events: none; }
    </style>
</head>
<body>

<div class="overlay">
    <div class="container">
        <header>
            <h1>Monitor <b>System</b></h1>
            <div style="font-size: 0.6rem; color: #3b82f6; letter-spacing: 1px;">ONLINE</div>
        </header>

        <div class="top-stats-layout">
            <div class="grid-main-stats">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $nuevosUsuarios; ?></span>
                    <span class="stat-label">Usuarios</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $nuevasPublis; ?></span>
                    <span class="stat-label">Publicaciones</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $totalComments; ?></span>
                    <span class="stat-label">Feedback</span>
                </div>
            </div>

            <div class="pills-container">
                <div class="user-pill">
                    <small>Más antiguo</small>
                    <strong>@<?php echo $primerUser; ?></strong>
                </div>
                <div class="user-pill">
                    <small>Más nuevo</small>
                    <strong>@<?php echo $ultimoUser; ?></strong>
                </div>
                <div class="user-pill">
                    <small>Más activo</small>
                    <strong>@<?php echo $userActivo; ?></strong>
                </div>
            </div>
        </div>

        <div class="main-chart-section">
            <div class="chart-title">Actividad Mensual - <?php echo strtoupper($nombreMes); ?></div>
            <div style="height: 300px; width: 100%; position: relative;">
                <canvas id="bigMonthlyChart"></canvas>
            </div>
            <div style="margin-top: 30px; text-align: center;">
            <a href="admin.php" style="
                display: inline-flex;
                align-items: center;
                gap: 10px;
                background: rgba(255, 255, 255, 0.05);
                color: #94a3b8;
                padding: 12px 25px;
                border-radius: 50px;
                text-decoration: none;
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 2px;
                border: none;
                backdrop-filter: blur(10px);
                transition: 0.3s;
            " onmouseover="this.style.borderColor='#3b82f6'; this.style.color='#3b82f6';" 
              onmouseout="this.style.borderColor='rgba(255, 255, 255, 0.1)'; this.style.color='#94a3b8';">
              Volver a Gestión de Usuarios
            </a>
        </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('bigMonthlyChart').getContext('2d');
    
    let blueGradient = ctx.createLinearGradient(0, 0, 0, 400);
    blueGradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
    blueGradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labelsDias); ?>,
            datasets: [{
                data: <?php echo json_encode($valoresDias); ?>,
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
                    grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false }, 
                    ticks: { 
                        color: '#475569',
                        font: { size: 9 },
                        maxTicksLimit: 10 
                    } 
                }
            }
        }
    });
</script>

</body>
</html>