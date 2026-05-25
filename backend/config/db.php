<?php
// backend/config/db.php

// Datos de conexión reales extraídos de tu hosting en IONOS
$host = 'db5020437286.hosting-data.io'; 
$db   = 'dbs15660988'; 
$user = 'dbu2367501'; 
$pass = 'Tadeuossanbaudelio29032006*'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    // Configurar el modo de error de PDO a excepción
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Configurar el modo de fetch por defecto a Array Asociativo para el json
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si falla motivo  en el log 
    http_response_code(500);
    die(json_encode(["success" => false, "message" => "Error de conexión: " . $e->getMessage()]));
}