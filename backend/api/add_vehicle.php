<?php
session_start([
    'cookie_samesite' => 'None',
    'cookie_secure'   => true,
]);
include '../config/db.php';

header("Access-Control-Allow-Origin: https://autoopinions.es");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Sesión no iniciada."]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit;
}

$user_id     = $_SESSION['user_id'];
$brand       = trim($_POST['brand']       ?? '');
$model       = trim($_POST['model']       ?? '');
$year        = trim($_POST['year']        ?? '');
$km          = trim($_POST['km']          ?? '');
$potencia_cv = trim($_POST['potencia_cv'] ?? '');
$description = trim($_POST['description'] ?? '');

if (!$brand || !$model || !$year || !$km || !$potencia_cv) {
    echo json_encode(["success" => false, "message" => "Rellena todos los campos obligatorios."]);
    exit;
}

$image_name = "";
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
} else {
    echo json_encode(["success" => false, "message" => "La imagen es obligatoria."]);
    exit;
}

try {
    $sql  = "INSERT INTO vehicles (brand, model, year, km, potencia_cv, description, image, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$brand, $model, $year, $km, $potencia_cv, $description, $image_name, $user_id]);
    echo json_encode(["success" => true, "message" => "Vehículo publicado correctamente."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error al guardar en la base de datos."]);
}
?>