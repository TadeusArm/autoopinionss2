<?php
// backend/api/admin_dashboard.php
session_start();
include '../config/db.php';

header('Content-Type: application/json; charset=UTF-8');

// Solo admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

try {
    // Estadísticas últimos 7 días
    $nuevos_usuarios = $pdo->query("SELECT COUNT(*) FROM users    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    $nuevas_publis   = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    $total_comments  = $pdo->query("SELECT COUNT(*) FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

    // Perfiles destacados
    $primer_user = $pdo->query("SELECT username FROM users ORDER BY id ASC  LIMIT 1")->fetchColumn();
    $ultimo_user = $pdo->query("SELECT username FROM users ORDER BY id DESC LIMIT 1")->fetchColumn();
    $user_activo = $pdo->query("SELECT u.username FROM users u JOIN vehicles v ON u.id = v.user_id GROUP BY u.id ORDER BY COUNT(v.id) DESC LIMIT 1")->fetchColumn();

    // Gráfico mensual
    $mes_actual  = date('m');
    $anio_actual = date('Y');
    $nombre_mes  = date('F');

    $qg = $pdo->prepare("
        SELECT DAY(created_at) AS dia, COUNT(*) AS total
        FROM vehicles
        WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?
        GROUP BY DAY(created_at)
        ORDER BY dia ASC
    ");
    $qg->execute([$mes_actual, $anio_actual]);
    $resultados = $qg->fetchAll(PDO::FETCH_ASSOC);

    $dias_del_mes    = cal_days_in_month(CAL_GREGORIAN, $mes_actual, $anio_actual);
    $datos_mensuales = array_fill(1, $dias_del_mes, 0);
    foreach ($resultados as $row) {
        $datos_mensuales[$row['dia']] = (int)$row['total'];
    }

    echo json_encode([
        'success'         => true,
        'nuevos_usuarios' => (int)$nuevos_usuarios,
        'nuevas_publis'   => (int)$nuevas_publis,
        'total_comments'  => (int)$total_comments,
        'primer_user'     => $primer_user ?: '—',
        'ultimo_user'     => $ultimo_user ?: '—',
        'user_activo'     => $user_activo ?: '—',
        'nombre_mes'      => $nombre_mes,
        'labels_dias'     => array_keys($datos_mensuales),
        'valores_dias'    => array_values($datos_mensuales),
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
}
?>