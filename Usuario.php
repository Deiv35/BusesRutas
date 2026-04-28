<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["idUsuario"]) || $_SESSION["tipo"] != "Usuario Comun") {
    header("Location: IniciarSesion.php");
    exit();
}

$idUsuario = (int)$_SESSION["idUsuario"];
$mensaje = "";
$error = "";

function limpiar($dato) {
    return htmlspecialchars(trim((string)$dato), ENT_QUOTES, "UTF-8");
}

/* =========================
   QUITAR FAVORITO
========================= */
try {
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["quitarFavorito"])) {
        $idRuta = (int)($_POST["idRuta"] ?? 0);

        if ($idRuta > 0) {
            $stmt = $conexion->prepare("
                UPDATE dbo.RutasFavoritas
                SET Estado = 0
                WHERE IdUsuario = :idUsuario
                  AND IdRuta = :idRuta
            ");

            $stmt->execute([
                ":idUsuario" => $idUsuario,
                ":idRuta" => $idRuta
            ]);

            $mensaje = "Ruta quitada de tus guardados.";
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

/* =========================
   INFORMACIÓN USUARIO
========================= */
$sql = "
    SELECT 
        U.IdUsuario,
        U.NombreUsuario,
        U.Correo,
        T.NombreTipo,
        U.FechaRegistro,
        U.Estado
    FROM dbo.Usuarios U
    INNER JOIN dbo.TiposUsuario T
        ON U.IdTipoUsuario = T.IdTipoUsuario
    WHERE U.IdUsuario = :idUsuario
";

$stmt = $conexion->prepare($sql);
$stmt->execute([":idUsuario" => $idUsuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   RUTAS GUARDADAS
========================= */
$stmt = $conexion->prepare("
    SELECT 
        RF.IdFavorito,
        RF.FechaGuardado,

        R.IdRuta,
        R.NombreRuta,
        R.PrecioRuta,
        R.HoraInicio,
        R.HoraFin,
        R.Estado,

        RD.DescripcionRuta,

        U.NombreEmpresa,
        U.NombreUsuario AS UsuarioEmpresa
    FROM dbo.RutasFavoritas RF
    INNER JOIN dbo.Rutas R
        ON RF.IdRuta = R.IdRuta
    INNER JOIN dbo.Usuarios U
        ON R.IdEmpresa = U.IdUsuario
    LEFT JOIN dbo.RutaDetalle RD
        ON R.IdRuta = RD.IdRuta
    WHERE RF.IdUsuario = :idUsuario
      AND RF.Estado = 1
      AND R.Estado = 1
      AND U.Estado = 1
    ORDER BY RF.FechaGuardado DESC
");

$stmt->execute([":idUsuario" => $idUsuario]);
$rutasGuardadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rutasGuardadas as $index => $ruta) {
    $stmtParadas = $conexion->prepare("
        SELECT 
            RP.OrdenParada,
            RP.NombreParada,
            RP.DireccionParada,
            M.NombreMunicipio,
            M.Departamento
        FROM dbo.RutaParadas RP
        INNER JOIN dbo.Municipios M
            ON RP.IdMunicipio = M.IdMunicipio
        WHERE RP.IdRuta = :idRuta
        ORDER BY RP.OrdenParada
    ");

    $stmtParadas->execute([":idRuta" => $ruta["IdRuta"]]);
    $rutasGuardadas[$index]["Paradas"] = $stmtParadas->fetchAll(PDO::FETCH_ASSOC);

    $stmtSalidas = $conexion->prepare("
        SELECT 
            HoraSalida,
            LugarSalida,
            OrdenSalida
        FROM dbo.RutaSalidas
        WHERE IdRuta = :idRuta
        ORDER BY OrdenSalida
    ");

    $stmtSalidas->execute([":idRuta" => $ruta["IdRuta"]]);
    $rutasGuardadas[$index]["Salidas"] = $stmtSalidas->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Usuario</title>

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
        }

        .brand {
            color: #58a6ff;
            font-size: 24px;
            font-weight: bold;
        }

        .container {
            width: 92%;
            max-width: 1100px;
            margin: auto;
            padding: 35px 0;
        }

        .header {
            background: rgba(7, 17, 31, 0.9);
            border: 1px solid rgba(42, 130, 255, 0.3);
            border-radius: 22px;
            padding: 28px;
            margin-bottom: 25px;
            box-shadow: 0 0 35px rgba(0, 94, 255, 0.18);
        }

        h1 {
            margin-top: 0;
            color: #58a6ff;
            font-size: 36px;
        }

        h2, h3 {
            color: #ffffff;
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

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
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

        .btn {
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

        .btn:hover {
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

        .btn-warning {
            background: linear-gradient(135deg, #f59f00, #b36b00);
        }

        .top-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .top-actions form {
            margin: 0;
        }

        .message {
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .success {
            background: rgba(0, 184, 148, 0.15);
            border: 1px solid #00b894;
            color: #4dffd8;
        }

        .error {
            background: rgba(255, 59, 59, 0.15);
            border: 1px solid #ff3b3b;
            color: #ff9b9b;
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

        .ruta-card {
            background: #0f1f3a;
            padding: 24px;
            margin-bottom: 20px;
            border-radius: 18px;
            border: 1px solid rgba(88,166,255,0.25);
        }

        .ruta-header {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            align-items: flex-start;
        }

        .ruta-header h3 {
            color: #58a6ff;
            margin-top: 0;
        }

        .info-line {
            margin: 8px 0;
            color: #d7e9ff;
        }

        .info-line strong {
            color: #9ecbff;
        }

        .precio {
            display: inline-block;
            color: #00ffa6;
            font-weight: bold;
            background: rgba(0, 255, 166, 0.12);
            border: 1px solid rgba(0, 255, 166, 0.4);
            padding: 8px 12px;
            border-radius: 999px;
            margin: 10px 0;
        }

        .paradas-lista, .salidas-lista {
            list-style: none;
            padding: 0;
            margin-top: 10px;
        }

        .paradas-lista li, .salidas-lista li {
            background: rgba(6, 15, 29, 0.8);
            border: 1px solid rgba(88,166,255,0.18);
            padding: 10px;
            border-radius: 12px;
            margin-bottom: 8px;
            color: #d7e9ff;
        }

        .parada-num {
            color: #58a6ff;
            font-weight: bold;
        }

        .vacio {
            background: #0f1f3a;
            padding: 20px;
            border-radius: 15px;
            color: #9ecbff;
        }

        @media (max-width: 700px) {
            .topbar {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .top-actions {
                width: 100%;
                flex-wrap: wrap;
            }

            .top-actions .btn,
            .top-actions button {
                width: 100%;
                text-align: center;
            }

            .ruta-header {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="topbar">
    <div class="brand">TranBus</div>

    <div class="top-actions">
        <a href="index.php" class="btn btn-secondary">Volver al inicio</a>

        <form action="CerrarSesion.php" method="post">
            <button type="submit" class="btn btn-danger">Cerrar sesión</button>
        </form>
    </div>
</div>

<div class="container">

    <div class="header">
        <h1>Panel de Usuario</h1>
        <p>Consulta tu información y tus rutas guardadas.</p>
    </div>

    <?php if ($mensaje != ""): ?>
        <div class="message success"><?php echo limpiar($mensaje); ?></div>
    <?php endif; ?>

    <?php if ($error != ""): ?>
        <div class="message error"><?php echo limpiar($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <h3>Información del usuario</h3>

        <div class="info-grid">
            <div class="info-box">
                <strong>ID</strong>
                <?php echo limpiar($usuario["IdUsuario"] ?? "-"); ?>
            </div>

            <div class="info-box">
                <strong>Usuario</strong>
                <?php echo limpiar($usuario["NombreUsuario"] ?? "-"); ?>
            </div>

            <div class="info-box">
                <strong>Correo</strong>
                <?php echo limpiar($usuario["Correo"] ?? "-"); ?>
            </div>

            <div class="info-box">
                <strong>Tipo</strong>
                <?php echo limpiar($usuario["NombreTipo"] ?? "-"); ?>
            </div>

            <div class="info-box">
                <strong>Fecha de registro</strong>
                <?php echo limpiar($usuario["FechaRegistro"] ?? "-"); ?>
            </div>

            <div class="info-box">
                <strong>Estado</strong>
                <span class="badge <?php echo ($usuario["Estado"] ?? 0) == 1 ? "activo" : "inactivo"; ?>">
                    <?php echo ($usuario["Estado"] ?? 0) == 1 ? "Activo" : "Inactivo"; ?>
                </span>
            </div>
        </div>
    </div>

    <h2>Mis rutas guardadas</h2>

    <?php if (count($rutasGuardadas) > 0): ?>
        <?php foreach ($rutasGuardadas as $ruta): ?>

            <?php
                $paradas = $ruta["Paradas"];
                $salidas = $ruta["Salidas"];

                $origen = count($paradas) > 0 ? $paradas[0] : null;
                $destino = count($paradas) > 0 ? $paradas[count($paradas) - 1] : null;
            ?>

            <div class="ruta-card">
                <div class="ruta-header">
                    <h3>★ <?php echo limpiar($ruta["NombreRuta"]); ?></h3>

                    <form method="post" action="Usuario.php">
                        <input type="hidden" name="idRuta" value="<?php echo limpiar($ruta["IdRuta"]); ?>">
                        <button type="submit" name="quitarFavorito" class="btn btn-warning">
                            Quitar de guardados
                        </button>
                    </form>
                </div>

                <div class="info-line">
                    <strong>Empresa:</strong>
                    <?php echo limpiar($ruta["NombreEmpresa"] ?: $ruta["UsuarioEmpresa"]); ?>
                </div>

                <div class="info-line">
                    <strong>Origen:</strong>
                    <?php if ($origen): ?>
                        <?php echo limpiar($origen["NombreMunicipio"]); ?>
                        (<?php echo limpiar($origen["Departamento"]); ?>)
                    <?php else: ?>
                        No definido
                    <?php endif; ?>
                </div>

                <div class="info-line">
                    <strong>Destino:</strong>
                    <?php if ($destino): ?>
                        <?php echo limpiar($destino["NombreMunicipio"]); ?>
                        (<?php echo limpiar($destino["Departamento"]); ?>)
                    <?php else: ?>
                        No definido
                    <?php endif; ?>
                </div>

                <div class="info-line">
                    <strong>Horario de operación:</strong>
                    <?php echo limpiar(substr((string)$ruta["HoraInicio"], 0, 5)); ?>
                    -
                    <?php echo limpiar(substr((string)$ruta["HoraFin"], 0, 5)); ?>
                </div>

                <div class="precio">
                    Precio: $<?php echo number_format((float)$ruta["PrecioRuta"], 0, ",", "."); ?>
                </div>

                <div class="info-line">
                    <strong>Descripción:</strong><br>
                    <?php echo !empty($ruta["DescripcionRuta"]) ? limpiar($ruta["DescripcionRuta"]) : "Sin descripción."; ?>
                </div>

                <div class="info-line">
                    <strong>Fecha en que guardaste esta ruta:</strong>
                    <?php echo limpiar($ruta["FechaGuardado"]); ?>
                </div>

                <div class="info-line">
                    <strong>Paradas:</strong>
                </div>

                <?php if (count($paradas) > 0): ?>
                    <ul class="paradas-lista">
                        <?php foreach ($paradas as $parada): ?>
                            <li>
                                <span class="parada-num">
                                    Parada <?php echo limpiar($parada["OrdenParada"]); ?>:
                                </span>

                                <?php echo limpiar($parada["NombreMunicipio"]); ?>
                                (<?php echo limpiar($parada["Departamento"]); ?>)

                                <br>

                                <?php if (!empty($parada["NombreParada"])): ?>
                                    <strong><?php echo limpiar($parada["NombreParada"]); ?></strong> —
                                <?php endif; ?>

                                <?php echo limpiar($parada["DireccionParada"]); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No hay paradas registradas para esta ruta.</p>
                <?php endif; ?>

                <div class="info-line">
                    <strong>Salidas:</strong>
                </div>

                <?php if (count($salidas) > 0): ?>
                    <ul class="salidas-lista">
                        <?php foreach ($salidas as $salida): ?>
                            <li>
                                <strong><?php echo limpiar(substr((string)$salida["HoraSalida"], 0, 5)); ?></strong>
                                —
                                <?php echo limpiar($salida["LugarSalida"]); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No hay salidas registradas para esta ruta.</p>
                <?php endif; ?>
            </div>

        <?php endforeach; ?>
    <?php else: ?>
        <div class="vacio">
            Todavía no tienes rutas guardadas. Ve al inicio y presiona la estrella ☆ en alguna ruta.
        </div>
    <?php endif; ?>

</div>

</body>
</html>