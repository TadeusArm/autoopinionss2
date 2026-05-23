<?php
session_start();
include 'config/db.php';
require_once 'includes/functions_mail.php'; 

$modo_hosting = true; 

if(!isset($_SESSION['user_id']) || !isset($_GET['vehicle_id'])){
    header("Location: index.php");
    exit;
}

$coche_id = $_GET['vehicle_id'];
$mi_id = $_SESSION['user_id'];

// Comprobar si ya dejó una opinión principal (no respuesta)
$check = $pdo->prepare("SELECT id FROM comments WHERE user_id = ? AND vehicle_id = ? AND parent_id IS NULL");
$check->execute([$mi_id, $coche_id]);
$ya_he_opinado = $check->fetch();

// --- LÓGICA DE INSERCIÓN ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nota       = $_POST['nota'] ?? null;
    $comentario = trim($_POST['comentario'] ?? '');
    $parent_id  = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    $es_respuesta = ($parent_id !== null);

    $puede_enviar = $es_respuesta
        ? !empty($comentario)
        : (!$ya_he_opinado && $nota && !empty($comentario));

    if ($puede_enviar) {
        try {
            $pdo->beginTransaction();

            $ins_comm = $pdo->prepare("INSERT INTO comments (user_id, vehicle_id, content, parent_id) VALUES (?, ?, ?, ?)");
            $ins_comm->execute([$mi_id, $coche_id, $comentario, $parent_id]);

            // Rating solo en opinión principal
            if (!$es_respuesta) {
                $ins_rate = $pdo->prepare("INSERT INTO ratings (user_id, vehicle_id, rating) VALUES (?, ?, ?)");
                $ins_rate->execute([$mi_id, $coche_id, $nota]);
            }

            $pdo->commit();

            // Email al dueño, solo en opinión principal
            if (!$es_respuesta) {
                try {
                    $st_owner = $pdo->prepare("SELECT v.brand, v.model, u.email, u.username 
                                             FROM vehicles v 
                                             JOIN users u ON v.user_id = u.id 
                                             WHERE v.id = ?");
                    $st_owner->execute([$coche_id]);
                    $owner = $st_owner->fetch();

                    if ($owner && function_exists('enviarNotificacionEmail')) {
                        enviarNotificacionEmail(
                            $owner['email'], 
                            $owner['username'], 
                            'comment', 
                            $owner['brand'] . " " . $owner['model']
                        );
                    }
                } catch (Exception $e_mail) {}
            }

            header("Location: comments.php?vehicle_id=$coche_id#leer");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: index.php?error=db");
            exit;
        }
    }
}

// Datos del coche
$st_c = $pdo->prepare("SELECT v.*, u.username FROM vehicles v JOIN users u ON v.user_id = u.id WHERE v.id = ?");
$st_c->execute([$coche_id]);
$c = $st_c->fetch();

