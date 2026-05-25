<?php
// backend/api/change_password.php
session_start();
include '../config/db.php';

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Sesión no iniciada."]);
    exit;
}

$user_id      = $_SESSION['user_id'];
$current_pass = $_POST['current_password'] ?? '';
$new_pass     = $_POST['new_password']     ?? '';

// Validaciones básicas (el JS ya las hace, pero siempre validar en servidor)
if (empty($current_pass) || empty($new_pass)) {
    echo json_encode(["success" => false, "message" => "Faltan campos obligatorios."]);
    exit;
}

if (strlen($new_pass) < 6) {
    echo json_encode(["success" => false, "message" => "La nueva contraseña debe tener al menos 6 caracteres."]);
    exit;
}

try {
    // 1. Obtener contraseña actual
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success" => false, "message" => "Usuario no encontrado."]);
        exit;
    }

    // 2. Verificar contraseña actual
    if (!password_verify($current_pass, $user['password'])) {
        echo json_encode(["success" => false, "message" => "La contraseña actual no es correcta."]);
        exit;
    }

    // 3. Guardar nueva contraseña
    $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
    $stmt2  = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt2->execute([$hashed, $user_id]);

    echo json_encode(["success" => true, "message" => "¡Contraseña actualizada correctamente!"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error de servidor: " . $e->getMessage()]);
}
?>