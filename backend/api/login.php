<?php
session_start();
include 'config/db.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = $_POST['login_input'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, username, password, profile_pic, role, verified FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$login_input, $login_input]);
    $user = $stmt->fetch();

    if($user && password_verify($password, $user['password'])){
        if($user['verified'] == 0) {
            $message = "Error: Debes confirmar tu cuenta por correo antes de entrar.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_pic'] = $user['profile_pic'];
            $_SESSION['role'] = $user['role'];
           
            header("Location: index.php");
            exit;
        }
    } else {
        $message = "Usuario o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AutoOpinions</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.jpg">
    <link rel="icon" type="image/png" href="assets/img/favicon.png"> <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="overlay"></div>
    <div class="card">
        <h2 class="text-center">AUTO OPINIONS</h2>
        <p class="subtitle text-center">Inicia sesión para continuar</p>

        <?php if($message): ?>
            <div class="alert alert-error">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <input type="text" name="login_input" placeholder="Usuario o correo electrónico" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Contraseña" required>
            </div>
            <button type="submit" class="btn-submit">Entrar</button>
        </form>
       
        <p class="text-footer">
            ¿Eres nuevo? <a href="register.php">Crea una cuenta</a>
        </p>
    </div>
</body>
</html>