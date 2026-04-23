<?php
header('Content-Type: application/json');
require_once '../conexion.php';

$stmt = $pdo->query("SELECT id, nombre, lat, lng, cantidad FROM marcadores");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>