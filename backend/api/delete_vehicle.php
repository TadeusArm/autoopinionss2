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
header("Content-Type: application/json; charset=UTF-8");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(["success" => false, "message" => "Sesión no iniciada."]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(["success" => false, "message" => "Método no permitido."]); exit; }

$v_id = (int)($_POST['vehicle_id'] ?? 0);
$u_id = (int)$_SESSION['user_id'];

if ($v_id <= 0) { echo json_encode(["success" => false, "message" => "ID no válido."]); exit; }

$stmt_img = $pdo->prepare("SELECT image FROM vehicles WHERE id = ? AND user_id = ?");
$stmt_img->execute([$v_id, $u_id]);
$coche = $stmt_img->fetch(PDO::FETCH_ASSOC);
if (!$coche) { http_response_code(403); echo json_encode(["success" => false, "message" => "No tienes permiso."]); exit; }

if (!empty($coche['image'])) {
    $ruta_foto = __DIR__ . "/../../assets/img/vehicles/" . basename($coche['image']);
    if (file_exists($ruta_foto)) unlink($ruta_foto);
}

try {
    $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ? AND user_id = ?");
    $stmt->execute([$v_id, $u_id]);
    echo json_encode(["success" => true, "message" => "Vehículo eliminado correctamente."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error al eliminar."]);
}
?>