<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include '../config/db.php';
include '../includes/functions_mail.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username     = trim($_POST['username'] ?? '');
    $email        = trim($_POST['email']    ?? '');
    $password_raw = $_POST['password']      ?? '';

    if (empty($username) || empty($email) || empty($password_raw)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    try {
        // 1. Comprobar si el email está baneado
        $ban = $pdo->prepare("SELECT id FROM banned_emails WHERE email = ?");
        $ban->execute([$email]);
        if ($ban->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este correo no puede ser utilizado para registrarse.']);
            exit;
        }

        // 2. Comprobar si el usuario o email ya existen
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $check->execute([$email, $username]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El usuario o correo ya existen.']);
            exit;
        }

        // 3. Crear cuenta
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