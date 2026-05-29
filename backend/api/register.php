<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start([
    'cookie_samesite' => 'None',
    'cookie_secure'   => true,
]);
include 'include '../config/db.php';';
include 'includes/functions_mail.php';

header("Access-Control-Allow-Origin: https://autoopinions.es");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=UTF-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username     = trim($_POST['username'] ?? '');
    $email        = trim($_POST['email']    ?? '');
    $password_raw = $_POST['password']      ?? '';

    if (empty($username) || empty($email) || empty($password_raw)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    try {
        $ban = $pdo->prepare("SELECT id FROM banned_emails WHERE email = ?");
        $ban->execute([$email]);
        if ($ban->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este correo no puede ser utilizado para registrarse.']);
            exit;
        }

        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $check->execute([$email, $username]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El usuario o correo ya existen.']);
            exit;
        }

        $password = password_hash($password_raw, PASSWORD_DEFAULT);
        $token    = bin2hex(random_bytes(16));

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, token, verified) VALUES (?, ?, ?, 'user', ?, 0)");

        if ($stmt->execute([$username, $email, $password, $token])) {
            if (enviarNotificacionEmail($email, $username, 'verify', $token)) {
                echo json_encode(['success' => true, 'message' => '¡Cuenta creada! Revisa tu correo para activar.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Cuenta creada, pero falló el envío de email.']);
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de BD: ' . $e->getMessage()]);
    }
}
?>