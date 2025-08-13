<?php
session_start();

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'inicio';

$usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
if (!$usuario) {
    die("Debes iniciar sesión como padre para ver tus notas.");
}

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// Obtener notas actuales
$notas = [];
$res = $mysqli->prepare("SELECT Notas FROM padres WHERE Nombre = ?");
$res->bind_param("s", $usuario);
$res->execute();
$res->bind_result($notas_json);
if ($res->fetch() && $notas_json) {
    $notas = json_decode($notas_json, true);
    if (!is_array($notas)) $notas = [];
}
$res->close();

// Guardar nota nueva
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["nota"])) {
    $nota = trim($_POST["nota"]);
    if (!empty($nota)) {
        $notas[] = [
            "texto" => $nota,
            "fecha" => time()
        ];
        $nuevo_json = json_encode($notas);
        $stmt = $mysqli->prepare("UPDATE padres SET Notas = ? WHERE Nombre = ?");
        $stmt->bind_param("ss", $nuevo_json, $usuario);
        $stmt->execute();
        $stmt->close();

        // Notificaciones SOLO cuando se guarda una nota nueva
        if (isset($_SESSION['user_id'])) {
            $id_nino = $_SESSION['user_id'];
            $res = $mysqli->prepare("SELECT id_padres FROM relacion_nino_padre WHERE id_niños = ?");
            $res->bind_param("i", $id_nino);
            $res->execute();
            $res->bind_result($id_padre);
            while ($res->fetch()) {
                $mensaje = 'Nueva nota agregada';
                $stmt = $mysqli->prepare("INSERT INTO notificaciones (id_niños, id_padres, mensaje, leido, fecha) VALUES (?, ?, ?, 0, NOW())");
                $stmt->bind_param("iis", $id_nino, $id_padre, $mensaje);
                $stmt->execute();
                $stmt->close();
            }
            $res->close();
        }

        header("Location: notas.php");
        exit();
    }
}

// Eliminar nota
if (isset($_GET["eliminar"])) {
    $id = intval($_GET["eliminar"]);
    if (isset($notas[$id]) && time() - $notas[$id]["fecha"] <= 1800) {
        array_splice($notas, $id, 1);
        $nuevo_json = json_encode($notas);
        $stmt = $mysqli->prepare("UPDATE padres SET Notas = ? WHERE Nombre = ?");
        $stmt->bind_param("ss", $nuevo_json, $usuario);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: notas.php");
    exit();
}

// Editar nota
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["editar_id"])) {
    $id = intval($_POST["editar_id"]);
    $nuevo_texto = trim($_POST["nuevo_texto"]);
    if (isset($notas[$id]) && time() - $notas[$id]["fecha"] <= 1800) {
        $notas[$id]["texto"] = $nuevo_texto;
        $nuevo_json = json_encode($notas);
        $stmt = $mysqli->prepare("UPDATE padres SET Notas = ? WHERE Nombre = ?");
        $stmt->bind_param("ss", $nuevo_json, $usuario);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: notas.php");
    exit();
}

// Función para mostrar tiempo amigable
function mostrarTiempo($segundos) {
    if ($segundos < 60) {
        return "Hace $segundos segundo(s)";
    }
    if ($segundos < 3600) {
        $minutos = floor($segundos / 60);
        return "Hace $minutos minuto(s)";
    }
    if ($segundos < 86400) {
        $horas = floor($segundos / 3600);
        return $horas == 1 ? "Hace 1 hora" : "Hace $horas horas";
    }
    $dias = floor($segundos / 86400);
    return $dias == 1 ? "Hace 1 día" : "Hace $dias días";
}
function safe_chart_row($nombre, $puntaje, $tiempo) {
    $nombre = addslashes($nombre);
    $puntaje = is_numeric($puntaje) ? intval($puntaje) : 0;
    $tiempo = is_numeric($tiempo) ? intval($tiempo) : 0;
    return "['{$nombre}',{$puntaje},{$tiempo}]";
}

$datos_juegos = [];
// Se agrega 'Dificultad' a la consulta SQL
$res = $mysqli->query("SELECT Nombre, Puntaje, Tiempo, Juego, Dificultad FROM juegos ORDER BY Juego, Puntaje DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $datos_juegos[] = $row;
    }
    $res->close();
}

$datos_memorama = array_filter($datos_juegos, function($item) {
    return $item['Juego'] == 'memorama';
});

$datos_puzzle = array_filter($datos_juegos, function($item) {
    return $item['Juego'] == 'puzzle';
});

$datos_rompecabezas = array_filter($datos_juegos, function($item) {
    return $item['Juego'] == 'rompecabezas';
});

