<?php
session_start([
    'cookie_samesite' => 'None',
    'cookie_secure'   => true,
]);
include '../config/db.php';

header("Access-Control-Allow-Origin: https://autoopinions.es");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=UTF-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

$user_id      = $_SESSION['user_id'];
$search       = $_GET['search']   ?? '';
$brand_filter = $_GET['brand']    ?? '';
$min_year     = $_GET['min_year'] ?? '';
$max_year     = $_GET['max_year'] ?? '';
$min_km       = $_GET['min_km']   ?? '';
$max_km       = $_GET['max_km']   ?? '';
$min_cv       = $_GET['min_cv']   ?? '';
$max_cv       = $_GET['max_cv']   ?? '';

try {
    $sql = "SELECT v.*, u.username,
            (SELECT COUNT(*) FROM comments WHERE vehicle_id = v.id) as total_comentarios,
            (SELECT AVG(rating) FROM ratings WHERE vehicle_id = v.id) as nota_media
            FROM vehicles v JOIN users u ON v.user_id = u.id WHERE 1=1";

    $params = [];
    if (!empty($search))       { $sql .= " AND (v.brand LIKE ? OR v.model LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    if (!empty($brand_filter)) { $sql .= " AND v.brand = ?";        $params[] = $brand_filter;  }
    if (!empty($min_year))     { $sql .= " AND v.year >= ?";        $params[] = (int)$min_year; }
    if (!empty($max_year))     { $sql .= " AND v.year <= ?";        $params[] = (int)$max_year; }
    if (!empty($min_km))       { $sql .= " AND v.km >= ?";          $params[] = (int)$min_km;   }
    if (!empty($max_km))       { $sql .= " AND v.km <= ?";          $params[] = (int)$max_km;   }
    if (!empty($min_cv))       { $sql .= " AND v.potencia_cv >= ?"; $params[] = (int)$min_cv;   }
    if (!empty($max_cv))       { $sql .= " AND v.potencia_cv <= ?"; $params[] = (int)$max_cv;   }

    $sql .= " ORDER BY v.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $coches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $marcas_query     = $pdo->query("SELECT DISTINCT brand FROM vehicles WHERE brand != '' ORDER BY brand ASC");
    $todas_las_marcas = $marcas_query->fetchAll(PDO::FETCH_COLUMN);

    foreach ($coches as &$c) {
        $st = $pdo->prepare("SELECT id FROM comments WHERE user_id = ? AND vehicle_id = ?");
        $st->execute([$user_id, $c['id']]);
        $c['ya_opino'] = (bool)$st->fetch();
        $img_db = $c['image'] ?? '';
        $c['image'] = !empty($img_db) ? "/assets/img/vehicles/" . basename($img_db) : null;
        $c['description'] = !empty($c['description']) ? $c['description'] : ($c['descripcion'] ?? 'Sin descripción disponible.');
    }

    echo json_encode(['success' => true, 'vehicles' => $coches, 'marcas' => $todas_las_marcas]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
}
?>