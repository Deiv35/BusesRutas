<?php
session_start();
require_once "conexion.php";

$tipoUsuario = $_SESSION["tipo"] ?? "Visitante";
$categoriaEmpresa = $_SESSION["categoriaEmpresa"] ?? "";
$idUsuario = isset($_SESSION["idUsuario"]) ? (int)$_SESSION["idUsuario"] : null;

$puedeUbicarParadas = false;
$puedeCrearPuntosControl = false;
$esAdmin = false;
$esEmpresaNormal = false;
$esContador = false;

if ($tipoUsuario == "Administrador") {
    $esAdmin = true;
    $puedeUbicarParadas = true;
    $puedeCrearPuntosControl = true;
}

if ($tipoUsuario == "Empresa" && $categoriaEmpresa != "Contador") {
    $esEmpresaNormal = true;
    $puedeUbicarParadas = true;
    $puedeCrearPuntosControl = true;
}

if ($tipoUsuario == "Empresa" && $categoriaEmpresa == "Contador") {
    $esContador = true;
    $puedeCrearPuntosControl = true;
}

function limpiar($dato) {
    return htmlspecialchars(trim((string)$dato), ENT_QUOTES, "UTF-8");
}

function generarCodigoPuntoControl($conexion) {
    do {
        $codigo = str_pad((string)random_int(0, 99999999), 8, "0", STR_PAD_LEFT);

        $stmt = $conexion->prepare("
            SELECT COUNT(*)
            FROM dbo.PuntosControl
            WHERE CodigoAcceso = :codigo
        ");
        $stmt->execute([":codigo" => $codigo]);

        $existe = (int)$stmt->fetchColumn();
    } while ($existe > 0);

    return $codigo;
}

/* =========================
   ACCIONES AJAX
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json; charset=utf-8");

    $data = json_decode(file_get_contents("php://input"), true);
    $accion = $data["accion"] ?? "";

    try {
        if ($accion === "actualizarParada") {
            if (!$puedeUbicarParadas) {
                echo json_encode(["success" => false, "error" => "No tienes permisos."]);
                exit();
            }

            $idRutaParada = (int)($data["idRutaParada"] ?? 0);
            $lat = $data["lat"] ?? null;
            $lng = $data["lng"] ?? null;

            if ($esAdmin) {
                $stmt = $conexion->prepare("
                    UPDATE dbo.RutaParadas
                    SET Lat = :lat, Lng = :lng
                    WHERE IdRutaParada = :idRutaParada
                ");

                $stmt->execute([
                    ":lat" => $lat,
                    ":lng" => $lng,
                    ":idRutaParada" => $idRutaParada
                ]);
            } else {
                $stmt = $conexion->prepare("
                    UPDATE RP
                    SET RP.Lat = :lat,
                        RP.Lng = :lng
                    FROM dbo.RutaParadas RP
                    INNER JOIN dbo.Rutas R
                        ON RP.IdRuta = R.IdRuta
                    WHERE RP.IdRutaParada = :idRutaParada
                      AND R.IdEmpresa = :idEmpresa
                ");

                $stmt->execute([
                    ":lat" => $lat,
                    ":lng" => $lng,
                    ":idRutaParada" => $idRutaParada,
                    ":idEmpresa" => $idUsuario
                ]);
            }

            echo json_encode(["success" => true]);
            exit();
        }

        if ($accion === "crearPuntoControl") {
            if (!$puedeCrearPuntosControl) {
                echo json_encode(["success" => false, "error" => "No tienes permisos."]);
                exit();
            }

            $nombre = trim($data["nombre"] ?? "");
            $descripcion = trim($data["descripcion"] ?? "");
            $lat = $data["lat"] ?? null;
            $lng = $data["lng"] ?? null;
            $idRuta = !empty($data["idRuta"]) ? (int)$data["idRuta"] : null;

            if ($esAdmin) {
                $idEmpresa = !empty($data["idEmpresa"]) ? (int)$data["idEmpresa"] : null;
            } else {
                $idEmpresa = $idUsuario;
            }

            if (!$idEmpresa || $nombre == "" || !$lat || !$lng) {
                echo json_encode(["success" => false, "error" => "Datos incompletos."]);
                exit();
            }

            $codigoAcceso = generarCodigoPuntoControl($conexion);

            $stmt = $conexion->prepare("
            INSERT INTO dbo.PuntosControl (
                IdEmpresa,
                IdRuta,
                NombrePunto,
                Descripcion,
                Lat,
                Lng,
                CodigoAcceso
            )
            VALUES (
                :idEmpresa,
                :idRuta,
                :nombre,
                :descripcion,
                :lat,
                :lng,
                :codigoAcceso
                )
            ");

            $stmt->execute([
                ":idEmpresa" => $idEmpresa,
                ":idRuta" => $idRuta,
                ":nombre" => $nombre,
                ":descripcion" => $descripcion,
                ":lat" => $lat,
                ":lng" => $lng,
                ":codigoAcceso" => $codigoAcceso
            ]);


            echo json_encode(["success" => true]);
            exit();
        }

        if ($accion === "actualizarPuntoControl") {
    if (!$puedeCrearPuntosControl) {
        echo json_encode(["success" => false, "error" => "No tienes permisos."]);
        exit();
    }

    $idPuntoControl = (int)($data["idPuntoControl"] ?? 0);
    $lat = $data["lat"] ?? null;
    $lng = $data["lng"] ?? null;

    if ($esAdmin) {
        $stmt = $conexion->prepare("
            UPDATE dbo.PuntosControl
            SET Lat = :lat,
                Lng = :lng
            WHERE IdPuntoControl = :id
        ");

        $stmt->execute([
            ":lat" => $lat,
            ":lng" => $lng,
            ":id" => $idPuntoControl
        ]);
        } else {
            $stmt = $conexion->prepare("
                UPDATE dbo.PuntosControl
                SET Lat = :lat,
                    Lng = :lng
                WHERE IdPuntoControl = :id
                AND IdEmpresa = :idEmpresa
            ");

            $stmt->execute([
                ":lat" => $lat,
                ":lng" => $lng,
                ":id" => $idPuntoControl,
                ":idEmpresa" => $idUsuario
            ]);
        }

        echo json_encode(["success" => true]);
        exit();
    }

        if ($accion === "eliminarPuntoControl") {
            if (!$puedeCrearPuntosControl) {
                echo json_encode(["success" => false, "error" => "No tienes permisos."]);
                exit();
            }

            $idPuntoControl = (int)($data["idPuntoControl"] ?? 0);

            if ($esAdmin) {
                $stmt = $conexion->prepare("
                    UPDATE dbo.PuntosControl
                    SET Estado = 0
                    WHERE IdPuntoControl = :id
                ");

                $stmt->execute([":id" => $idPuntoControl]);
            } else {
                $stmt = $conexion->prepare("
                    UPDATE dbo.PuntosControl
                    SET Estado = 0
                    WHERE IdPuntoControl = :id
                      AND IdEmpresa = :idEmpresa
                ");

                $stmt->execute([
                    ":id" => $idPuntoControl,
                    ":idEmpresa" => $idUsuario
                ]);
            }

            echo json_encode(["success" => true]);
            exit();
        }

        echo json_encode(["success" => false, "error" => "Acción inválida."]);
        exit();

    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
        exit();
    }
}

/* =========================
   CARGAR RUTAS DE BUS
========================= */
if ($esEmpresaNormal || $esContador) {
    $stmt = $conexion->prepare("
        SELECT 
            R.IdRuta,
            R.IdEmpresa,
            R.NombreRuta,
            R.PrecioRuta,
            R.HoraInicio,
            R.HoraFin,
            R.Estado,
            RD.DescripcionRuta,
            U.NombreEmpresa,
            U.NombreUsuario
        FROM dbo.Rutas R
        INNER JOIN dbo.Usuarios U
            ON R.IdEmpresa = U.IdUsuario
        LEFT JOIN dbo.RutaDetalle RD
            ON R.IdRuta = RD.IdRuta
        WHERE R.IdEmpresa = :idEmpresa
          AND R.Estado = 1
        ORDER BY R.NombreRuta
    ");

    $stmt->execute([":idEmpresa" => $idUsuario]);
} else {
    $stmt = $conexion->prepare("
        SELECT 
            R.IdRuta,
            R.IdEmpresa,
            R.NombreRuta,
            R.PrecioRuta,
            R.HoraInicio,
            R.HoraFin,
            R.Estado,
            RD.DescripcionRuta,
            U.NombreEmpresa,
            U.NombreUsuario
        FROM dbo.Rutas R
        INNER JOIN dbo.Usuarios U
            ON R.IdEmpresa = U.IdUsuario
        LEFT JOIN dbo.RutaDetalle RD
            ON R.IdRuta = RD.IdRuta
        WHERE R.Estado = 1
          AND U.Estado = 1
        ORDER BY R.NombreRuta
    ");

    $stmt->execute();
}

$rutasBus = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rutasBus as $i => $ruta) {
    $stmtParadas = $conexion->prepare("
        SELECT
            RP.IdRutaParada,
            RP.IdRuta,
            RP.IdMunicipio,
            RP.OrdenParada,
            RP.NombreParada,
            RP.DireccionParada,
            RP.Lat,
            RP.Lng,
            M.NombreMunicipio,
            M.Departamento
        FROM dbo.RutaParadas RP
        INNER JOIN dbo.Municipios M
            ON RP.IdMunicipio = M.IdMunicipio
        WHERE RP.IdRuta = :idRuta
        ORDER BY RP.OrdenParada
    ");

    $stmtParadas->execute([":idRuta" => $ruta["IdRuta"]]);
    $rutasBus[$i]["Paradas"] = $stmtParadas->fetchAll(PDO::FETCH_ASSOC);

    $stmtSalidas = $conexion->prepare("
        SELECT
            HoraSalida,
            LugarSalida
        FROM dbo.RutaSalidas
        WHERE IdRuta = :idRuta
        ORDER BY OrdenSalida
    ");

    $stmtSalidas->execute([":idRuta" => $ruta["IdRuta"]]);
    $rutasBus[$i]["Salidas"] = $stmtSalidas->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================
   PUNTOS DE CONTROL
========================= */
if ($esAdmin) {
    $stmt = $conexion->prepare("
        SELECT
            PC.IdPuntoControl,
            PC.IdEmpresa,
            PC.IdRuta,
            PC.NombrePunto,
            PC.Descripcion,
            PC.Lat,
            PC.Lng,
            PC.CodigoAcceso,
            U.NombreEmpresa,
            U.NombreUsuario,
            R.NombreRuta
        FROM dbo.PuntosControl PC
        INNER JOIN dbo.Usuarios U
            ON PC.IdEmpresa = U.IdUsuario
        LEFT JOIN dbo.Rutas R
            ON PC.IdRuta = R.IdRuta
        WHERE PC.Estado = 1
        ORDER BY PC.IdPuntoControl DESC
    ");

    $stmt->execute();
} elseif ($tipoUsuario == "Empresa") {
    $stmt = $conexion->prepare("
        SELECT
            PC.IdPuntoControl,
            PC.IdEmpresa,
            PC.IdRuta,
            PC.NombrePunto,
            PC.Descripcion,
            PC.Lat,
            PC.Lng,
            PC.CodigoAcceso,
            U.NombreEmpresa,
            U.NombreUsuario,
            R.NombreRuta
        FROM dbo.PuntosControl PC
        INNER JOIN dbo.Usuarios U
            ON PC.IdEmpresa = U.IdUsuario
        LEFT JOIN dbo.Rutas R
            ON PC.IdRuta = R.IdRuta
        WHERE PC.Estado = 1
          AND PC.IdEmpresa = :idEmpresa
        ORDER BY PC.IdPuntoControl DESC
    ");

    $stmt->execute([":idEmpresa" => $idUsuario]);
} else {
    $stmt = $conexion->prepare("
        SELECT
            PC.IdPuntoControl,
            PC.IdEmpresa,
            PC.IdRuta,
            PC.NombrePunto,
            PC.Descripcion,
            PC.Lat,
            PC.Lng,
            PC.CodigoAcceso,
            U.NombreEmpresa,
            U.NombreUsuario,
            R.NombreRuta
        FROM dbo.PuntosControl PC
        INNER JOIN dbo.Usuarios U
            ON PC.IdEmpresa = U.IdUsuario
        LEFT JOIN dbo.Rutas R
            ON PC.IdRuta = R.IdRuta
        WHERE 1 = 0
    ");

    $stmt->execute();
}

$puntosControl = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   EMPRESAS PARA ADMIN
========================= */
$empresas = [];

if ($esAdmin) {
    $empresas = $conexion->query("
        SELECT
            IdUsuario,
            NombreUsuario,
            NombreEmpresa
        FROM dbo.Usuarios
        WHERE IdTipoUsuario = 2
          AND Estado = 1
        ORDER BY NombreEmpresa
    ")->fetchAll(PDO::FETCH_ASSOC);
}
?> 

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mapa de rutas</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #05070d;
        }

        #map {
            height: 100vh;
            width: 100%;
        }

        .btn-volver {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1200;
            background: linear-gradient(135deg, #006eff, #003d99);
            color: white;
            padding: 11px 16px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 8px 25px rgba(0,0,0,0.35);
            border: 1px solid rgba(88, 166, 255, 0.35);
        }

        .panel {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(8, 18, 34, 0.96);
            color: #e8f1ff;
            padding: 14px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.35);
            z-index: 1000;
            width: 350px;
            max-height: 88vh;
            overflow-y: auto;
            font-size: 13px;
            border: 1px solid rgba(88, 166, 255, 0.35);
        }

        .panel.minimizado {
            width: 58px;
            height: 52px;
            overflow: hidden;
            padding: 10px;
        }

        .panel.minimizado > *:not(.btn-toggle-panel) {
            display: none;
        }

        .btn-toggle-panel {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 34px;
            height: 30px;
            padding: 0;
            border-radius: 8px;
            font-size: 18px;
            z-index: 1300;
        }

        .panel h3 {
            font-size: 16px;
            margin-bottom: 8px;
            color: #58a6ff;
            border-bottom: 1px solid rgba(88, 166, 255, 0.25);
            padding-bottom: 6px;
        }

        .panel hr {
            margin: 12px 0;
            border: none;
            height: 1px;
            background: rgba(88,166,255,0.25);
        }

        .lista {
            list-style: none;
            padding: 0;
            margin: 0 0 8px 0;
        }

        .lista li {
            padding: 8px;
            border-bottom: 1px solid rgba(88,166,255,0.15);
            cursor: pointer;
            color: #d7e9ff;
        }

        .lista li:hover {
            background: rgba(31, 139, 255, 0.12);
        }

        .btn-small,
        button {
            background: linear-gradient(135deg, #006eff, #003d99);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 6px 9px;
            font-size: 12px;
            margin-top: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-small.danger {
            background: linear-gradient(135deg, #ff3b3b, #9b111e);
        }

        .btn-small.success {
            background: linear-gradient(135deg, #00b894, #006b5a);
        }

        .btn-small.warning {
            background: #ffc107;
            color: #111;
        }

        .acciones {
            display: flex;
            gap: 8px;
            margin: 8px 0;
            flex-wrap: wrap;
        }

        .input-map {
            width: 100%;
            padding: 8px;
            border-radius: 8px;
            border: 1px solid #234b77;
            background: #08111f;
            color: white;
            margin: 5px 0;
        }

        .badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 999px;
            background: rgba(88,166,255,0.18);
            color: #9ecbff;
            font-size: 11px;
            margin-top: 4px;
        }

        .parada-marker {
            background: #006eff;
            color: white;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            box-shadow: 0 0 4px black;
            font-weight: bold;
            font-size: 12px;
        }

        .control-marker {
            background: #ff9800;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            box-shadow: 0 0 4px black;
            font-weight: bold;
            font-size: 12px;
        }

        .user-marker {
            background: #00b894;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            box-shadow: 0 0 4px black;
            font-weight: bold;
            font-size: 12px;
        }

        .resultado {
            background: rgba(6,15,29,0.85);
            border: 1px solid rgba(88,166,255,0.2);
            border-radius: 10px;
            padding: 8px;
            margin-bottom: 8px;
            color: #d7e9ff;
        }

        .leaflet-routing-container {
            display: none;
        }

        @media (max-width: 700px) {
            .btn-volver {
                top: 12px;
                left: 12px;
                padding: 9px 12px;
                font-size: 12px;
            }

            .panel {
                width: calc(100% - 24px);
                left: 12px;
                right: 12px;
                top: 60px;
                max-height: 45vh;
            }

            .panel.minimizado {
                width: 58px;
                height: 52px;
                left: auto;
                right: 12px;
                top: 12px;
            }
        }
    </style>
</head>

<body>

<div id="map"></div>

<a href="index.php" class="btn-volver">← Volver al inicio</a>

<div class="panel">
    <button type="button" class="btn-toggle-panel" onclick="togglePanel()" title="Ocultar menú">
        −
    </button>

    <h3>🚌 Mapa de rutas</h3>

    <p>
        Rol:
        <span class="badge"><?php echo limpiar($tipoUsuario); ?></span>

        <?php if ($categoriaEmpresa != ""): ?>
            <span class="badge"><?php echo limpiar($categoriaEmpresa); ?></span>
        <?php endif; ?>
    </p>

    <hr>

    <?php if (!$puedeUbicarParadas && !$puedeCrearPuntosControl): ?>
        <h3>📍 Buscar ruta</h3>
        <p>Primero elige si quieres marcar origen o destino, luego haz clic en el mapa.</p>

        <div class="acciones">
            <button type="button" onclick="activarOrigen()">Elegir origen</button>
            <button type="button" onclick="activarDestino()">Elegir destino</button>
            <button type="button" class="btn-small success" onclick="calcularTrayectoUsuario()">Calcular trayecto</button>
            <button type="button" class="btn-small success" onclick="calcularRutasUtiles()">Buscar rutas</button>
            <button type="button" class="btn-small danger" onclick="limpiarBusqueda()">Limpiar</button>
        </div>

        <div id="resultadoRutas"></div>

        <hr>
    <?php endif; ?>

    <?php if ($puedeUbicarParadas): ?>
        <h3>📌 Ubicar paradas</h3>
        <p>Selecciona una parada y luego haz clic en el mapa.</p>

        <select id="selectParada" class="input-map">
            <option value="">Seleccione parada</option>
        </select>

        <div class="acciones">
            <button type="button" class="btn-small success" onclick="modoGestion = 'parada'">
                Modo ubicar parada
            </button>
        </div>

        <hr>
    <?php endif; ?>

    <?php if ($puedeCrearPuntosControl): ?>
        <h3>⏱️ Puntos de control</h3>
        <p>Haz clic en el mapa para crear un punto donde se tomarán tiempos.</p>

        <?php if ($esAdmin): ?>
            <select id="selectEmpresaControl" class="input-map">
                <option value="">Seleccione empresa</option>

                <?php foreach ($empresas as $e): ?>
                    <option value="<?php echo limpiar($e["IdUsuario"]); ?>">
                        <?php echo limpiar($e["NombreEmpresa"] ?: $e["NombreUsuario"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <select id="selectRutaControl" class="input-map">
            <option value="">Ruta opcional</option>
        </select>

        <div class="acciones">
            <button type="button" class="btn-small warning" onclick="modoGestion = 'control'">
                Modo punto control
            </button>
        </div>

        <ul id="listaPuntosControl" class="lista"></ul>

        <hr>
    <?php endif; ?>

    <h3>🛣️ Rutas cargadas</h3>
    <ul id="listaRutas" class="lista"></ul>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<script>
const TIPO_USUARIO = "<?php echo $tipoUsuario; ?>";
const CATEGORIA_EMPRESA = "<?php echo $categoriaEmpresa; ?>";
const PUEDE_UBICAR_PARADAS = <?php echo $puedeUbicarParadas ? "true" : "false"; ?>;
const PUEDE_CREAR_PUNTOS_CONTROL = <?php echo $puedeCrearPuntosControl ? "true" : "false"; ?>;
const ES_ADMIN = <?php echo $esAdmin ? "true" : "false"; ?>;

const rutasBus = <?php echo json_encode($rutasBus, JSON_UNESCAPED_UNICODE); ?>;
const puntosControl = <?php echo json_encode($puntosControl, JSON_UNESCAPED_UNICODE); ?>;

const bounds = L.latLngBounds([[4.65, -74.40], [4.85, -74.00]]);

const map = L.map('map').setView([4.76, -74.22], 12);

L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap & CartoDB'
}).addTo(map);

map.setMaxBounds(bounds);
map.on('drag', () => map.panInsideBounds(bounds, { animate: false }));
map.setMinZoom(10);
map.setMaxZoom(18);

L.rectangle(bounds, {
    color: 'green',
    weight: 3,
    fill: false,
    dashArray: '8,6'
}).addTo(map);

let capasRutas = L.layerGroup().addTo(map);
let capasParadas = L.layerGroup().addTo(map);
let capasControl = L.layerGroup().addTo(map);
let capasUsuario = L.layerGroup().addTo(map);

let modoGestion = "";
let modoSeleccion = "origen";

let origenUsuario = null;
let destinoUsuario = null;
let markerOrigen = null;
let markerDestino = null;
let rutaUsuarioControl = null;

let colores = [
    "#006eff", "#00b894", "#ff7675", "#fdcb6e",
    "#a29bfe", "#e84393", "#00cec9", "#e17055"
];

function activarOrigen() {
    modoGestion = "";
    modoSeleccion = "origen";
    alert("Ahora haz clic en el mapa para marcar el origen.");
}

function activarDestino() {
    modoGestion = "";
    modoSeleccion = "destino";
    alert("Ahora haz clic en el mapa para marcar el destino.");
}

function iconParada(numero) {
    return L.divIcon({
        className: "",
        html: `<div class="parada-marker">${numero}</div>`,
        iconSize: [26, 26]
    });
}

function iconControl() {
    return L.divIcon({
        className: "",
        html: `<div class="control-marker">⏱</div>`,
        iconSize: [24, 24]
    });
}

function iconUsuario(texto) {
    return L.divIcon({
        className: "",
        html: `<div class="user-marker">${texto}</div>`,
        iconSize: [24, 24]
    });
}

function cargarSelects() {
    const selectParada = document.getElementById("selectParada");
    const selectRutaControl = document.getElementById("selectRutaControl");

    if (selectParada) {
        rutasBus.forEach(ruta => {
            ruta.Paradas.forEach(parada => {
                let opt = document.createElement("option");
                opt.value = parada.IdRutaParada;
                opt.textContent = `${ruta.NombreRuta} - Parada ${parada.OrdenParada}: ${parada.NombreMunicipio}`;
                selectParada.appendChild(opt);
            });
        });
    }

    if (selectRutaControl) {
        rutasBus.forEach(ruta => {
            let opt = document.createElement("option");
            opt.value = ruta.IdRuta;
            opt.textContent = ruta.NombreRuta;
            selectRutaControl.appendChild(opt);
        });
    }
}

function dibujarRutasYParadas() {
    capasRutas.clearLayers();
    capasParadas.clearLayers();

    rutasBus.forEach((ruta, index) => {
        let color = colores[index % colores.length];
        let puntos = [];

        ruta.Paradas.forEach(parada => {
            if (parada.Lat && parada.Lng) {
                let lat = parseFloat(parada.Lat);
                let lng = parseFloat(parada.Lng);

                puntos.push(L.latLng(lat, lng));

                let marker = L.marker([lat, lng], {
                    icon: iconParada(parada.OrdenParada)
                }).addTo(capasParadas);

                marker.bindPopup(`
                    <b>${ruta.NombreRuta}</b><br>
                    Empresa: ${ruta.NombreEmpresa || ruta.NombreUsuario}<br>
                    Parada ${parada.OrdenParada}: ${parada.NombreMunicipio}<br>
                    ${parada.NombreParada || ""}<br>
                    ${parada.DireccionParada}<br>
                    Precio: $${Number(ruta.PrecioRuta).toLocaleString("es-CO")}
                `);
            }
        });

        if (puntos.length >= 2) {
            let control = L.Routing.control({
                waypoints: puntos,
                router: L.Routing.osrmv1({
                    serviceUrl: 'https://router.project-osrm.org/route/v1'
                }),
                lineOptions: {
                    styles: [{ color: color, weight: 5, opacity: 0.85 }]
                },
                addWaypoints: false,
                draggableWaypoints: false,
                fitSelectedRoutes: false,
                showAlternatives: false,
                routeWhileDragging: false
            }).addTo(capasRutas);

            control.on('routesfound', () => {
                let c = control.getContainer();
                if (c) c.style.display = 'none';
            });
        }
    });
}

function dibujarPuntosControl() {
    capasControl.clearLayers();

    puntosControl.forEach(p => {
        let marker = L.marker([parseFloat(p.Lat), parseFloat(p.Lng)], {
            icon: iconControl(),
            draggable: PUEDE_CREAR_PUNTOS_CONTROL
        }).addTo(capasControl);

        if (PUEDE_CREAR_PUNTOS_CONTROL) {
            marker.on("dragend", async function(e) {
                let pos = marker.getLatLng();

                if (!bounds.contains(pos)) {
                    alert("Fuera del área permitida.");
                    location.reload();
                    return;
                }

                await actualizarPuntoControl(p.IdPuntoControl, pos.lat, pos.lng);
            });
        }

        let btnEliminar = "";

        if (PUEDE_CREAR_PUNTOS_CONTROL) {
            btnEliminar = `
                <br>
                <button class="btn-small danger" onclick="eliminarPuntoControl(${p.IdPuntoControl})">
                    Eliminar
                </button>
            `;
        }

        marker.bindPopup(`
            <b>${p.NombrePunto}</b><br>
            Código: <b>${p.CodigoAcceso || "Sin código"}</b><br>
            ${p.Descripcion || ""}<br>
            Empresa: ${p.NombreEmpresa || p.NombreUsuario}<br>
            Ruta: ${p.NombreRuta || "Sin ruta"}
            ${btnEliminar}
        `);
    });

    actualizarListaPuntosControl();
}

function actualizarListaRutas() {
    const ul = document.getElementById("listaRutas");
    ul.innerHTML = "";

    rutasBus.forEach((ruta) => {
        let li = document.createElement("li");
        let paradasUbicadas = ruta.Paradas.filter(p => p.Lat && p.Lng).length;

        li.innerHTML = `
            <b>${ruta.NombreRuta}</b><br>
            Empresa: ${ruta.NombreEmpresa || ruta.NombreUsuario}<br>
            Precio: $${Number(ruta.PrecioRuta).toLocaleString("es-CO")}<br>
            <span class="badge">${paradasUbicadas}/${ruta.Paradas.length} paradas ubicadas</span>
        `;

        li.onclick = () => enfocarRuta(ruta);
        ul.appendChild(li);
    });

    if (rutasBus.length === 0) {
        ul.innerHTML = "<li><em>No hay rutas disponibles.</em></li>";
    }
}

function actualizarListaPuntosControl() {
    const ul = document.getElementById("listaPuntosControl");

    if (!ul) return;

    ul.innerHTML = "";

    puntosControl.forEach(p => {
        let li = document.createElement("li");

        li.innerHTML = `
        <b>${p.NombrePunto}</b><br>
        Código: <b>${p.CodigoAcceso || "Sin código"}</b><br>
        ${p.NombreEmpresa || p.NombreUsuario}<br>
        <span class="badge">${p.NombreRuta || "Sin ruta"}</span>
    `;

        li.onclick = () => map.setView([parseFloat(p.Lat), parseFloat(p.Lng)], 16);
        ul.appendChild(li);
    });

    if (puntosControl.length === 0) {
        ul.innerHTML = "<li><em>No hay puntos de control.</em></li>";
    }
}

function enfocarRuta(ruta) {
    let puntos = [];

    ruta.Paradas.forEach(p => {
        if (p.Lat && p.Lng) {
            puntos.push([parseFloat(p.Lat), parseFloat(p.Lng)]);
        }
    });

    if (puntos.length > 0) {
        map.fitBounds(puntos, { padding: [40, 40] });
    }
}

map.on("click", async function(e) {
    if (!bounds.contains(e.latlng)) {
        alert("Fuera del área permitida.");
        return;
    }

    if (modoGestion === "parada" && PUEDE_UBICAR_PARADAS) {
        await ubicarParada(e.latlng);
        return;
    }

    if (modoGestion === "control" && PUEDE_CREAR_PUNTOS_CONTROL) {
        await crearPuntoControl(e.latlng);
        return;
    }

    seleccionarPuntoUsuario(e.latlng);
});

async function ubicarParada(latlng) {
    const select = document.getElementById("selectParada");

    if (!select || !select.value) {
        alert("Selecciona una parada primero.");
        return;
    }

    if (!confirm("¿Guardar esta ubicación para la parada seleccionada?")) {
        return;
    }

    const resp = await fetch("mapa.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            accion: "actualizarParada",
            idRutaParada: select.value,
            lat: latlng.lat,
            lng: latlng.lng
        })
    });

    const json = await resp.json();

    if (json.success) {
        alert("Parada actualizada correctamente.");
        location.reload();
    } else {
        alert(json.error || "Error al actualizar parada.");
    }
}

async function crearPuntoControl(latlng) {
    let nombre = prompt("Nombre del punto de control:");

    if (!nombre) return;

    let descripcion = prompt("Descripción opcional:", "") || "";
    let idEmpresa = null;

    if (ES_ADMIN) {
        const selectEmpresa = document.getElementById("selectEmpresaControl");

        if (!selectEmpresa.value) {
            alert("Selecciona una empresa.");
            return;
        }

        idEmpresa = selectEmpresa.value;
    }

    const selectRuta = document.getElementById("selectRutaControl");
    let idRuta = selectRuta ? selectRuta.value : "";

    const resp = await fetch("mapa.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            accion: "crearPuntoControl",
            nombre: nombre,
            descripcion: descripcion,
            idEmpresa: idEmpresa,
            idRuta: idRuta,
            lat: latlng.lat,
            lng: latlng.lng
        })
    });

    const json = await resp.json();

    if (json.success) {
        alert("Punto de control creado.");
        location.reload();
    } else {
        alert(json.error || "Error al crear punto.");
    }
}

async function actualizarPuntoControl(id, lat, lng) {
    const resp = await fetch("mapa.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            accion: "actualizarPuntoControl",
            idPuntoControl: id,
            lat: lat,
            lng: lng
        })
    });

    const json = await resp.json();

    if (!json.success) {
        alert(json.error || "Error al mover punto de control.");
        location.reload();
    }
}

async function eliminarPuntoControl(id) {
    if (!confirm("¿Eliminar este punto de control?")) return;

    const resp = await fetch("mapa.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            accion: "eliminarPuntoControl",
            idPuntoControl: id
        })
    });

    const json = await resp.json();

    if (json.success) {
        alert("Punto eliminado.");
        location.reload();
    } else {
        alert(json.error || "Error al eliminar punto.");
    }
}

