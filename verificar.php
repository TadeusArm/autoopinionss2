<?php
// verificar.php
include 'backend/config/db.php';

$data = ['message' => '', 'status' => ''];

if (isset($_GET['token'])) {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE token = ? AND verified = 0");
    $stmt->execute([$_GET['token']]);
    $user = $stmt->fetch();

    if ($user) {
        $update = $pdo->prepare("UPDATE users SET verified = 1, token = NULL WHERE id = ?");
        $update->execute([$user['id']]);
        $data = ['message' => "¡Felicidades @" . htmlspecialchars($user['username']) . "! Tu cuenta ha sido activada.", 'status' => 'success'];
    } else {
        $data = ['message' => "Error: El enlace no es válido o la cuenta ya fue activada.", 'status' => 'error'];
    }
} else {
    $data = ['message' => "Error: Código de verificación no proporcionado.", 'status' => 'error'];
}
?>
<script>
    const verifData = <?php echo json_encode($data); ?>;
</script>
<?php include 'verificar.html'; ?>