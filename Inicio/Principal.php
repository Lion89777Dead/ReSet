<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$username = htmlspecialchars($_SESSION['username']);

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal - Bienvenido</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
            padding-top: 50px;
        }

        .header {
            width: 100%;
            background-color: #9b59b6;
            color: white;
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }

        .header h1 {
            margin: 0;
            font-size: 2.2em;
            font-weight: 600;
        }

        .welcome-message {
            margin-top: 100px;
            margin-bottom: 40px;
            font-size: 1.5em;
            color: #555;
            text-align: center;
        }

        .menu-container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            text-align: center;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .menu-container ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .menu-container ul li {
            margin-bottom: 15px;
        }

        .menu-container ul li:last-child {
            margin-bottom: 0;
        }

        .menu-container ul li a {
            display: block;
            padding: 15px 20px;
            background-color: #71b7e6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
            letter-spacing: 0.5px;
        }

        .menu-container ul li a:hover {
            background-color: #5a9ed4;
            transform: translateY(-2px);
        }

        .logout-link {
            margin-top: 30px;
            font-size: 1em;
        }

        .logout-link a {
            color: #d9534f;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .logout-link a:hover {
            color: #c9302c;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8em;
            }
            .welcome-message {
                font-size: 1.2em;
                margin-top: 80px;
            }
            .menu-container {
                margin: 0 20px;
                padding: 25px;
            }
            .menu-container ul li a {
                padding: 12px 15px;
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Bienvenido a ReSet</h1>
    </div>

    <p class="welcome-message">Bienvenido, <?php echo $username; ?>! Has iniciado sesión correctamente.</p>

    <div class="menu-container">
        <h3></h3>
        <ul>
            <li><a href="notas.php">Notas</a></li>
             <h3></h3>
            <li><a href="rompecabezas/index.php">Juegos</a></li>
            <li><a href="mini.php">Mini diario o dibujos</a></li>
            <li><a href="#">Contactar Soporte</a></li>
        </ul>
        <div class="logout-link">
            <a href="Principal.php?logout=true">Cerrar Sesión</a>
        </div>
    </div>
</body>
</html>