function seleccionarPuntoUsuario(latlng) {
    if (modoSeleccion === "origen") {
        origenUsuario = latlng;

        if (markerOrigen) {
            capasUsuario.removeLayer(markerOrigen);
        }

        markerOrigen = L.marker(latlng, {
            icon: iconUsuario("I")
        }).addTo(capasUsuario);

        markerOrigen.bindPopup("Inicio seleccionado").openPopup();

        return;
    }

    if (modoSeleccion === "destino") {
        destinoUsuario = latlng;

        if (markerDestino) {
            capasUsuario.removeLayer(markerDestino);
        }

        markerDestino = L.marker(latlng, {
            icon: iconUsuario("F")
        }).addTo(capasUsuario);

        markerDestino.bindPopup("Fin seleccionado").openPopup();
    }
}

function calcularTrayectoUsuario() {
    if (!origenUsuario || !destinoUsuario) {
        alert("Primero selecciona inicio y fin.");
        return;
    }

    if (rutaUsuarioControl) {
        map.removeControl(rutaUsuarioControl);
    }

    rutaUsuarioControl = L.Routing.control({
        waypoints: [
            L.latLng(origenUsuario.lat, origenUsuario.lng),
            L.latLng(destinoUsuario.lat, destinoUsuario.lng)
        ],
        router: L.Routing.osrmv1({
            serviceUrl: 'https://router.project-osrm.org/route/v1'
        }),
        lineOptions: {
            styles: [{ color: "#00b894", weight: 5, opacity: 0.9 }]
        },
        addWaypoints: false,
        draggableWaypoints: false,
        fitSelectedRoutes: true,
        showAlternatives: false,
        routeWhileDragging: false
    }).addTo(map);

    rutaUsuarioControl.on("routesfound", function(e) {
        const ruta = e.routes[0];
        const distanciaKm = (ruta.summary.totalDistance / 1000).toFixed(2);
        const tiempoMin = Math.round(ruta.summary.totalTime / 60);

        const div = document.getElementById("resultadoRutas");

        if (div) {
            div.innerHTML = `
                <div class="resultado">
                    <b>Trayecto calculado</b><br>
                    Distancia aproximada: ${distanciaKm} km<br>
                    Tiempo aproximado: ${tiempoMin} minutos
                </div>
            `;
        }
    });
}

