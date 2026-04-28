<?php
header('Content-Type: application/json');
require_once '../conexion.php';

$data = json_decode(file_get_contents('php://input'), true);

$nombre = trim($data['nombre'] ?? '');
$valor = isset($data['valor']) ? intval($data['valor']) : 0;
$waypoints = $data['waypoints'] ?? [];

try {
    $conexion->beginTransaction();

    $stmt = $conexion->prepare("
        INSERT INTO dbo.MapaRutas (Nombre, Valor)
        VALUES (:nombre, :valor)
    ");

    $stmt->execute([
        ':nombre' => $nombre,
        ':valor' => $valor
    ]);

    $idRuta = $conexion->lastInsertId();

    $stmtWp = $conexion->prepare("
        INSERT INTO dbo.MapaRutaWaypoints (
            IdMapaRuta,
            Lat,
            Lng,
            OrdenWaypoint
        )
        VALUES (
            :idRuta,
            :lat,
            :lng,
            :orden
        )
    ");

    $orden = 1;

    foreach ($waypoints as $wp) {
        $stmtWp->execute([
            ':idRuta' => $idRuta,
            ':lat' => $wp['lat'],
            ':lng' => $wp['lng'],
            ':orden' => $orden
        ]);

        $orden++;
    }

    $conexion->commit();

    echo json_encode(['success' => true, 'id' => $idRuta]);

} catch (PDOException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>