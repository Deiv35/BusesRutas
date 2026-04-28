<?php
$serverName = "db28471.public.databaseasp.net";
$database = "db28471";
$user = "db28471";
$password = "2Fb%y9-EH_z7";

try {
    $conexion = new PDO(
        "sqlsrv:Server=$serverName;Database=$database;Encrypt=true;TrustServerCertificate=true;MultipleActiveResultSets=true",
        $user,
        $password
    );

    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
