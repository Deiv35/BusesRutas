<?php
header('Content-Type: application/json');
require_once '../config.php';

$stmt = $pdo->query("SELECT id, nombre, waypoints, valor FROM rutas");
$rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rutas as &$r) {
    $r['waypoints'] = json_decode($r['waypoints'], true);
}
echo json_encode($rutas);
?>