function calcularRutasUtiles() {
    if (!origenUsuario || !destinoUsuario) {
        alert("Selecciona origen y destino.");
        return;
    }

    const resultados = [];

    rutasBus.forEach(ruta => {
        let paradasConCoords = ruta.Paradas.filter(p => p.Lat && p.Lng);

        if (paradasConCoords.length < 2) return;

        let paradaOrigen = null;
        let paradaDestino = null;
        let distanciaOrigen = Infinity;
        let distanciaDestino = Infinity;

        paradasConCoords.forEach(p => {
            let latlng = L.latLng(parseFloat(p.Lat), parseFloat(p.Lng));
            let dOrigen = distanciaMetros(origenUsuario, latlng);
            let dDestino = distanciaMetros(destinoUsuario, latlng);

            if (dOrigen < distanciaOrigen) {
                distanciaOrigen = dOrigen;
                paradaOrigen = p;
            }

            if (dDestino < distanciaDestino) {
                distanciaDestino = dDestino;
                paradaDestino = p;
            }
        });

        if (!paradaOrigen || !paradaDestino) return;

        if (parseInt(paradaOrigen.OrdenParada) >= parseInt(paradaDestino.OrdenParada)) return;

        resultados.push({
            ruta: ruta,
            paradaOrigen: paradaOrigen,
            paradaDestino: paradaDestino,
            distanciaOrigen: distanciaOrigen,
            distanciaDestino: distanciaDestino
        });
    });

    resultados.sort((a, b) => {
        let totalA = a.distanciaOrigen + a.distanciaDestino;
        let totalB = b.distanciaOrigen + b.distanciaDestino;

        if (totalA === totalB) {
            return parseFloat(a.ruta.PrecioRuta) - parseFloat(b.ruta.PrecioRuta);
        }

        return totalA - totalB;
    });

    mostrarResultados(resultados);
}

