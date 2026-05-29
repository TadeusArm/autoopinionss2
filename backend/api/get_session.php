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

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No hay sesión activa']);
    exit;
}

echo json_encode([
    'success' => true,
    'user' => [
        'id'          => $_SESSION['user_id'],
        'username'    => $_SESSION['username'],
        'profile_pic' => $_SESSION['profile_pic'] ?? null,
        'role'        => $_SESSION['role'] ?? 'user'
    ]
]);
?>