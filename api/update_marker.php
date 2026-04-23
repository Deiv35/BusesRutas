<?php
header('Content-Type: application/json');
require_once '../conexion.php';

$data = json_decode(file_get_contents('php://input'), true);
$sql = "UPDATE marcadores SET nombre = :nombre, lat = :lat, lng = :lng, cantidad = :cantidad WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':nombre' => $data['nombre'],
    ':lat' => $data['lat'],
    ':lng' => $data['lng'],
    ':cantidad' => $data['cantidad'],
    ':id' => $data['id']
]);
echo json_encode(['success' => true]);
?>