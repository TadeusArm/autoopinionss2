<?php
// backend/api/admin.php
session_start();
include '../config/db.php';

header('Content-Type: application/json; charset=UTF-8');

// Solo admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

$mi_id  = (int)$_SESSION['user_id'];
$metodo = $_SERVER['REQUEST_METHOD'];

// ─── GET: listar usuarios ────────────────────────────────────────────────────
if ($metodo === 'GET') {
    $stmt    = $pdo->query("SELECT id, username, email, role, profile_pic FROM users ORDER BY role ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Marcar cuál es la cuenta actual
    foreach ($usuarios as &$u) {
        $u['is_current'] = ($u['id'] == $mi_id);
    }

    echo json_encode(['success' => true, 'usuarios' => $usuarios]);
    exit;
}

// ─── POST: banear usuario ────────────────────────────────────────────────────
if ($metodo === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID no válido.']);
        exit;
    }

    // No puedes banearte a ti mismo
    if ($user_id === $mi_id) {
        echo json_encode(['success' => false, 'message' => 'No puedes banearte a ti mismo.']);
        exit;
    }

    try {
        // Obtener email antes de borrar
        $st = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $st->execute([$user_id]);
        $usuario = $st->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
            exit;
        }

        $pdo->beginTransaction();

        // 1. Guardar email en banned_emails
        $ins = $pdo->prepare("INSERT IGNORE INTO banned_emails (email) VALUES (?)");
        $ins->execute([$usuario['email']]);

        // 2. Eliminar el usuario (ON DELETE CASCADE se encarga del resto)
        $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $del->execute([$user_id]);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Usuario baneado correctamente.']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al banear el usuario.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
?>