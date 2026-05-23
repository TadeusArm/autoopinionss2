<?php
// Silenciamos errores para producción
ini_set('display_errors', 0);
error_reporting(0);

include 'config/db.php';

$message = "";
$status = ""; // Para determinar el color de la alerta

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    try {
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE token = ? AND verified = 0");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            $update = $pdo->prepare("UPDATE users SET verified = 1, token = NULL WHERE id = ?");
            if ($update->execute([$user['id']])) {
                $message = "¡Felicidades @" . htmlspecialchars($user['username']) . "! Tu cuenta ha sido activada con éxito.";
                $status = "success";
            }
        } else {
            $message = "Error: El enlace no es válido o la cuenta ya ha sido activada.";
            $status = "error";
        }
    } catch (Exception $e) {
        $message = "Error: Problema de conexión con el servidor.";
        $status = "error";
    }
} else {
    $message = "Error: No se ha proporcionado un código de verificación.";
    $status = "error";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación - AutoOpinions</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.jpg">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Estilos específicos para integrar el Glassmorphism del fondo con la card */
        body { 
            background: url('assets/img/fondoverificar.jpg') center/cover no-repeat fixed !important; 
            margin: 0; 
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Capa glassmorphism que empaña el fondo */
        .glass-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 1;
        }

        /* Card alineada al estilo del Register/Login */
        .card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 400px;
        }

        .btn-link {
            display: block;
            text-align: center;
            text-decoration: none;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="glass-overlay"></div>

    <div class="card">
        <h2 class="text-center">AUTO OPINIONS</h2>
        <p class="subtitle text-center">Estado de la cuenta</p>

        <?php if($message): ?>
            <div class="alert <?php echo ($status == 'success') ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="login.php" class="btn-submit" style="text-decoration: none; display: block; text-align: center;">
                Ir al Inicio de Sesión
            </a>
        </div>

        <p class="text-footer">
            ¿Tienes problemas? <a href="https://mail.google.com/mail/u/0/?fs=1&amp;to=notificaciones@autoopinions.es&amp;su=Escribenos+dudas+a+AutoOpinions&amp;tf=cm">Contacta con nosotros</a>
        </p>
    </div>
</body>
</html>