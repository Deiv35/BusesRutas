<?php
header('Content-Type: application/json');
require_once '../conexion.php';

$data = json_decode(file_get_contents('php://input'), true);

$nombre = trim($data['nombre'] ?? '');
$lat = $data['lat'] ?? null;
$lng = $data['lng'] ?? null;
$cantidad = isset($data['cantidad']) ? intval($data['cantidad']) : 0;

$stmt = $conexion->prepare("
    INSERT INTO dbo.MapaMarcadores (Nombre, Lat, Lng, Cantidad)
    VALUES (:nombre, :lat, :lng, :cantidad)
");

$stmt->execute([
    ':nombre' => $nombre,
    ':lat' => $lat,
    ':lng' => $lng,
    ':cantidad' => $cantidad
]);

echo json_encode(['success' => true, 'id' => $conexion->lastInsertId()]);
?>