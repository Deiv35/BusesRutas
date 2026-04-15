<!DOCTYPE html>
<html>
<head>
    <title>Mapa de rutas</title>
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
            height: 60px;
        }

        .nav-buttons a button {
            margin-left: 10px;
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            background: #2a2a2a;
            color: white;
            cursor: pointer;
        }

        /* CONTENIDO */
        .container {
            padding: 20px;
        }

        h1 {
            text-align: center;
        }

        /* 🔍 BUSCADOR */
        .buscador {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .buscador input {
            width: 250px;
            padding: 10px;
            border-radius: 8px 0 0 8px;
            border: none;
            background: #2a2a2a;
            color: white;
        }

        .buscador button {
            padding: 10px 15px;
            border: none;
            border-radius: 0 8px 8px 0;
            background: #4da6ff;
            color: white;
            cursor: pointer;
        }

        .buscador button:hover {
            background: #3399ff;
        }

        /* MAPA */
        .mapa {
            width: 100%;
            height: 500px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.6);
        }
    </style>

    <script>
        function buscarRuta() {
            var texto = document.getElementById("busqueda").value;

            if (texto.trim() === "") {
                alert("Escribe una ruta o ciudad");
                return;
            }

            var mapa = document.getElementById("mapaFrame");

            // 🔥 cambia dinámicamente la ruta
            mapa.src = "https://www.google.com/maps?q=" + encodeURIComponent(texto) + "&output=embed";
        }
    </script>
</head>

<body>

<!-- NAVBAR -->
<div class="navbar">
    <img src="logo.jpg" class="logo">
    <div class="nav-buttons">
        <a href="index.php"><button>Inicio</button></a>
    </div>
</div>

<!-- CONTENIDO -->
<div class="container">
    <h1>Mapa de rutas</h1>

    <!-- 🔍 BUSCADOR -->
    <div class="buscador">
        <input type="text" id="busqueda" placeholder="Ej: Madrid, Mosquera o ruta completa">
        <button onclick="buscarRuta()">Buscar</button>
    </div>

    <!-- MAPA -->
    <div class="mapa">
        <iframe 
            id="mapaFrame"
            src="https://www.google.com/maps?q=Facatativá,Madrid,Mosquera,Funza&output=embed"
            width="100%" 
            height="100%" 
            style="border:0;"
            allowfullscreen=""
            loading="lazy">
        </iframe>
    </div>
</div>

</body>
</html>