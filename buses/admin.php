<?php
session_start();

if(!isset($_SESSION['rol']) || $_SESSION['rol'] != 'admin'){
    header("Location: login.php");
    exit();
}

// 🔥 arrays simulados
if(!isset($_SESSION['usuarios'])) $_SESSION['usuarios'] = [];
if(!isset($_SESSION['rutas'])) $_SESSION['rutas'] = [];

// AGREGAR USUARIO
if(isset($_POST['agregar_usuario'])){
    $_SESSION['usuarios'][] = [
        'user' => $_POST['user'],
        'pass' => $_POST['pass']
    ];
}

// AGREGAR RUTA
if(isset($_POST['agregar_ruta'])){
    $_SESSION['rutas'][] = [
        'origen' => $_POST['origen'],
        'destino' => $_POST['destino'],
        'hora' => $_POST['hora']
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Panel Administrador</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #121212;
            color: white;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            padding: 15px 30px;
            background: #1e1e1e;
        }

        .logo { height: 60px; }

        .container { padding: 30px; }

        h1 { text-align: center; color: #4da6ff; }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .card {
            background: #1e1e1e;
            padding: 20px;
            border-radius: 15px;
        }

        .btn {
            margin-top: 10px;
            padding: 8px;
            background: #4da6ff;
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
        }

        .formulario {
            display: none;
            margin-top: 15px;
        }

        input {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border-radius: 6px;
            border: none;
            background: #2a2a2a;
            color: white;
        }

        .lista {
            margin-top: 10px;
            font-size: 14px;
            color: #ccc;
        }
    </style>

    <script>
        function toggle(id){
            var x = document.getElementById(id);
            x.style.display = (x.style.display === "block") ? "none" : "block";
        }
    </script>
</head>

<body>

<div class="navbar">
    <img src="logo.jpg" class="logo">
    <div>
        <a href="index.php"><button class="btn">Inicio</button></a>
    </div>
</div>

<div class="container">
    <h1>Panel de Administrador</h1>

    <div class="cards">

        <!-- USUARIOS -->
        <div class="card">
            <h3>Gestionar Usuarios</h3>
            <button class="btn" onclick="toggle('formUser')">Agregar Usuario</button>

            <div class="formulario" id="formUser">
                <form method="POST">
                    <input type="text" name="user" placeholder="Usuario" required>
                    <input type="text" name="pass" placeholder="Contraseña" required>
                    <button class="btn" name="agregar_usuario">Guardar</button>
                </form>
            </div>

            <div class="lista">
                <?php foreach($_SESSION['usuarios'] as $u): ?>
                    <p>👤 <?php echo $u['user']; ?></p>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- RUTAS -->
        <div class="card">
            <h3>Gestionar Rutas</h3>
            <button class="btn" onclick="toggle('formRuta')">Agregar Ruta</button>

            <div class="formulario" id="formRuta">
                <form method="POST">
                    <input type="text" name="origen" placeholder="Origen" required>
                    <input type="text" name="destino" placeholder="Destino" required>
                    <input type="text" name="hora" placeholder="Hora" required>
                    <button class="btn" name="agregar_ruta">Guardar</button>
                </form>
            </div>

            <div class="lista">
                <?php foreach($_SESSION['rutas'] as $r): ?>
                    <p>🚌 <?php echo $r['origen']." → ".$r['destino']." (".$r['hora'].")"; ?></p>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CONFIG -->
        <div class="card">
            <h3>Configuración</h3>
            <p>Opciones generales del sistema.</p>
        </div>

    </div>
</div>

</body>
</html>