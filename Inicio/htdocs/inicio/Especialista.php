<?php
session_start();

// Validar que el usuario sea especialista
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'especialista') {
    header("Location: login.php");
    exit;
}

// Conexión a la base de datos
$mysqli = new mysqli("localhost", "root", "", "inicio");
if ($mysqli->connect_errno) {
    die("Error de conexión: " . $mysqli->connect_error);
}

$mensaje = "";

// Registrar niño
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["registrar_nino"])) {
    $nombre = $_POST["nombre"];
    $contrasena = $_POST["contrasena"];
    $genero = $_POST["genero"];
    $edad = $_POST["edad"];
    $padre = $_POST["padre"];
    $madre = $_POST["madre"];

    // Insertar en inicio_niños
    $stmt = $mysqli->prepare("INSERT INTO inicio_niños (Nombre, Contraseña, Genero, Edad, Padre, Madre) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nombre, $contrasena, $genero, $edad, $padre, $madre);

    if ($stmt->execute()) {
        // Obtener el ID insertado
        $nuevo_id_nino = $stmt->insert_id;

        // Insertar también en la tabla niños (solo ID y Nombre)
        $stmt2 = $mysqli->prepare("INSERT INTO niños (ID, Nombre) VALUES (?, ?)");
        $stmt2->bind_param("is", $nuevo_id_nino, $nombre);
        $stmt2->execute();
        $stmt2->close();

        // Buscar todos los padres relacionados con este niño
        $res = $mysqli->prepare("SELECT id_padre FROM relacion_nino_padre WHERE id_nino = ?");
        $res->bind_param("i", $nuevo_id_nino);
        $res->execute();
        $res->bind_result($id_padre);
        while ($res->fetch()) {
            // Inserta notificación para cada padre relacionado
            $stmt3 = $mysqli->prepare("INSERT INTO notificaciones (id_nino, id_padre, tipo, mensaje, fecha, leida) VALUES (?, ?, ?, ?, NOW(), 0)");
            $tipo = 'nota'; // o 'dibujo'
            $mensaje_notif = 'Nueva nota agregada'; // o 'Nuevo dibujo guardado'
            $stmt3->bind_param("iiss", $nuevo_id_nino, $id_padre, $tipo, $mensaje_notif);
            $stmt3->execute();
            $stmt3->close();
        }
        $res->close();

        $mensaje = "Niño registrado correctamente.";
    } else {
        $mensaje = "Error al registrar: " . $stmt->error;
    }
    $stmt->close();
}

// Registrar padre y guardar solo ID y Nombre en la tabla padres
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["registrar_padre"])) {
    $nombre_padre = $_POST["nombre_padre"];
    $contrasena_padre = $_POST["contrasena_padre"];
    $genero_padre = $_POST["genero_padre"];
    $edad_padre = $_POST["edad_padre"];
    $codigo_postal = $_POST["codigo_postal"];
    $telefono = $_POST["telefono"];
    $calle = $_POST["calle"];
    $colonia = $_POST["colonia"];
    $numero_vivienda = $_POST["numero_vivienda"];

    // Insertar en inicio_padres
    $stmt = $mysqli->prepare("INSERT INTO inicio_padres (Nombre, Contraseña, Genero, Edad, Codigo_Postal, Telefono, Calle, Colonia, Numero_de_Vivienda) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $nombre_padre, $contrasena_padre, $genero_padre, $edad_padre, $codigo_postal, $telefono, $calle, $colonia, $numero_vivienda);

    if ($stmt->execute()) {
        // Obtener el ID insertado
        $nuevo_id_padre = $stmt->insert_id;

        // Insertar también en la tabla padres (solo ID y Nombre)
        $stmt2 = $mysqli->prepare("INSERT INTO padres (ID, Nombre) VALUES (?, ?)");
        $stmt2->bind_param("is", $nuevo_id_padre, $nombre_padre);
        $stmt2->execute();
        $stmt2->close();

        $mensaje = "Padre registrado correctamente.";
    } else {
        $mensaje = "Error al registrar padre: " . $stmt->error;
    }
    $stmt->close();
}

// Si el usuario logueado fuera padre, mostrar notificaciones
$num_no_leidas = 0;
if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'padre') {
    $id_padre = $_SESSION['user_id'];
    $res = $mysqli->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_padre = ? AND leida = 0");
    $res->bind_param("i", $id_padre);
    $res->execute();
    $res->bind_result($num_no_leidas);
    $res->fetch();
    $res->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Módulo Especialista</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f8fc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 30px 40px;
            text-align: center;
        }
        h1 {
            color: #6c3483;
            margin-bottom: 30px;
        }
        .actions {
            margin-top: 30px;
        }
        .actions a {
            display: inline-block;
            margin: 0 15px;
            padding: 12px 28px;
            background: #9b59b6;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .actions a:hover {
            background: #8e44ad;
        }
        form.registro-nino {
            margin-top: 40px;
            text-align: left;
            background: #f3e6fa;
            padding: 24px 18px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(155,89,182,0.08);
        }
        form.registro-nino label {
            font-weight: bold;
            color: #6c3483;
        }
        form.registro-nino input, form.registro-nino select {
            width: 100%;
            padding: 8px;
            margin-bottom: 16px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        .mensaje {
            margin: 18px 0;
            color: #27ae60;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bienvenido al Módulo Especialista</h1>
        <p>Desde aquí puedes registrar nuevos usuarios o modificar los existentes.</p>
        <div class="actions">
            <a href="register.php">Registrar Usuario</a>
            <a href="Modificacion.php">Modificar Usuario</a>
            <button id="toggleRegistroNino" type="button" style="background:#27ae60; color:#fff; border:none; border-radius:6px; font-weight:600; padding:12px 28px; margin-left:15px; cursor:pointer; transition:background 0.2s;">Registrar Niño</button>
            <button id="toggleRegistroPadre" type="button" style="background:#2980b9; color:#fff; border:none; border-radius:6px; font-weight:600; padding:12px 28px; margin-left:15px; cursor:pointer; transition:background 0.2s;">Registrar Padre</button>
        </div>

        <!-- Formulario para registrar niño -->
        <div id="formRegistroNino" style="display:none;">
            <form class="registro-nino" method="POST">
                <h2>Registrar Niño</h2>
                <?php if ($mensaje && isset($_POST["registrar_nino"])) echo "<div class='mensaje'>$mensaje</div>"; ?>
                <label for="nombre">Nombre:</label>
                <input type="text" name="nombre" id="nombre" required>

                <label for="contrasena">Contraseña:</label>
                <input type="password" name="contrasena" id="contrasena" required>

                <label for="genero">Género:</label>
                <select name="genero" id="genero" required>
                    <option value="">Selecciona...</option>
                    <option value="Niño">Niño</option>
                    <option value="Niña">Niña</option>
                    <option value="Otro">Otro</option>
                </select>

                <label for="edad">Edad:</label>
                <input type="number" name="edad" id="edad" min="1" max="18" required>

                <label for="padre">Padre:</label>
                <input type="text" name="padre" id="padre" required>

                <label for="madre">Madre:</label>
                <input type="text" name="madre" id="madre" required>

                <button type="submit" name="registrar_nino" style="background:#27ae60;">Registrar Niño</button>
                <button type="button" id="ocultarRegistroNino" style="background:#e74c3c; margin-left:10px;">Ocultar</button>
            </form>
        </div>

        <!-- Formulario para registrar padre -->
        <div id="formRegistroPadre" style="display:none;">
            <form class="registro-nino" method="POST">
                <h2>Registrar Padre</h2>
                <?php if ($mensaje && isset($_POST["registrar_padre"])) echo "<div class='mensaje'>$mensaje</div>"; ?>
                <label for="nombre_padre">Nombre:</label>
                <input type="text" name="nombre_padre" id="nombre_padre" required>

                <label for="contrasena_padre">Contraseña:</label>
                <input type="password" name="contrasena_padre" id="contrasena_padre" required>

                <label for="genero_padre">Género:</label>
                <select name="genero_padre" id="genero_padre" required>
                    <option value="">Selecciona...</option>
                    <option value="Padre">Padre</option>
                    <option value="Madre">Madre</option>
                    <option value="Otro">Otro</option>
                </select>

                <label for="edad_padre">Edad:</label>
                <input type="number" name="edad_padre" id="edad_padre" min="18" max="99" required>

                <label for="codigo_postal">Código Postal:</label>
                <input type="text" name="codigo_postal" id="codigo_postal" required>

                <label for="telefono">Teléfono:</label>
                <input type="text" name="telefono" id="telefono" required>

                <label for="calle">Calle:</label>
                <input type="text" name="calle" id="calle" required>

                <label for="colonia">Colonia:</label>
                <input type="text" name="colonia" id="colonia" required>

                <label for="numero_vivienda">Número de Vivienda:</label>
                <input type="text" name="numero_vivienda" id="numero_vivienda" required>

                <button type="submit" name="registrar_padre" style="background:#2980b9;">Registrar Padre</button>
                <button type="button" id="ocultarRegistroPadre" style="background:#e74c3c; margin-left:10px;">Ocultar</button>
            </form>
        </div>

        <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'padre'): ?>
        <div style="position:fixed;top:20px;right:30px;">
            <a href="notificaciones.php" style="position:relative;">
                <img src="campana.png" alt="Notificaciones" style="width:36px;">
                <?php if ($num_no_leidas > 0): ?>
                    <span style="position:absolute;top:-8px;right:-8px;background:#e74c3c;color:#fff;border-radius:50%;padding:2px 8px;font-size:14px;"><?php echo $num_no_leidas; ?></span>
                <?php endif; ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

<script>
    const toggleNinoBtn = document.getElementById('toggleRegistroNino');
    const togglePadreBtn = document.getElementById('toggleRegistroPadre');
    const formNinoDiv = document.getElementById('formRegistroNino');
    const formPadreDiv = document.getElementById('formRegistroPadre');
    const ocultarNinoBtn = document.getElementById('ocultarRegistroNino');
    const ocultarPadreBtn = document.getElementById('ocultarRegistroPadre');

    if (toggleNinoBtn && togglePadreBtn && formNinoDiv && formPadreDiv) {
        toggleNinoBtn.addEventListener('click', () => {
            formNinoDiv.style.display = 'block';
            formPadreDiv.style.display = 'none';
            toggleNinoBtn.style.display = 'none';
            togglePadreBtn.style.display = 'inline-block';
        });

        togglePadreBtn.addEventListener('click', () => {
            formPadreDiv.style.display = 'block';
            formNinoDiv.style.display = 'none';
            togglePadreBtn.style.display = 'none';
            toggleNinoBtn.style.display = 'inline-block';
        });
    }

    if (ocultarNinoBtn && formNinoDiv && toggleNinoBtn) {
        ocultarNinoBtn.addEventListener('click', () => {
            formNinoDiv.style.display = 'none';
            toggleNinoBtn.style.display = 'inline-block';
        });
    }

    if (ocultarPadreBtn && formPadreDiv && togglePadreBtn) {
        ocultarPadreBtn.addEventListener('click', () => {
            formPadreDiv.style.display = 'none';
            togglePadreBtn.style.display = 'inline-block';
        });
    }
</script>