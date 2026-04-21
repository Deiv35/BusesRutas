<?php
session_start();
require_once("conexion.php");

function limpiar($dato) {
    return htmlspecialchars((string)$dato, ENT_QUOTES, 'UTF-8');
}

$rutas = [];
$error = "";

try {
    $sql = "
        SELECT 
            r.IdRuta,
            r.NombreRuta,
            r.HoraInicio,
            r.HoraFin,
            e.NombreEmpresa,
            rd.DescripcionRuta
        FROM Rutas r
        INNER JOIN Empresas e ON r.IdEmpresa = e.IdEmpresa
        LEFT JOIN RutaDetalle rd ON r.IdRuta = rd.IdRuta
        WHERE r.Estado = 1
          AND e.Estado = 1
        ORDER BY r.HoraInicio ASC, r.IdRuta DESC
    ";

    $stmt = $conn->query($sql);
    $rutasBase = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $recorridos = [];
    $sqlRecorridos = "
        SELECT 
            rm.IdRuta,
            rm.OrdenRecorrido,
            m.NombreMunicipio
        FROM RutaMunicipios rm
        INNER JOIN Municipios m ON rm.IdMunicipio = m.IdMunicipio
        ORDER BY rm.IdRuta, rm.OrdenRecorrido
    ";
    $stmtRecorridos = $conn->query($sqlRecorridos);
    $rowsRecorridos = $stmtRecorridos->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rowsRecorridos as $row) {
        $recorridos[$row['IdRuta']][] = $row['NombreMunicipio'];
    }

    $paradas = [];
    $sqlParadas = "
        SELECT 
            IdRuta,
            OrdenParada,
            NombreParada,
            DireccionReferencia,
            Observaciones
        FROM ParadasRuta
        WHERE Estado = 1
        ORDER BY IdRuta, OrdenParada
    ";
    $stmtParadas = $conn->query($sqlParadas);
    $rowsParadas = $stmtParadas->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rowsParadas as $row) {
        $paradas[$row['IdRuta']][] = $row;
    }

    foreach ($rutasBase as $ruta) {
        $idRuta = $ruta['IdRuta'];
        $ruta['RecorridoTexto'] = isset($recorridos[$idRuta])
            ? implode(' → ', $recorridos[$idRuta])
            : 'Recorrido no disponible';

        $ruta['Paradas'] = $paradas[$idRuta] ?? [];
        $rutas[] = $ruta;
    }

} catch (PDOException $e) {
    $error = "Error al cargar rutas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Rutas de Buses</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #121212;
            color: white;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background: #1e1e1e;
        }

        .logo {
            height: 70px;
        }

        .nav-buttons a button {
            margin-left: 10px;
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            background: #2a2a2a;
            color: white;
            cursor: pointer;
            transition: 0.3s;
        }

        .nav-buttons a button:hover {
            background: #3a3a3a;
        }

        .banner {
            width: 100%;
            height: 260px;
            background: #000;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .banner img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .container {
            padding: 30px;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #4da6ff;
        }

        .mensaje-error {
            background: #6b1d1d;
            color: #ffd4d4;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .rutas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .ruta {
            background: #1e1e1e;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
            transition: transform 0.2s, background 0.3s;
        }

        .ruta:hover {
            transform: scale(1.03);
            background: #2a2a2a;
        }

        .hora {
            font-size: 20px;
            font-weight: bold;
            color: #4da6ff;
            margin-bottom: 10px;
        }

        .empresa {
            font-size: 14px;
            color: #aaa;
            margin-bottom: 8px;
        }

        .trayecto {
            margin-top: 10px;
            font-size: 15px;
            line-height: 1.5;
        }

        .btn-info {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            background: #4da6ff;
            color: white;
            cursor: pointer;
            margin-top: 12px;
        }

        .btn-info:hover {
            background: #3399ff;
        }

        .info {
            display: none;
            margin-top: 12px;
            font-size: 14px;
            color: #ccc;
            background: #252525;
            padding: 12px;
            border-radius: 10px;
        }

        .info p {
            margin: 6px 0;
        }

        .info ul {
            margin: 8px 0 0 18px;
            padding: 0;
        }

        .sin-rutas {
            text-align: center;
            color: #bbb;
            margin-top: 30px;
            font-size: 18px;
        }
    </style>

    <script>
        function toggleInfo(id, btn) {
            var info = document.getElementById(id);

            if (info.style.display === "block") {
                info.style.display = "none";
                btn.innerText = "Mostrar más";
            } else {
                info.style.display = "block";
                btn.innerText = "Mostrar menos";
            }
        }
    </script>
</head>

<body>

<div class="navbar">
    <img src="logo.jpg" class="logo" alt="Logo">
    <div class="nav-buttons">

    <a href="mapa.php"><button type="button">Mapa</button></a>

    <?php if (isset($_SESSION['usuario'])): ?>

        <span style="margin-left:10px; margin-right:10px;">
            👤 <?php echo limpiar($_SESSION['usuario']); ?>
        </span>

        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
            <a href="admin.php"><button type="button">Panel Admin</button></a>
        <?php endif; ?>

        <a href="logout.php"><button type="button">Cerrar sesión</button></a>

    <?php else: ?>

        <a href="login.php"><button type="button">Iniciar Sesión</button></a>

    <?php endif; ?>

</div>
</div>

<div class="banner">
    <img src="fondoindex.jpg" alt="Banner principal">
</div>

<div class="container">
    <h1>Rutas Disponibles</h1>

    <?php if ($error !== ""): ?>
        <div class="mensaje-error"><?php echo limpiar($error); ?></div>
    <?php endif; ?>

    <?php if (count($rutas) > 0): ?>
        <div class="rutas">
            <?php foreach ($rutas as $index => $ruta): ?>
                <div class="ruta">
                    <div class="hora">
                        <?php echo limpiar(substr((string)$ruta['HoraInicio'], 0, 5)); ?>
                        -
                        <?php echo limpiar(substr((string)$ruta['HoraFin'], 0, 5)); ?>
                    </div>

                    <div class="empresa">
                        Empresa: <?php echo limpiar($ruta['NombreEmpresa']); ?>
                    </div>

                    <div class="trayecto">
                        <?php echo limpiar($ruta['RecorridoTexto']); ?>
                    </div>

                    <button class="btn-info" onclick="toggleInfo('info<?php echo $index; ?>', this)">
                        Mostrar más
                    </button>

                    <div class="info" id="info<?php echo $index; ?>">
                        <p><strong>Ruta:</strong> <?php echo limpiar($ruta['NombreRuta']); ?></p>
                        <p><strong>Descripción:</strong> <?php echo limpiar($ruta['DescripcionRuta'] ?: 'Sin descripción'); ?></p>

                        <p><strong>Paradas:</strong></p>
                        <?php if (!empty($ruta['Paradas'])): ?>
                            <ul>
                                <?php foreach ($ruta['Paradas'] as $parada): ?>
                                    <li>
                                        <?php echo limpiar($parada['NombreParada']); ?>
                                        <?php if (!empty($parada['DireccionReferencia'])): ?>
                                            - <?php echo limpiar($parada['DireccionReferencia']); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($parada['Observaciones'])): ?>
                                            (<?php echo limpiar($parada['Observaciones']); ?>)
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No hay paradas registradas.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="sin-rutas">No hay rutas disponibles en este momento.</div>
    <?php endif; ?>
</div>

</body>
</html>
