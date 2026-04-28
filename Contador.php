<?php
session_start();
require_once "conexion.php";

date_default_timezone_set("America/Bogota");

if (!isset($_SESSION["idUsuario"]) || $_SESSION["tipo"] != "Empresa") {
    header("Location: IniciarSesion.php");
    exit();
}

if (($_SESSION["categoriaEmpresa"] ?? "") != "Contador") {
    header("Location: Empresa.php");
    exit();
}

$mensaje = "";
$error = "";
$resultado = "";
$proximaSalida = null;
$ultimosRegistros = [];
$totalGeneral = 0;

$idUsuarioContador = (int)$_SESSION["idUsuario"];

function limpiar($dato) {
    return htmlspecialchars(trim((string)$dato), ENT_QUOTES, "UTF-8");
}

function horaASegundos($hora) {
    $partes = explode(":", substr((string)$hora, 0, 8));
    return ((int)$partes[0] * 3600) + ((int)$partes[1] * 60) + (int)($partes[2] ?? 0);
}

function diferenciaTexto($diferenciaSegundos) {
    $abs = abs($diferenciaSegundos);

    $horas = floor($abs / 3600);
    $minutos = floor(($abs % 3600) / 60);
    $segundos = $abs % 60;

    $partes = [];

    if ($horas > 0) {
        $partes[] = $horas . " hora" . ($horas == 1 ? "" : "s");
    }

    if ($minutos > 0) {
        $partes[] = $minutos . " minuto" . ($minutos == 1 ? "" : "s");
    }

    if ($segundos > 0 || empty($partes)) {
        $partes[] = $segundos . " segundo" . ($segundos == 1 ? "" : "s");
    }

    $textoTiempo = implode(", ", $partes);

    if ($diferenciaSegundos < 0) {
        return "Pasó " . $textoTiempo . " antes de la hora programada.";
    } elseif ($diferenciaSegundos > 0) {
        return "Pasó " . $textoTiempo . " después de la hora programada.";
    } else {
        return "Pasó exactamente a la hora programada.";
    }
}

