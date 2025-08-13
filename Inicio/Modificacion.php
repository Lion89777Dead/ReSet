<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'inicio';

$error_message = '';
$success_message = '';
$datos_usuario = null;
$tipo_usuario = '';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . htmlspecialchars($conn->connect_error));
}

// Buscar usuario (por Nombre) en ambas tablas
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['buscar_usuario'])) {
    $usuario = $_POST['usuario'] ?? '';
    $tipo = $_POST['tipo'] ?? '';

    if (empty($usuario) || empty($tipo)) {
        $error_message = "Completa todos los campos para buscar.";
    } elseif ($tipo === "nino") {
        $stmt = $conn->prepare("SELECT * FROM inicio_niños WHERE Nombre = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $datos_usuario = $row;
            $tipo_usuario = "nino";
        } else {
            $error_message = "Niño no encontrado.";
        }
        $stmt->close();
    } elseif ($tipo === "padre") {
        $stmt = $conn->prepare("SELECT * FROM inicio_padres WHERE Nombre = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $datos_usuario = $row;
            $tipo_usuario = "padre";
        } else {
            $error_message = "Padre no encontrado.";
        }
        $stmt->close();
    } else {
        $error_message = "Selecciona el tipo de usuario.";
    }
}

// Modificar datos del usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modificar_usuario'])) {
    $tipo = $_POST['tipo'] ?? '';
    $nombre = $_POST['nombre'] ?? '';

    if (empty($tipo) || empty($nombre)) {
        $error_message = "No hay usuario seleccionado para modificar.";
    } else if ($tipo === "nino") {
        $contrasena = $_POST['contrasena'] ?? '';
        $genero = $_POST['genero'] ?? '';
        $edad = $_POST['edad'] ?? '';
        $padre = $_POST['padre'] ?? '';
        $madre = $_POST['madre'] ?? '';

        if (empty($contrasena) || empty($genero) || empty($edad) || empty($padre) || empty($madre)) {
            $error_message = "Todos los campos son obligatorios para modificar un niño.";
        } else {
            $stmt = $conn->prepare("UPDATE inicio_niños SET Contraseña=?, Genero=?, Edad=?, Padre=?, Madre=? WHERE Nombre=?");
            $stmt->bind_param("ssssss", $contrasena, $genero, $edad, $padre, $madre, $nombre);
            if ($stmt->execute()) {
                $success_message = '¡Datos del niño modificados exitosamente!';
            } else {
                $error_message = 'Error al modificar niño: ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    }
    elseif ($tipo === "padre") {
        $contrasena = $_POST['contrasena'] ?? '';
        $genero = $_POST['genero'] ?? '';
        $edad = $_POST['edad'] ?? '';
        $codigo_postal = $_POST['codigo_postal'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $calle = $_POST['calle'] ?? '';
        $colonia = $_POST['colonia'] ?? '';
        $numero_vivienda = $_POST['numero_vivienda'] ?? '';

        if (empty($contrasena) || empty($genero) || empty($edad) || empty($codigo_postal) || empty($telefono) || empty($calle) || empty($colonia) || empty($numero_vivienda)) {
            $error_message = "Todos los campos son obligatorios para modificar un padre.";
        } else {
            $stmt = $conn->prepare("UPDATE inicio_padres SET Contraseña=?, Genero=?, Edad=?, Codigo_Postal=?, Telefono=?, Calle=?, Colonia=?, Numero_de_Vivienda=? WHERE Nombre=?");
            $stmt->bind_param("sssssssss", $contrasena, $genero, $edad, $codigo_postal, $telefono, $calle, $colonia, $numero_vivienda, $nombre);
            if ($stmt->execute()) {
                $success_message = '¡Datos del padre modificados exitosamente!';
            } else {
                $error_message = 'Error al modificar padre: ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Usuario</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
        }
        .container {
            background-color: rgba(255,255,255,0.97);
            padding: 38px 34px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(108,99,255,0.08);
            width: 100%;
            max-width: 480px;
            text-align: center;
            backdrop-filter: blur(2px);
        }
        h2 {
            margin-bottom: 28px;
            color: #6c63ff;
            font-weight: 800;
            font-size: 2em;
            letter-spacing:0.01em;
        }
        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .input-group input, .input-group select {
            width: calc(100% - 20px);
            padding: 12px 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s, box-shadow 0.3s;
            outline: none;
        }
        .input-group input:focus, .input-group select:focus {
            border-color: #6c63ff;
            box-shadow: 0 0 8px rgba(108,99,255,0.08);
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, #6c63ff 60%, #ffb347 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.07em;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }
        button:hover {
            background: linear-gradient(90deg, #ffb347 60%, #6c63ff 100%);
            transform: scale(1.04);
        }
        .error-message {
            color: #e74c3c;
            margin-top: 18px;
            font-size: 0.96em;
            font-weight: 600;
        }
        .success-message {
            color: #27ae60;
            margin-top: 18px;
            font-size: 0.96em;
            font-weight: 600;
        }
        .volver {
            display:inline-block;
            margin-top: 20px;
            color: #6c63ff;
            font-weight: bold;
            text-decoration: underline;
            font-size: 1em;
            cursor:pointer;
            transition: color 0.2s;
        }
        .volver:hover { color:#ff7f50; }
        @media (max-width: 500px) {
            .container {
                margin: 20px;
                padding: 20px;
            }
            h2 {
                font-size: 1.3em;
            }
        }
        .btn-regresar {
  display: inline-block;
  background: linear-gradient(135deg, #ff7e5f, #feb47b);
  color: white;
  padding: 10px 20px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: bold;
  font-size: 1em;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.btn-regresar:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.btn-regresar:active {
  transform: translateY(0);
  box-shadow: none;
}
.footer {
  background-color: #000;
  color: #fff;
  text-align: center;
  padding: 15px 10px;
  font-size: 0.95em;
  border-top: 3px solid var(--accent);
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
}
footer p {
  margin: 0;  /* Quita márgenes extra */
}
    </style>
</head>
<body>
    <div class="container">
        <h2>Modificar Usuario</h2>
        <?php
        if (!empty($error_message)) {
            echo '<p class="error-message">' . htmlspecialchars($error_message) . '</p>';
        }
        if (!empty($success_message)) {
            echo '<p class="success-message">' . htmlspecialchars($success_message) . '</p>';
        }
        ?>
        <?php if (!$datos_usuario): ?>
            <form method="POST" action="Modificacion.php">
                <div class="input-group">
                    <label for="usuario">Nombre de usuario</label>
                    <input type="text" id="usuario" name="usuario" placeholder="Nombre de usuario" required>
                </div>
                <div class="input-group">
                    <label for="tipo">Tipo de usuario</label>
                    <select name="tipo" id="tipo" required>
                        <option value="">Selecciona...</option>
                        <option value="nino">Niño</option>
                        <option value="padre">Padre</option>
                    </select>
                </div>
                <button type="submit" name="buscar_usuario">Buscar usuario</button><a href="Especialista.php" class="btn-regresar">⬅ Regresar</a>

            </form>
        <?php elseif ($tipo_usuario == "nino"): ?>
            <form method="POST" action="Modificacion.php">
                <input type="hidden" name="tipo" value="nino">
                <div class="input-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($datos_usuario['Nombre']); ?>" readonly>
                </div>
                <div class="input-group">
                    <label for="contrasena">Contraseña</label>
                    <input type="text" id="contrasena" name="contrasena" value="<?php echo htmlspecialchars($datos_usuario['Contraseña']); ?>" required>
                </div>
                <div class="input-group">
                    <label for="genero">Género</label>
                    <select id="genero" name="genero" required>
                        <option value="Niño" <?php if ($datos_usuario['Genero']=="Niño") echo "selected"; ?>>Niño</option>
                        <option value="Niña" <?php if ($datos_usuario['Genero']=="Niña") echo "selected"; ?>>Niña</option>
                        <option value="Otro" <?php if ($datos_usuario['Genero']=="Otro") echo "selected"; ?>>Otro</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="edad">Edad</label>
                    <input type="number" id="edad" name="edad" value="<?php echo htmlspecialchars($datos_usuario['Edad']); ?>" min="1" max="18" required>
                </div>
                <div class="input-group">
                    <label for="padre">Padre</label>
                    <input type="text" id="padre" name="padre" value="<?php echo htmlspecialchars($datos_usuario['Padre']); ?>" required>
                </div>
                <div class="input-group">
                    <label for="madre">Madre</label>
                    <input type="text" id="madre" name="madre" value="<?php echo htmlspecialchars($datos_usuario['Madre']); ?>" required>
                </div>
                <button type="submit" name="modificar_usuario">Modificar</button>
            </form>
            <a href="Modificacion.php" class="volver">Volver a buscar otro usuario</a>
        <?php elseif ($tipo_usuario == "padre"): ?>
            <form method="POST" action="Modificacion.php">
                <input type="hidden" name="tipo" value="padre">
                <div class="input-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($datos_usuario['Nombre']); ?>" readonly>
                </div>
                <div class="input-group">
                    <label for="contrasena">Contraseña</label>
                    <input type="text" id="contrasena" name="contrasena" value="<?php echo htmlspecialchars($datos_usuario['Contraseña']); ?>" required>
                </div>
                <div class="input-group">
                    <label for="genero">Género</label>
                    <select id="genero" name="genero" required>
                        <option value="Padre" <?php if ($datos_usuario['Genero']=="Padre") echo "selected"; ?>>Padre</option>
                        <option value="Madre" <?php if ($datos_usuario['Genero']=="Madre") echo "selected"; ?>>Madre</option>
                        <option value="Otro" <?php if ($datos_usuario['Genero']=="Otro") echo "selected"; ?>>Otro</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="edad">Edad</label>
                    <input type="number" id="edad" name="edad" value="<?php echo htmlspecialchars($datos_usuario['Edad']); ?>" min="18" max="99" required>
                </div>
                <div class="input-group">
                    <label for="codigo_postal">Código Postal</label>
                    <input type="text" id="codigo_postal" name="codigo_postal" value="<?php echo htmlspecialchars($datos_usuario['Codigo_Postal']); ?>" required>
                </div>
                <div class="input-group">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($datos_usuario['Telefono']); ?>" required>
                </div>
                <div class="input-group">
                    <label for="calle">Calle</label>
                    <input type="text" id="calle" name="calle" value="<?php echo htmlspecialchars($datos_usuario['Calle']); ?>" required>
                </div>
                <div class="input-group">
                    <label for="colonia">Colonia</label>
                    <input type="text" id="colonia" name="colonia" value="<?php echo htmlspecialchars($datos_usuario['Colonia']); ?>" required>
                </div>
                <div class="input-group">
                    <label for="numero_vivienda">Número de Vivienda</label>
                    <input type="text" id="numero_vivienda" name="numero_vivienda" value="<?php echo htmlspecialchars($datos_usuario['Numero_de_Vivienda']); ?>" required>
                </div>
                <button type="submit" name="modificar_usuario">Modificar</button>
            </form>
            <a href="Modificacion.php" class="volver">Volver a buscar otro usuario</a>
        <?php endif; ?>
    </div>
    <footer class="footer">
  <p>Creado por: Edwin Abraham Hernández Rivero &nbsp;|&nbsp; Axel Abraham Esquivel Saucedo &nbsp;|&nbsp; Janemir Alexander Aguirre Rios</p>
</footer>

</body>
</html>