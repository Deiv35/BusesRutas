<?php
header('Content-Type: application/json');
require_once '../config.php';

$data = json_decode(file_get_contents('php://input'), true);
$sql = "UPDATE rutas SET nombre = :nombre, waypoints = :waypoints, valor = :valor WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':nombre' => $data['nombre'],
    ':waypoints' => json_encode($data['waypoints']),
    ':valor' => $data['valor'],
    ':id' => $data['id']
]);
echo json_encode(['success' => true]);
?>