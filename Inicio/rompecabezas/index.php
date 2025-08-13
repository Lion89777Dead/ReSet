<?php
session_start();
$usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Rompecabezas Deslizante</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Estilos del rompecabezas */
        body { font-family: Arial, sans-serif; text-align: center; }
        .btn-chido { margin: 10px 8px; padding: 12px 28px; background: linear-gradient(90deg, #6c63ff 60%, #ff7f50 100%);
            color: #fff; border: none; border-radius: 14px; font-size: 18px; font-weight: bold; cursor: pointer;
            box-shadow: 1px 2px 8px #ffd6e0; transition: background 0.2s, transform 0.2s; outline: none; }
        .btn-chido:hover { background: linear-gradient(90deg, #ff7f50 60%, #6c63ff 100%); transform: scale(1.07);}
        #marcador { margin-bottom: 12px; font-weight: bold; font-size: 1.1em; }
        #puzzle-container {
            margin: 0 auto;
            display: grid;
            gap: 2px;
            border: 4px solid #333;
        }
        .tile {
            background-color: #4CAF50;
            color: white;
            font-size: 32px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            user-select: none;
            transition: background-color 0.3s;
        }
        .tile:hover { background-color: #45a049; }
        .empty { background-color: #ccc; cursor: default; }
        #puntaje-final { margin-top: 18px; font-size: 1.35em; color: #ff7f50; font-weight: bold; display: none;}
        .puzzle-deshabilitado { pointer-events: none; opacity: 0.7;}
    </style>
</head>
<body>
    <h1>Rompecabezas Deslizante</h1>
    <div id="controles-dificultad">
        <button id="btn-nivel1" class="btn-chido">Nivel 1 (3x3)</button>
        <button id="btn-nivel2" class="btn-chido">Nivel 2 (4x4)</button>
        <button id="btn-nivel3" class="btn-chido">Nivel 3 (5x5)</button>
        <button id="btn-nivel4" class="btn-chido">Nivel 4 (6x6)</button>
    </div>
    <div id="controles-juego">
        <button id="btn-iniciar" class="btn-chido">Iniciar Juego</button>
        <button id="reiniciar" class="btn-chido">Reiniciar</button>
    </div>
    <div id="marcador">
        Tiempo: <span id="tiempo">0</span> seg |
        Puntaje: <span id="puntaje">0</span>
    </div>
    <div id="puzzle-container"></div>
    <div id="puntaje-final"></div>
    <script src="script.js"></script>
    <script>
        const usuario = <?php echo json_encode($usuario); ?>;
    </script>
</body>
</html>