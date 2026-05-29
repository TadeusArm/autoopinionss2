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

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $login_input = $_POST['login_input'] ?? '';
    $password    = $_POST['password']    ?? '';

    if (empty($login_input) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    try {
        $ban = $pdo->prepare("SELECT id FROM banned_emails WHERE email = ? OR username = ?");
        $ban->execute([$login_input, $login_input]);
        if ($ban->fetch()) {
            echo json_encode(['success' => false, 'banned' => true, 'message' => 'Esta cuenta ha sido suspendida.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, username, email, password, profile_pic, role, verified FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['verified'] == 0) {
                echo json_encode(['success' => false, 'message' => 'Debes confirmar tu cuenta por correo antes de entrar.']);
                exit;
            }
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['username']    = $user['username'];
            $_SESSION['profile_pic'] = $user['profile_pic'];
            $_SESSION['role']        = $user['role'];
            echo json_encode(['success' => true, 'message' => 'Login exitoso.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de procesamiento en el servidor.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido.']);
}
?>