<?php
session_start();

$error = "";

if($_POST){
    $user = $_POST['user'];
    $pass = $_POST['pass'];

    if($user == "admin" && $pass == "admin"){
        $_SESSION['rol'] = 'admin';
        header("Location: admin.php");
        exit();
    } elseif($user == "empresa" && $pass == "empresa"){
        $_SESSION['rol'] = 'empresa';
        header("Location: empresa.php");
        exit();
    } else {
        $error = "Credenciales incorrectas";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
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

        /* CONTENEDOR LOGIN */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: calc(100vh - 80px);
        }

        .login-box {
            background: #1e1e1e;
            padding: 30px;
            border-radius: 15px;
            width: 300px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.6);
        }

        .login-box h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #4da6ff;
        }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 8px;
            background: #2a2a2a;
            color: white;
        }

        .input-group input:focus {
            outline: none;
            background: #333;
        }

        .btn-login {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 8px;
            background: #4da6ff;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-login:hover {
            background: #3399ff;
        }

        .error {
            color: #ff4d4d;
            text-align: center;
            margin-bottom: 10px;
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

<!-- LOGIN -->
<div class="login-container">
    <div class="login-box">

        <h2>Iniciar Sesión</h2>

        <?php if($error != ""): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <input type="text" name="user" placeholder="Usuario" required>
            </div>

            <div class="input-group">
                <input type="password" name="pass" placeholder="Contraseña" required>
            </div>

            <button class="btn-login" type="submit">Ingresar</button>
        </form>

    </div>
</div>

</body>
</html>