// Lista de comentarios — LEFT JOIN al padre para mostrar la cita
$st_l = $pdo->prepare("
    SELECT 
        c.*, 
        u.username, 
        r.rating,
        parent_c.content  AS parent_content,
        parent_u.username AS parent_username
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    LEFT JOIN ratings r ON (r.user_id = c.user_id AND r.vehicle_id = c.vehicle_id)
    LEFT JOIN comments parent_c ON c.parent_id = parent_c.id
    LEFT JOIN users    parent_u ON parent_c.user_id = parent_u.id
    WHERE c.vehicle_id = ? 
    ORDER BY c.id DESC
");
$st_l->execute([$coche_id]);
$lista = $st_l->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Opiniones - AutoOpinions</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.jpg">
    <style>
        body { 
            background: url('assets/img/fondo-comments.webp') center/cover no-repeat fixed !important; 
            margin: 0; padding: 0;
        }
        .contenedor-comentarios { max-width: 800px; margin: 0 auto; padding: 20px; position: relative; z-index: 2; }
        .bloque-glass { 
            background: rgba(17, 24, 39, 0.75); 
            backdrop-filter: blur(16px); 
            -webkit-backdrop-filter: blur(16px);
            padding: 25px; border-radius: 16px; margin-bottom: 20px; color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .coche-preview-header { display: flex; gap: 20px; align-items: center; }
        .img-preview { 
            width: 150px; height: 100px; object-fit: cover; border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.2); flex-shrink: 0; cursor: pointer; transition: 0.3s;
        }
        .img-preview:hover { transform: scale(1.05); }
        .btn-volver { 
            background: rgba(239, 68, 68, 0.15); color: #fca5a5; padding: 10px 18px; 
            text-decoration: none; border-radius: 10px; font-weight: bold; display: inline-block; 
            margin-bottom: 15px; transition: 0.3s; border: 1px solid rgba(239, 68, 68, 0.2); font-size: 0.9rem;
        }
        .btn-volver:hover { background: rgba(239,68,68,0.3); color: white; }

        /* Estrellas */
        .rating-stars { display: flex; flex-direction: row-reverse; justify-content: flex-end; margin: 10px 0; }
        .rating-stars input { display: none; }
        .rating-stars label { font-size: 2.5rem; color: rgba(255, 255, 255, 0.2); cursor: pointer; transition: 0.2s; margin-right: 5px; }
        .rating-stars input:checked ~ label, .rating-stars label:hover, .rating-stars label:hover ~ label { 
            color: #fbbf24; text-shadow: 0 0 10px rgba(251, 191, 36, 0.5); 
        }

        textarea { 
            width: 100%; background: rgba(255, 255, 255, 0.05); 
            border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 10px; 
            padding: 12px; color: white; box-sizing: border-box; resize: none; 
        }
        button[type="submit"] { 
            width: 100%; padding: 14px; border-radius: 10px; border: none; 
            background: #10b981; color: white; font-weight: bold; font-size: 1rem; cursor: pointer; 
        }

        /* Comentario individual */
        .comment-item {
            border-bottom: 1px solid rgba(255,255,255,0.07);
            padding: 16px 0;
        }
        .comment-item:last-child { border-bottom: none; }

        /* --- CITA estilo Discord/WhatsApp --- */
        /* Se muestra dentro del comentario guardado (respuesta ya publicada) */
        .quote-block {
            background: rgba(59, 130, 246, 0.08);
            border-left: 3px solid #3b82f6;
            border-radius: 0 8px 8px 0;
            padding: 8px 12px;
            margin-bottom: 10px;
        }
        .quote-block .quote-author {
            color: #60a5fa;
            font-size: 0.78rem;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .quote-block .quote-text {
            color: #94a3b8;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Preview de cita dentro del formulario (antes de enviar) */
        .reply-preview {
            display: none;
            background: rgba(59, 130, 246, 0.08);
            border-left: 3px solid #3b82f6;
            border-radius: 0 8px 8px 0;
            padding: 8px 12px;
            margin-bottom: 8px;
        }
        .reply-preview .preview-author {
            color: #60a5fa;
            font-size: 0.78rem;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .reply-preview .preview-text {
            color: #94a3b8;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Botón responder */
        .btn-responder {
            background: none;
            border: none;
            color: #60a5fa;
            font-size: 0.78rem;
            cursor: pointer;
            padding: 4px 0;
            width: auto;
            margin-top: 4px;
        }
        .btn-responder:hover { color: #93c5fd; }

        /* Formulario de respuesta */
        .form-respuesta { display: none; margin-top: 10px; }
        .form-respuesta textarea { height: 70px; font-size: 0.9rem; }
        .form-respuesta button[type="submit"] { 
            font-size: 0.85rem; padding: 9px; margin-top: 6px; background: #3b82f6;
        }

        @media (max-width: 768px) {
            .contenedor-comentarios { padding: 10px; }
            .coche-preview-header { flex-direction: column; text-align: center; }
            .img-preview { width: 100%; height: 180px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="contenedor-comentarios">
        <a href="index.php" class="btn-volver">← Volver al Muro</a>

        <!-- Info del coche -->
        <div class="bloque-glass">
            <div class="coche-preview-header">
                <?php if($c['image']): ?>
                    <a href="assets/img/vehicles/<?= htmlspecialchars($c['image']); ?>" target="_blank">
                        <img src="assets/img/vehicles/<?= htmlspecialchars($c['image']); ?>" class="img-preview" alt="Coche">
                    </a>
                <?php else: ?>
                    <img src="assets/img/no-foto.webp" class="img-preview" alt="Sin foto">
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; color: #60a5fa;"><?= htmlspecialchars($c['brand']." ".$c['model']); ?></h2>
                    <p style="color: #9ca3af; margin: 5px 0;">Publicado por: <b>@<?= htmlspecialchars($c['username']); ?></b></p>
                </div>
            </div>
        </div>

        <!-- Formulario opinión principal -->
        <div class="bloque-glass">
            <?php if($ya_he_opinado): ?>
                <div style="text-align: center; color:#4ade80; font-weight: bold;">✓ Ya has valorado este vehículo.</div>
            <?php else: ?>
                <h3 style="margin-top:0;">Danos tu valoración</h3>
                <form method="POST">
                    <div class="rating-stars">
                        <input type="radio" id="star5" name="nota" value="5" required /><label for="star5">★</label>
                        <input type="radio" id="star4" name="nota" value="4" /><label for="star4">★</label>
                        <input type="radio" id="star3" name="nota" value="3" /><label for="star3">★</label>
                        <input type="radio" id="star2" name="nota" value="2" /><label for="star2">★</label>
                        <input type="radio" id="star1" name="nota" value="1" /><label for="star1">★</label>
                    </div>
                    <textarea name="comentario" placeholder="¿Qué te parece este coche?" required style="height: 100px;"></textarea>
                    <button type="submit" style="margin-top: 15px;">Publicar Opinión</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Lista de comentarios -->
        <div class="bloque-glass" id="leer">
            <h3 style="margin-top:0;">Opiniones de la comunidad</h3>

            <?php foreach($lista as $l): 
                $es_respuesta = !empty($l['parent_id']);
            ?>
                <div class="comment-item">

                    <?php if($es_respuesta && !empty($l['parent_username'])): ?>
                        <!-- Cita del mensaje al que responde (ya guardado) -->
                        <div class="quote-block">
                            <div class="quote-author">↩ @<?= htmlspecialchars($l['parent_username']); ?></div>
                            <div class="quote-text">
                                <?= htmlspecialchars(mb_substr($l['parent_content'] ?? '', 0, 120)) ?>
                                <?= mb_strlen($l['parent_content'] ?? '') > 120 ? '…' : '' ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Cabecera del comentario -->
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong style="color: <?= $es_respuesta ? '#93c5fd' : '#f8fafc' ?>;">
                            @<?= htmlspecialchars($l['username']); ?>
                        </strong>
                        <?php if(!$es_respuesta && $l['rating']): ?>
                            <span style="color: #fbbf24;">
                                <?php for($i=1; $i<=5; $i++) echo ($i <= $l['rating']) ? '★' : '☆'; ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <p style="margin: 8px 0; color: #d1d5db;"><?= nl2br(htmlspecialchars($l['content'])); ?></p>

                    <!-- Botón responder (disponible en todos los comentarios) -->
                    <button 
                        class="btn-responder"
                        onclick="toggleReply(
                            <?= $l['id'] ?>, 
                            '<?= htmlspecialchars($l['username'], ENT_QUOTES) ?>', 
                            '<?= htmlspecialchars(mb_substr($l['content'], 0, 100), ENT_QUOTES) ?>'
                        )">
                        ↩ Responder
                    </button>

                    <!-- Formulario de respuesta con preview de cita -->
                    <div id="form-<?= $l['id'] ?>" class="form-respuesta">
                        <div class="reply-preview" id="preview-<?= $l['id'] ?>">
                            <div class="preview-author" id="preview-author-<?= $l['id'] ?>"></div>
                            <div class="preview-text"  id="preview-text-<?= $l['id'] ?>"></div>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="parent_id" value="<?= $l['id'] ?>">
                            <textarea name="comentario" required placeholder="Escribe tu respuesta..."></textarea>
                            <button type="submit">Enviar respuesta</button>
                        </form>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function toggleReply(id, author, text) {
            // Cerrar todos los formularios abiertos primero
            document.querySelectorAll('.form-respuesta').forEach(function(f) {
                f.style.display = 'none';
            });

            var form    = document.getElementById('form-'           + id);
            var preview = document.getElementById('preview-'        + id);
            var pAuthor = document.getElementById('preview-author-' + id);
            var pText   = document.getElementById('preview-text-'   + id);

            // Rellenar la preview con quién y qué se está citando
            pAuthor.textContent = '↩ @' + author;
            pText.textContent   = text + (text.length >= 100 ? '…' : '');
            preview.style.display = 'block';

            form.style.display = 'block';
            form.querySelector('textarea').focus();
        }
    </script>
</body>
</html>