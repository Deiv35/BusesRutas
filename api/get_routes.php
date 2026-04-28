<?php
header('Content-Type: application/json');
require_once '../conexion.php';

$stmt = $conexion->query("
    SELECT 
        IdMapaRuta AS id,
        Nombre AS nombre,
        Valor AS valor
    FROM dbo.MapaRutas
    WHERE Estado = 1
");

$rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rutas as &$ruta) {
    $stmtWp = $conexion->prepare("
        SELECT 
            Lat AS lat,
            Lng AS lng
        FROM dbo.MapaRutaWaypoints
        WHERE IdMapaRuta = :idRuta
        ORDER BY OrdenWaypoint
    ");

    $stmtWp->execute([
        ':idRuta' => $ruta['id']
    ]);

    $ruta['waypoints'] = $stmtWp->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($rutas);
?>