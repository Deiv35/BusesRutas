<?php
header('Content-Type: application/json');
require_once '../conexion.php';

$data = json_decode(file_get_contents('php://input'), true);

$idRuta = $data['id'];
$nombre = trim($data['nombre']);
$valor = intval($data['valor']);
$waypoints = $data['waypoints'] ?? [];

try {
    $conexion->beginTransaction();

    $stmt = $conexion->prepare("
        UPDATE dbo.MapaRutas
        SET 
            Nombre = :nombre,
            Valor = :valor
        WHERE IdMapaRuta = :id
    ");

    $stmt->execute([
        ':nombre' => $nombre,
        ':valor' => $valor,
        ':id' => $idRuta
    ]);

    $stmt = $conexion->prepare("
        DELETE FROM dbo.MapaRutaWaypoints
        WHERE IdMapaRuta = :idRuta
    ");

    $stmt->execute([
        ':idRuta' => $idRuta
    ]);

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

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>