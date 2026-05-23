<?php
session_start();
include 'config/db.php';
include 'includes/functions_mail.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(16));

    // Comprobar si el usuario o email ya existen
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $check->execute([$email, $username]);
    
    if ($check->fetch()) {
        $message = "Error: El nombre de usuario o el correo ya están registrados.";
    } else {
        try {
            // Insertar nuevo usuario con verified = 0 (pendiente)
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, token, verified) VALUES (?, ?, ?, 'user', ?, 0)");
            
            if ($stmt->execute([$username, $email, $password, $token])) {
                
               
                // Le pasamos: el email, el nombre, el tipo 'verify' y el token
                if (enviarNotificacionEmail($email, $username, 'verify', $token)) {
                    $message = "¡Cuenta creada! Por favor, revisa tu correo (incluyendo Spam) para activarla.";
                } else {
                    // Si llega aquí, es que PHPMailer falló (revisa la ruta del autoload en el otro archivo)
                    $message = "Error: La cuenta se creó, pero el servidor de correo falló. Contacta con soporte.";
                }
            }
        } catch (PDOException $e) {
            $message = "Error: No se pudo conectar con la base de datos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - AutoOpinions</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="overlay"></div>
    
    <div class="card">
        <h2 class="text-center">REGISTRO</h2>
        <p class="subtitle text-center">Únete a nuestra comunidad del motor</p>

        <?php if(!empty($message)): ?>
            <div class="alert <?php echo (strpos($message, 'Error') !== false) ? 'alert-error' : 'alert-success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if(strpos($message, 'revisa tu correo') === false): ?>
            <form method="POST" action="register.php">
                <div class="input-group">
                    <input type="text" name="username" placeholder="Nombre de usuario" required>
                </div>
                <div class="input-group">
                    <input type="email" name="email" placeholder="Correo electrónico" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Contraseña" required>
                </div>
                <button type="submit" class="btn-submit">Registrarme ahora</button>
            </form>
        <?php endif; ?>
        
        <p class="text-footer">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </p>
    </div>
</body>
</html>