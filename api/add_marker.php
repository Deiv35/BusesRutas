<?php
header('Content-Type: application/json');
require_once '../config.php';

$data = json_decode(file_get_contents('php://input'), true);
$nombre = trim($data['nombre']);
$lat = $data['lat'];
$lng = $data['lng'];
$cantidad = isset($data['cantidad']) ? intval($data['cantidad']) : 0;

$sql = "INSERT INTO marcadores (nombre, lat, lng, cantidad) VALUES (:nombre, :lat, :lng, :cantidad)";
$stmt = $pdo->prepare($sql);
$stmt->execute([':nombre' => $nombre, ':lat' => $lat, ':lng' => $lng, ':cantidad' => $cantidad]);

echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
?>