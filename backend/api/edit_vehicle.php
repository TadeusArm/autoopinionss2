<?php
session_start();
include 'config/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$mensaje = "";
$user_id = $_SESSION['user_id'];
$vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt_load = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND user_id = ?");
$stmt_load->execute([$vehicle_id, $user_id]);
$coche = $stmt_load->fetch();

if (!$coche) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $brand       = $_POST['brand'];
    $model       = $_POST['model'];
    $year        = $_POST['year'];
    $km          = $_POST['km'];
    $potencia_cv = (int)$_POST['potencia_cv'];
    $description = $_POST['description'];

    $image_name = $coche['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = time() . "_" . $user_id . "." . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], "assets/img/vehicles/" . $image_name);
    }

    $sql = "UPDATE vehicles SET brand = ?, model = ?, year = ?, km = ?, potencia_cv = ?, description = ?, image = ? WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$brand, $model, $year, $km, $potencia_cv, $description, $image_name, $vehicle_id, $user_id])) {
        header("Location: profile.php?id=" . $user_id);
        exit;
    } else {
        $mensaje = "Error al actualizar el vehículo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Vehículo - AutoOpinions</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.jpg">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: url('assets/img/PublicarVehiculo.jpeg') no-repeat center center fixed;
            background-size: cover;
            color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .overlay {
            background: rgba(10, 15, 25, 0.85);
            min-height: 100vh;
            padding: 40px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .glass-card {
            width: 100%;
            max-width: 600px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .header-section {
            text-align: center;
            margin-bottom: 35px;
        }

        h1 { font-weight: 200; font-size: 1.5rem; letter-spacing: 4px; text-transform: uppercase; margin: 0; }
        h1 b { color: #3b82f6; font-weight: 900; }

        .input-group { margin-bottom: 20px; }

        .label-text {
            color: #64748b;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
            display: block;
        }

        input[type="text"],
        input[type="number"],
        input[type="file"],
        textarea {
            width: 100%;
            padding: 14px;
            background: rgba(255, 255, 255, 0.05);
            border: none;
            border-radius: 12px;
            color: white;
            font-family: 'Segoe UI', sans-serif;
            font-size: 0.9rem;
            outline: none;
            transition: background 0.3s ease;
        }

        textarea {
            resize: none;
            height: 100px;
        }

        input:focus, textarea:focus {
            background: rgba(255, 255, 255, 0.08);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }

        .cancel-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #475569;
            text-decoration: none;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        @media (max-width: 480px) {
            .form-row, .form-row-3 { grid-template-columns: 1fr; gap: 0; }
        }
    </style>
</head>
<body>

<div class="overlay">
    <div class="glass-card">
        <div class="header-section">
            <h1>Editar <b>Vehículo</b></h1>
        </div>

        <?php if($mensaje): ?>
            <p style="color: #ef4444; text-align: center; margin-bottom: 15px;"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="input-group">
                    <span class="label-text">Marca</span>
                    <input type="text" name="brand" value="<?= htmlspecialchars($coche['brand']) ?>" required>
                </div>
                <div class="input-group">
                    <span class="label-text">Modelo</span>
                    <input type="text" name="model" value="<?= htmlspecialchars($coche['model']) ?>" required>
                </div>
            </div>

            <div class="form-row-3">
                <div class="input-group">
                    <span class="label-text">Año</span>
                    <input type="number" name="year" value="<?= htmlspecialchars($coche['year']) ?>" required>
                </div>
                <div class="input-group">
                    <span class="label-text">Kilómetros</span>
                    <input type="number" name="km" value="<?= htmlspecialchars($coche['km']) ?>" required>
                </div>
                <div class="input-group">
                    <span class="label-text">Potencia (CV)</span>
                    <input type="number" name="potencia_cv" value="<?= htmlspecialchars($coche['potencia_cv'] ?? '') ?>" min="1" max="2000">
                </div>
            </div>

            <div class="input-group">
                <span class="label-text">Descripción</span>
                <textarea name="description"><?= htmlspecialchars($coche['description'] ?? $coche['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="input-group">
                <span class="label-text">Imagen (dejar vacío para mantener la actual)</span>
                <input type="file" name="image" accept="image/*">
            </div>

            <button type="submit" class="btn-submit">Guardar cambios</button>
            <a href="profile.php?id=<?= $user_id ?>" class="cancel-link">Cancelar y volver</a>
        </form>
    </div>
</div>

</body>
</html>