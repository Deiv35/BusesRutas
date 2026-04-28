<?php
require_once "conexion.php";

function crearUsuario(
    $conexion,
    $usuario,
    $correo,
    $password,
    $tipo,
    $categoriaEmpresa = null,
    $nombreEmpresa = null,
    $nitEmpresa = null,
    $direccionEmpresa = null,
    $telefonoEmpresa = null,
    $ciudadEmpresa = null,
    $correoEmpresa = null,
    $nombreContacto = null
) {
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "
        INSERT INTO dbo.Usuarios (
            NombreUsuario,
            Correo,
            PasswordHash,
            IdTipoUsuario,
            IdCategoriaEmpresa,
            NombreEmpresa,
            NitEmpresa,
            DireccionEmpresa,
            TelefonoEmpresa,
            CiudadEmpresa,
            CorreoEmpresa,
            NombreContacto,
            Estado
        )
        OUTPUT INSERTED.IdUsuario
        VALUES (
            :usuario,
            :correo,
            :hash,
            :tipo,
            :categoriaEmpresa,
            :nombreEmpresa,
            :nitEmpresa,
            :direccionEmpresa,
            :telefonoEmpresa,
            :ciudadEmpresa,
            :correoEmpresa,
            :nombreContacto,
            1
        )
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":usuario" => $usuario,
        ":correo" => $correo,
        ":hash" => $hash,
        ":tipo" => $tipo,
        ":categoriaEmpresa" => $categoriaEmpresa,
        ":nombreEmpresa" => $nombreEmpresa,
        ":nitEmpresa" => $nitEmpresa,
        ":direccionEmpresa" => $direccionEmpresa,
        ":telefonoEmpresa" => $telefonoEmpresa,
        ":ciudadEmpresa" => $ciudadEmpresa,
        ":correoEmpresa" => $correoEmpresa,
        ":nombreContacto" => $nombreContacto
    ]);

    return (int)$stmt->fetchColumn();
}

function generarCodigoAccesoContador($conexion) {
    do {
        $codigo = str_pad((string)random_int(0, 99999999), 8, "0", STR_PAD_LEFT);

        $stmt = $conexion->prepare("
            SELECT
                (
                    SELECT COUNT(*)
                    FROM dbo.ContadoresEmpresa
                    WHERE CodigoAcceso = :codigo1
                )
                +
                (
                    SELECT COUNT(*)
                    FROM dbo.PuntosControl
                    WHERE CodigoAcceso = :codigo2
                )
        ");

        $stmt->execute([
            ":codigo1" => $codigo,
            ":codigo2" => $codigo
        ]);

        $existe = (int)$stmt->fetchColumn();

    } while ($existe > 0);

    return $codigo;
}

function crearContadorEmpresa(
    $conexion,
    $idEmpresa,
    $nombreContador,
    $usuarioContador,
    $correoContador,
    $password,
    $cedulaContador,
    $idPuntoControl = null
) {
    $codigoAcceso = generarCodigoAccesoContador($conexion);

    $idUsuarioContador = crearUsuario(
        $conexion,
        $usuarioContador,
        $correoContador,
        $password,
        2,
        2,
        "Contador - " . $nombreContador
    );

    $sql = "
        INSERT INTO dbo.ContadoresEmpresa (
            IdUsuarioContador,
            IdEmpresa,
            IdPuntoControl,
            NombreContador,
            CedulaContador,
            CodigoAcceso,
            Estado
        )
        VALUES (
            :idUsuarioContador,
            :idEmpresa,
            :idPuntoControl,
            :nombreContador,
            :cedulaContador,
            :codigoAcceso,
            1
        )
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":idUsuarioContador" => $idUsuarioContador,
        ":idEmpresa" => $idEmpresa,
        ":idPuntoControl" => $idPuntoControl,
        ":nombreContador" => $nombreContador,
        ":cedulaContador" => $cedulaContador,
        ":codigoAcceso" => $codigoAcceso
    ]);

    return $codigoAcceso;
}

try {
    $conexion->beginTransaction();

    // ADMINISTRADOR
    crearUsuario(
        $conexion,
        "admin",
        "admin@tranbus.com",
        "Admin123",
        1
    );

    // EMPRESA INFORMATIVA
    $idEmpresaInfo = crearUsuario(
        $conexion,
        "empresaInfo",
        "info@empresa.com",
        "Empresa123",
        2,
        1,
        "Empresa Informativa S.A",
        "900123456",
        "Calle 10 #20-30",
        "3001112233",
        "Bogotá",
        "contacto@empresa.com",
        "Carlos Pérez"
    );

    // EMPRESA CONTADOR
    crearUsuario(
        $conexion,
        "empresaContador",
        "contador@empresa.com",
        "Contador123",
        2,
        2,
        "Empresa Contadora S.A",
        "900654321",
        "Carrera 15 #45-60",
        "3009998877",
        "Bogotá",
        "contabilidad@empresa.com",
        "Laura Gómez"
    );

    // CONTADOR ASOCIADO A EMPRESA INFORMATIVA
    $codigoContador = crearContadorEmpresa(
        $conexion,
        $idEmpresaInfo,
        "Pedro Contador",
        "pedroContador",
        "pedro.contador@empresa.com",
        "Pedro123",
        "1010101010"
    );

    // USUARIO COMÚN
    crearUsuario(
        $conexion,
        "usuario1",
        "usuario@tranbus.com",
        "Usuario123",
        3
    );

    $conexion->commit();

    echo "Usuarios creados correctamente 🚀<br>";
    echo "Código del contador Pedro: " . $codigoContador;

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    echo "Error al crear usuarios: " . $e->getMessage();
}
?>