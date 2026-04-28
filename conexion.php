<?php
$serverName = "DESKTOP-56FEFQK";
$database = "BusesRemake";

try {
    $conexion = new PDO(
        "sqlsrv:Server=$serverName;Database=$database",
        null,
        null
    );

    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>