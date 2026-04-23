<?php
$serverName = "db28471.public.databaseasp.net";
$database   = "db28471";
$username   = "db28471";
$password   = "2Fb%y9-EH_z7";

try {
    $conn = new PDO(
        "odbc:Driver={ODBC Driver 17 for SQL Server};Server=$serverName;Database=$database;",
        $username,
        $password
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // echo "Conexión exitosa";
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
