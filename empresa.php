<?php
session_start();
require_once(__DIR__ . "/conexion.php");

if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'empresa') {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$error = "";
$idUsuario = (int)$_SESSION['id_usuario'];
$idRutaSeleccionada = isset($_GET['ruta']) ? (int)$_GET['ruta'] : 0;
$rutaSeleccionada = null;

function limpiar($dato) {
    return htmlspecialchars((string)$dato, ENT_QUOTES, 'UTF-8');
}

function normalizarHora($hora) {
    $hora = trim((string)$hora);

    if ($hora === "") {
        return "";
    }

    // Si viene como 08:30, convertir a 08:30:00
    if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
        return $hora . ":00";
    }

    return $hora;
}

try {
    // OBTENER EMPRESA DEL USUARIO
    $sqlEmpresa = "SELECT IdEmpresa, IdUsuario, NombreEmpresa, NIT, Direccion, Telefono, Ciudad, CorreoEmpresa, NombreContacto, Estado
                   FROM Empresas
                   WHERE IdUsuario = :id_usuario";
    $stmtEmpresa = $conn->prepare($sqlEmpresa);
    $stmtEmpresa->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
    $stmtEmpresa->execute();
    $empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) {
        die("No se encontró una empresa asociada a este usuario.");
    }

    $idEmpresa = (int)$empresa['IdEmpresa'];

    // ACTUALIZAR EMPRESA
    if (isset($_POST['actualizar_empresa'])) {
        $nombreEmpresa = trim($_POST['nombre_empresa'] ?? '');
        $nit = trim($_POST['nit'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $ciudad = trim($_POST['ciudad'] ?? '');
        $correoEmpresa = trim($_POST['correo_empresa'] ?? '');
        $nombreContacto = trim($_POST['nombre_contacto'] ?? '');

        if ($nombreEmpresa === "" || $nit === "" || $direccion === "" || $telefono === "" || $ciudad === "") {
            throw new Exception("Completa los datos obligatorios de la empresa.");
        }

        $sqlUpdateEmpresa = "UPDATE Empresas
                             SET NombreEmpresa = :nombre_empresa,
                                 NIT = :nit,
                                 Direccion = :direccion,
                                 Telefono = :telefono,
                                 Ciudad = :ciudad,
                                 CorreoEmpresa = :correo_empresa,
                                 NombreContacto = :nombre_contacto
                             WHERE IdEmpresa = :id_empresa";

        $stmtUpdateEmpresa = $conn->prepare($sqlUpdateEmpresa);
        $stmtUpdateEmpresa->bindValue(':nombre_empresa', $nombreEmpresa);
        $stmtUpdateEmpresa->bindValue(':nit', $nit);
        $stmtUpdateEmpresa->bindValue(':direccion', $direccion);
        $stmtUpdateEmpresa->bindValue(':telefono', $telefono);
        $stmtUpdateEmpresa->bindValue(':ciudad', $ciudad);
        $stmtUpdateEmpresa->bindValue(':correo_empresa', $correoEmpresa);
        $stmtUpdateEmpresa->bindValue(':nombre_contacto', $nombreContacto);
        $stmtUpdateEmpresa->bindValue(':id_empresa', $idEmpresa, PDO::PARAM_INT);
        $stmtUpdateEmpresa->execute();

        $mensaje = "Información de la empresa actualizada correctamente.";

        $stmtEmpresa->execute();
        $empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);
    }

    // CREAR RUTA
    if (isset($_POST['crear_ruta'])) {
        $nombreRuta = trim($_POST['nombre_ruta'] ?? '');
        $horaInicio = normalizarHora($_POST['hora_inicio'] ?? '');
        $horaFin = normalizarHora($_POST['hora_fin'] ?? '');
        $descripcionRuta = trim($_POST['descripcion_ruta'] ?? '');
        $municipiosSeleccionados = isset($_POST['municipios']) && is_array($_POST['municipios'])
            ? $_POST['municipios']
            : [];

        if ($nombreRuta === "" || $horaInicio === "" || $horaFin === "" || $descripcionRuta === "") {
            throw new Exception("Todos los campos principales de la ruta son obligatorios.");
        }

        if (count($municipiosSeleccionados) < 2) {
            throw new Exception("Debes seleccionar al menos dos municipios para la ruta.");
        }

        if ($horaInicio >= $horaFin) {
            throw new Exception("La hora de inicio debe ser menor que la hora de fin.");
        }

        $conn->beginTransaction();

        $sqlInsertRuta = "INSERT INTO Rutas (IdEmpresa, NombreRuta, HoraInicio, HoraFin)
                          OUTPUT INSERTED.IdRuta
                          VALUES (:id_empresa, :nombre_ruta, CONVERT(time, :hora_inicio), CONVERT(time, :hora_fin))";

        $stmtInsertRuta = $conn->prepare($sqlInsertRuta);
        $stmtInsertRuta->bindValue(':id_empresa', $idEmpresa, PDO::PARAM_INT);
        $stmtInsertRuta->bindValue(':nombre_ruta', $nombreRuta);
        $stmtInsertRuta->bindValue(':hora_inicio', $horaInicio);
        $stmtInsertRuta->bindValue(':hora_fin', $horaFin);
        $stmtInsertRuta->execute();

        $idRuta = (int)$stmtInsertRuta->fetchColumn();

        if ($idRuta <= 0) {
            throw new Exception("No se pudo obtener el Id de la ruta creada.");
        }

        $sqlInsertDetalle = "INSERT INTO RutaDetalle (IdRuta, DescripcionRuta)
                             VALUES (:id_ruta, :descripcion)";
        $stmtInsertDetalle = $conn->prepare($sqlInsertDetalle);
        $stmtInsertDetalle->bindValue(':id_ruta', $idRuta, PDO::PARAM_INT);
        $stmtInsertDetalle->bindValue(':descripcion', $descripcionRuta);
        $stmtInsertDetalle->execute();

        $sqlInsertMunicipioRuta = "INSERT INTO RutaMunicipios (IdRuta, IdMunicipio, OrdenRecorrido)
                                   VALUES (:id_ruta, :id_municipio, :orden)";
        $stmtInsertMunicipioRuta = $conn->prepare($sqlInsertMunicipioRuta);

        $ordenRecorrido = 1;

        foreach ($municipiosSeleccionados as $idMunicipio) {
            $idMunicipio = (int)$idMunicipio;

            if ($idMunicipio <= 0) {
                continue;
            }

            $stmtInsertMunicipioRuta->bindValue(':id_ruta', $idRuta, PDO::PARAM_INT);
            $stmtInsertMunicipioRuta->bindValue(':id_municipio', $idMunicipio, PDO::PARAM_INT);
            $stmtInsertMunicipioRuta->bindValue(':orden', $ordenRecorrido, PDO::PARAM_INT);
            $stmtInsertMunicipioRuta->execute();

            $ordenRecorrido++;
        }

        if (isset($_POST['nombre_parada']) && is_array($_POST['nombre_parada'])) {
            $sqlInsertParada = "INSERT INTO ParadasRuta (IdRuta, NombreParada, DireccionReferencia, Observaciones, OrdenParada)
                                VALUES (:id_ruta, :nombre_parada, :direccion_referencia, :observaciones, :orden_parada)";
            $stmtInsertParada = $conn->prepare($sqlInsertParada);

            $nombresParada = $_POST['nombre_parada'];
            $direccionesParada = $_POST['direccion_parada'] ?? [];
            $observacionesParada = $_POST['observacion_parada'] ?? [];

            $ordenParada = 1;

            for ($i = 0; $i < count($nombresParada); $i++) {
                $nombreParada = trim($nombresParada[$i] ?? '');
                $direccionReferencia = trim($direccionesParada[$i] ?? '');
                $observaciones = trim($observacionesParada[$i] ?? '');

                if ($nombreParada !== "") {
                    $stmtInsertParada->bindValue(':id_ruta', $idRuta, PDO::PARAM_INT);
                    $stmtInsertParada->bindValue(':nombre_parada', $nombreParada);
                    $stmtInsertParada->bindValue(':direccion_referencia', $direccionReferencia);
                    $stmtInsertParada->bindValue(':observaciones', $observaciones);
                    $stmtInsertParada->bindValue(':orden_parada', $ordenParada, PDO::PARAM_INT);
                    $stmtInsertParada->execute();

                    $ordenParada++;
                }
            }
        }

        $conn->commit();
        $mensaje = "Ruta creada correctamente.";
    }

    // ACTUALIZAR RUTA
    if (isset($_POST['actualizar_ruta'])) {
        $idRuta = (int)($_POST['id_ruta'] ?? 0);
        $nombreRuta = trim($_POST['nombre_ruta'] ?? '');
        $horaInicio = normalizarHora($_POST['hora_inicio'] ?? '');
        $horaFin = normalizarHora($_POST['hora_fin'] ?? '');
        $descripcionRuta = trim($_POST['descripcion_ruta'] ?? '');
        $municipiosSeleccionados = isset($_POST['municipios']) && is_array($_POST['municipios'])
            ? $_POST['municipios']
            : [];

        if ($idRuta <= 0) {
            throw new Exception("Ruta inválida.");
        }

        if ($nombreRuta === "" || $horaInicio === "" || $horaFin === "" || $descripcionRuta === "") {
            throw new Exception("Todos los campos principales de la ruta son obligatorios.");
        }

        if (count($municipiosSeleccionados) < 2) {
            throw new Exception("Debes seleccionar al menos dos municipios para la ruta.");
        }

        if ($horaInicio >= $horaFin) {
            throw new Exception("La hora de inicio debe ser menor que la hora de fin.");
        }

        $sqlValidarRuta = "SELECT IdRuta FROM Rutas WHERE IdRuta = :id_ruta AND IdEmpresa = :id_empresa";
        $stmtValidarRuta = $conn->prepare($sqlValidarRuta);
        $stmtValidarRuta->bindValue(':id_ruta', $idRuta, PDO::PARAM_INT);
        $stmtValidarRuta->bindValue(':id_empresa', $idEmpresa, PDO::PARAM_INT);
        $stmtValidarRuta->execute();

        if (!$stmtValidarRuta->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception("No puedes editar una ruta que no pertenece a tu empresa.");
        }

        $conn->beginTransaction();

        $sqlUpdateRuta = "UPDATE Rutas
                          SET NombreRuta = :nombre_ruta,
                              HoraInicio = CONVERT(time, :hora_inicio),
                              HoraFin = CONVERT(time, :hora_fin)
                          WHERE IdRuta = :id_ruta AND IdEmpresa = :id_empresa";

        $stmtUpdateRuta = $conn->prepare($sqlUpdateRuta);
        $stmtUpdateRuta->bindValue(':nombre_ruta', $nombreRuta);
        $stmtUpdateRuta->bindValue(':hora_inicio', $horaInicio);
        $stmtUpdateRuta->bindValue(':hora_fin', $horaFin);
        $stmtUpdateRuta->bindValue(':id_ruta', $idRuta, PDO::PARAM_INT);
        $stmtUpdateRuta->bindValue(':id_empresa', $idEmpresa, PDO::PARAM_INT);
        $stmtUpdateRuta->execute();

        $sqlExisteDetalle = "SELECT IdRutaDetalle FROM RutaDetalle WHERE IdRuta = :id_ruta";
        $stmtExisteDetalle = $conn->prepare($sqlExisteDetalle);
        $stmtExisteDetalle->bindValue(':id_ruta', $idRuta, PDO::PARAM_INT);
        $stmtExisteDetalle->execute();
        $detalleExiste = $stmtExisteDetalle->fetch(PDO::FETCH_ASSOC);

        if ($detalleExiste) {
            $sqlUpdateDetalle = "UPDATE RutaDetalle
                                 SET DescripcionRuta = :descripcion
                                 WHERE IdRuta = :id_ruta";
            $stmtUpdateDetalle = $conn->prepare($sqlUpdateDetalle);
            $stmtUpdateDetalle->bindValue(':descripcion', $descripcionRuta);
            $stmtUpdateDetalle->bindValue(':id_ruta', $idRuta, PDO::PARAM_INT);
            $stmtUpdateDetalle->execute();
        } else {
            $sqlInsertDetalle = "INSERT INTO RutaDetalle (IdRuta, DescripcionRuta)
                                 VALUES (:id_ruta, :descripcion)";
            $stmtInsertDetalle = $conn->prepare($sqlInsertDetalle);
            $stmtInsertDetalle->bindValue(':id_ruta', $idRuta, PDO::PARAM_INT);
            $stmtInsertDetalle->bindValue(':descripcion', $descripcionRuta);
            $stmtInsertDetalle->execute();
        }

        $sqlDeleteMunicipiosRuta = "DELETE FROM RutaMunicipios WHERE IdRuta = :id_ruta";
        $stmtDeleteMunicipiosRuta = $conn->prepare($sqlDeleteMunicipiosRuta);
        $stmtDeleteMunicipiosRuta->bindValue(':id_ruta', $idRuta, PDO::PARAM_INT);
        $stmtDeleteMunicipiosRuta->execute();

        $sqlInsertMunicipioRuta = "INSERT INTO RutaMunicipios (IdRuta, IdMunicipio, OrdenRecorrido)
                                   VALUES (:id_ruta, :id_municipio, :orden)";
        $stmtInsertMunicipioRuta = $conn->prepare($sqlInsertMunicipioRuta);

        $ordenRecorrido = 1;

        foreach ($municipiosSeleccionados as $idMunicipio) {
            $idMunicipio = (int)$idMunicipio;

            if ($idMunicipio <= 0) {
                continue;
            }

            $stmtInsertMunicipioRuta->bindValue(':id_ruta', $idRuta, PDO::PARAM_INT);
            $stmtInsertMunicipioRuta->bindValue(':id_municipio', $idMunicipio, PDO::PARAM_INT);
            $stmtInsertMunicipioRuta->bindValue(':orden', $ordenRecorrido, PDO::PARAM_INT);
            $stmtInsertMunicipioRuta->execute();

            $ordenRecorrido++;
        }

        $sqlDeleteParadas = "DELETE FROM ParadasRuta WHERE IdRuta = :id_ruta";
        $stmtDeleteParadas = $conn->prepare($sqlDeleteParadas);
        $stmtDeleteParadas->bindValue(':id_ruta', $idRuta, PDO::PARAM_INT);
        $stmtDeleteParadas->execute();

        if (isset($_POST['nombre_parada']) && is_array($_POST['nombre_parada'])) {
            $sqlInsertParada = "INSERT INTO ParadasRuta (IdRuta, NombreParada, DireccionReferencia, Observaciones, OrdenParada, Estado)
                                VALUES (:id_ruta, :nombre_parada, :direccion_referencia, :observaciones, :orden_parada, 1)";
            $stmtInsertParada = $conn->prepare($sqlInsertParada);

            $nombresParada = $_POST['nombre_parada'];
            $direccionesParada = $_POST['direccion_parada'] ?? [];
            $observacionesParada = $_POST['observacion_parada'] ?? [];

            $ordenParada = 1;

            for ($i = 0; $i < count($nombresParada); $i++) {
                $nombreParada = trim($nombresParada[$i] ?? '');
                $direccionReferencia = trim($direccionesParada[$i] ?? '');
                $observaciones = trim($observacionesParada[$i] ?? '');

                if ($nombreParada !== "") {
                    $stmtInsertParada->bindValue(':id_ruta', $idRuta, PDO::PARAM_INT);
                    $stmtInsertParada->bindValue(':nombre_parada', $nombreParada);
                    $stmtInsertParada->bindValue(':direccion_referencia', $direccionReferencia);
                    $stmtInsertParada->bindValue(':observaciones', $observaciones);
                    $stmtInsertParada->bindValue(':orden_parada', $ordenParada, PDO::PARAM_INT);
                    $stmtInsertParada->execute();

                    $ordenParada++;
                }
            }
        }

        $conn->commit();

        $mensaje = "Ruta actualizada correctamente.";
        $idRutaSeleccionada = $idRuta;
    }

    // ELIMINAR RUTA
    if (isset($_POST['eliminar_ruta'])) {
        $idRutaEliminar = (int)($_POST['id_ruta'] ?? 0);

        if ($idRutaEliminar <= 0) {
            throw new Exception("Ruta inválida.");
        }

        $sqlValidarRuta = "SELECT IdRuta FROM Rutas WHERE IdRuta = :id_ruta AND IdEmpresa = :id_empresa";
        $stmtValidarRuta = $conn->prepare($sqlValidarRuta);
        $stmtValidarRuta->bindValue(':id_ruta', $idRutaEliminar, PDO::PARAM_INT);
        $stmtValidarRuta->bindValue(':id_empresa', $idEmpresa, PDO::PARAM_INT);
        $stmtValidarRuta->execute();

        if (!$stmtValidarRuta->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception("No puedes eliminar una ruta que no pertenece a tu empresa.");
        }

        $sqlEliminarRuta = "DELETE FROM Rutas WHERE IdRuta = :id_ruta AND IdEmpresa = :id_empresa";
        $stmtEliminarRuta = $conn->prepare($sqlEliminarRuta);
        $stmtEliminarRuta->bindValue(':id_ruta', $idRutaEliminar, PDO::PARAM_INT);
        $stmtEliminarRuta->bindValue(':id_empresa', $idEmpresa, PDO::PARAM_INT);
        $stmtEliminarRuta->execute();

        $mensaje = "Ruta eliminada correctamente.";

        if ($idRutaSeleccionada === $idRutaEliminar) {
            $idRutaSeleccionada = 0;
        }
    }

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    $error = "Error de base de datos: " . $e->getMessage();

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    $error = $e->getMessage();
}