function mostrarResultados(resultados) {
    const div = document.getElementById("resultadoRutas");

    if (!div) return;

    div.innerHTML = "";

    if (resultados.length === 0) {
        div.innerHTML = `
            <div class="resultado">
                No se encontraron rutas útiles con las paradas ubicadas.
            </div>
        `;
        return;
    }

    resultados.forEach(res => {
        let salidas = "";

        if (res.ruta.Salidas && res.ruta.Salidas.length > 0) {
            salidas = res.ruta.Salidas.map(s => {
                return `${String(s.HoraSalida).substring(0,5)} - ${s.LugarSalida}`;
            }).join("<br>");
        } else {
            salidas = "Sin horarios registrados.";
        }

        let item = document.createElement("div");
        item.className = "resultado";

        item.innerHTML = `
            <b>${res.ruta.NombreRuta}</b><br>
            Empresa: ${res.ruta.NombreEmpresa || res.ruta.NombreUsuario}<br>
            Precio: $${Number(res.ruta.PrecioRuta).toLocaleString("es-CO")}<br>
            Tomar en: <b>${res.paradaOrigen.NombreMunicipio}</b> - ${res.paradaOrigen.DireccionParada}<br>
            Bajarse en: <b>${res.paradaDestino.NombreMunicipio}</b> - ${res.paradaDestino.DireccionParada}<br>
            Distancia al punto de abordaje: ${Math.round(res.distanciaOrigen)} m<br>
            <span class="badge">Salidas</span><br>
            ${salidas}
        `;

        item.onclick = () => {
            enfocarRuta(res.ruta);
        };

        div.appendChild(item);
    });
}

