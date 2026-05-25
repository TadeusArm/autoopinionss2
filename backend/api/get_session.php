<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

// Si no hay sesión iniciada, devolvemos un error 401 (No autorizado)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No hay sesión activa']);
    exit;
}

// Si hay sesión, le pasamos los datos al JavaScript
echo json_encode([
    'success' => true,
    'user' => [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'profile_pic' => $_SESSION['profile_pic'] ?? null,
        'role' => $_SESSION['role'] ?? 'user'
    ]
]);
?>