// CARGAR MUNICIPIOS
$sqlMunicipios = "SELECT IdMunicipio, NombreMunicipio, Departamento
                  FROM Municipios
                  WHERE Estado = 1
                  ORDER BY Departamento, NombreMunicipio";
$stmtMunicipios = $conn->query($sqlMunicipios);
$municipios = $stmtMunicipios->fetchAll(PDO::FETCH_ASSOC);

// CARGAR RUTAS
$sqlRutas = "SELECT r.IdRuta, r.NombreRuta, r.HoraInicio, r.HoraFin, r.Estado, rd.DescripcionRuta
             FROM Rutas r
             LEFT JOIN RutaDetalle rd ON r.IdRuta = rd.IdRuta
             WHERE r.IdEmpresa = :id_empresa
             ORDER BY r.IdRuta DESC";
$stmtRutas = $conn->prepare($sqlRutas);
$stmtRutas->bindValue(':id_empresa', $idEmpresa, PDO::PARAM_INT);
$stmtRutas->execute();
$rutas = $stmtRutas->fetchAll(PDO::FETCH_ASSOC);

// CARGAR RUTA SELECCIONADA
$municipiosSeleccionadosRuta = [];
$paradasSeleccionadasRuta = [];

if ($idRutaSeleccionada > 0) {
    $sqlRutaSel = "SELECT r.IdRuta, r.NombreRuta, r.HoraInicio, r.HoraFin, r.Estado, rd.DescripcionRuta
                   FROM Rutas r
                   LEFT JOIN RutaDetalle rd ON r.IdRuta = rd.IdRuta
                   WHERE r.IdRuta = :id_ruta AND r.IdEmpresa = :id_empresa";
    $stmtRutaSel = $conn->prepare($sqlRutaSel);
    $stmtRutaSel->bindValue(':id_ruta', $idRutaSeleccionada, PDO::PARAM_INT);
    $stmtRutaSel->bindValue(':id_empresa', $idEmpresa, PDO::PARAM_INT);
    $stmtRutaSel->execute();
    $rutaSeleccionada = $stmtRutaSel->fetch(PDO::FETCH_ASSOC);

    if ($rutaSeleccionada) {
        $sqlMunicipiosSel = "SELECT IdMunicipio
                             FROM RutaMunicipios
                             WHERE IdRuta = :id_ruta
                             ORDER BY OrdenRecorrido";
        $stmtMunicipiosSel = $conn->prepare($sqlMunicipiosSel);
        $stmtMunicipiosSel->bindValue(':id_ruta', $idRutaSeleccionada, PDO::PARAM_INT);
        $stmtMunicipiosSel->execute();
        $municipiosSeleccionadosRuta = $stmtMunicipiosSel->fetchAll(PDO::FETCH_COLUMN);

        $sqlParadasSel = "SELECT NombreParada, DireccionReferencia, Observaciones
                          FROM ParadasRuta
                          WHERE IdRuta = :id_ruta
                          ORDER BY OrdenParada";
        $stmtParadasSel = $conn->prepare($sqlParadasSel);
        $stmtParadasSel->bindValue(':id_ruta', $idRutaSeleccionada, PDO::PARAM_INT);
        $stmtParadasSel->execute();
        $paradasSeleccionadasRuta = $stmtParadasSel->fetchAll(PDO::FETCH_ASSOC);
    }
}

