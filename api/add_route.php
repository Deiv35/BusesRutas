<?php
header('Content-Type: application/json');
require_once '../config.php';

$data = json_decode(file_get_contents('php://input'), true);
$nombre = trim($data['nombre']);
$waypoints = json_encode($data['waypoints']);
$valor = isset($data['valor']) ? intval($data['valor']) : 0;

$sql = "INSERT INTO rutas (nombre, waypoints, valor) VALUES (:nombre, :waypoints, :valor)";
$stmt = $pdo->prepare($sql);
$stmt->execute([':nombre' => $nombre, ':waypoints' => $waypoints, ':valor' => $valor]);
echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
?>