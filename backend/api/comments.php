<?php
session_start([
    'cookie_samesite' => 'None',
    'cookie_secure'   => true,
]);
include '../config/db.php';

// Incluir PHPMailer con ruta absoluta al vendor
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
    require_once dirname(__DIR__) . '/includes/functions_mail.php';
}

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

$mi_id  = $_SESSION['user_id'];
$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $vehicle_id = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
    if ($vehicle_id <= 0) { echo json_encode(["success" => false, "message" => "ID no válido."]); exit; }

    try {
        $st = $pdo->prepare("SELECT v.*, u.username FROM vehicles v JOIN users u ON v.user_id = u.id WHERE v.id = ?");
        $st->execute([$vehicle_id]);
        $coche = $st->fetch(PDO::FETCH_ASSOC);

        if (!$coche) { echo json_encode(["success" => false, "message" => "Vehículo no encontrado."]); exit; }

        $coche['image'] = !empty($coche['image']) ? "/assets/img/vehicles/" . basename($coche['image']) : "";

        $check = $pdo->prepare("SELECT id FROM comments WHERE user_id = ? AND vehicle_id = ? AND parent_id IS NULL");
        $check->execute([$mi_id, $vehicle_id]);
        $ya_opine = (bool)$check->fetch();

        $st_l = $pdo->prepare("
            SELECT c.*, u.username, r.rating,
                   parent_c.content AS parent_content, parent_u.username AS parent_username
            FROM comments c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN ratings r ON (r.user_id = c.user_id AND r.vehicle_id = c.vehicle_id)
            LEFT JOIN comments parent_c ON c.parent_id = parent_c.id
            LEFT JOIN users parent_u ON parent_c.user_id = parent_u.id
            WHERE c.vehicle_id = ? ORDER BY c.id DESC
        ");
        $st_l->execute([$vehicle_id]);
        $comentarios = $st_l->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true, "coche" => $coche, "ya_opine" => $ya_opine,
            "comentarios" => $comentarios,
            "user_header" => [
                "username"    => $_SESSION['username'] ?? '',
                "profile_pic" => !empty($_SESSION['profile_pic']) ? "/assets/img/avatars/" . basename($_SESSION['profile_pic']) : ""
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error de servidor: " . $e->getMessage()]);
    }
    exit;
}

if ($metodo === 'POST') {
    $vehicle_id  = (int)($_POST['vehicle_id'] ?? 0);
    $comentario  = trim($_POST['comentario']  ?? '');
    $nota        = $_POST['nota']             ?? null;
    $parent_id   = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $es_respuesta = ($parent_id !== null);

    if ($vehicle_id <= 0 || empty($comentario)) { echo json_encode(["success" => false, "message" => "Datos incompletos."]); exit; }

    if (!$es_respuesta) {
        $check = $pdo->prepare("SELECT id FROM comments WHERE user_id = ? AND vehicle_id = ? AND parent_id IS NULL");
        $check->execute([$mi_id, $vehicle_id]);
        if ($check->fetch()) { echo json_encode(["success" => false, "message" => "Ya has valorado este vehículo."]); exit; }
        if (!$nota) { echo json_encode(["success" => false, "message" => "Selecciona una puntuación."]); exit; }
    }

    try {
        $pdo->beginTransaction();
        $ins = $pdo->prepare("INSERT INTO comments (user_id, vehicle_id, content, parent_id) VALUES (?, ?, ?, ?)");
        $ins->execute([$mi_id, $vehicle_id, $comentario, $parent_id]);
        if (!$es_respuesta) {
            $ins_r = $pdo->prepare("INSERT INTO ratings (user_id, vehicle_id, rating) VALUES (?, ?, ?)");
            $ins_r->execute([$mi_id, $vehicle_id, $nota]);
        }
        $pdo->commit();

        if (!$es_respuesta) {
            try {
                $st_owner = $pdo->prepare("SELECT v.brand, v.model, u.email, u.username FROM vehicles v JOIN users u ON v.user_id = u.id WHERE v.id = ?");
                $st_owner->execute([$vehicle_id]);
                $owner = $st_owner->fetch();
                if ($owner && function_exists('enviarNotificacionEmail')) {
                    enviarNotificacionEmail($owner['email'], $owner['username'], 'comment', $owner['brand'] . " " . $owner['model']);
                }
            } catch (Exception $e_mail) {}
        }
        echo json_encode(["success" => true, "message" => "Publicado correctamente."]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al guardar: " . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["success" => false, "message" => "Método no permitido."]);
?>