function limpiarBusqueda() {
    origenUsuario = null;
    destinoUsuario = null;

    if (markerOrigen) {
        capasUsuario.removeLayer(markerOrigen);
        markerOrigen = null;
    }

    if (markerDestino) {
        capasUsuario.removeLayer(markerDestino);
        markerDestino = null;
    }

    if (rutaUsuarioControl) {
        map.removeControl(rutaUsuarioControl);
        rutaUsuarioControl = null;
    }

    const div = document.getElementById("resultadoRutas");

    if (div) {
        div.innerHTML = "";
    }

    modoSeleccion = "origen";
}

function distanciaMetros(a, b) {
    const R = 6371000;
    const lat1 = a.lat * Math.PI / 180;
    const lat2 = b.lat * Math.PI / 180;
    const dLat = (b.lat - a.lat) * Math.PI / 180;
    const dLng = (b.lng - a.lng) * Math.PI / 180;

    const x =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1) * Math.cos(lat2) *
        Math.sin(dLng / 2) * Math.sin(dLng / 2);

    const c = 2 * Math.atan2(Math.sqrt(x), Math.sqrt(1 - x));

    return R * c;
}

function togglePanel() {
    const panel = document.querySelector(".panel");
    const btn = document.querySelector(".btn-toggle-panel");

    panel.classList.toggle("minimizado");

    if (panel.classList.contains("minimizado")) {
        btn.textContent = "+";
        btn.title = "Mostrar menú";
    } else {
        btn.textContent = "−";
        btn.title = "Ocultar menú";
    }
}

cargarSelects();
dibujarRutasYParadas();
dibujarPuntosControl();
actualizarListaRutas();
</script>

</body>
</html>