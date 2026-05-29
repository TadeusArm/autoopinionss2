<?php
session_start([
    'cookie_samesite' => 'None',
    'cookie_secure'   => true,
]);

header("Access-Control-Allow-Origin: https://autoopinions.es");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=UTF-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_destroy();
echo json_encode(['success' => true]);
?>