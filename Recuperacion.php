<?php
require_once "conexion.php";

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $correo = trim($_POST["correo"]);
    $nuevaContrasena = $_POST["nuevaContrasena"];
    $confirmarContrasena = $_POST["confirmarContrasena"];

    if ($nuevaContrasena !== $confirmarContrasena) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipoMensaje = "error";
    } else {

        $sqlBuscar = "
            SELECT IdUsuario 
            FROM dbo.Usuarios 
            WHERE Correo = :correo 
              AND Estado = 1
        ";

        $stmtBuscar = $conexion->prepare($sqlBuscar);
        $stmtBuscar->bindParam(":correo", $correo);
        $stmtBuscar->execute();

        $usuario = $stmtBuscar->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {

            $passwordHash = password_hash($nuevaContrasena, PASSWORD_DEFAULT);

            $sqlActualizar = "
                UPDATE dbo.Usuarios
                SET PasswordHash = :passwordHash
                WHERE Correo = :correo
                  AND Estado = 1
            ";

            $stmtActualizar = $conexion->prepare($sqlActualizar);
            $stmtActualizar->bindParam(":passwordHash", $passwordHash);
            $stmtActualizar->bindParam(":correo", $correo);
            $stmtActualizar->execute();

            $mensaje = "Contraseña actualizada correctamente.";
            $tipoMensaje = "success";
        } else {
            $mensaje = "No existe un usuario activo con ese correo.";
            $tipoMensaje = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top left, #0b3a66 0%, transparent 35%),
                linear-gradient(135deg, #05070d, #07111f 55%, #02040a);
            color: white;
            min-height: 100vh;
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

        .container {
            width: 100%;
            max-width: 450px;
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
            margin-bottom: 20px;
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
            outline: none;
        }

        input:focus {
            border-color: #1f8bff;
            box-shadow: 0 0 0 2px rgba(31, 139, 255, 0.2);
        }

        .password-box {
            display: flex;
            gap: 10px;
        }

        .password-box input {
            flex: 1;
        }

        .btn {
            padding: 10px 16px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #006eff, #003d99);
            color: white;
            width: 100%;
        }

        .btn-secondary {
            background: #1b2638;
            border: 1px solid #365b86;
            color: white;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(0, 110, 255, 0.25);
        }

        .message {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 18px;
            text-align: center;
            font-weight: bold;
        }

        .message.error {
            background: rgba(255, 59, 59, 0.15);
            border: 1px solid #ff3b3b;
            color: #ff9b9b;
        }

        .message.success {
            background: rgba(0, 184, 148, 0.15);
            border: 1px solid #00b894;
            color: #4dffd8;
        }

        .links {
            margin-top: 18px;
            text-align: center;
        }

        .links a {
            color: #58a6ff;
            text-decoration: none;
            font-size: 14px;
        }

        .links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .container {
                width: 90%;
                margin-top: 60px;
                padding: 25px;
            }

            .topbar {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>

<div class="topbar">
    <h2>Recuperación</h2>
    <a href="index.php" class="btn btn-secondary">Volver al inicio</a>
</div>

<div class="container">

    <h2>Recuperar contraseña</h2>

    <?php if (!empty($mensaje)): ?>
        <div class="message <?php echo $tipoMensaje; ?>">
            <?php echo htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8"); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="Recuperacion.php">

        <label>Correo:</label>
        <input type="email" name="correo" required>

        <label>Nueva contraseña:</label>
        <div class="password-box">
            <input type="password" name="nuevaContrasena" id="nuevaContrasena" required>
            <button type="button" class="btn btn-secondary" id="btnToggle1" onclick="togglePassword('nuevaContrasena', 'btnToggle1')">Ver</button>
        </div>

        <label>Confirmar contraseña:</label>
        <div class="password-box">
            <input type="password" name="confirmarContrasena" id="confirmarContrasena" required>
            <button type="button" class="btn btn-secondary" id="btnToggle2" onclick="togglePassword('confirmarContrasena', 'btnToggle2')">Ver</button>
        </div>

        <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
    </form>

    <div class="links">
        <a href="IniciarSesion.php">Volver al login</a>
    </div>

</div>

<script>
function togglePassword(inputId, buttonId) {
    var input = document.getElementById(inputId);
    var btn = document.getElementById(buttonId);

    if (input.type === "password") {
        input.type = "text";
        btn.textContent = "Ocultar";
    } else {
        input.type = "password";
        btn.textContent = "Ver";
    }
}
</script>

</body>
</html>