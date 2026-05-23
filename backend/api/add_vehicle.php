<?php
session_start();
include 'config/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $brand       = $_POST['brand'];
    $model       = $_POST['model'];
    $year        = $_POST['year'];
    $km          = $_POST['km']; 
    $potencia_cv = $_POST['potencia_cv'];
    $description = $_POST['description'];
    $user_id     = $_SESSION['user_id'];

    $image_name = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = time() . "_" . $user_id . "." . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], "assets/img/vehicles/" . $image_name);
    }

    // INSERT con el campo description incluido
    $sql = "INSERT INTO vehicles (brand, model, year, km, potencia_cv, description, image, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$brand, $model, $year, $km, $potencia_cv, $description, $image_name, $user_id])) {
        header("Location: index.php");
        exit;
    } else {
        $mensaje = "Error al subir el vehículo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar Coche - AutoOpinions</title>
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
            max-width: 600px; /* Un poco más ancho para la descripción */
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
            .form-row { grid-template-columns: 1fr; gap: 0; }
        }
    </style>
</head>
<body>

<div class="overlay">
    <div class="glass-card">
        <div class="header-section">
            <h1>Publicar <b>Vehículo</b></h1>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="input-group">
                    <span class="label-text">Marca</span>
                    <input type="text" name="brand" placeholder="BMW" required>
                </div>
                <div class="input-group">
                    <span class="label-text">Modelo</span>
                    <input type="text" name="model" placeholder="Serie 3" required>
                </div>
            </div>

            <div class="form-row">
    <div class="input-group">
        <span class="label-text">Año</span>
        <input type="number" name="year" placeholder="2024" required>
    </div>
    <div class="input-group">
        <span class="label-text">Kilómetros</span>
        <input type="number" name="km" placeholder="0" required>
    </div>
</div>
<div class="input-group">
    <span class="label-text">Potencia (CV)</span>
    <input type="number" name="potencia_cv" placeholder="150" required>
</div>

            <div class="input-group">
                <span class="label-text">Descripción</span>
                <textarea name="description" placeholder="Cuéntanos algo sobre esta joya..."></textarea>
            </div>

            <div class="input-group">
                <span class="label-text">Imagen del vehículo</span>
                <input type="file" name="image" accept="image/*" required>
            </div>

            <button type="submit" class="btn-submit">Publicar ahora</button>
            <a href="index.php" class="cancel-link">Volver atrás</a>
        </form>
    </div>
</div>

</body>
</html>