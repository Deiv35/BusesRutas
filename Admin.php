<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["idUsuario"]) || $_SESSION["tipo"] != "Administrador") {
    header("Location: IniciarSesion.php");
    exit();
}

$mensaje = "";
$error = "";

$usuarioEditar = null;
$municipioEditar = null;

$idUsuarioEditar = isset($_GET["editar"]) ? (int)$_GET["editar"] : 0;
$idMunicipioEditar = isset($_GET["municipio"]) ? (int)$_GET["municipio"] : 0;

function limpiar($dato) {
    return htmlspecialchars(trim((string)$dato), ENT_QUOTES, "UTF-8");
}

try {

    if (isset($_POST["crear"])) {
        $usuario = trim($_POST["usuario"]);
        $correo = trim($_POST["correo"]);
        $contrasena = $_POST["contrasena"];
        $tipo = $_POST["tipo"];

        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        if ($tipo == 2) {
            $categoriaEmpresa = 1;
            $nombreEmpresa = trim($_POST["nombreEmpresa"]);
            $nitEmpresa = trim($_POST["nitEmpresa"]);
            $direccionEmpresa = trim($_POST["direccionEmpresa"]);
            $telefonoEmpresa = trim($_POST["telefonoEmpresa"]);
            $ciudadEmpresa = trim($_POST["ciudadEmpresa"]);
            $correoEmpresa = trim($_POST["correoEmpresa"]);
            $nombreContacto = trim($_POST["nombreContacto"]);
        } else {
            $categoriaEmpresa = null;
            $nombreEmpresa = null;
            $nitEmpresa = null;
            $direccionEmpresa = null;
            $telefonoEmpresa = null;
            $ciudadEmpresa = null;
            $correoEmpresa = null;
            $nombreContacto = null;
        }

        $sql = "
            INSERT INTO dbo.Usuarios (
                NombreUsuario, Correo, PasswordHash, IdTipoUsuario,
                IdCategoriaEmpresa, NombreEmpresa, NitEmpresa,
                DireccionEmpresa, TelefonoEmpresa, CiudadEmpresa,
                CorreoEmpresa, NombreContacto
            )
            VALUES (
                :usuario, :correo, :hash, :tipo,
                :categoriaEmpresa, :nombreEmpresa, :nitEmpresa,
                :direccionEmpresa, :telefonoEmpresa, :ciudadEmpresa,
                :correoEmpresa, :nombreContacto
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

        $mensaje = "Usuario creado correctamente.";
    }

    if (isset($_POST["actualizar"])) {
        $idUsuario = (int)$_POST["idUsuario"];
        $usuario = trim($_POST["usuario"]);
        $correo = trim($_POST["correo"]);
        $tipo = $_POST["tipo"];
        $estado = $_POST["estado"];
        $contrasena = $_POST["contrasena"];

        if ($tipo == 2) {
            $categoriaEmpresa = 1;
            $nombreEmpresa = trim($_POST["nombreEmpresa"]);
            $nitEmpresa = trim($_POST["nitEmpresa"]);
            $direccionEmpresa = trim($_POST["direccionEmpresa"]);
            $telefonoEmpresa = trim($_POST["telefonoEmpresa"]);
            $ciudadEmpresa = trim($_POST["ciudadEmpresa"]);
            $correoEmpresa = trim($_POST["correoEmpresa"]);
            $nombreContacto = trim($_POST["nombreContacto"]);
        } else {
            $categoriaEmpresa = null;
            $nombreEmpresa = null;
            $nitEmpresa = null;
            $direccionEmpresa = null;
            $telefonoEmpresa = null;
            $ciudadEmpresa = null;
            $correoEmpresa = null;
            $nombreContacto = null;
        }

        if (!empty($contrasena)) {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);

            $sql = "
                UPDATE dbo.Usuarios
                SET 
                    NombreUsuario = :usuario,
                    Correo = :correo,
                    PasswordHash = :hash,
                    IdTipoUsuario = :tipo,
                    IdCategoriaEmpresa = :categoriaEmpresa,
                    NombreEmpresa = :nombreEmpresa,
                    NitEmpresa = :nitEmpresa,
                    DireccionEmpresa = :direccionEmpresa,
                    TelefonoEmpresa = :telefonoEmpresa,
                    CiudadEmpresa = :ciudadEmpresa,
                    CorreoEmpresa = :correoEmpresa,
                    NombreContacto = :nombreContacto,
                    Estado = :estado
                WHERE IdUsuario = :idUsuario
                  AND (
                    IdTipoUsuario = 3
                    OR (IdTipoUsuario = 2 AND IdCategoriaEmpresa = 1)
                  )
            ";

            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(":hash", $hash);
        } else {
            $sql = "
                UPDATE dbo.Usuarios
                SET 
                    NombreUsuario = :usuario,
                    Correo = :correo,
                    IdTipoUsuario = :tipo,
                    IdCategoriaEmpresa = :categoriaEmpresa,
                    NombreEmpresa = :nombreEmpresa,
                    NitEmpresa = :nitEmpresa,
                    DireccionEmpresa = :direccionEmpresa,
                    TelefonoEmpresa = :telefonoEmpresa,
                    CiudadEmpresa = :ciudadEmpresa,
                    CorreoEmpresa = :correoEmpresa,
                    NombreContacto = :nombreContacto,
                    Estado = :estado
                WHERE IdUsuario = :idUsuario
                  AND (
                    IdTipoUsuario = 3
                    OR (IdTipoUsuario = 2 AND IdCategoriaEmpresa = 1)
                  )
            ";

            $stmt = $conexion->prepare($sql);
        }

        $stmt->bindParam(":usuario", $usuario);
        $stmt->bindParam(":correo", $correo);
        $stmt->bindParam(":tipo", $tipo);
        $stmt->bindParam(":categoriaEmpresa", $categoriaEmpresa);
        $stmt->bindParam(":nombreEmpresa", $nombreEmpresa);
        $stmt->bindParam(":nitEmpresa", $nitEmpresa);
        $stmt->bindParam(":direccionEmpresa", $direccionEmpresa);
        $stmt->bindParam(":telefonoEmpresa", $telefonoEmpresa);
        $stmt->bindParam(":ciudadEmpresa", $ciudadEmpresa);
        $stmt->bindParam(":correoEmpresa", $correoEmpresa);
        $stmt->bindParam(":nombreContacto", $nombreContacto);
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":idUsuario", $idUsuario);
        $stmt->execute();

        $mensaje = "Usuario actualizado correctamente.";
        $idUsuarioEditar = $idUsuario;
    }

    if (isset($_GET["eliminar"])) {
        $idEliminar = (int)$_GET["eliminar"];

        $stmt = $conexion->prepare("
            DELETE FROM dbo.Usuarios
            WHERE IdUsuario = :id
              AND (
                IdTipoUsuario = 3
                OR (IdTipoUsuario = 2 AND IdCategoriaEmpresa = 1)
              )
        ");

        $stmt->execute([":id" => $idEliminar]);
        $mensaje = "Usuario eliminado correctamente.";
    }

    if (isset($_POST["crearMunicipio"])) {
        $nombreMunicipio = trim($_POST["nombreMunicipio"]);
        $departamento = trim($_POST["departamento"]);
        $estadoMunicipio = $_POST["estadoMunicipio"];

        $stmt = $conexion->prepare("
            INSERT INTO dbo.Municipios (
                NombreMunicipio,
                Departamento,
                Estado
            )
            VALUES (
                :nombre,
                :departamento,
                :estado
            )
        ");

        $stmt->execute([
            ":nombre" => $nombreMunicipio,
            ":departamento" => $departamento,
            ":estado" => $estadoMunicipio
        ]);

        $mensaje = "Municipio creado correctamente.";
    }

    if (isset($_POST["actualizarMunicipio"])) {
        $idMunicipio = (int)$_POST["idMunicipio"];
        $nombreMunicipio = trim($_POST["nombreMunicipio"]);
        $departamento = trim($_POST["departamento"]);
        $estadoMunicipio = $_POST["estadoMunicipio"];

        $stmt = $conexion->prepare("
            UPDATE dbo.Municipios
            SET 
                NombreMunicipio = :nombre,
                Departamento = :departamento,
                Estado = :estado
            WHERE IdMunicipio = :id
        ");

        $stmt->execute([
            ":nombre" => $nombreMunicipio,
            ":departamento" => $departamento,
            ":estado" => $estadoMunicipio,
            ":id" => $idMunicipio
        ]);

        $mensaje = "Municipio actualizado correctamente.";
        $idMunicipioEditar = $idMunicipio;
    }

    if (isset($_GET["eliminarMunicipio"])) {
        $idMunicipio = (int)$_GET["eliminarMunicipio"];

        $stmt = $conexion->prepare("
            DELETE FROM dbo.Municipios
            WHERE IdMunicipio = :id
        ");

        $stmt->execute([":id" => $idMunicipio]);
        $mensaje = "Municipio eliminado correctamente.";
    }

} catch (PDOException $e) {
    $error = "Error en base de datos: " . $e->getMessage();
}

$stmtAdmin = $conexion->prepare("
    SELECT 
        U.IdUsuario,
        U.NombreUsuario,
        U.Correo,
        T.NombreTipo,
        U.Estado
    FROM dbo.Usuarios U
    INNER JOIN dbo.TiposUsuario T
        ON U.IdTipoUsuario = T.IdTipoUsuario
    WHERE U.IdUsuario = :id
");

$stmtAdmin->execute([":id" => $_SESSION["idUsuario"]]);
$admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

$filtroTipo = $_GET["tipo"] ?? "";
$busqueda = trim($_GET["buscar"] ?? "");

$sqlUsuarios = "
    SELECT 
        U.IdUsuario,
        U.NombreUsuario,
        U.Correo,
        U.NombreEmpresa,
        U.NitEmpresa,
        U.CiudadEmpresa,
        U.NombreContacto,
        U.Estado,
        U.FechaRegistro,
        T.NombreTipo,
        CE.NombreCategoria
    FROM dbo.Usuarios U
    INNER JOIN dbo.TiposUsuario T
        ON U.IdTipoUsuario = T.IdTipoUsuario
    LEFT JOIN dbo.CategoriasEmpresa CE
        ON U.IdCategoriaEmpresa = CE.IdCategoriaEmpresa
    WHERE 
        (
            U.IdTipoUsuario = 3
            OR (U.IdTipoUsuario = 2 AND U.IdCategoriaEmpresa = 1)
        )
";

$params = [];

if ($filtroTipo != "") {
    $sqlUsuarios .= " AND U.IdTipoUsuario = :filtroTipo ";
    $params[":filtroTipo"] = $filtroTipo;
}

if ($busqueda != "") {
    $sqlUsuarios .= " AND (U.NombreUsuario LIKE :busqueda OR U.Correo LIKE :busqueda) ";
    $params[":busqueda"] = "%" . $busqueda . "%";
}

$sqlUsuarios .= " ORDER BY U.IdUsuario DESC ";

$stmtUsuarios = $conexion->prepare($sqlUsuarios);
$stmtUsuarios->execute($params);
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

if ($idUsuarioEditar > 0) {
    $stmt = $conexion->prepare("
        SELECT *
        FROM dbo.Usuarios
        WHERE IdUsuario = :id
          AND (
            IdTipoUsuario = 3
            OR (IdTipoUsuario = 2 AND IdCategoriaEmpresa = 1)
          )
    ");

    $stmt->execute([":id" => $idUsuarioEditar]);
    $usuarioEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

$municipios = $conexion->query("
    SELECT *
    FROM dbo.Municipios
    ORDER BY NombreMunicipio
")->fetchAll(PDO::FETCH_ASSOC);

if ($idMunicipioEditar > 0) {
    $stmt = $conexion->prepare("
        SELECT *
        FROM dbo.Municipios
        WHERE IdMunicipio = :id
    ");

    $stmt->execute([":id" => $idMunicipioEditar]);
    $municipioEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrador</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top left, #0b3a66 0%, transparent 35%),
                linear-gradient(135deg, #05070d, #07111f 55%, #02040a);
            color: #e8f1ff;
            min-height: 100vh;
        }

        .topbar {
            width: 100%;
            padding: 16px 5%;
            background: rgba(3, 10, 20, 0.95);
            border-bottom: 1px solid rgba(88, 166, 255, 0.28);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.35);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(12px);
        }

        .topbar-user {
            color: #d7e9ff;
            font-size: 15px;
        }

        .topbar-user strong {
            color: #58a6ff;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-actions form {
            margin: 0;
        }

        .container {
            width: 95%;
            max-width: 1400px;
            margin: auto;
            padding: 30px 0;
        }

        .header {
            background: rgba(7, 17, 31, 0.9);
            border: 1px solid rgba(42, 130, 255, 0.3);
            border-radius: 22px;
            padding: 28px;
            margin-bottom: 25px;
            box-shadow: 0 0 35px rgba(0, 94, 255, 0.18);
        }

        h1, h2, h3 {
            margin-top: 0;
            color: #ffffff;
        }

        h1 {
            font-size: 36px;
            color: #58a6ff;
        }

        h2 {
            border-left: 5px solid #1f8bff;
            padding-left: 12px;
            margin-top: 35px;
        }

        .card {
            background: rgba(8, 18, 34, 0.92);
            border: 1px solid rgba(80, 150, 255, 0.22);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 25px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.35);
        }

        .admin-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
        }

        .info-box {
            background: #0b1628;
            border: 1px solid #173d66;
            border-radius: 14px;
            padding: 14px;
        }

        .info-box strong {
            color: #58a6ff;
            display: block;
            margin-bottom: 5px;
        }

        label {
            color: #9ecbff;
            font-weight: bold;
            font-size: 14px;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 14px;
            margin-top: 6px;
            border-radius: 12px;
            border: 1px solid #234b77;
            background: #08111f;
            color: #ffffff;
            outline: none;
            transition: 0.2s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #1f8bff;
            box-shadow: 0 0 0 3px rgba(31, 139, 255, 0.18);
        }

        button, .btn {
            border: none;
            border-radius: 12px;
            padding: 11px 18px;
            background: linear-gradient(135deg, #006eff, #003d99);
            color: white;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: 0.2s;
        }

        button:hover, .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 110, 255, 0.35);
        }

        .btn-secondary {
            background: #1b2638;
            border: 1px solid #365b86;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff3b3b, #9b111e);
        }

        .btn-success {
            background: linear-gradient(135deg, #00b894, #006b5a);
        }

        .message {
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .message.success {
            background: rgba(0, 184, 148, 0.15);
            border: 1px solid #00b894;
            color: #4dffd8;
        }

        .message.error {
            background: rgba(255, 59, 59, 0.15);
            border: 1px solid #ff3b3b;
            color: #ff9b9b;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
        }

        .empresa-box {
            border: 1px solid rgba(88, 166, 255, 0.35);
            background: rgba(6, 15, 29, 0.8);
            border-radius: 18px;
            padding: 18px;
            margin-top: 18px;
        }

        .filters {
            display: grid;
            grid-template-columns: 220px 1fr auto auto;
            gap: 14px;
            align-items: end;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
            border-radius: 18px;
            border: 1px solid rgba(88, 166, 255, 0.25);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
            background: #07111f;
        }

        th {
            background: #0b3a66;
            color: #ffffff;
            padding: 14px;
            text-align: left;
            white-space: nowrap;
        }

        td {
            padding: 13px;
            border-bottom: 1px solid rgba(88, 166, 255, 0.15);
            color: #d7e9ff;
        }

        tr:hover td {
            background: rgba(31, 139, 255, 0.08);
        }

        a {
            color: #58a6ff;
            font-weight: bold;
            text-decoration: none;
        }

        a:hover {
            color: #9ecbff;
            text-decoration: underline;
        }

        .acciones {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: bold;
            display: inline-block;
        }

        .activo {
            background: rgba(0, 184, 148, 0.18);
            color: #4dffd8;
            border: 1px solid #00b894;
        }

        .inactivo {
            background: rgba(255, 59, 59, 0.18);
            color: #ff9b9b;
            border: 1px solid #ff3b3b;
        }

        small {
            color: #8aa9c9;
        }

        @media (max-width: 800px) {
            .filters {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .topbar-actions {
                width: 100%;
                flex-wrap: wrap;
            }

            .topbar-actions .btn,
            .topbar-actions button {
                width: 100%;
                text-align: center;
            }

            h1 {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

<div class="topbar">
    <div class="topbar-user">
        Bienvenido, <strong><?php echo limpiar($admin["NombreUsuario"]); ?></strong>
    </div>

    <div class="topbar-actions">
        <a href="index.php" class="btn btn-secondary">Volver al Inicio</a>

        <form action="CerrarSesion.php" method="post">
            <button type="submit" class="btn-danger">Cerrar sesión</button>
        </form>
    </div>
</div>

<div class="container">

    <div class="header">
        <h1>Panel Administrador</h1>
        <p>Gestión de usuarios, empresas informativas y municipios.</p>
    </div>

    <?php if ($mensaje != ""): ?>
        <div class="message success"><?php echo limpiar($mensaje); ?></div>
    <?php endif; ?>

    <?php if ($error != ""): ?>
        <div class="message error"><?php echo limpiar($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <h3>Información del administrador</h3>

        <div class="admin-info">
            <div class="info-box">
                <strong>ID</strong>
                <?php echo limpiar($admin["IdUsuario"]); ?>
            </div>

            <div class="info-box">
                <strong>Usuario</strong>
                <?php echo limpiar($admin["NombreUsuario"]); ?>
            </div>

            <div class="info-box">
                <strong>Correo</strong>
                <?php echo limpiar($admin["Correo"]); ?>
            </div>

            <div class="info-box">
                <strong>Tipo</strong>
                <?php echo limpiar($admin["NombreTipo"]); ?>
            </div>

            <div class="info-box">
                <strong>Estado</strong>
                <span class="badge <?php echo $admin["Estado"] == 1 ? "activo" : "inactivo"; ?>">
                    <?php echo $admin["Estado"] == 1 ? "Activo" : "Inactivo"; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="card">
        <h2><?php echo $usuarioEditar ? "Editar usuario" : "Crear usuario"; ?></h2>

        <form method="post" action="Admin.php">
            <?php if ($usuarioEditar): ?>
                <input type="hidden" name="idUsuario" value="<?php echo limpiar($usuarioEditar["IdUsuario"]); ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div>
                    <label>Usuario:</label>
                    <input type="text" name="usuario" required
                           value="<?php echo $usuarioEditar ? limpiar($usuarioEditar["NombreUsuario"]) : ""; ?>">
                </div>

                <div>
                    <label>Correo:</label>
                    <input type="email" name="correo" required
                           value="<?php echo $usuarioEditar ? limpiar($usuarioEditar["Correo"]) : ""; ?>">
                </div>

                <div>
                    <label>Contraseña:</label>
                    <input type="password" name="contrasena" id="contrasena" <?php echo $usuarioEditar ? "" : "required"; ?>>
                    <?php if ($usuarioEditar): ?>
                        <small>Déjala vacía si no deseas cambiarla.</small>
                    <?php endif; ?>
                </div>

                <div style="align-self:end;">
                    <button type="button" class="btn-secondary" onclick="togglePassword()">Ver contraseña</button>
                </div>

                <div>
                    <label>Tipo:</label>
                    <select name="tipo" id="tipoUsuario" onchange="mostrarEmpresa()" required>
                        <option value="3" <?php echo ($usuarioEditar && $usuarioEditar["IdTipoUsuario"] == 3) ? "selected" : ""; ?>>
                            Usuario común
                        </option>
                        <option value="2" <?php echo ($usuarioEditar && $usuarioEditar["IdTipoUsuario"] == 2) ? "selected" : ""; ?>>
                            Empresa informativa
                        </option>
                    </select>
                </div>

                <?php if ($usuarioEditar): ?>
                    <div>
                        <label>Estado:</label>
                        <select name="estado">
                            <option value="1" <?php echo $usuarioEditar["Estado"] == 1 ? "selected" : ""; ?>>Activo</option>
                            <option value="0" <?php echo $usuarioEditar["Estado"] == 0 ? "selected" : ""; ?>>Inactivo</option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <div id="datosEmpresa" class="empresa-box" style="display:none;">
                <h3>Datos de empresa informativa</h3>

                <div class="form-grid">
                    <div>
                        <label>Nombre empresa:</label>
                        <input type="text" name="nombreEmpresa"
                               value="<?php echo $usuarioEditar ? limpiar($usuarioEditar["NombreEmpresa"]) : ""; ?>">
                    </div>

                    <div>
                        <label>NIT:</label>
                        <input type="text" name="nitEmpresa"
                               value="<?php echo $usuarioEditar ? limpiar($usuarioEditar["NitEmpresa"]) : ""; ?>">
                    </div>

                    <div>
                        <label>Dirección:</label>
                        <input type="text" name="direccionEmpresa"
                               value="<?php echo $usuarioEditar ? limpiar($usuarioEditar["DireccionEmpresa"]) : ""; ?>">
                    </div>

                    <div>
                        <label>Teléfono:</label>
                        <input type="text" name="telefonoEmpresa"
                               value="<?php echo $usuarioEditar ? limpiar($usuarioEditar["TelefonoEmpresa"]) : ""; ?>">
                    </div>

                    <div>
                        <label>Ciudad:</label>
                        <input type="text" name="ciudadEmpresa"
                               value="<?php echo $usuarioEditar ? limpiar($usuarioEditar["CiudadEmpresa"]) : ""; ?>">
                    </div>

                    <div>
                        <label>Correo empresa:</label>
                        <input type="email" name="correoEmpresa"
                               value="<?php echo $usuarioEditar ? limpiar($usuarioEditar["CorreoEmpresa"]) : ""; ?>">
                    </div>

                    <div>
                        <label>Nombre contacto:</label>
                        <input type="text" name="nombreContacto"
                               value="<?php echo $usuarioEditar ? limpiar($usuarioEditar["NombreContacto"]) : ""; ?>">
                    </div>
                </div>
            </div>

            <br>

            <?php if ($usuarioEditar): ?>
                <button type="submit" name="actualizar" class="btn-success">Actualizar</button>
                <button type="button" class="btn-secondary" onclick="window.location.href='Admin.php'">Cancelar</button>
            <?php else: ?>
                <button type="submit" name="crear" class="btn-success">Crear usuario</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h2>Usuarios registrados</h2>

        <form method="get" action="Admin.php" class="filters">
            <div>
                <label>Filtrar por tipo:</label>
                <select name="tipo">
                    <option value="">Todos</option>
                    <option value="3" <?php echo $filtroTipo == "3" ? "selected" : ""; ?>>Usuario común</option>
                    <option value="2" <?php echo $filtroTipo == "2" ? "selected" : ""; ?>>Empresa informativa</option>
                </select>
            </div>

            <div>
                <label>Buscar:</label>
                <input type="text" name="buscar" placeholder="Usuario o correo"
                       value="<?php echo limpiar($busqueda); ?>">
            </div>

            <button type="submit">Buscar</button>
            <button type="button" class="btn-secondary" onclick="window.location.href='Admin.php'">Limpiar</button>
        </form>

        <br>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Tipo</th>
                    <th>Empresa</th>
                    <th>NIT</th>
                    <th>Ciudad</th>
                    <th>Contacto</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>

                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?php echo limpiar($u["IdUsuario"]); ?></td>
                        <td><?php echo limpiar($u["NombreUsuario"]); ?></td>
                        <td><?php echo limpiar($u["Correo"]); ?></td>
                        <td><?php echo limpiar($u["NombreTipo"]); ?></td>
                        <td><?php echo limpiar($u["NombreEmpresa"] ?? "-"); ?></td>
                        <td><?php echo limpiar($u["NitEmpresa"] ?? "-"); ?></td>
                        <td><?php echo limpiar($u["CiudadEmpresa"] ?? "-"); ?></td>
                        <td><?php echo limpiar($u["NombreContacto"] ?? "-"); ?></td>
                        <td>
                            <span class="badge <?php echo $u["Estado"] == 1 ? "activo" : "inactivo"; ?>">
                                <?php echo $u["Estado"] == 1 ? "Activo" : "Inactivo"; ?>
                            </span>
                        </td>
                        <td>
                            <div class="acciones">
                                <a href="Admin.php?editar=<?php echo $u["IdUsuario"]; ?>">Editar</a>
                                <a href="Admin.php?eliminar=<?php echo $u["IdUsuario"]; ?>"
                                   onclick="return confirm('¿Seguro que deseas eliminar este usuario?')">
                                    Eliminar
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <div class="card">
        <h2><?php echo $municipioEditar ? "Editar municipio" : "Crear municipio"; ?></h2>

        <form method="post" action="Admin.php">
            <?php if ($municipioEditar): ?>
                <input type="hidden" name="idMunicipio" value="<?php echo limpiar($municipioEditar["IdMunicipio"]); ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div>
                    <label>Municipio:</label>
                    <input type="text" name="nombreMunicipio" required
                           value="<?php echo $municipioEditar ? limpiar($municipioEditar["NombreMunicipio"]) : ""; ?>">
                </div>

                <div>
                    <label>Departamento:</label>
                    <input type="text" name="departamento" required
                           value="<?php echo $municipioEditar ? limpiar($municipioEditar["Departamento"]) : ""; ?>">
                </div>

                <div>
                    <label>Estado:</label>
                    <select name="estadoMunicipio">
                        <option value="1" <?php echo (!$municipioEditar || $municipioEditar["Estado"] == 1) ? "selected" : ""; ?>>Activo</option>
                        <option value="0" <?php echo ($municipioEditar && $municipioEditar["Estado"] == 0) ? "selected" : ""; ?>>Inactivo</option>
                    </select>
                </div>
            </div>

            <br>

            <?php if ($municipioEditar): ?>
                <button type="submit" name="actualizarMunicipio" class="btn-success">Actualizar municipio</button>
                <button type="button" class="btn-secondary" onclick="window.location.href='Admin.php'">Cancelar</button>
            <?php else: ?>
                <button type="submit" name="crearMunicipio" class="btn-success">Crear municipio</button>
            <?php endif; ?>
        </form>

        <br>

        <h3>Municipios registrados</h3>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Municipio</th>
                    <th>Departamento</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>

                <?php foreach ($municipios as $m): ?>
                    <tr>
                        <td><?php echo limpiar($m["IdMunicipio"]); ?></td>
                        <td><?php echo limpiar($m["NombreMunicipio"]); ?></td>
                        <td><?php echo limpiar($m["Departamento"]); ?></td>
                        <td>
                            <span class="badge <?php echo $m["Estado"] == 1 ? "activo" : "inactivo"; ?>">
                                <?php echo $m["Estado"] == 1 ? "Activo" : "Inactivo"; ?>
                            </span>
                        </td>
                        <td>
                            <div class="acciones">
                                <a href="Admin.php?municipio=<?php echo $m["IdMunicipio"]; ?>">Editar</a>
                                <a href="Admin.php?eliminarMunicipio=<?php echo $m["IdMunicipio"]; ?>"
                                   onclick="return confirm('¿Eliminar municipio?')">
                                    Eliminar
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

</div>

<script>
function togglePassword() {
    var input = document.getElementById("contrasena");
    input.type = input.type === "password" ? "text" : "password";
}

function mostrarEmpresa() {
    var tipo = document.getElementById("tipoUsuario").value;
    var datos = document.getElementById("datosEmpresa");

    datos.style.display = tipo === "2" ? "block" : "none";
}

mostrarEmpresa();
</script>

</body>
</html>