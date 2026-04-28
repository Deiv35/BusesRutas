<?php
header('Content-Type: application/json');
require_once '../conexion.php';

$stmt = $conexion->query("
    SELECT 
        IdMarcador AS id,
        Nombre AS nombre,
        Lat AS lat,
        Lng AS lng,
        Cantidad AS cantidad
    FROM dbo.MapaMarcadores
    WHERE Estado = 1
");

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>