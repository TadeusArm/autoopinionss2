<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json; charset=UTF-8');

// Solo usuarios logueados
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit;
}

// Solo acepta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido.']);
    exit;
}

$user_id       = $_SESSION['user_id'];
$new_username  = trim($_POST['username']       ?? '');
$new_bio       = trim($_POST['bio']            ?? '');
$new_location  = trim($_POST['location']       ?? '');
$new_instagram = trim($_POST['instagram_user'] ?? '');

if (empty($new_username)) {
    echo json_encode(['success' => false, 'message' => 'El nombre de usuario no puede estar vacío.']);
    exit;
}

try {
    // --- 1. GESTIÓN DE FOTO DE PERFIL ---
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext      = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Formato de imagen no válido. Usa JPG, PNG o GIF.']);
            exit;
        }

        $upload_dir = __DIR__ . '/../../assets/img/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $new_filename = $user_id . '_' . time() . '.' . $ext;

        if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $new_filename)) {
            echo json_encode(['success' => false, 'message' => 'Error al subir la imagen.']);
            exit;
        }

        $ruta_bd = 'assets/img/avatars/' . $new_filename;
        $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?")->execute([$ruta_bd, $user_id]);
        $_SESSION['profile_pic'] = $ruta_bd;
    }

    // --- 2. ACTUALIZAR DATOS DE TEXTO ---
    $stmt = $pdo->prepare("UPDATE users SET username = ?, bio = ?, location = ?, instagram_user = ? WHERE id = ?");

    if ($stmt->execute([$new_username, $new_bio, $new_location, $new_instagram, $user_id])) {
        $_SESSION['username'] = $new_username;
        echo json_encode(['success' => true, 'message' => '¡Perfil guardado correctamente!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de procesamiento en el servidor.']);
}
?>