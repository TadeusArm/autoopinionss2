<?php
include '../config/db.php';

header("Access-Control-Allow-Origin: https://autoopinions.es");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=UTF-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

try {
    $stmt = $pdo->query("
        SELECT v.id, v.brand, v.model, v.year, v.km, v.potencia_cv, v.image,
               u.username,
               (SELECT AVG(rating) FROM ratings WHERE vehicle_id = v.id) AS nota_media
        FROM vehicles v JOIN users u ON v.user_id = u.id
        ORDER BY v.id DESC LIMIT 6
    ");
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($vehicles as &$v) {
        $v['image'] = !empty($v['image']) ? '/assets/img/vehicles/' . basename($v['image']) : null;
    }

    echo json_encode(['success' => true, 'vehicles' => $vehicles]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de servidor.']);
}
?>