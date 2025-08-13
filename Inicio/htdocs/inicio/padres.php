<?php
session_start();

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'inicio';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    if (empty($usuario) || empty($contrasena)) {
        $error_message = 'Por favor, completa ambos campos.';
    } else {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

        if ($conn->connect_error) {
            $error_message = "Error de conexión. Intenta más tarde.";
        } else {
            $stmt = $conn->prepare("SELECT ID, Nombre, Contraseña FROM inicio_padres WHERE Nombre = ?");
            if ($stmt) {
                $stmt->bind_param("s", $usuario);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    if ($contrasena == $user['Contraseña']) {
                        $_SESSION['id_padres'] = $user['ID'];
                        $_SESSION['usuario'] = $user['Nombre'];

                        header('Location: ../../notas.php');
                        exit();

                    } else {
                        $error_message = 'Contraseña incorrecta.';
                    }
                } else {
                    $error_message = 'Usuario no encontrado.';
                }
                $stmt->close();
            } else {
                $error_message = "Error al preparar la consulta.";
            }
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión Padres</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            
            /* --- CORRECCIÓN DEL FONDO: APLICANDO EL GRADIENTE --- */
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(5px);
        }

        h2 { color: #333; margin-bottom: 30px; font-weight: 700; font-size: 2em; }
        .input-group { margin-bottom: 25px; text-align: left; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 0.9em; }
        input[type="text"], input[type="password"] { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 1em; box-sizing: border-box; transition: border-color 0.3s; }
        input[type="text"]:focus, input[type="password"]:focus { border-color: #9b59b6; outline: none; }
        button { width: 100%; padding: 15px; border: none; border-radius: 8px; background-color: #9b59b6; color: white; font-size: 1.1em; font-weight: 600; cursor: pointer; transition: background-color 0.3s, transform 0.2s; letter-spacing: 0.5px;}
        button:hover { background-color: #8e44ad; transform: translateY(-2px);}
        .error-message { color: #d9534f; margin-top: 20px; font-size: 0.9em; font-weight: 500;}
        @media (max-width: 500px) {.container {margin: 20px; padding: 30px;} h2 {font-size: 1.8em;} button {font-size: 1em; padding: 12px;}}
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
    </style>
</head>
<body>
    
    <div class="container">
        <h2>Iniciar Sesión</h2>
        <?php
        if (!empty($error_message)) {
            echo '<p class="error-message">' . htmlspecialchars($error_message) . '</p>';
        }
        ?>
        <form action="" method="POST">
            <div class="input-group">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" required>
            </div>
            <div class="input-group">
                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena" required>
            </div>
            <button type="submit">Entrar</button>
            <a href="index.php" class="btn-regresar">⬅ Regresar</a>
        </form>
    </div>
    <footer class="footer">
  <p>Creado por: Edwin Abraham Hernández Rivero &nbsp;|&nbsp; Axel Abraham Esquivel Saucedo &nbsp;|&nbsp; Janemir Alexander Aguirre Rios</p>
</footer>

</body>
</html>