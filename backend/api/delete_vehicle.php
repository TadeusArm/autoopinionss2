<?php
session_start();
include '../config/db.php'; // Asegúrate de que la ruta sea correcta

if (isset($_POST['delete_btn']) && isset($_SESSION['user_id'])) {
    $v_id = (int)$_POST['vehicle_id'];
    $u_id = (int)$_SESSION['user_id'];

    // 1. (Opcional) Borrar la imagen física del servidor para no dejar basura
    $stmt_img = $pdo->prepare("SELECT image FROM vehicles WHERE id = ? AND user_id = ?");
    $stmt_img->execute([$v_id, $u_id]);
    $coche = $stmt_img->fetch();

    if ($coche && !empty($coche['image'])) {
        $ruta_foto = "../assets/img/vehicles/" . $coche['image'];
        if (file_exists($ruta_foto)) {
            unlink($ruta_foto);
        }
    }

    // 2. Borrar de la base de datos
    // Nota: Si tienes comentarios asociados, asegúrate de que la tabla comments 
    // tenga "ON DELETE CASCADE" o borra los comentarios primero.
    $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ? AND user_id = ?");
    
    if ($stmt->execute([$v_id, $u_id])) {
        header("Location: ../profile.php?id=" . $u_id . "&msg=deleted");
    } else {
        header("Location: ../profile.php?id=" . $u_id . "&msg=error");
    }
    exit();
} else {
    header("Location: ../index.php");
    exit();
}