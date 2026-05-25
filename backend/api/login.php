<?php
session_start();
include '../config/db.php';

// Establecer cabecera para respuesta de la API
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Captura segura de las variables POST
    $login_input = $_POST['login_input'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validación de integridad de datos recibidos
    if (empty($login_input) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Error: Todos los campos son obligatorios.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, username, password, profile_pic, role, verified FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        // Verificación de credenciales
        if ($user && password_verify($password, $user['password'])) {
            
            // Verificación de estado de cuenta
            if ($user['verified'] == 0) {
                echo json_encode(['success' => false, 'message' => 'Error: Debes confirmar tu cuenta por correo antes de entrar.']);
                exit;
            } else {
                // Asignación de variables de sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['profile_pic'] = $user['profile_pic'];
                $_SESSION['role'] = $user['role'];
                
                echo json_encode(['success' => true, 'message' => 'Login exitoso.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos']);
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