<?php
// backend/api/follow_action.php
session_start();
include '../config/db.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$follower_id = (int)$_SESSION['user_id'];
$followed_id = (int)($_POST['followed_id'] ?? 0);
$accion      = $_POST['accion'] ?? '';

if ($followed_id <= 0 || !in_array($accion, ['follow', 'unfollow'])) {
    echo json_encode(['success' => false, 'message' => 'Datos no válidos.']);
    exit;
}

// No puedes seguirte a ti mismo
if ($follower_id === $followed_id) {
    echo json_encode(['success' => false, 'message' => 'No puedes seguirte a ti mismo.']);
    exit;
}

try {
    if ($accion === 'follow') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO follows (follower_id, followed_id) VALUES (?, ?)");
        $stmt->execute([$follower_id, $followed_id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$follower_id, $followed_id]);
    }

    echo json_encode(['success' => true, 'accion' => $accion]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
}
?>