// MUNICIPIOS POR RUTA
$municipiosPorRuta = [];
$sqlMunicipiosRuta = "SELECT rm.IdRuta, rm.OrdenRecorrido, m.NombreMunicipio
                      FROM RutaMunicipios rm
                      INNER JOIN Municipios m ON rm.IdMunicipio = m.IdMunicipio
                      INNER JOIN Rutas r ON rm.IdRuta = r.IdRuta
                      WHERE r.IdEmpresa = :id_empresa
                      ORDER BY rm.IdRuta, rm.OrdenRecorrido";
$stmtMunicipiosRuta = $conn->prepare($sqlMunicipiosRuta);
$stmtMunicipiosRuta->bindValue(':id_empresa', $idEmpresa, PDO::PARAM_INT);
$stmtMunicipiosRuta->execute();
$rowsMunicipiosRuta = $stmtMunicipiosRuta->fetchAll(PDO::FETCH_ASSOC);

foreach ($rowsMunicipiosRuta as $row) {
    $municipiosPorRuta[$row['IdRuta']][] = $row['NombreMunicipio'];
}

// PARADAS POR RUTA
$paradasPorRuta = [];
$sqlParadasRuta = "SELECT p.IdRuta, p.OrdenParada, p.NombreParada, p.DireccionReferencia, p.Observaciones
                   FROM ParadasRuta p
                   INNER JOIN Rutas r ON p.IdRuta = r.IdRuta
                   WHERE r.IdEmpresa = :id_empresa
                   ORDER BY p.IdRuta, p.OrdenParada";
