<?php
$host = 'db5020437286.hosting-data.io'; 
$db   = 'dbs15660988'; 
$user = 'dbu2367501'; 
$pass = 'Tadeuossanbaudelio29032006*'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Aquí NO usamos die con JSON, para evitar romper el flujo
    exit("Error grave de conexión: " . $e->getMessage());
}
?>