// Obtener la dificultad de cada juego para los títulos
$dificultad_memorama = !empty($datos_memorama) ? current($datos_memorama)['Dificultad'] : 'No disponible';
$dificultad_puzzle = !empty($datos_puzzle) ? current($datos_puzzle)['Dificultad'] : 'No disponible';
$dificultad_rompecabezas = !empty($datos_rompecabezas) ? current($datos_rompecabezas)['Dificultad'] : 'No disponible';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bloc de Notas</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent: #6c63ff;
            --accent2: #ff7f50;
            --bg: #f5f6fa;
            --panel: #fff;
            --border: #ececec;
        }
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: var(--bg);
            margin:0;
            min-height:100vh;
        }
        .container {
            max-width: 880px;
            min-width: 350px;
            margin: 48px auto 0 auto;
            background: var(--panel);
            border-radius: 20px;
            box-shadow: 0 6px 32px rgba(108,99,255,0.07);
            padding: 48px 48px 30px 48px;
            animation: fadein 1s;
            display: flex;
            flex-direction: column;
            gap: 0px;
        }
        @keyframes fadein {
            from {transform:translateY(30px);opacity:0;}
            to {transform:translateY(0);opacity:1;}
        }
        h1 {
            color: var(--accent);
            font-weight: 800;
            margin-bottom: 18px;
            font-size: 2.3em;
            letter-spacing:0.02em;
        }
        h2 {
            font-weight: 600;
            color: #222;
            margin-top:38px;
            margin-bottom:18px;
            font-size: 1.38em;
        }
        form {
            margin-bottom: 32px;
            animation: fadein 0.7s;
        }
        textarea {
            width: 100%;
            min-height: 130px;
            padding: 13px 16px;
            font-size: 1.08em;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            outline:none;
            background: #f8f8ff;
            transition: border-color 0.2s;
            resize: vertical;
            margin-bottom: 1px;
        }
        textarea:focus { border-color: var(--accent); }
        button, .btn {
            margin-top: 12px;
            padding: 12px 32px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 14px;
            border: none;
            background: linear-gradient(90deg, var(--accent) 60%, var(--accent2) 100%);
            color: #fff;
            cursor: pointer;
            box-shadow: 0 2px 14px rgba(108,99,255,0.08);
            transition: background 0.2s, transform 0.18s;
            outline: none;
            letter-spacing: 0.01em;
        }
        button:hover, .btn:hover {
            background: linear-gradient(90deg, var(--accent2) 60%, var(--accent) 100%);
            transform: scale(1.05);
        }
        pre {
            background: #f5f6fa;
            padding: 18px 16px;
            border-radius: 10px;
            font-size: 1.15em;
            font-family: inherit;
            font-weight: 500;
            margin: 0;
            border-left: 5px solid var(--accent);
            word-break: break-all;
        }
        .nota-acciones { margin-top: 16px; }
        .nota-tiempo {
            color: #888;
            font-size: 1.05em;
            font-style: italic;
            margin-top: 10px;
            display: block;
            letter-spacing:0.02em;
        }
        .edit-form textarea { min-height:80px; }
        .edit-form .btn { margin-top:12px; }
        .edit-form a { margin-left:16px; color:var(--accent2); text-decoration:none; font-weight:600; font-size:1em;}
        .edit-form a:hover { text-decoration:underline; }
        .nota-box {
            margin-bottom: 38px;
            background: #fafaff;
            border-radius: 13px;
            box-shadow: 0 2px 12px #ececec;
            padding: 20px 22px 12px 22px;
            border:1.5px solid #f2f2f2;
            transition: box-shadow 0.2s;
            animation: fadein 1s;
        }
        .nota-box:hover {
            box-shadow: 0 8px 32px #e8ebfa;
        }
        @media (max-width:900px) {
            .container { max-width: 98vw; padding: 16px 4vw 10px 4vw;}
        }
        @media (max-width:600px) {
            .container { padding:10px 2vw 7px 2vw;}
            pre { font-size:1em; padding:13px 8px;}
            .nota-box { padding:13px 7px 7px 8px;}
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bloc de Notas</h1>

        <form method="post">
            <textarea name="nota" placeholder="Escribe una nota..."></textarea><br>
            <button type="submit">Guardar Nota</button>
        </form>

        <h2>Notas Guardadas:</h2>
        <?php foreach ($notas as $i => $n): ?>
            <div class="nota-box">
                <?php if (isset($_GET["editar"]) && $_GET["editar"] == $i): ?>
                    <form method="post" class="edit-form">
                        <textarea name="nuevo_texto"><?php echo htmlspecialchars($n["texto"]); ?></textarea><br>
                        <input type="hidden" name="editar_id" value="<?php echo $i; ?>">
                        <button type="submit" class="btn">Guardar Cambios</button>
                        <a href="notas.php">Cancelar</a>
                    </form>
                <?php else: ?>
                    <pre><?php echo htmlspecialchars($n["texto"]); ?></pre>
                    <span class="nota-tiempo">
                        <?php
                            $segundos = time() - $n["fecha"];
                            echo mostrarTiempo($segundos);
                        ?>
                    </span>
                    <div class="nota-acciones">
                        <?php if ($segundos <= 1800): // 30 minutos ?>
                            <a href="notas.php?editar=<?php echo $i; ?>" class="btn" style="background:linear-gradient(90deg,#ffb347 60%,#ff7f50 100%);color:#fff;">Editar</a>
                            <a href="notas.php?eliminar=<?php echo $i; ?>" class="btn" style="background:linear-gradient(90deg,#e74c3c 60%,#ff7f50 100%);color:#fff;margin-left:12px;" onclick="return confirm('¿Eliminar esta nota?');">Eliminar</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div id="graficas-container">
            <div class="grafica-box">
                <div class="grafica-titulo">Memorama (Dificultad: <?php echo htmlspecialchars($dificultad_memorama); ?>)</div>
                <div id="grafica_memorama" style="width:100%; max-width:600px; height:300px;"></div>
            </div>
            <div class="grafica-box">
                <div class="grafica-titulo">Puzzle (Dificultad: <?php echo htmlspecialchars($dificultad_puzzle); ?>)</div>
                <div id="grafica_puzzle" style="width:100%; max-width:600px; height:300px;"></div>
            </div>
            <div class="grafica-box">
                <div class="grafica-titulo">Rompecabezas (Dificultad: <?php echo htmlspecialchars($dificultad_rompecabezas); ?>)</div>
                <div id="grafica_rompecabezas" style="width:100%; max-width:600px; height:300px;"></div>
            </div>
        </div>

        <div style="position:fixed;top:20px;right:30px;">
            <a href="notificaciones.php" style="position:relative;">
                <img src="img/campana.png" alt="Notificaciones" style="width:36px;">
                <?php if (isset($num_no_leidas) && $num_no_leidas > 0): ?>
                    <span style="position:absolute;top:-8px;right:-8px;background:#e74c3c;color:#fff;border-radius:50%;padding:2px 8px;font-size:14px;"><?php echo $num_no_leidas; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script>
        // Google Charts
        google.charts.load('current', {'packages':['corechart']});

        // Función para dibujar todas las gráficas una vez que la biblioteca esté cargada
        google.charts.setOnLoadCallback(drawAllCharts);

        function drawAllCharts() {
            drawMemoramaChart();
            drawPuzzleChart();
            drawRompecabezasChart();
        }

        function drawMemoramaChart() {
            const data = google.visualization.arrayToDataTable([
                ['Niño', 'Puntaje', 'Tiempo'],
                <?php
                $rows = [];
                foreach ($datos_memorama as $p) {
                    $rows[] = safe_chart_row($p['Nombre'], $p['Puntaje'], $p['Tiempo']);
                }
                echo implode(",\n", $rows);
                ?>
            ]);
            const options = {
                title: 'Memorama (Dificultad: <?php echo htmlspecialchars($dificultad_memorama); ?>)',
                legend: { position: 'top' },
                colors: ['#463fc0ff', '#2ecc71'],
                series: {
                    0: {targetAxisIndex:0, type:'bars', color:'#6c63ff'},
                    1: {targetAxisIndex:1, type:'line', color:'#2ecc71'}
                },
                vAxes: {
                    0: {title: 'Puntaje'},
                    1: {title: 'Tiempo (seg)'}
                }
            };
            new google.visualization.ComboChart(document.getElementById('grafica_memorama')).draw(data, options);
        }
        function drawPuzzleChart() {
            const data = google.visualization.arrayToDataTable([
                ['Niño', 'Puntaje', 'Tiempo'],
                <?php
                $rows = [];
                foreach ($datos_puzzle as $p) {
                    $rows[] = safe_chart_row($p['Nombre'], $p['Puntaje'], $p['Tiempo']);
                }
                echo implode(",\n", $rows);
                ?>
            ]);
            const options = {
                title: 'Puzzle (Dificultad: <?php echo htmlspecialchars($dificultad_puzzle); ?>)',
                legend: { position: 'top' },
                colors: ['#ffb347', '#27ae60'],
                series: {
                    0: {targetAxisIndex:0, type:'bars', color:'#ffb347'},
                    1: {targetAxisIndex:1, type:'line', color:'#27ae60'}
                },
                vAxes: {
                    0: {title: 'Puntaje'},
                    1: {title: 'Tiempo (seg)'}
                }
            };
            new google.visualization.ComboChart(document.getElementById('grafica_puzzle')).draw(data, options);
        }
        function drawRompecabezasChart() {
            const data = google.visualization.arrayToDataTable([
                ['Niño', 'Puntaje', 'Tiempo'],
                <?php
                $rows = [];
                foreach ($datos_rompecabezas as $p) {
                    $rows[] = safe_chart_row($p['Nombre'], $p['Puntaje'], $p['Tiempo']);
                }
                echo implode(",\n", $rows);
                ?>
            ]);
            const options = {
                title: 'Rompecabezas (Dificultad: <?php echo htmlspecialchars($dificultad_rompecabezas); ?>)',
                legend: { position: 'top' },
                colors: ['#e74c3c', '#2980b9'],
                series: {
                    0: {targetAxisIndex:0, type:'bars', color:'#e74c3c'},
                    1: {targetAxisIndex:1, type:'line', color:'#2980b9'}
                },
                vAxes: {
                    0: {title: 'Puntaje'},
                    1: {title: 'Tiempo (seg)'}
                }
            };
            new google.visualization.ComboChart(document.getElementById('grafica_rompecabezas')).draw(data, options);
        }
    </script>
</body>
</html>