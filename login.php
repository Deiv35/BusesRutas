<?php
session_start();
require_once(__DIR__ . "/conexion.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['user']);
    $pass = trim($_POST['pass']);

    try {
        $sql = "SELECT IdUsuario, TipoUsuario, NombreUsuario, Correo, Contra, Estado
                FROM Usuarios
                WHERE (NombreUsuario = :usuario OR Correo = :correo)
                AND Estado = 1";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':usuario', $user, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $user, PDO::PARAM_STR);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            if ($pass === trim($usuario['Contra'])) {
                $_SESSION['id_usuario'] = $usuario['IdUsuario'];
                $_SESSION['usuario'] = $usuario['NombreUsuario'];
                $_SESSION['rol'] = $usuario['TipoUsuario'];
                $_SESSION['correo'] = $usuario['Correo'];

                if ($usuario['TipoUsuario'] === 'admin') {
                    header("Location: admin.php");
                    exit();
                } elseif ($usuario['TipoUsuario'] === 'empresa') {
                    header("Location: empresa.php");
                    exit();
                } else {
                    $error = "El tipo de usuario no es válido.";
                }
            } else {
                $error = "Credenciales incorrectas.";
            }
        } else {
            $error = "Credenciales incorrectas.";
        }
    } catch (PDOException $e) {
        $error = "Error al iniciar sesión: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
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
            width: 320px;
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

        .input-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            color: #ccc;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 8px;
            background: #2a2a2a;
            color: white;
            box-sizing: border-box;
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

<div class="navbar">
    <img src="logo.jpg" class="logo" alt="Logo">
    <div class="nav-buttons">
        <a href="index.php"><button type="button">Inicio</button></a>
    </div>
</div>

<div class="login-container">
    <div class="login-box">
        <h2>Iniciar Sesión</h2>

        <?php if ($error != ""): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label for="user">Usuario o correo</label>
                <input
                    type="text"
                    id="user"
                    name="user"
                    placeholder="Ej: usuario123 o correo@gmail.com"
                    required
                >
            </div>

            <div class="input-group">
                <label for="pass">Contraseña</label>
                <input
                    type="password"
                    id="pass"
                    name="pass"
                    placeholder="Ingresa tu contraseña"
                    required
                >
            </div>

            <button class="btn-login" type="submit">Ingresar</button>
        </form>
    </div>
</div>

</body>
</html>