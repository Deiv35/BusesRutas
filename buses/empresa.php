<?php
session_start();

if(!isset($_SESSION['rol']) || $_SESSION['rol'] != 'empresa'){
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Panel Empresa</title>
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
            padding: 30px;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #4da6ff;
        }

        /* TARJETAS */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .card {
            background: #1e1e1e;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.6);
            transition: 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            background: #2a2a2a;
        }

        .card h3 {
            margin-top: 0;
            color: #4da6ff;
        }

        .card p {
            color: #ccc;
        }

        .btn {
            margin-top: 10px;
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            background: #4da6ff;
            color: white;
            cursor: pointer;
        }

        .btn:hover {
            background: #3399ff;
        }
    </style>
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
    <h1>Panel de Empresa</h1>

    <div class="cards">

        <div class="card">
            <h3>Gestionar Rutas</h3>
            <p>Agrega, edita o elimina rutas disponibles.</p>
            <button class="btn">Administrar</button>
        </div>

        <div class="card">
            <h3>Horarios</h3>
            <p>Configura los horarios de salida de los buses.</p>
            <button class="btn">Editar</button>
        </div>

        <div class="card">
            <h3>Reportes</h3>
            <p>Consulta estadísticas de uso de rutas.</p>
            <button class="btn">Ver Reportes</button>
        </div>

    </div>
</div>

</body>
</html>