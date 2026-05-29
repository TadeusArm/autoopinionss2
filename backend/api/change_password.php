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

$user_id      = $_SESSION['user_id'];
$current_pass = $_POST['current_password'] ?? '';
$new_pass     = $_POST['new_password']     ?? '';

if (empty($current_pass) || empty($new_pass)) { echo json_encode(["success" => false, "message" => "Faltan campos obligatorios."]); exit; }
if (strlen($new_pass) < 6) { echo json_encode(["success" => false, "message" => "La nueva contraseña debe tener al menos 6 caracteres."]); exit; }

try {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) { echo json_encode(["success" => false, "message" => "Usuario no encontrado."]); exit; }
    if (!password_verify($current_pass, $user['password'])) { echo json_encode(["success" => false, "message" => "La contraseña actual no es correcta."]); exit; }

    $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
    $stmt2  = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt2->execute([$hashed, $user_id]);
    echo json_encode(["success" => true, "message" => "¡Contraseña actualizada correctamente!"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error de servidor."]);
}
?>