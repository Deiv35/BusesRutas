<?php
header('Content-Type: application/json');
require_once '../conexion.php';

$data = json_decode(file_get_contents('php://input'), true);
$stmt = $pdo->prepare("DELETE FROM marcadores WHERE id = ?");
$stmt->execute([$data['id']]);
echo json_encode(['success' => true]);
?>