<?php
session_start();
include 'config/db.php';

// Si no está logueado, a la calle
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$mensaje = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];

    // 1. Sacar la contraseña actual de la base de datos
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // 2. Verificaciones de seguridad
    if (!password_verify($current_pass, $user['password'])) {
        $error = "La contraseña actual no es correcta.";
    } elseif (strlen($new_pass) < 6) {
        $error = "La nueva contraseña debe tener al menos 6 caracteres.";
    } else {
        // 3. Todo bien, actualizamos
        $hashed_password = password_hash($new_pass, PASSWORD_BCRYPT);
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        if ($pdo->prepare($sql)->execute([$hashed_password, $user_id])) {
            $mensaje = "¡Contraseña actualizada correctamente!";
        } else {
            $error = "Hubo un error al guardar la contraseña.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - AutoOpinions</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.jpg">
    <style>
        .password-container {
            max-width: 400px;
            margin: 80px auto;
            padding: 0 20px;
        }
        .alert {
            padding: 12px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-success { background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2); }
        .alert-error { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.2); }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #94a3b8; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="overlay"></div>
    
    <?php include 'includes/header.php'; ?>

    <div class="password-container">
        <div class="card">
            <h2 style="text-align: center; margin-bottom: 30px;">Seguridad</h2>

            <?php if($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Tu contraseña actual</label>
                    <input type="password" name="current_password" required placeholder="••••••••">
                </div>

                <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 25px 0;"></div>

                <div class="form-group">
                    <label>Nueva contraseña</label>
                    <input type="password" name="new_password" required placeholder="Nueva contraseña">
                </div>

                <button type="submit" class="btn-submit">Cambiar contraseña</button>
            </form>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="edit_profile.php" style="color: #64748b; text-decoration: none; font-size: 0.9rem;">← Cancelar y volver</a>
        </div>
    </div>
</body>
</html>