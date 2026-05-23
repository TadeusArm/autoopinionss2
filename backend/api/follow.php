<?php
session_start();
include '../config/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $follower_id = $_SESSION['user_id'];
    $followed_id = $_POST['followed_id'];
    $accion = $_POST['accion'];

    // No puedes seguirte a ti mismo
    if ($follower_id == $followed_id) {
        header("Location: ../profile.php?id=$followed_id");
        exit;
    }

    if ($accion === 'follow') {
        // Insertamos el seguimiento
        $stmt = $pdo->prepare("INSERT IGNORE INTO follows (follower_id, followed_id) VALUES (?, ?)");
        $stmt->execute([$follower_id, $followed_id]);
    } else if ($accion === 'unfollow') {
        // Borramos el seguimiento
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$follower_id, $followed_id]);
    }

    // Volvemos al perfil donde estábamos
    header("Location: ../profile.php?id=$followed_id");
    exit;
} else {
    // Si alguien intenta entrar a este archivo por URL, lo mandamos al index
    header("Location: ../index.php");
    exit;
}