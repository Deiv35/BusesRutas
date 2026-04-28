<?php
header('Content-Type: application/json');
require_once '../conexion.php';

$data = json_decode(file_get_contents('php://input'), true);

$stmt = $conexion->prepare("
    UPDATE dbo.MapaMarcadores
    SET 
        Nombre = :nombre,
        Lat = :lat,
        Lng = :lng,
        Cantidad = :cantidad
    WHERE IdMarcador = :id
");

$stmt->execute([
    ':nombre' => $data['nombre'],
    ':lat' => $data['lat'],
    ':lng' => $data['lng'],
    ':cantidad' => $data['cantidad'],
    ':id' => $data['id']
]);

echo json_encode(['success' => true]);
?>