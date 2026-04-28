<?php
session_start();
require_once "conexion.php";

$panelUrl = "IniciarSesion.php";
$esUsuarioComun = false;
$idUsuarioSesion = $_SESSION["idUsuario"] ?? null;

if (isset($_SESSION["idUsuario"])) {
    if ($_SESSION["tipo"] == "Administrador") {
        $panelUrl = "Admin.php";
    } elseif ($_SESSION["tipo"] == "Empresa") {
        if (($_SESSION["categoriaEmpresa"] ?? "") == "Contador") {
            $panelUrl = "Contador.php";
        } else {
            $panelUrl = "Empresa.php";
        }
    } elseif ($_SESSION["tipo"] == "Usuario Comun") {
        $panelUrl = "Usuario.php";
        $esUsuarioComun = true;
    }
}

function limpiar($dato) {
    return htmlspecialchars(trim((string)$dato), ENT_QUOTES, "UTF-8");
}

$mensaje = "";
$error = "";
$busqueda = trim($_GET["buscar"] ?? "");

/* =========================
   GUARDAR / QUITAR FAVORITO
========================= */
try {
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accionFavorito"])) {
        if (!$esUsuarioComun) {
            header("Location: IniciarSesion.php");
            exit();
        }

        $idRuta = (int)($_POST["idRuta"] ?? 0);
        $accion = $_POST["accionFavorito"];

        if ($idRuta <= 0) {
            throw new Exception("Ruta inválida.");
        }

        if ($accion == "guardar") {
            $stmt = $conexion->prepare("
                SELECT IdFavorito, Estado
                FROM dbo.RutasFavoritas
                WHERE IdUsuario = :idUsuario
                  AND IdRuta = :idRuta
            ");

            $stmt->execute([
                ":idUsuario" => $idUsuarioSesion,
                ":idRuta" => $idRuta
            ]);

            $favorito = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($favorito) {
                $stmt = $conexion->prepare("
                    UPDATE dbo.RutasFavoritas
                    SET Estado = 1,
                        FechaGuardado = GETDATE()
                    WHERE IdFavorito = :idFavorito
                ");

                $stmt->execute([
                    ":idFavorito" => $favorito["IdFavorito"]
                ]);
            } else {
                $stmt = $conexion->prepare("
                    INSERT INTO dbo.RutasFavoritas (
                        IdUsuario,
                        IdRuta,
                        Estado
                    )
                    VALUES (
                        :idUsuario,
                        :idRuta,
                        1
                    )
                ");

                $stmt->execute([
                    ":idUsuario" => $idUsuarioSesion,
                    ":idRuta" => $idRuta
                ]);
            }

            $mensaje = "Ruta guardada en favoritos.";
        }

        if ($accion == "quitar") {
            $stmt = $conexion->prepare("
                UPDATE dbo.RutasFavoritas
                SET Estado = 0
                WHERE IdUsuario = :idUsuario
                  AND IdRuta = :idRuta
            ");

            $stmt->execute([
                ":idUsuario" => $idUsuarioSesion,
                ":idRuta" => $idRuta
            ]);

            $mensaje = "Ruta quitada de favoritos.";
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

/* =========================
   CONSULTAR RUTAS
========================= */
$sql = "
    SELECT 
        R.IdRuta,
        R.NombreRuta,
        R.PrecioRuta,
        RD.DescripcionRuta,
        U.NombreEmpresa,
        U.NombreUsuario,

        CASE 
            WHEN RF.IdFavorito IS NOT NULL AND RF.Estado = 1 THEN 1
            ELSE 0
        END AS EsFavorita

    FROM dbo.Rutas R
    INNER JOIN dbo.Usuarios U
        ON R.IdEmpresa = U.IdUsuario
    LEFT JOIN dbo.RutaDetalle RD
        ON R.IdRuta = RD.IdRuta
    LEFT JOIN dbo.RutasFavoritas RF
        ON R.IdRuta = RF.IdRuta
       AND RF.IdUsuario = :idUsuarioFavorito

    WHERE R.Estado = 1
      AND U.Estado = 1
";

$params = [
    ":idUsuarioFavorito" => $esUsuarioComun ? $idUsuarioSesion : 0
];

if ($busqueda !== "") {
    $sql .= " AND R.NombreRuta LIKE :busqueda ";
    $params[":busqueda"] = "%" . $busqueda . "%";
}

$sql .= " ORDER BY R.IdRuta DESC ";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rutas as $index => $ruta) {
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
    $rutas[$index]["Paradas"] = $stmtParadas->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>TranBus</title>

<style>
body {
    margin: 0;
    font-family: Arial;
    background: linear-gradient(135deg, #05070d, #07111f);
    color: white;
}

.topbar {
    padding: 15px 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #0a1425;
}

.brand {
    color: #58a6ff;
    font-size: 24px;
    font-weight: bold;
}

.btn {
    padding: 10px 15px;
    border-radius: 8px;
    text-decoration: none;
    color: white;
    margin-left: 5px;
    border: none;
    cursor: pointer;
    font-weight: bold;
}

.btn-primary {
    background: #006eff;
}

.btn-danger {
    background: red;
}

.hero {
    width: 90%;
    max-width: 1100px;
    margin: 60px auto 30px auto;
    padding: 60px 20px;
    text-align: center;
    border-radius: 25px;
    background:
        linear-gradient(rgba(2,8,18,0.7), rgba(2,8,18,0.9)),
        radial-gradient(circle at top left, rgba(0,110,255,0.5), transparent 40%);
    border: 1px solid rgba(88,166,255,0.3);
}

.hero h1 {
    font-size: 60px;
    color: #58a6ff;
    margin-bottom: 10px;
}

.hero p {
    font-size: 18px;
    color: #d7e9ff;
}

.rutas-section {
    width: 90%;
    max-width: 1100px;
    margin: auto;
    padding-bottom: 50px;
}

.section-title {
    margin-bottom: 20px;
    border-left: 4px solid #1f8bff;
    padding-left: 10px;
}

.buscador {
    background: #0f1f3a;
    border: 1px solid rgba(88,166,255,0.25);
    padding: 18px;
    border-radius: 16px;
    margin-bottom: 22px;
    display: flex;
    gap: 10px;
}

.buscador input {
    flex: 1;
    padding: 13px;
    border-radius: 10px;
    border: 1px solid #234b77;
    background: #08111f;
    color: white;
    outline: none;
}

.buscador button,
.buscador a {
    padding: 13px 18px;
    border-radius: 10px;
    border: none;
    background: #006eff;
    color: white;
    text-decoration: none;
    cursor: pointer;
    font-weight: bold;
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

.ruta-card h3 {
    color: #58a6ff;
    margin-bottom: 10px;
    margin-top: 0;
}

.estrella-form {
    margin: 0;
}

.estrella-btn {
    border: none;
    background: transparent;
    color: #ffd43b;
    font-size: 34px;
    cursor: pointer;
    line-height: 1;
}

.estrella-btn:hover {
    transform: scale(1.12);
}

.estrella-login {
    color: #666;
    font-size: 32px;
    text-decoration: none;
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

.paradas-lista {
    list-style: none;
    padding: 0;
    margin-top: 10px;
}

.paradas-lista li {
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

.descripcion {
    margin-top: 12px;
    color: #d7e9ff;
}

.vacio {
    background: #0f1f3a;
    padding: 20px;
    border-radius: 15px;
    color: #9ecbff;
}

.message {
    padding: 14px 18px;
    border-radius: 14px;
    margin-bottom: 18px;
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

@media (max-width: 700px) {
    .topbar {
        flex-direction: column;
        gap: 12px;
    }

    .hero h1 {
        font-size: 42px;
    }

    .buscador {
        flex-direction: column;
    }

    .ruta-header {
        flex-direction: row;
    }
}
</style>
</head>

<body>

<div class="topbar">
    <div class="brand">TranBus</div>

    <div>
        <a href="mapa.php" class="btn btn-primary">Mapa</a>

        <?php if (isset($_SESSION["idUsuario"])): ?>
            <a href="<?php echo limpiar($panelUrl); ?>" class="btn btn-primary">Panel</a>

            <form action="CerrarSesion.php" method="post" style="display:inline;">
                <button class="btn btn-danger">Salir</button>
            </form>
        <?php else: ?>
            <a href="IniciarSesion.php" class="btn btn-primary">Login</a>
        <?php endif; ?>
    </div>
</div>

<div class="hero">
    <h1>TranBus</h1>
    <p>Consulta rutas, precios y paradas de forma fácil.</p>
</div>

<div class="rutas-section">
    <h2 class="section-title">Rutas disponibles</h2>

    <?php if ($mensaje != ""): ?>
        <div class="message success"><?php echo limpiar($mensaje); ?></div>
    <?php endif; ?>

    <?php if ($error != ""): ?>
        <div class="message error"><?php echo limpiar($error); ?></div>
    <?php endif; ?>

    <form method="get" action="index.php" class="buscador">
        <input 
            type="text" 
            name="buscar" 
            placeholder="Buscar ruta por nombre..."
            value="<?php echo limpiar($busqueda); ?>"
        >

        <button type="submit">Buscar</button>

        <?php if ($busqueda !== ""): ?>
            <a href="index.php">Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (count($rutas) > 0): ?>
        <?php foreach ($rutas as $ruta): ?>

            <?php
                $paradas = $ruta["Paradas"];
                $origen = count($paradas) > 0 ? $paradas[0] : null;
                $destino = count($paradas) > 0 ? $paradas[count($paradas) - 1] : null;
                $esFavorita = (int)$ruta["EsFavorita"] === 1;
            ?>

            <div class="ruta-card">

                <div class="ruta-header">
                    <h3><?php echo limpiar($ruta["NombreRuta"]); ?></h3>

                    <?php if ($esUsuarioComun): ?>
                        <form method="post" action="index.php<?php echo $busqueda !== "" ? "?buscar=" . urlencode($busqueda) : ""; ?>" class="estrella-form">
                            <input type="hidden" name="idRuta" value="<?php echo limpiar($ruta["IdRuta"]); ?>">

                            <?php if ($esFavorita): ?>
                                <input type="hidden" name="accionFavorito" value="quitar">
                                <button type="submit" class="estrella-btn" title="Quitar de guardados">★</button>
                            <?php else: ?>
                                <input type="hidden" name="accionFavorito" value="guardar">
                                <button type="submit" class="estrella-btn" title="Guardar ruta">☆</button>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <a href="IniciarSesion.php" class="estrella-login" title="Inicia sesión para guardar rutas">☆</a>
                    <?php endif; ?>
                </div>

                <div class="info-line">
                    <strong>Empresa:</strong>
                    <?php echo limpiar($ruta["NombreEmpresa"] ?: $ruta["NombreUsuario"]); ?>
                </div>

                <div class="info-line">
                    <strong>Inicia en:</strong>
                    <?php if ($origen): ?>
                        <?php echo limpiar($origen["NombreMunicipio"]); ?>
                        (<?php echo limpiar($origen["Departamento"]); ?>)
                    <?php else: ?>
                        No definido
                    <?php endif; ?>
                </div>

                <div class="info-line">
                    <strong>Va hasta:</strong>
                    <?php if ($destino): ?>
                        <?php echo limpiar($destino["NombreMunicipio"]); ?>
                        (<?php echo limpiar($destino["Departamento"]); ?>)
                    <?php else: ?>
                        No definido
                    <?php endif; ?>
                </div>

                <div class="precio">
                    Precio: $<?php echo number_format((float)$ruta["PrecioRuta"], 0, ",", "."); ?>
                </div>

                <div class="descripcion">
                    <strong>Descripción:</strong><br>
                    <?php echo !empty($ruta["DescripcionRuta"]) ? limpiar($ruta["DescripcionRuta"]) : "Sin descripción."; ?>
                </div>

                <div class="info-line">
                    <strong>Paradas por las que pasa:</strong>
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

            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="vacio">
            No hay rutas disponibles.
        </div>
    <?php endif; ?>

</div>

</body>
</html>