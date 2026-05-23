<?php
session_start();
include 'config/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$mensaje = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = $_POST['username'];
    $new_bio = $_POST['bio'];
    $new_location = $_POST['location'];
    $new_instagram = $_POST['instagram_user'];

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_pic']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = $user_id . "_" . time() . "." . $ext;
            $upload_path = "assets/img/avatars/" . $new_filename;

            if (!is_dir("assets/img/avatars/")) {
                mkdir("assets/img/avatars/", 0777, true);
            }

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                $sql_pic = "UPDATE users SET profile_pic = ? WHERE id = ?";
                $pdo->prepare($sql_pic)->execute([$upload_path, $user_id]);
                $_SESSION['profile_pic'] = $upload_path;
                $mensaje = "¡Foto de perfil actualizada! ";
            }
        } else {
            $error = "Formato de imagen no válido.";
        }
    }

    if (!empty($new_username)) {
        $sql = "UPDATE users SET username = ?, bio = ?, location = ?, instagram_user = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$new_username, $new_bio, $new_location, $new_instagram, $user_id])) {
            $_SESSION['username'] = $new_username;
            $mensaje .= "Datos guardados correctamente.";
        }
    }
}

$stmt = $pdo->prepare("SELECT username, email, profile_pic, bio, location, instagram_user FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - AutoOpinions</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>

        body {
            background: url('assets/img/editarperfil.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            min-height: 100vh;
        }

        .overlay {
            background: rgba(10, 15, 25, 0.85);
            min-height: 100vh;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: -1;
        }

        .edit-container { 
            max-width: 550px; 
            margin: 0 auto; 
            padding: 110px 20px 50px; 
        }

        /* Estilo Glassmorphism mejorado sin bordes pesados */
        .card-profile {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 35px;
            border-radius: 24px;
            border: none
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            color: white;
        }

        .profile-header { text-align: center; margin-bottom: 30px; }
        
        .avatar-picker { position: relative; width: 120px; height: 120px; margin: 0 auto 15px; }
        .profile-avatar-big {
            width: 100%; height: 100%; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem; font-weight: 800; color: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            overflow: hidden; background: #3b82f6;
        }
        .profile-avatar-big img { width: 100%; height: 100%; object-fit: cover; }
        
        .change-pic-btn {
            position: absolute; bottom: 5px; right: 5px;
            background: #3b82f6; color: white;
            border-radius: 50%; width: 38px; height: 38px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: 0.3s; z-index: 10;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        .change-pic-btn:hover { background: #2563eb; transform: scale(1.1); }
        #file-input { display: none; }

        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; 
            color: #64748b; 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            margin-bottom: 8px; 
        }
        
        input[type="text"], textarea {
            width: 100%; padding: 14px; border-radius: 12px;
            background: rgba(255, 255, 255, 0.05); border: none;
            color: white; font-size: 0.95rem; outline: none;
            transition: 0.3s; font-family: 'Segoe UI', sans-serif;
        }
        input:focus, textarea:focus { background: rgba(255, 255, 255, 0.08); }

        .btn-link-sec {
            display: block; text-align: center; padding: 12px;
            background: rgba(255, 255, 255, 0.03); border-radius: 12px; color: #94a3b8;
            text-decoration: none; font-size: 0.85rem; font-weight: 600; margin: 20px 0; transition: 0.3s;
        }
        .btn-link-sec:hover { color: white; background: rgba(255, 255, 255, 0.06); }

        .btn-submit {
            width: 100%; background: #3b82f6; color: white; padding: 16px;
            border: none; border-radius: 12px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 1px; cursor: pointer;
            transition: 0.3s;
        }
        .btn-submit:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3); }

        .logout-section { margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.05); text-align: center; }
        .btn-logout { color: #fca5a5; text-decoration: none; font-size: 0.85rem; font-weight: 600; opacity: 0.7; transition: 0.3s; }
        .btn-logout:hover { opacity: 1; }

        .alert { padding: 14px; border-radius: 12px; margin-bottom: 25px; text-align: center; font-size: 0.9rem; }
        .alert-success { background: rgba(34, 197, 94, 0.1); color: #4ade80; border: none; }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: #fca5a5; border: none; }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <?php include 'includes/header.php'; ?>

    <div class="edit-container">
        <div class="card-profile">
            <form method="POST" enctype="multipart/form-data">
                
                <div class="profile-header">
                    <div class="avatar-picker">
                        <div class="profile-avatar-big" id="avatar-preview">
                            <?php 
                            $foto_actual = $user['profile_pic'];
                            if(!empty($foto_actual) && file_exists($foto_actual)): ?>
                                <img src="<?php echo htmlspecialchars($foto_actual); ?>" alt="Avatar">
                            <?php else: ?>
                                <span id="avatar-placeholder"><?php echo strtoupper(substr($user['username'] ?? 'U', 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <label for="file-input" class="change-pic-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                        </label>
                        <input id="file-input" type="file" name="profile_pic" accept="image/*">
                    </div>
                    <h2 style="margin:0; font-weight: 300; letter-spacing: 1px;"><?php echo htmlspecialchars($user['username']); ?></h2>
                    <p style="color: #64748b; font-size: 0.8rem; margin-top: 5px;"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>

                <?php if($mensaje): ?> <div class="alert alert-success"><?php echo $mensaje; ?></div> <?php endif; ?>
                <?php if($error): ?> <div class="alert alert-error"><?php echo $error; ?></div> <?php endif; ?>

                <div class="form-group">
                    <label>Nombre de usuario</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Ubicación</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" placeholder="Barcelona, ES">
                    </div>
                    <div class="form-group">
                        <label>Instagram</label>
                        <input type="text" name="instagram_user" value="<?php echo htmlspecialchars($user['instagram_user'] ?? ''); ?>" placeholder="@usuario">
                    </div>
                </div>

                <div class="form-group">
                    <label>Biografía</label>
                    <textarea name="bio" rows="3" placeholder="Cuéntanos algo sobre ti y tus coches..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <a href="change_password.php" class="btn-link-sec">
                    Cambiar contraseña de seguridad
                </a>

                <button type="submit" class="btn-submit">
                    Guardar Perfil
                </button>

                <div class="logout-section">
                    <a href="logout.php" class="btn-logout">Cerrar sesión</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('file-input').onchange = function(evt) {
            const files = evt.target.files;
            if (FileReader && files && files.length) {
                const fr = new FileReader();
                fr.onload = function () {
                    const preview = document.getElementById('avatar-preview');
                    const placeholder = document.getElementById('avatar-placeholder');
                    let img = preview.querySelector('img');
                    if (!img) { 
                        img = document.createElement('img'); 
                        preview.appendChild(img); 
                    }
                    img.src = fr.result;
                    if (placeholder) placeholder.style.display = 'none';
                }
                fr.readAsDataURL(files[0]);
            }
        };
    </script>
</body>
</html>