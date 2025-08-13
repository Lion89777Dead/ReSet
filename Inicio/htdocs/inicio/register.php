<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'Inicio';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    $confirm_contrasena = $_POST['confirm_contrasena'] ?? '';

    if (empty($usuario) || empty($contrasena) || empty($confirm_contrasena)) {
        $error_message = 'Por favor, completa todos los campos.';
    } elseif ($contrasena !== $confirm_contrasena) {
        $error_message = 'Las contraseñas no coinciden.';
    } elseif (strlen($contrasena) < 6) {
        $error_message = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

        if ($conn->connect_error) {
            die("Error de conexión a la base de datos: " . $conn->connect_error);
        }

        $stmt_check = $conn->prepare("SELECT ID FROM Inicio WHERE Usuario = ?");
        if ($stmt_check) {
            $stmt_check->bind_param("s", $usuario);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $error_message = 'El nombre de usuario ya existe. Por favor, elige otro.';
                $stmt_check->close();
                $conn->close();
            } else {
                $stmt_check->close();

                $hashed_password = password_hash($contrasena, PASSWORD_DEFAULT);

                $stmt_insert = $conn->prepare("INSERT INTO Inicio (Usuario, Contrasena) VALUES (?, ?)");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("ss", $usuario, $hashed_password);

                    if ($stmt_insert->execute()) {
                        $success_message = '¡Registro exitoso! Serás redirigido a la página de especialista.';
                        header('Location: Especialista.php');
                        exit();
                    } else {
                        $error_message = 'Error al registrar usuario: ' . $stmt_insert->error;
                    }
                    $stmt_insert->close();
                } else {
                    $error_message = "Error en la preparación de la consulta de inserción: " . $conn->error;
                }
                $conn->close();
            }
        } else {
            $error_message = "Error en la preparación de la consulta de verificación de usuario: " . $conn->error;
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
    <title>Registro de Usuario</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
            background-color: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        h2 {
            margin-bottom: 30px;
            color: #333;
            font-weight: 600;
            font-size: 2em;
        }

        .input-group {
            margin-bottom: 25px;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 400;
            font-size: 0.95em;
        }

        .input-group input {
            width: calc(100% - 20px);
            padding: 12px 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            outline: none;
        }

        .input-group input:focus {
            border-color: #71b7e6;
            box-shadow: 0 0 8px rgba(113, 183, 230, 0.4);
        }

        button {
            width: 100%;
            padding: 15px;
            background-color: #9b59b6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            letter-spacing: 0.5px;
        }

        button:hover {
            background-color: #8e44ad;
            transform: translateY(-2px);
        }

        .error-message {
            color: #d9534f;
            margin-top: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .success-message {
            color: #5cb85c;
            margin-top: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }

        @media (max-width: 500px) {
            .container {
                margin: 20px;
                padding: 30px;
            }
            h2 {
                font-size: 1.8em;
            }
            button {
                font-size: 1em;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registrar Nuevo Usuario</h2>

        <?php
        if (!empty($error_message)) {
            echo '<p class="error-message">' . htmlspecialchars($error_message) . '</p>';
        }
        if (!empty($success_message)) {
            echo '<p class="success-message">' . htmlspecialchars($success_message) . '</p>';
        }
        ?>

        <form action="register.php" method="POST">
            <div class="input-group">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" placeholder="Crea un nombre de usuario" required>
            </div>
            <div class="input-group">
                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena" placeholder="Crea tu contraseña" required>
            </div>
            <div class="input-group">
                <label for="confirm_contrasena">Confirmar Contraseña</label>
                <input type="password" id="confirm_contrasena" name="confirm_contrasena" placeholder="Repite tu contraseña" required>
            </div>
            <button type="submit">Registrar</button>
        </form>
    </div>
</body>
</html>