try {
    $stmt = $conexion->prepare("
        SELECT
            CE.IdContador,
            CE.IdEmpresa,
            CE.IdPuntoControl,
            CE.NombreContador,
            CE.CedulaContador,
            CE.CodigoAcceso,
            CE.Estado,
            U.NombreUsuario,
            U.Correo,
            PC.NombrePunto,
            PC.IdRuta,
            R.NombreRuta
        FROM dbo.ContadoresEmpresa CE
        INNER JOIN dbo.Usuarios U
            ON CE.IdUsuarioContador = U.IdUsuario
        LEFT JOIN dbo.PuntosControl PC
            ON CE.IdPuntoControl = PC.IdPuntoControl
        LEFT JOIN dbo.Rutas R
            ON PC.IdRuta = R.IdRuta
        WHERE CE.IdUsuarioContador = :idUsuarioContador
          AND CE.Estado = 1
          AND U.Estado = 1
    ");

    $stmt->execute([
        ":idUsuarioContador" => $idUsuarioContador
    ]);

    $contador = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contador) {
        throw new Exception("No se encontró información activa para este contador.");
    }

    if (empty($contador["IdPuntoControl"])) {
        throw new Exception("Este contador no tiene un punto de control asignado.");
    }

    if (empty($contador["IdRuta"])) {
        throw new Exception("El punto de control asignado no tiene una ruta asociada.");
    }

    $idContador = (int)$contador["IdContador"];
    $idEmpresa = (int)$contador["IdEmpresa"];
    $idPuntoControl = (int)$contador["IdPuntoControl"];
    $idRuta = (int)$contador["IdRuta"];

    function obtenerSiguienteSalida($conexion, $idRuta, $idContador) {
        $stmt = $conexion->prepare("
            SELECT TOP 1
                RS.IdSalida,
                RS.IdRuta,
                RS.HoraSalida,
                RS.LugarSalida,
                RS.OrdenSalida
            FROM dbo.RutaSalidas RS
            WHERE RS.IdRuta = :idRuta
              AND RS.IdSalida NOT IN (
                    SELECT RC.IdSalida
                    FROM dbo.RegistrosContador RC
                    WHERE RC.IdContador = :idContador
                      AND RC.FechaRegistro = CAST(GETDATE() AS DATE)
              )
            ORDER BY RS.OrdenSalida ASC
        ");

        $stmt->execute([
            ":idRuta" => $idRuta,
            ":idContador" => $idContador
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $proximaSalida = obtenerSiguienteSalida($conexion, $idRuta, $idContador);

    if (isset($_POST["registrarTiempo"])) {
        if (!$proximaSalida) {
            $resultado = "Ya no pasan más busetas por hoy.";
        } else {
            $fechaHoraActual = date("Y-m-d H:i:s");
            $fechaActual = date("Y-m-d");
            $horaActualTexto = date("H:i:s");

            $horaProgramada = substr((string)$proximaSalida["HoraSalida"], 0, 8);

            $segundosActuales = horaASegundos($horaActualTexto);
            $segundosProgramados = horaASegundos($horaProgramada);

            $diferenciaSegundos = $segundosActuales - $segundosProgramados;

            $stmt = $conexion->prepare("
                INSERT INTO dbo.RegistrosContador (
                    IdContador,
                    IdEmpresa,
                    IdPuntoControl,
                    IdRuta,
                    IdSalida,
                    HoraProgramada,
                    FechaRegistro,
                    FechaHoraRegistro,
                    DiferenciaSegundos
                )
                VALUES (
                    :idContador,
                    :idEmpresa,
                    :idPuntoControl,
                    :idRuta,
                    :idSalida,
                    :horaProgramada,
                    :fechaRegistro,
                    :fechaHoraRegistro,
                    :diferenciaSegundos
                )
            ");

            $stmt->execute([
                ":idContador" => $idContador,
                ":idEmpresa" => $idEmpresa,
                ":idPuntoControl" => $idPuntoControl,
                ":idRuta" => $idRuta,
                ":idSalida" => $proximaSalida["IdSalida"],
                ":horaProgramada" => $horaProgramada,
                ":fechaRegistro" => $fechaActual,
                ":fechaHoraRegistro" => $fechaHoraActual,
                ":diferenciaSegundos" => $diferenciaSegundos
            ]);

            $mensaje = "Tiempo registrado correctamente a las " . $horaActualTexto . ".";

            $resultado =
                "Ruta: " . $contador["NombreRuta"] . ". " .
                "Salida programada: " . substr($horaProgramada, 0, 5) . ". " .
                "Hora registrada: " . $horaActualTexto . ". " .
                diferenciaTexto($diferenciaSegundos);

            $proximaSalida = obtenerSiguienteSalida($conexion, $idRuta, $idContador);
        }
    }

    $stmt = $conexion->prepare("
        SELECT TOP 10
            RC.FechaRegistro,
            RC.FechaHoraRegistro,
            RC.HoraProgramada,
            RC.DiferenciaSegundos,
            RS.LugarSalida,
            RS.OrdenSalida,
            R.NombreRuta
        FROM dbo.RegistrosContador RC
        INNER JOIN dbo.RutaSalidas RS
            ON RC.IdSalida = RS.IdSalida
        INNER JOIN dbo.Rutas R
            ON RC.IdRuta = R.IdRuta
        WHERE RC.IdContador = :idContador
        ORDER BY RC.FechaHoraRegistro DESC
    ");

    $stmt->execute([
        ":idContador" => $idContador
    ]);

    $ultimosRegistros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conexion->prepare("
        SELECT COUNT(*)
        FROM dbo.RegistrosContador
        WHERE IdContador = :idContador
          AND FechaRegistro = CAST(GETDATE() AS DATE)
    ");

    $stmt->execute([
        ":idContador" => $idContador
    ]);

    $totalGeneral = (int)$stmt->fetchColumn();

} catch (Throwable $e) {
    $error = $e->getMessage();
    $contador = null;
    $proximaSalida = null;
    $ultimosRegistros = [];
    $totalGeneral = 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Contador</title>

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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar strong {
            color: #58a6ff;
        }

        .container {
            width: 92%;
            max-width: 950px;
            margin: auto;
            padding: 40px 0;
        }

        .card {
            background: rgba(8, 18, 34, 0.92);
            border: 1px solid rgba(80, 150, 255, 0.22);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.35);
            text-align: center;
        }

        h1 {
            color: #58a6ff;
            font-size: 38px;
            margin-top: 0;
        }

        h2, h3 {
            color: #ffffff;
        }

        .boton-tiempo {
            width: 100%;
            min-height: 220px;
            border: none;
            border-radius: 28px;
            background: linear-gradient(135deg, #00b894, #006b5a);
            color: white;
            font-size: 34px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 15px 35px rgba(0, 184, 148, 0.35);
            transition: 0.2s;
        }

        .boton-tiempo:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 45px rgba(0, 184, 148, 0.45);
        }

        .boton-tiempo:disabled {
            background: #444;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .message {
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
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

        .resultado {
            font-size: 24px;
            line-height: 1.5;
            color: #ffffff;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 14px;
            margin-top: 20px;
        }

        .info-box {
            background: #0b1628;
            border: 1px solid #173d66;
            border-radius: 14px;
            padding: 14px;
            text-align: left;
        }

        .info-box strong {
            color: #58a6ff;
            display: block;
            margin-bottom: 5px;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #07111f;
            margin-top: 15px;
            min-width: 800px;
        }

        th {
            background: #0b3a66;
            color: white;
            padding: 12px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid rgba(88, 166, 255, 0.15);
            color: #d7e9ff;
        }

        .btn {
            border: none;
            border-radius: 12px;
            padding: 11px 18px;
            background: #1b2638;
            border: 1px solid #365b86;
            color: white;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff3b3b, #9b111e);
            border: none;
        }

        form {
            margin: 0;
        }
    </style>
</head>

<body>

<div class="topbar">
    <div>
        Contador:
        <strong>
            <?php echo limpiar($contador["NombreContador"] ?? "No disponible"); ?>
        </strong>
    </div>

    <form action="CerrarSesion.php" method="post">
        <button type="submit" class="btn btn-danger">Cerrar sesión</button>
    </form>
</div>

<div class="container">

    <div class="card">
        <h1>Panel Contador</h1>
        <p>Presiona el botón cuando pase la siguiente buseta programada.</p>

        <?php if ($contador): ?>
            <div class="info-grid">
                <div class="info-box">
                    <strong>Punto de control</strong>
                    <?php echo limpiar($contador["NombrePunto"] ?? "Sin punto asignado"); ?>
                </div>

                <div class="info-box">
                    <strong>Ruta</strong>
                    <?php echo limpiar($contador["NombreRuta"] ?? "Sin ruta"); ?>
                </div>

                <div class="info-box">
                    <strong>Código contador</strong>
                    <?php echo limpiar($contador["CodigoAcceso"]); ?>
                </div>

                <div class="info-box">
                    <strong>Registros de hoy</strong>
                    <?php echo limpiar($totalGeneral); ?>
                </div>

                <div class="info-box">
                    <strong>Siguiente salida</strong>
                    <?php echo $proximaSalida ? limpiar(substr((string)$proximaSalida["HoraSalida"], 0, 5)) : "Ya no hay más"; ?>
                </div>

                <div class="info-box">
                    <strong>Lugar salida</strong>
                    <?php echo $proximaSalida ? limpiar($proximaSalida["LugarSalida"]) : "-"; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($mensaje != ""): ?>
        <div class="message success"><?php echo limpiar($mensaje); ?></div>
    <?php endif; ?>

    <?php if ($error != ""): ?>
        <div class="message error"><?php echo limpiar($error); ?></div>
    <?php endif; ?>

    <?php if ($resultado != ""): ?>
        <div class="card">
            <div class="resultado">
                <?php echo limpiar($resultado); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($contador): ?>
        <div class="card">
            <?php if ($proximaSalida): ?>
                <form method="post" action="Contador.php">
                    <button type="submit" name="registrarTiempo" class="boton-tiempo">
                        TOMAR TIEMPO<br>
                        <span style="font-size:20px;">
                            Salida programada: <?php echo limpiar(substr((string)$proximaSalida["HoraSalida"], 0, 5)); ?>
                        </span>
                    </button>
                </form>
            <?php else: ?>
                <button type="button" class="boton-tiempo" disabled>
                    YA NO PASAN MÁS BUSETAS<br>
                    <span style="font-size:20px;">Todas las salidas de hoy fueron registradas</span>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Últimos registros</h3>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Ruta</th>
                    <th>Salida programada</th>
                    <th>Hora registrada</th>
                    <th>Resultado</th>
                </tr>

                <?php if (empty($ultimosRegistros)): ?>
                    <tr>
                        <td colspan="6">Todavía no hay registros.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ultimosRegistros as $i => $r): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo limpiar(date("Y-m-d", strtotime($r["FechaHoraRegistro"]))); ?></td>
                            <td><?php echo limpiar($r["NombreRuta"]); ?></td>
                            <td><?php echo limpiar(substr((string)$r["HoraProgramada"], 0, 8)); ?></td>
                            <td><?php echo limpiar(date("H:i:s", strtotime($r["FechaHoraRegistro"]))); ?></td>
                            <td><?php echo limpiar(diferenciaTexto((int)$r["DiferenciaSegundos"])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>

</div>

</body>
</html>