$stmtParadasRuta = $conn->prepare($sqlParadasRuta);
$stmtParadasRuta->bindValue(':id_empresa', $idEmpresa, PDO::PARAM_INT);
$stmtParadasRuta->execute();
$rowsParadasRuta = $stmtParadasRuta->fetchAll(PDO::FETCH_ASSOC);

foreach ($rowsParadasRuta as $row) {
    $paradasPorRuta[$row['IdRuta']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Empresa</title>
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

        .container {
            padding: 30px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #4da6ff;
        }

        h2, h3 {
            color: #4da6ff;
        }

        .mensaje {
            background: #1f5f2c;
            color: #d4ffd9;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #6b1d1d;
            color: #ffd4d4;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 20px;
        }

        .panel, .bloque {
            background: #1e1e1e;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
        }

        .lista {
            max-height: 700px;
            overflow-y: auto;
        }

        .item {
            background: #2a2a2a;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .item a {
            text-decoration: none;
            color: white;
        }

        .bloques {
            display: grid;
            gap: 20px;
        }

        .fila-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        label {
            display: block;
            margin: 8px 0 4px;
            color: #ccc;
            font-size: 14px;
        }

        input, textarea, select {
            width: 100%;
            padding: 10px;
            margin: 6px 0 12px 0;
            border: none;
            border-radius: 8px;
            background: #2a2a2a;
            color: white;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        .municipios-check {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .municipio-item {
            background: #252525;
            padding: 10px;
            border-radius: 8px;
        }

        .municipio-item label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            margin: 0;
        }

        .municipio-item input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .parada-group {
            background: #252525;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 12px;
        }

        .small {
            font-size: 13px;
            color: #aaa;
        }

        .btn {
            margin-top: 8px;
            padding: 10px 14px;
            border: none;
            border-radius: 8px;
            background: #4da6ff;
            color: white;
            cursor: pointer;
        }

        .btn-danger {
            background: #d9534f;
        }

        .btn-warning {
            background: #f0ad4e;
            color: #111;
        }

        .acciones {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .separador {
            margin: 25px 0 10px;
            border-top: 1px solid #333;
        }

        .ruta-box {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 15px;
        }

        .ruta-box p {
            margin: 6px 0;
            color: #ddd;
        }

        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .fila-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        function agregarParada() {
            const container = document.getElementById('contenedor-paradas');
            const div = document.createElement('div');
            div.className = 'parada-group';
            div.innerHTML = `
                <input type="text" name="nombre_parada[]" placeholder="Nombre de la parada">
                <input type="text" name="direccion_parada[]" placeholder="Dirección o referencia">
                <textarea name="observacion_parada[]" placeholder="Observaciones"></textarea>
            `;
            container.appendChild(div);
        }
    </script>
</head>

<body>

<div class="navbar">
    <img src="logo.jpg" class="logo" alt="Logo">
    <div class="nav-buttons">
        <a href="index.php"><button type="button">Inicio</button></a>
        <a href="logout.php"><button type="button">Cerrar sesión</button></a>
    </div>
</div>

<div class="container">
    <h1>Panel de Empresa</h1>

    <?php if ($mensaje !== ""): ?>
        <div class="mensaje"><?php echo limpiar($mensaje); ?></div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="error"><?php echo limpiar($error); ?></div>
    <?php endif; ?>

    <div class="layout">

        <!-- LISTADO -->
        <div class="panel">
            <h3>Mis rutas</h3>
            <div class="lista">
                <?php if (count($rutas) > 0): ?>
                    <?php foreach ($rutas as $ruta): ?>
                        <div class="item">
                            <a href="empresa.php?ruta=<?php echo $ruta['IdRuta']; ?>">
                                <strong><?php echo limpiar($ruta['NombreRuta']); ?></strong><br>
                                <small>
                                    <?php echo limpiar(substr((string)$ruta['HoraInicio'], 0, 5)); ?> -
                                    <?php echo limpiar(substr((string)$ruta['HoraFin'], 0, 5)); ?>
                                </small>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No has registrado rutas todavía.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- CONTENIDO -->
        <div class="bloques">

            <!-- DATOS EMPRESA -->
            <div class="bloque">
                <h2>Información de la empresa</h2>

                <form method="POST">
                    <label for="nombre_empresa">Nombre de la empresa</label>
                    <input type="text" name="nombre_empresa" id="nombre_empresa" required
                           value="<?php echo limpiar($empresa['NombreEmpresa']); ?>">

                    <div class="fila-2">
                        <div>
                            <label for="nit">NIT</label>
                            <input type="text" name="nit" id="nit" required
                                   value="<?php echo limpiar($empresa['NIT']); ?>">
                        </div>
                        <div>
                            <label for="telefono">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" required
                                   value="<?php echo limpiar($empresa['Telefono']); ?>">
                        </div>
                    </div>

                    <label for="direccion">Dirección</label>
                    <input type="text" name="direccion" id="direccion" required
                           value="<?php echo limpiar($empresa['Direccion']); ?>">

                    <div class="fila-2">
                        <div>
                            <label for="ciudad">Ciudad</label>
                            <input type="text" name="ciudad" id="ciudad" required
                                   value="<?php echo limpiar($empresa['Ciudad']); ?>">
                        </div>
                        <div>
                            <label for="correo_empresa">Correo empresa</label>
                            <input type="email" name="correo_empresa" id="correo_empresa"
                                   value="<?php echo limpiar($empresa['CorreoEmpresa']); ?>">
                        </div>
                    </div>

                    <label for="nombre_contacto">Nombre de contacto</label>
                    <input type="text" name="nombre_contacto" id="nombre_contacto"
                           value="<?php echo limpiar($empresa['NombreContacto']); ?>">

                    <div class="acciones">
                        <button type="submit" class="btn" name="actualizar_empresa">Guardar cambios</button>
                        <button type="reset" class="btn btn-warning">Limpiar campos</button>
                    </div>
                </form>
            </div>

            <!-- CRUD RUTA -->
            <div class="bloque">
                <h2><?php echo $rutaSeleccionada ? 'Editar ruta' : 'Crear nueva ruta'; ?></h2>

                <form method="POST">
                    <?php if ($rutaSeleccionada): ?>
                        <input type="hidden" name="id_ruta" value="<?php echo $rutaSeleccionada['IdRuta']; ?>">
                    <?php endif; ?>

                    <label for="nombre_ruta">Nombre de la ruta</label>
                    <input type="text" name="nombre_ruta" id="nombre_ruta" required
                           value="<?php echo $rutaSeleccionada ? limpiar($rutaSeleccionada['NombreRuta']) : ''; ?>">

                    <div class="fila-2">
                        <div>
                            <label for="hora_inicio">Hora de inicio</label>
                            <input type="time" name="hora_inicio" id="hora_inicio" required
                                   value="<?php echo $rutaSeleccionada ? substr((string)$rutaSeleccionada['HoraInicio'], 0, 5) : ''; ?>">
                        </div>
                        <div>
                            <label for="hora_fin">Hora de fin</label>
                            <input type="time" name="hora_fin" id="hora_fin" required
                                   value="<?php echo $rutaSeleccionada ? substr((string)$rutaSeleccionada['HoraFin'], 0, 5) : ''; ?>">
                        </div>
                    </div>

                    <label for="descripcion_ruta">Descripción de la ruta</label>
                    <textarea name="descripcion_ruta" id="descripcion_ruta" required><?php echo $rutaSeleccionada ? limpiar($rutaSeleccionada['DescripcionRuta']) : ''; ?></textarea>

                    <label>Municipios del recorrido</label>
                    <p class="small">Márcalos en el orden en que quieras que quede el recorrido.</p>

                    <div class="municipios-check">
                        <?php foreach ($municipios as $m): ?>
                            <div class="municipio-item">
                                <label>
                                    <input type="checkbox" name="municipios[]" value="<?php echo $m['IdMunicipio']; ?>"
                                        <?php echo in_array($m['IdMunicipio'], $municipiosSeleccionadosRuta) ? 'checked' : ''; ?>>
                                    <?php echo limpiar($m['NombreMunicipio'] . " - " . $m['Departamento']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <label>Paradas de la ruta</label>
                    <p class="small">Puedes escribir paradas libres, como calles, puentes, parques o referencias.</p>

                    <div id="contenedor-paradas">
                        <?php if ($rutaSeleccionada && count($paradasSeleccionadasRuta) > 0): ?>
                            <?php foreach ($paradasSeleccionadasRuta as $parada): ?>
                                <div class="parada-group">
                                    <input type="text" name="nombre_parada[]" placeholder="Nombre de la parada"
                                           value="<?php echo limpiar($parada['NombreParada']); ?>">
                                    <input type="text" name="direccion_parada[]" placeholder="Dirección o referencia"
                                           value="<?php echo limpiar($parada['DireccionReferencia']); ?>">
                                    <textarea name="observacion_parada[]" placeholder="Observaciones"><?php echo limpiar($parada['Observaciones']); ?></textarea>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="parada-group">
                                <input type="text" name="nombre_parada[]" placeholder="Nombre de la parada">
                                <input type="text" name="direccion_parada[]" placeholder="Dirección o referencia">
                                <textarea name="observacion_parada[]" placeholder="Observaciones"></textarea>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="button" class="btn" onclick="agregarParada()">Agregar otra parada</button>

                    <div class="acciones">
                        <?php if ($rutaSeleccionada): ?>
                            <button type="submit" class="btn" name="actualizar_ruta">Actualizar ruta</button>
                            <button type="reset" class="btn btn-warning">Limpiar campos</button>
                            <button type="button" class="btn btn-warning" onclick="window.location.href='empresa.php';">Nueva ruta</button>
                            <button type="submit" class="btn btn-danger" name="eliminar_ruta"
                                    onclick="return confirm('¿Seguro que deseas eliminar esta ruta?');">
                                Eliminar ruta
                            </button>
                        <?php else: ?>
                            <button type="submit" class="btn" name="crear_ruta">Guardar ruta</button>
                            <button type="reset" class="btn btn-warning">Limpiar campos</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- RESUMEN RUTAS -->
            <div class="bloque">
                <h2>Mis rutas registradas</h2>

                <?php if (count($rutas) > 0): ?>
                    <?php foreach ($rutas as $ruta): ?>
                        <div class="ruta-box">
                            <p><strong>Nombre:</strong> <?php echo limpiar($ruta['NombreRuta']); ?></p>
                            <p><strong>Horario:</strong> <?php echo limpiar(substr((string)$ruta['HoraInicio'], 0, 5)); ?> - <?php echo limpiar(substr((string)$ruta['HoraFin'], 0, 5)); ?></p>
                            <p><strong>Descripción:</strong> <?php echo limpiar($ruta['DescripcionRuta'] ?: 'Sin descripción'); ?></p>

                            <p><strong>Recorrido:</strong>
                                <?php
                                if (isset($municipiosPorRuta[$ruta['IdRuta']])) {
                                    echo limpiar(implode(' -> ', $municipiosPorRuta[$ruta['IdRuta']]));
                                } else {
                                    echo 'Sin municipios';
                                }
                                ?>
                            </p>

                            <p><strong>Paradas:</strong></p>
                            <?php if (isset($paradasPorRuta[$ruta['IdRuta']])): ?>
                                <ul>
                                    <?php foreach ($paradasPorRuta[$ruta['IdRuta']] as $parada): ?>
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
                                <p class="small">Sin paradas registradas.</p>
                            <?php endif; ?>

                            <div class="acciones">
                                <button type="button" class="btn"
                                        onclick="window.location.href='empresa.php?ruta=<?php echo $ruta['IdRuta']; ?>';">
                                    Seleccionar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No has registrado rutas todavía.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

</body>
</html>
