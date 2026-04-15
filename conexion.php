<?php
$serverName = "DESKTOP-56FEFQK"; // 🔥 IMPORTANTE: doble \\ 
$database   = "buses";

try {
    $conn = new PDO(
        "sqlsrv:Server=$serverName;Database=$database;TrustServerCertificate=true"
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>