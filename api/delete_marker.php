<?php
header('Content-Type: application/json');
require_once '../conexion.php';

$data = json_decode(file_get_contents('php://input'), true);

$stmt = $conexion->prepare("
    UPDATE dbo.MapaMarcadores
    SET Estado = 0
    WHERE IdMarcador = :id
");

$stmt->execute([
    ':id' => $data['id']
]);

echo json_encode(['success' => true]);
?>