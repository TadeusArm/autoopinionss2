<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $login_input = $_POST['login_input'] ?? '';
    $password    = $_POST['password']    ?? '';

    if (empty($login_input) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    try {
        // 1. Comprobar si el input está baneado por email O por username
        $ban = $pdo->prepare("SELECT id FROM banned_emails WHERE email = ? OR username = ?");
        $ban->execute([$login_input, $login_input]);
        if ($ban->fetch()) {
            echo json_encode(['success' => false, 'banned' => true, 'message' => 'Esta cuenta ha sido suspendida.']);
            exit;
        }

        // 2. Buscar usuario
        $stmt = $pdo->prepare("SELECT id, username, email, password, profile_pic, role, verified FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            // 3. Verificación de cuenta
            if ($user['verified'] == 0) {
                echo json_encode(['success' => false, 'message' => 'Debes confirmar tu cuenta por correo antes de entrar.']);
                exit;
            }

            // 4. Login correcto
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['username']    = $user['username'];
            $_SESSION['profile_pic'] = $user['profile_pic'];
            $_SESSION['role']        = $user['role'];

            echo json_encode(['success' => true, 'message' => 'Login exitoso.']);
            exit;

        } else {
            echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos.']);
            exit;
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de procesamiento en el servidor.']);
        exit;
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido.']);
    exit;
}
?>