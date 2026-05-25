<?php
// backend/api/edit_vehicle.php
session_start();
include '../config/db.php';

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Sesión no iniciada."]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$metodo  = $_SERVER['REQUEST_METHOD'];

// ─── GET: cargar datos del vehículo ─────────────────────────────────────────
if ($metodo === 'GET') {
    $vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($vehicle_id <= 0) {
        echo json_encode(["success" => false, "message" => "ID no válido."]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND user_id = ?");
    $stmt->execute([$vehicle_id, $user_id]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehicle) {
        echo json_encode(["success" => false, "message" => "Vehículo no encontrado."]);
        exit;
    }

    echo json_encode([
        "success"  => true,
        "vehicle"  => $vehicle,
        "user_id"  => $user_id
    ]);
    exit;
}

// ─── POST: guardar cambios ───────────────────────────────────────────────────
if ($metodo === 'POST') {
    $vehicle_id  = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : 0;
    $brand       = trim($_POST['brand']       ?? '');
    $model       = trim($_POST['model']       ?? '');
    $year        = trim($_POST['year']        ?? '');
    $km          = trim($_POST['km']          ?? '');
    $potencia_cv = (int)($_POST['potencia_cv'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($vehicle_id <= 0 || !$brand || !$model || !$year || !$km) {
        echo json_encode(["success" => false, "message" => "Rellena todos los campos obligatorios."]);
        exit;
    }

    // Verificar que el vehículo pertenece al usuario
    $stmt_check = $pdo->prepare("SELECT image FROM vehicles WHERE id = ? AND user_id = ?");
    $stmt_check->execute([$vehicle_id, $user_id]);
    $coche = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$coche) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tienes permiso para editar este vehículo."]);
        exit;
    }

    // Imagen — mantener la actual si no se sube una nueva
    $image_name = $coche['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            echo json_encode(["success" => false, "message" => "Formato de imagen no permitido."]);
            exit;
        }

        $image_name  = time() . "_" . $user_id . "." . $ext;
        $upload_path = __DIR__ . "/../../assets/img/vehicles/" . $image_name;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            echo json_encode(["success" => false, "message" => "Error al subir la imagen."]);
            exit;
        }
    }

    // Actualizar en BD
    try {
        $sql  = "UPDATE vehicles SET brand=?, model=?, year=?, km=?, potencia_cv=?, description=?, image=? WHERE id=? AND user_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$brand, $model, $year, $km, $potencia_cv, $description, $image_name, $vehicle_id, $user_id]);

        echo json_encode([
            "success"  => true,
            "message"  => "Vehículo actualizado correctamente.",
            "redirect" => "profile.html?id=" . $user_id
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al guardar en la base de datos."]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["success" => false, "message" => "Método no permitido."]);
?>