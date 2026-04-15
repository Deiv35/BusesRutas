<!DOCTYPE html>
<html>
<head>
    <title>Rutas de Buses</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #121212;
            color: white;
        }

        /* NAVBAR */
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
		background: #000; /* fondo negro para enmarcar */

		display: flex;
		justify-content: center;
		align-items: center;
		}

		.banner img {
			max-width: 100%;
			max-height: 100%;
			object-fit: contain; /* 🔥 NO recorta */
		}

        /* CONTENIDO */
        .container {
            padding: 30px;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
        }

        .rutas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
            transform: scale(1.05);
            background: #2a2a2a;
        }

        .hora {
            font-size: 20px;
            font-weight: bold;
            color: #4da6ff;
        }

        .trayecto {
            margin-top: 10px;
        }

        .btn-info {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            background: #4da6ff;
            color: white;
            cursor: pointer;
            margin-top: 10px;
        }

        .info {
            display: none;
            margin-top: 10px;
            font-size: 14px;
            color: #ccc;
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

<!-- NAVBAR -->
<div class="navbar">
    <img src="logo.jpg" class="logo">
    <div class="nav-buttons">
        <a href="mapa.php"><button>Mapa</button></a>
        <a href="login.php"><button>Iniciar Sesión</button></a>
    </div>
</div>

<div class="banner">
    <img src="fondoindex.jpg">
</div>
<!-- CONTENIDO -->
<div class="container">
    <h1>Rutas Disponibles</h1>

    <div class="rutas">

        <div class="ruta">
            <div class="hora">6:00 AM</div>
            <div class="trayecto">Facatativá → Madrid → Mosquera → Funza</div>
            <button class="btn-info" onclick="toggleInfo('info1', this)">Mostrar más</button>
            <div class="info" id="info1">Paradas: Centro, Terminal Madrid, Parque Mosquera. Tiempo estimado: 1h</div>
        </div>

        <div class="ruta">
            <div class="hora">7:30 AM</div>
            <div class="trayecto">Facatativá → Madrid → Mosquera → Funza</div>
            <button class="btn-info" onclick="toggleInfo('info2', this)">Mostrar más</button>
            <div class="info" id="info2">Paradas: Hospital, Madrid centro, Funza parque. Tiempo estimado: 1h 10min</div>
        </div>

        <div class="ruta">
            <div class="hora">9:00 AM</div>
            <div class="trayecto">Facatativá → Madrid → Mosquera → Funza</div>
            <button class="btn-info" onclick="toggleInfo('info3', this)">Mostrar más</button>
            <div class="info" id="info3">Ruta directa con pocas paradas. Tiempo: 50min</div>
        </div>

        <div class="ruta">
            <div class="hora">12:00 PM</div>
            <div class="trayecto">Facatativá → Madrid → Mosquera → Funza</div>
            <button class="btn-info" onclick="toggleInfo('info4', this)">Mostrar más</button>
            <div class="info" id="info4">Alta demanda. Paradas completas. Tiempo: 1h 20min</div>
        </div>

        <div class="ruta">
            <div class="hora">3:00 PM</div>
            <div class="trayecto">Facatativá → Madrid → Mosquera → Funza</div>
            <button class="btn-info" onclick="toggleInfo('info5', this)">Mostrar más</button>
            <div class="info" id="info5">Ruta rápida. Ideal para estudiantes. Tiempo: 55min</div>
        </div>

    </div>
</div>

</body>
</html>