<?php
// backend/api/get_profile.php
session_start();
include '../config/db.php';

header("Content-Type: application/json; charset=UTF-8");

$perfil_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($perfil_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID de perfil no válido."]);
    exit;
}

// Si no hay sesión activa, mi_id = 0 (visitante anónimo)
$mi_id = $_SESSION['user_id'] ?? 0;

try {
    // 1. Datos del usuario — incluye location e instagram_user
    $stmt = $pdo->prepare("SELECT id, username, profile_pic, bio, location, instagram_user FROM users WHERE id = ?");
    $stmt->execute([$perfil_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo json_encode(["success" => false, "banned" => true, "message" => "Usuario no encontrado o suspendido."]);
        exit;
    }

    // 2. Seguidores y seguidos
    $stmt_followers = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = ?");
    $stmt_followers->execute([$perfil_id]);
    $total_seguidores = $stmt_followers->fetchColumn();

    $stmt_following = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
    $stmt_following->execute([$perfil_id]);
    $total_seguidos = $stmt_following->fetchColumn();

    // 3. ¿Lo sigo yo?
    $lo_sigo = false;
    if ($mi_id > 0 && $mi_id != $perfil_id) {
        $stmt_check = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?");
        $stmt_check->execute([$mi_id, $perfil_id]);
        $lo_sigo = (bool)$stmt_check->fetch();
    }

    // 4. Coches del garaje
    $stmt_coches = $pdo->prepare("
        SELECT v.*,
        (SELECT COUNT(*) FROM comments WHERE vehicle_id = v.id) AS total_comentarios,
        (SELECT AVG(rating) FROM ratings WHERE vehicle_id = v.id) AS nota_media
        FROM vehicles v
        WHERE v.user_id = ?
        ORDER BY v.id DESC
    ");
    $stmt_coches->execute([$perfil_id]);
    $coches_db = $stmt_coches->fetchAll(PDO::FETCH_ASSOC);

    $garaje = [];
    foreach ($coches_db as $v) {
        $garaje[] = [
            "id"                => $v['id'],
            "brand"             => $v['brand'],
            "model"             => $v['model'],
            "image"             => !empty($v['image']) ? "/assets/img/vehicles/" . basename($v['image']) : "",
            "nota_media"        => $v['nota_media'] ? round($v['nota_media'], 1) : '--',
            "total_comentarios" => $v['total_comentarios']
        ];
    }

    // 5. Respuesta
    echo json_encode([
        "success"   => true,
        "is_owner"  => ($mi_id > 0 && $mi_id == $perfil_id),
        "mi_id"     => $mi_id,
        "user_header" => [
            "username"    => $_SESSION['username'] ?? '',
            "profile_pic" => !empty($_SESSION['profile_pic']) ? "/assets/img/avatars/" . basename($_SESSION['profile_pic']) : ""
        ],
        "profile_data" => [
            "id"              => $usuario['id'],
            "username"        => $usuario['username'],
            "profile_pic"     => !empty($usuario['profile_pic']) ? "/assets/img/avatars/" . basename($usuario['profile_pic']) : "",
            "bio"             => $usuario['bio'] ?? '',
            "location"        => $usuario['location'] ?? '',
            "instagram_user"  => $usuario['instagram_user'] ?? '',
            "total_seguidores"=> (int)$total_seguidores,
            "total_seguidos"  => (int)$total_seguidos,
            "lo_sigo"         => $lo_sigo
        ],
        "garage" => $garaje
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error de servidor: " . $e->getMessage()]);
}
?>