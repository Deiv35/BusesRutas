<?php
session_start();
require_once "conexion.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = trim($_POST["usuario"]);
    $contrasena = $_POST["contrasena"];

    $sql = "
        SELECT 
            U.IdUsuario,
            U.NombreUsuario,
            U.PasswordHash,
            T.NombreTipo,
            CE.NombreCategoria
        FROM dbo.Usuarios U
        INNER JOIN dbo.TiposUsuario T
            ON U.IdTipoUsuario = T.IdTipoUsuario
        LEFT JOIN dbo.CategoriasEmpresa CE
            ON U.IdCategoriaEmpresa = CE.IdCategoriaEmpresa
        WHERE (U.NombreUsuario = :usuario OR U.Correo = :correo)
          AND U.Estado = 1
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(":usuario", $usuario);
    $stmt->bindParam(":correo", $usuario);
    $stmt->execute();

    $usuarioDB = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuarioDB) {

        if (password_verify($contrasena, $usuarioDB["PasswordHash"])) {

            $_SESSION["idUsuario"] = $usuarioDB["IdUsuario"];
            $_SESSION["usuario"] = $usuarioDB["NombreUsuario"];
            $_SESSION["tipo"] = $usuarioDB["NombreTipo"];
            $_SESSION["categoriaEmpresa"] = $usuarioDB["NombreCategoria"];

            if ($usuarioDB["NombreTipo"] == "Administrador") {
                header("Location: Admin.php");
                exit();

            } elseif ($usuarioDB["NombreTipo"] == "Empresa") {

                if ($usuarioDB["NombreCategoria"] == "Contador") {
                    header("Location: Contador.php");
                    exit();
                } else {
                    header("Location: Empresa.php");
                    exit();
                }

            } elseif ($usuarioDB["NombreTipo"] == "Usuario Comun") {
                header("Location: Usuario.php");
                exit();
            }

        } else {
            $mensaje = "Contraseña incorrecta";
        }

    } else {
        $mensaje = "Usuario/correo no encontrado o inactivo";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>

    <style>
        body {
            margin: 0;
            font-family: Arial;
            background:
                radial-gradient(circle at top left, #0b3a66 0%, transparent 35%),
                linear-gradient(135deg, #05070d, #07111f 55%, #02040a);
            color: white;
        }

        .topbar {
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(3, 10, 20, 0.95);
            border-bottom: 1px solid #1f8bff;
        }

        .topbar h2 {
            margin: 0;
            color: #58a6ff;
        }

        .btn {
            padding: 10px 16px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #006eff, #003d99);
            color: white;
        }

        .btn-secondary {
            background: #1b2638;
            border: 1px solid #365b86;
            color: white;
        }

        .container {
            width: 100%;
            max-width: 420px;
            margin: 80px auto;
            background: rgba(8, 18, 34, 0.95);
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 0 40px rgba(0, 94, 255, 0.2);
            border: 1px solid rgba(88, 166, 255, 0.2);
        }

        h2 {
            text-align: center;
            color: #58a6ff;
        }

        label {
            color: #9ecbff;
            font-size: 14px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            margin-bottom: 15px;
            border-radius: 10px;
            border: 1px solid #234b77;
            background: #08111f;
            color: white;
        }

        .password-box {
            display: flex;
            gap: 10px;
        }

        .error {
            background: rgba(255, 59, 59, 0.15);
            border: 1px solid #ff3b3b;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 15px;
            text-align: center;
            color: #ff9b9b;
            font-weight: bold;
        }

        .links {
            margin-top: 15px;
            text-align: center;
        }

        .links a {
            display: block;
            color: #58a6ff;
            margin-top: 8px;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="topbar">
    <h2>Login</h2>
    <a href="index.php" class="btn btn-secondary">Volver</a>
</div>

<div class="container">

    <h2>Iniciar Sesión</h2>

    <?php if (!empty($mensaje)): ?>
        <div class="error"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <form method="post">

        <label>Usuario o correo:</label>
        <input type="text" name="usuario" required>

        <label>Contraseña:</label>
        <div class="password-box">
            <input type="password" name="contrasena" id="contrasena" required>
            <button type="button" class="btn-secondary" onclick="togglePassword()">Ver</button>
        </div>

        <br>

        <button type="submit" class="btn btn-primary">Ingresar</button>

    </form>

    <div class="links">
        <a href="Registrar.php">Registrarse</a>
        <a href="Recuperacion.php">¿Olvidaste tu contraseña?</a>
    </div>

</div>

<script>
function togglePassword() {
    var input = document.getElementById("contrasena");

    if (input.type === "password") {
        input.type = "text";
    } else {
        input.type = "password";
    }
}
</script>

</body>
</html>