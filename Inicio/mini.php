<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "inicio");
if ($mysqli->connect_errno) {
    die("Error de conexión: " . htmlspecialchars($mysqli->connect_error));
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$usuario_nino = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;

// Lógica para obtener los juegos asignados al niño
$juegos_asignados = [];
if ($user_id) {
    if ($stmt = $mysqli->prepare("SELECT juego, dificultad FROM asignaciones_juegos WHERE id_nino = ?")) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $juegos_asignados[$row['juego']] = $row['dificultad'];
        }
        $stmt->close();
    }
}

// Guardar entrada de texto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["texto"]) && $user_id) {
    $entrada = trim($_POST["texto"]);
    if (!empty($entrada)) {
        $fecha = date("Y-m-d H:i:s");
        $nueva_nota = "[$fecha]\n$entrada\n---\n";

        if ($stmt = $mysqli->prepare("UPDATE niños SET Nota = CONCAT(IFNULL(Nota,''), ?) WHERE ID = ?")) {
            $stmt->bind_param("si", $nueva_nota, $user_id);
            $stmt->execute();
            $stmt->close();
        }

        $id_nino = $user_id;
        if ($res = $mysqli->prepare("SELECT id_padres FROM relacion_nino_padre WHERE id_niños = ?")) {
            $res->bind_param("i", $id_nino);
            $res->execute();
            $res->bind_result($id_padre);
            while ($res->fetch()) {
                $mensaje = 'Nueva nota agregada';
                if ($stmt = $mysqli->prepare("INSERT INTO notificaciones (id_niños, id_padres, mensaje, leido, fecha) VALUES (?, ?, ?, 0, NOW())")) {
                    $stmt->bind_param("iis", $id_nino, $id_padre, $mensaje);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            $res->close();
        }
    }
}

// Guardar dibujo en base64
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["imagen"]) && $user_id) {
    $imgData = $_POST["imagen"];
    $imgData = str_replace('data:image/png;base64,', '', $imgData);
    $imgData = str_replace(' ', '+', $imgData);
    $data = base64_decode($imgData);

    if (!file_exists("diario/dibujos")) {
        mkdir("diario/dibujos", 0777, true);
    }
    $filename = 'diario/dibujos/dibujo_' . time() . '_' . uniqid() . '.png';
    file_put_contents($filename, $data);

    if ($stmt_check = $mysqli->prepare("SELECT Dibujo FROM niños WHERE ID = ?")) {
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $stmt_check->bind_result($dibujos_existentes);
        $stmt_check->fetch();
        $stmt_check->close();

        $separador = ($dibujos_existentes) ? "," : "";
        $nueva_ruta = $separador . $filename;

        if ($stmt_update = $mysqli->prepare("UPDATE niños SET Dibujo = CONCAT(IFNULL(Dibujo,''), ?) WHERE ID = ?")) {
            $stmt_update->bind_param("si", $nueva_ruta, $user_id);
            $stmt_update->execute();
            $stmt_update->close();
        }
    }

    $id_nino = $user_id;
    if ($res = $mysqli->prepare("SELECT id_padres FROM relacion_nino_padre WHERE id_niños = ?")) {
        $res->bind_param("i", $id_nino);
        $res->execute();
        $res->bind_result($id_padre);
        while ($res->fetch()) {
            $mensaje = 'Nuevo dibujo agregado';
            if ($stmt = $mysqli->prepare("INSERT INTO notificaciones (id_niños, id_padres, mensaje, leido, fecha) VALUES (?, ?, ?, 0, NOW())")) {
                $stmt->bind_param("iis", $id_nino, $id_padre, $mensaje);
                $stmt->execute();
                $stmt->close();
            }
        }
        $res->close();
    }
}
// Cálculo del promedio semanal de puntaje
$promedio_semanal = null;
$num_partidas = 0;

if ($usuario_nino) {
    // Determina inicio y fin de la semana (lunes a domingo)
    $hoy = new DateTime();
    $dia_semana = $hoy->format('N'); // 1 (lunes) a 7 (domingo)
    $inicio_semana = clone $hoy;
    $fin_semana = clone $hoy;
    $inicio_semana->modify('-' . ($dia_semana - 1) . ' days')->setTime(0,0,0);
    $fin_semana->modify('+' . (7 - $dia_semana) . ' days')->setTime(23,59,59);
$sql = "SELECT Puntaje FROM juegos WHERE Nombre = ? AND fecha_asignacion BETWEEN ? AND ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $nombre = $usuario_nino;
        // Asumimos que la tabla juegos tiene un campo de fecha (agrega uno si no lo tienes, por ejemplo, 'fecha_asignacion' TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
        $fecha_inicio = $inicio_semana->format('Y-m-d');
        $fecha_fin = $fin_semana->format('Y-m-d');
        $stmt->bind_param("sss", $nombre, $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $res = $stmt->get_result();
        $total = 0; $num_partidas = 0;
        while ($row = $res->fetch_assoc()) {
            $total += intval($row['Puntaje']);
            $num_partidas++;
        }
        $stmt->close();
        if ($num_partidas > 0) {
            $promedio_semanal = round($total / $num_partidas, 2);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mini Diario Creativo</title>
    <style>
        body {
    font-family: Arial, sans-serif;
    background: #f6f6fa;
    padding: 40px;
    max-width: 900px;
    margin: auto;
    color: #333;
    text-align: center;
}
h1 {
    color: #ff7f50;
    text-shadow: 2px 2px 0 #fff176, 4px 4px 0 #ffd6e0;
    font-size: 2.7em;
    margin-bottom: 10px;
    letter-spacing: 2px;
}
h2 {
    color: #6c63ff;
    text-shadow: 1px 1px 0 #fff;
    margin-bottom: 12px;
}
h3 {
    color: #ffb347;
    margin-top: 12px;
    margin-bottom: 8px;
    font-size: 1.3em;
}
.section {
    margin-bottom: 40px;
    background: #fff8e1;
    border-radius: 18px;
    box-shadow: 1px 2px 8px #ffe0b2;
    padding: 24px 18px 18px 18px;
}
img {
    max-width: 180px;
    margin: 10px;
    border: 3px solid #ffd6e0;
    border-radius: 12px;
    box-shadow: 1px 2px 8px #ffe0b2;
}
.tab-btn {
    background: #f6f6fa;
    color: #6c63ff;
    border: none;
    font-weight: bold;
    font-size: 1.1em;
    padding: 18px 0;
    cursor: pointer;
    transition: background .2s, color .2s;
    border-radius: 12px 12px 0 0;
    border-bottom: 2px solid #eee;
    outline: none;
    flex: 1;
}
.tab-btn.active {
    background: #fff;
    color: #ff7f50;
    border-bottom: 2px solid #fff;
}
#tabs {
    display: flex; 
    border-bottom: 2px solid #eee; 
    border-radius: 18px 18px 0 0;
    overflow: hidden;
}
#tab-content {
    background: #fff;
    border-radius: 0 0 18px 18px;
    box-shadow: 0 2px 8px #eee;
    border: 2px solid #eee;
    border-top: none;
    overflow: visible !important;
    padding: 0;
}
.game-view {
    display: none;
    padding: 20px 0;
    min-height: 340px;
}
.game-view.active {
    display: block;
}
.btn-chido,
#descargarDibujo,
#guardarDibujo,
#limpiarCanvas {
    padding: 12px 28px;
    background: linear-gradient(90deg, #6c63ff 60%, #ff7f50 100%);
    color: #fff;
    border: none;
    border-radius: 14px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    box-shadow: 1px 2px 8px #ffd6e0;
    transition: background 0.2s, transform 0.2s;
    outline: none;
    margin: 8px 4px;
    display: inline-block;
}
.btn-chido:hover,
#descargarDibujo:hover,
#guardarDibujo:hover,
#limpiarCanvas:hover {
    background: linear-gradient(90deg, #ff7f50 60%, #6c63ff 100%);
    transform: scale(1.07);
}
#marcador, #marcador-puzzle, #marcador-memorama {
    margin-bottom: 12px; 
    font-weight: bold; 
    font-size: 1.1em; 
}
#puntaje-final, #puntaje-final-puzzle, #puntaje-final-memorama {
    margin-top: 18px; 
    font-size: 1.35em; 
    color: #ff7f50; 
    font-weight: bold; 
    display: none;
}
.puzzle-deshabilitado, .tablero-deshabilitado {
    pointer-events: none; 
    opacity: 0.7;
}
#puzzle-container-puzzle {
    position: relative;
    width: 400px;
    height: 400px;
    margin: auto;
    border: 2px solid #6c63ff;
    background: #fff;
    box-shadow: 0 2px 8px #eee;
}
.piece {
    position: absolute;
    box-shadow: 0 1px 6px #ccc;
    cursor: pointer;
    transition: box-shadow 0.2s;
    user-select: none;
    z-index: 1;
    background-repeat: no-repeat;
    border-radius: 6px;
}
.piece.selected {
    box-shadow: 0 4px 12px #6c63ff;
    z-index: 2;
}
#puzzle-container {
    margin: 0 auto;
    display: grid;
    /* Se ajusta el gap para un mejor espaciado */
    gap: 4px; 
    border: 4px solid #333;
    max-width: 400px; 
    /* Ajuste para móviles, pero con un mínimo */
    width: 90vw; 
    min-width: 250px;
}
.tile {
    background-color: #4CAF50;
    color: white;
    /* Se mejora el tamaño de la fuente para mejor legibilidad */
    font-size: clamp(18px, 4vw, 42px); 
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    user-select: none;
    transition: background-color 0.3s;
    height: 100%;
    width: 100%;
}
.tile:hover { background-color: #45a049; }
.empty { background-color: #ccc; cursor: default; }
#tablero {
    display: grid;
    grid-gap: 10px;
    justify-content: center;
    margin: 20px auto;
    max-width: 400px;
}
.carta {
    width: 80px;
    height: 80px;
    background: #3498db;
    color: #fff;
    font-size: 2em;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 10px;
    user-select: none;
    transition: background 0.3s;
}
.carta.volteada, .carta.completada {
    background: #2ecc71;
    cursor: default;
}
#mensaje-memorama {
    margin-top: 20px;
    font-size: 1.2em;
    color: #333;
}
/* COLORES PARA CÍRCULOS DE PALETA */
.color-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 1px 4px #ddd;
    cursor: pointer;
    display: inline-block;
    transition: box-shadow 0.2s, border 0.2s;
}
.color-circle.selected {
    border: 3px solid #6c63ff;
    box-shadow: 0 2px 8px #ff7f50;
}
input[type="color"]#colorPersonalizado {
    width: 36px;
    height: 36px;
    border: none;
    background: none;
    cursor: pointer;
    margin-left: 8px;
    vertical-align: middle;
}
textarea[name="texto"] {
    width: 80%;
    min-height: 70px;
    border-radius: 14px;
    border: 2px solid #ffd6e0;
    padding: 12px;
    font-size: 1.25em;
    margin-bottom: 12px;
    background: #fff;
    resize: vertical;
    box-shadow: 0 2px 8px #ffe0b2;
    transition: box-shadow 0.2s, border-color 0.2s;
}
textarea[name="texto"]:focus {
    border-color: #ff7f50;
    box-shadow: 0 4px 16px #ffd6e0;
}
form button[type="submit"] {
    padding: 10px 24px;
    background: linear-gradient(90deg, #ff7f50 60%, #6c63ff 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1.05em;
    font-weight: bold;
    cursor: pointer;
    box-shadow: 1px 2px 8px #ffd6e0;
    margin-top: 6px;
}
form button[type="submit"]:hover {
    background: linear-gradient(90deg, #6c63ff 60%, #ff7f50 100%);
    transform: scale(1.07);
}
input[type="range"]#grosorPicker {
    accent-color: #6c63ff;
    width: 80px;
    vertical-align: middle;
    margin-right: 8px;
}
#borradorBtn {
    width: 100%; 
    padding: 12px; 
    background: #fff; 
    color: #333; 
    border: 2px solid #ffb347; 
    border-radius: 16px; 
    font-size: 18px; 
    font-weight: bold; 
    cursor: pointer; 
    transition: background 0.2s, transform 0.2s;
    margin-top: 8px;
}
#borradorBtn:hover {
    background: #ffb347;
    color: #fff;
    transform: scale(1.07);
}
@media (max-width: 700px) {
    body {
        padding: 10px;
    }
    .section {
        padding: 12px 4px 10px 4px;
    }
    #tab-content {
        min-height: 150px;
    }
    #puzzle-container-puzzle, #puzzle-container, #tablero {
        max-width: 98vw !important;
        width: 98vw !important;
    }
    textarea[name="texto"] {
        width: 96%;
    }
}
#canvas {
    display: block;
    margin: 18px auto 8px auto;
    background: #fff;
    border: 3px solid #ffd6e0;
    border-radius: 18px;
    box-shadow: 0 2px 12px #ffe0b2;
    width: 600px;
    height: 400px;
    max-width: 98vw;
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
    <a href="htdocs/inicio/ninos.php" class="btn-regresar">⬅ Regresar</a>
    <h1>Mini Diario Creativo 🦄</h1>

    <div class="section">
        <h2>Escribir Entrada</h2>
        <form method="post">
            <textarea name="texto" placeholder="Hoy me siento... 😊"></textarea><br>
            <button type="submit">Guardar Entrada</button>
        </form>
    </div>

    <div class="section">
        <h2>¡Dibuja Algo Bonito!</h2>
        <div style="display: flex; align-items: flex-start; gap: 24px;">
            <div>
                <canvas id="canvas" width="600" height="400"></canvas><br>
                <button class="btn-chido" onclick="limpiarCanvas()" id="limpiarCanvas">Limpiar</button>
<button class="btn-chido" onclick="guardarDibujo()" id="guardarDibujo">Guardar Dibujo</button>
<button class="btn-chido" id="descargarDibujo" type="button">Descargar Dibujo</button>
            </div>
            <div style="display: flex; flex-direction: column; gap: 18px; min-width: 120px;">
                <div>
                    <label style="font-weight: bold;">Colores:</label><br>
                    <div id="colorPalette" style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
                        <div class="color-circle" data-color="#2c3e50" style="background:#2c3e50;" title="#2c3e50"></div>
                        <div class="color-circle" data-color="#e74c3c" style="background:#e74c3c;" title="#e74c3c"></div>
                        <div class="color-circle" data-color="#3498db" style="background:#3498db;" title="#3498db"></div>
                        <div class="color-circle" data-color="#27ae60" style="background:#27ae60;" title="#27ae60"></div>
                        <div class="color-circle" data-color="#f1c40f" style="background:#f1c40f;" title="#f1c40f"></div>
                        <div class="color-circle" data-color="#e67e22" style="background:#e67e22;" title="#e67e22"></div>
                        <div class="color-circle" data-color="#9b59b6" style="background:#9b59b6;" title="#9b59b6"></div>
                        <div class="color-circle" data-color="#1abc9c" style="background:#1abc9c;" title="#1abc9c"></div>
                        <div class="color-circle" data-color="#ffb6c1" style="background:#ffb6c1;" title="#ffb6c1"></div>
                        <div class="color-circle" data-color="#000" style="background:#000;" title="#000"></div>
                        <input type="color" id="colorPersonalizado" value="#ff7f50" style="width:36px; height:36px; border:none; background:none; cursor:pointer; margin-left:8px;">
                    </div>
                </div>
                <div>
                    <label style="font-weight: bold;">Grosor:</label><br>
                    <input type="range" id="grosorPicker" min="1" max="20" value="2" style="width: 80px;">
                    <span id="grosorValor">2</span> px
                </div>
                <div>
                    <button id="borradorBtn" type="button" style="width: 100%; padding: 12px; background: #fff; color: #333; border: 2px solid #ffb347; border-radius: 16px; font-size: 18px; font-weight: bold; cursor: pointer; transition: background 0.2s, transform 0.2s;">Borrador</button>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
    <h2 style="color:#7c5fff; font-family:inherit; margin-bottom:10px;">Minijuegos</h2>
    <?php if (empty($juegos_asignados)): ?>
        <div style="font-size: 1.2em; color: #6c63ff; padding: 20px;">
            <p>Aún no tienes juegos asignados. Por favor, pide a tu psicólogo que te asigne uno.</p>
        </div>
    <?php else: ?>
        <div style="max-width: 100%; margin:auto;">
            <div id="tabs">
                <?php
                $first_game = true;
                foreach ($juegos_asignados as $juego => $dificultad):
                ?>
                    <button class="tab-btn <?php echo $first_game ? 'active' : ''; ?>" data-tab="<?php echo htmlspecialchars($juego); ?>">
                        <?php echo htmlspecialchars(ucfirst($juego)); ?>
                    </button>
                <?php $first_game = false; endforeach; ?>
            </div>
            <div id="tab-content">
                <?php
                $first_game = true;
                foreach ($juegos_asignados as $juego => $dificultad):
                ?>
                    <div class="game-view <?php echo $first_game ? 'active' : ''; ?>" id="<?php echo htmlspecialchars($juego); ?>">
                        <h2><?php echo htmlspecialchars(ucfirst($juego)); ?></h2>
                        <input type="hidden" class="dificultad-juego" data-juego="<?php echo htmlspecialchars($juego); ?>" value="<?php echo htmlspecialchars($dificultad); ?>">
                        <div id="acciones-<?php echo htmlspecialchars($juego); ?>">
                            <button id="btn-iniciar-<?php echo htmlspecialchars($juego); ?>" class="btn-chido">Iniciar Juego</button>
                            <button id="reiniciar-<?php echo htmlspecialchars($juego); ?>" class="btn-chido">Reiniciar</button>
                        </div>
                        <div id="marcador-<?php echo htmlspecialchars($juego); ?>">
                          Tiempo: <span id="tiempo-<?php echo htmlspecialchars($juego); ?>">0</span> seg |
                          Puntaje: <span id="puntaje-<?php echo htmlspecialchars($juego); ?>">0</span>
                        </div>
                        <?php if ($juego == 'memorama'): ?>
                            <div id="tablero"></div>
                        <?php elseif ($juego == 'puzzle'): ?>
                            <div id="puzzle-container-puzzle"></div>
                        <?php elseif ($juego == 'rompecabezas'): ?>
                            <div id="puzzle-container"></div>
                        <?php endif; ?>
                        <div id="mensaje-<?php echo htmlspecialchars($juego); ?>"></div>
                        <div id="puntaje-final-<?php echo htmlspecialchars($juego); ?>"></div>
                    </div>
                <?php $first_game = false; endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Entradas Guardadas</h2>
        <pre> <?php if ($user_id) { $res = $mysqli->prepare("SELECT Nota FROM niños WHERE ID = ?"); $res->bind_param("i", $user_id); $res->execute(); $res->bind_result($nota); if ($res->fetch() && $nota) { echo htmlspecialchars($nota); } else { echo "No hay entradas guardadas."; } $res->close(); } else { echo "Debes iniciar sesión como niño para ver tus entradas."; } ?> </pre>
    </div>
    <div class="section">
        <h2>Dibujos Guardados</h2> <?php if ($usuario_nino) { $res = $mysqli->prepare("SELECT Dibujo FROM niños WHERE Nombre = ?"); $res->bind_param("s", $usuario_nino); $res->execute(); $res->bind_result($dibujos); if ($res->fetch() && $dibujos) { $rutas = array_filter(explode(",", $dibujos)); foreach ($rutas as $img) { if (file_exists($img)) { echo "<img src='$img'>"; } } } else { echo "No hay dibujos guardados."; } $res->close(); } else { echo "Debes iniciar sesión como niño para ver tus dibujos."; } ?>
    </div>
    <div class="section">
        <h2>Historial de Minijuegos</h2> <table> <tr> <th>Juego</th> <th>Puntaje</th> <th>Tiempo</th> <th>Dificultad</th> </tr> <?php if ($usuario_nino) { $stmt = $mysqli->prepare("SELECT Juego, Puntaje, Tiempo, Dificultad FROM juegos WHERE Nombre = ? ORDER BY ID DESC"); $stmt->bind_param("s", $usuario_nino); $stmt->execute(); $stmt->bind_result($juego, $puntaje, $tiempo, $dificultad); $hayDatos = false; while ($stmt->fetch()) { $hayDatos = true; echo "<tr> <td>$juego</td> <td>$puntaje</td> <td>$tiempo</td> <td>$dificultad</td> </tr>"; } if (!$hayDatos) { echo "<tr><td colspan='4'>No hay registros de minijuegos.</td></tr>"; } $stmt->close(); } else { echo "<tr><td colspan='4'>Debes iniciar sesión para ver tu historial de minijuegos.</td></tr>"; } ?> </table>
    </div>
     <?php if ($promedio_semanal !== null): ?>
        <div class="section" style="background:#eafaf1;">
            <h2 style="color:#27ae60;">Promedio semanal de puntaje en minijuegos</h2>
            <div style="font-size:1.5em;font-weight:bold">
                <?php echo htmlspecialchars($promedio_semanal); ?>
            </div>
            <div style="font-size:0.95em;color:#4d4d4d;">
                (Calculado de <?php echo htmlspecialchars($num_partidas); ?> partida<?php echo $num_partidas==1?'':'s'; ?> esta semana)
            </div>
        </div>
    <?php endif; ?>
    
    <script>
    // TABLA DE COLORES & DIBUJO
    const canvas = document.getElementById("canvas");
    const ctx = canvas.getContext("2d");
    let dibujando = false;
    let colorActual = "#2c3e50";
    let grosorActual = 2;
    let modoBorrador = false;
    const borradorBtn = document.getElementById("borradorBtn");
    const colorCircles = document.querySelectorAll('.color-circle');
    const colorPersonalizado = document.getElementById('colorPersonalizado');
    colorCircles.forEach(circle => {
        circle.addEventListener('click', function() {
            colorActual = this.getAttribute('data-color');
            colorCircles.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            colorPersonalizado.value = colorActual;
            modoBorrador = false;
            borradorBtn.style.background = "#fff";
            borradorBtn.style.color = "#333";
            borradorBtn.textContent = "Borrador";
        });
    });
    colorCircles[0].classList.add('selected');
    colorPersonalizado.addEventListener('input', function() {
        colorActual = this.value;
        colorCircles.forEach(c => c.classList.remove('selected'));
        modoBorrador = false;
        borradorBtn.style.background = "#fff";
        borradorBtn.style.color = "#333";
        borradorBtn.textContent = "Borrador";
    });
    document.getElementById("grosorPicker").addEventListener("input", function() {
        grosorActual = parseInt(this.value);
        document.getElementById("grosorValor").textContent = grosorActual;
    });
    borradorBtn.addEventListener("click", function() {
        modoBorrador = !modoBorrador;
        if (modoBorrador) {
            borradorBtn.style.background = "#ffb347";
            borradorBtn.style.color = "#fff";
            borradorBtn.textContent = "Lápiz";
        } else {
            borradorBtn.style.background = "#fff";
            borradorBtn.style.color = "#333";
            borradorBtn.textContent = "Borrador";
        }
    });
    canvas.addEventListener("mousedown", (e) => {
        dibujando = true;
        const rect = canvas.getBoundingClientRect();
        ctx.beginPath();
        ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
    });
    canvas.addEventListener("mouseup", () => {
        dibujando = false;
        ctx.beginPath();
    });
    canvas.addEventListener("mouseout", () => {
        dibujando = false;
        ctx.beginPath();
    });
    canvas.addEventListener("mousemove", function(e) {
        if (!dibujando) return;
        const rect = canvas.getBoundingClientRect();
        ctx.lineWidth = grosorActual;
        ctx.lineCap = "round";
        if (modoBorrador) {
            ctx.strokeStyle = '#fff';
            ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
            ctx.stroke();
        } else {
            ctx.strokeStyle = colorActual;
            ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
            ctx.stroke();
        }
    });
    function limpiarCanvas() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
    function guardarDibujo() {
        const dataURL = canvas.toDataURL("image/png");
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "mini.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                console.log("Dibujo guardado con éxito.");
                alert("¡Dibujo guardado con éxito!");
            }
        };
        xhr.send("imagen=" + encodeURIComponent(dataURL));
    }
    document.getElementById('descargarDibujo').addEventListener('click', () => {
        const link = document.createElement('a');
        link.download = `dibujo_${new Date().toISOString().replace(/[:.]/g, '-')}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    });

    // Lógica para pestañas
    const tabs = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.game-view');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(item => item.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(tab.dataset.tab).classList.add('active');
        });
    });

    // JUEGO ROMPECABEZAS (SLIDING PUZZLE)
let puzzle_size = 3; // Valor por defecto
let timer_rompecabezas;
let seconds_rompecabezas = 0;
let moves_rompecabezas = 0;
let is_running_rompecabezas = false;
let tiles_rompecabezas = [];

const puzzle_container = document.getElementById('puzzle-container');
const iniciar_btn_rompecabezas = document.getElementById('btn-iniciar-rompecabezas');
const reiniciar_btn_rompecabezas = document.getElementById('reiniciar-rompecabezas');
const tiempo_span_rompecabezas = document.getElementById('tiempo-rompecabezas');
const puntaje_span_rompecabezas = document.getElementById('puntaje-rompecabezas');
const puntaje_final_rompecabezas = document.getElementById('puntaje-final-rompecabezas');
const marcador_rompecabezas = document.getElementById('marcador-rompecabezas');

// Leer la dificultad asignada desde el input oculto
const dificultad_rompecabezas_input = document.querySelector('.dificultad-juego[data-juego="rompecabezas"]');
if (dificultad_rompecabezas_input) {
    const dificultad = parseInt(dificultad_rompecabezas_input.value);
    if (dificultad === 1) puzzle_size = 3;
    else if (dificultad === 2) puzzle_size = 4;
    else if (dificultad === 3) puzzle_size = 5;
    else if (dificultad === 4) puzzle_size = 6;
}

// Event Listeners para los botones
if (iniciar_btn_rompecabezas) {
    iniciar_btn_rompecabezas.addEventListener('click', iniciar_juego_rompecabezas);
}
if (reiniciar_btn_rompecabezas) {
    reiniciar_btn_rompecabezas.addEventListener('click', reiniciar_juego_rompecabezas);
}

// Inicia el juego
function iniciar_juego_rompecabezas() {
    if (is_running_rompecabezas) return;
    is_running_rompecabezas = true;

    setup_and_draw_puzzle();

    timer_rompecabezas = setInterval(() => {
        seconds_rompecabezas++;
        if (tiempo_span_rompecabezas) tiempo_span_rompecabezas.textContent = seconds_rompecabezas;
    }, 1000);

    if (iniciar_btn_rompecabezas) iniciar_btn_rompecabezas.style.display = 'none';
    if (reiniciar_btn_rompecabezas) reiniciar_btn_rompecabezas.style.display = 'inline-block';
    if (puzzle_container) puzzle_container.classList.remove('puzzle-deshabilitado');
    if (marcador_rompecabezas) marcador_rompecabezas.style.display = 'block';
    if (puntaje_final_rompecabezas) puntaje_final_rompecabezas.style.display = 'none';
}

// Reinicia el juego
function reiniciar_juego_rompecabezas() {
    clearInterval(timer_rompecabezas);
    is_running_rompecabezas = false;
    seconds_rompecabezas = 0;
    moves_rompecabezas = 0;
    if (tiempo_span_rompecabezas) tiempo_span_rompecabezas.textContent = seconds_rompecabezas;
    if (puntaje_span_rompecabezas) puntaje_span_rompecabezas.textContent = moves_rompecabezas;
    if (iniciar_btn_rompecabezas) iniciar_btn_rompecabezas.style.display = 'inline-block';
    if (reiniciar_btn_rompecabezas) reiniciar_btn_rompecabezas.style.display = 'none';
    if (puntaje_final_rompecabezas) puntaje_final_rompecabezas.style.display = 'none';
    if (marcador_rompecabezas) marcador_rompecabezas.style.display = 'none';
    if (puzzle_container) puzzle_container.classList.add('puzzle-deshabilitado');
    setup_and_draw_puzzle(); // Prepara el tablero para el siguiente juego
}

// Prepara el tablero (estado inicial y mezclado)
function setup_and_draw_puzzle() {
    const total_tiles = puzzle_size * puzzle_size;
    // Crea un arreglo ordenado de 1 a N-1, con el 0 para el hueco
    tiles_rompecabezas = Array.from({ length: total_tiles }, (_, i) => (i + 1) % total_tiles);

    // Mezcla el arreglo (algoritmo Fisher-Yates)
    for (let i = tiles_rompecabezas.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [tiles_rompecabezas[i], tiles_rompecabezas[j]] = [tiles_rompecabezas[j], tiles_rompecabezas[i]];
    }
    draw_puzzle();
}

// Dibuja el tablero en el HTML
function draw_puzzle() {
    if (!puzzle_container) return;
    puzzle_container.innerHTML = '';
    puzzle_container.style.gridTemplateColumns = `repeat(${puzzle_size}, 1fr)`;

    tiles_rompecabezas.forEach((value, index) => {
        const tile = document.createElement('div');
        tile.classList.add('tile');
        if (value === 0) {
            tile.classList.add('empty');
            tile.textContent = '';
        } else {
            tile.textContent = value;
        }
        tile.addEventListener('click', () => {
            if (is_running_rompecabezas) move_tile(index);
        });
        puzzle_container.appendChild(tile);
    });
}

// Mueve una pieza si es adyacente al hueco
function move_tile(tile_index) {
    const empty_index = tiles_rompecabezas.indexOf(0);
    if (is_adjacent(tile_index, empty_index)) {
        // Intercambia la pieza con el hueco
        [tiles_rompecabezas[tile_index], tiles_rompecabezas[empty_index]] = [tiles_rompecabezas[empty_index], tiles_rompecabezas[tile_index]];
        
        moves_rompecabezas++;
        if (puntaje_span_rompecabezas) puntaje_span_rompecabezas.textContent = moves_rompecabezas;

        draw_puzzle();
        check_win_rompecabezas();
    }
}

// *** LÓGICA CORREGIDA ***
// Verifica si dos índices son adyacentes en el tablero
function is_adjacent(idx1, idx2) {
    const row1 = Math.floor(idx1 / puzzle_size);
    const col1 = idx1 % puzzle_size;
    const row2 = Math.floor(idx2 / puzzle_size);
    const col2 = idx2 % puzzle_size;

    // Retorna true si están en la misma fila y columnas contiguas,
    // O si están en la misma columna y filas contiguas.
    return (Math.abs(row1 - row2) === 1 && col1 === col2) || (Math.abs(col1 - col2) === 1 && row1 === row2);
}

// Verifica si el jugador ha ganado
function check_win_rompecabezas() {
    // El juego se gana si todas las piezas están en orden (1, 2, 3, ..., 0)
    const is_solved = tiles_rompecabezas.every((value, index) => {
        // La última pieza debe ser 0, las demás deben ser index + 1
        return value === (index + 1) % tiles_rompecabezas.length;
    });

    if (is_solved) {
        clearInterval(timer_rompecabezas);
        is_running_rompecabezas = false;
        if (puzzle_container) puzzle_container.classList.add('puzzle-deshabilitado');
        
        // El puntaje es inverso a los movimientos y el tiempo
        let final_score = Math.max(1000 - (moves_rompecabezas * 10) - (seconds_rompecabezas * 5), 100);
        
        if (puntaje_final_rompecabezas) {
            puntaje_final_rompecabezas.innerHTML = `¡Felicidades! Ganaste.<br>Movimientos: ${moves_rompecabezas}<br>Tiempo: ${seconds_rompecabezas}s<br>Puntaje: ${final_score}`;
            puntaje_final_rompecabezas.style.display = 'block';
        }
        
        // Enviar puntaje a la base de datos
        enviarPuntaje('rompecabezas', final_score, seconds_rompecabezas, puzzle_size);
    }
}

    // JUEGO PUZZLE (DRAG & DROP)
    // Se modifican las variables para usar la dificultad asignada
    let gridSizePuzzle = 2; // Valor por defecto
    let tiempoPuzzle = 0;
    let puntajePuzzle = 0;
    let juegoEnMarchaPuzzle = false;
    let timerPuzzle;
    const puzzleContainerPuzzle = document.getElementById('puzzle-container-puzzle');
    const btnIniciarPuzzle = document.getElementById('btn-iniciar-puzzle');
    const reiniciarPuzzleBtn = document.getElementById('reiniciar-puzzle');
    const tiempoPuzzleSpan = document.getElementById('tiempo-puzzle');
    const puntajePuzzleSpan = document.getElementById('puntaje-puzzle');
    const puntajeFinalPuzzle = document.getElementById('puntaje-final-puzzle');
    const marcadorPuzzle = document.getElementById('marcador-puzzle');
    const images = ['img1.jpg', 'img2.jpg', 'img3.jpg', 'img4.jpg'];
    let selectedImage = 'ballena.jpg';
    let pieces = [];
    let isDragging = false;
    let draggedPiece = null;
    let originalX, originalY;
    
    const dificultad_puzzle_input = document.querySelector('.dificultad-juego[data-juego="puzzle"]');
    if (dificultad_puzzle_input) {
        const dificultad = parseInt(dificultad_puzzle_input.value);
        if (dificultad === 1) gridSizePuzzle = 2;
        else if (dificultad === 2) gridSizePuzzle = 3;
        else if (dificultad === 3) gridSizePuzzle = 4;
        else if (dificultad === 4) gridSizePuzzle = 5;
    }
    
    if (btnIniciarPuzzle) {
        btnIniciarPuzzle.addEventListener('click', iniciarJuegoPuzzle);
    }
    if (reiniciarPuzzleBtn) {
        reiniciarPuzzleBtn.addEventListener('click', reiniciarJuegoPuzzle);
    }
    
    function iniciarJuegoPuzzle() {
        if (!juegoEnMarchaPuzzle) {
            juegoEnMarchaPuzzle = true;
            tiempoPuzzle = 0;
            puntajePuzzle = 0;
            if (tiempoPuzzleSpan) tiempoPuzzleSpan.textContent = tiempoPuzzle;
            if (puntajePuzzleSpan) puntajePuzzleSpan.textContent = puntajePuzzle;
            if (puntajeFinalPuzzle) puntajeFinalPuzzle.style.display = 'none';
            if (puzzleContainerPuzzle) puzzleContainerPuzzle.classList.remove('puzzle-deshabilitado');
            if (marcadorPuzzle) marcadorPuzzle.style.display = 'block';
            if (btnIniciarPuzzle) btnIniciarPuzzle.style.display = 'none';
            if (reiniciarPuzzleBtn) reiniciarPuzzleBtn.style.display = 'inline-block';
            
            clearInterval(timerPuzzle);
            timerPuzzle = setInterval(() => {
                tiempoPuzzle++;
                if (tiempoPuzzleSpan) tiempoPuzzleSpan.textContent = tiempoPuzzle;
            }, 1000);
            
            cargarPuzzle();
        }
    }
    
    function reiniciarJuegoPuzzle() {
        juegoEnMarchaPuzzle = false;
        clearInterval(timerPuzzle);
        if (puzzleContainerPuzzle) puzzleContainerPuzzle.innerHTML = '';
        pieces = [];
        if (marcadorPuzzle) marcadorPuzzle.style.display = 'none';
        if (puntajeFinalPuzzle) puntajeFinalPuzzle.style.display = 'none';
        if (btnIniciarPuzzle) btnIniciarPuzzle.style.display = 'inline-block';
        if (reiniciarPuzzleBtn) reiniciarPuzzleBtn.style.display = 'none';
    }
    
    function cargarPuzzle() {
        if (!puzzleContainerPuzzle) return;
        puzzleContainerPuzzle.innerHTML = '';
        pieces = [];
        const image = new Image();
        image.src = `${selectedImage}`;
        image.onload = () => {
            const pieceWidth = image.width / gridSizePuzzle;
            const pieceHeight = image.height / gridSizePuzzle;
            const shuffledIndexes = [...Array(gridSizePuzzle * gridSizePuzzle).keys()].sort(() => Math.random() - 0.5);
            
            for (let i = 0; i < shuffledIndexes.length; i++) {
                const piece = document.createElement('div');
                piece.className = 'piece';
                piece.style.width = `${400 / gridSizePuzzle}px`;
                piece.style.height = `${400 / gridSizePuzzle}px`;
                
                const originalIndex = shuffledIndexes[i];
                const row = Math.floor(originalIndex / gridSizePuzzle);
                const col = originalIndex % gridSizePuzzle;
                
                piece.style.background = `url(${image.src})`;
                piece.style.backgroundSize = `400px 400px`;
                piece.style.backgroundPosition = `-${col * (400 / gridSizePuzzle)}px -${row * (400 / gridSizePuzzle)}px`;
                
                const initialRow = Math.floor(i / gridSizePuzzle);
                const initialCol = i % gridSizePuzzle;
                piece.style.top = `${initialRow * (400 / gridSizePuzzle)}px`;
                piece.style.left = `${initialCol * (400 / gridSizePuzzle)}px`;
                
                piece.dataset.originalIndex = originalIndex;
                piece.dataset.currentIndex = i;
                
                piece.addEventListener('mousedown', iniciarArrastre);
                pieces.push(piece);
                puzzleContainerPuzzle.appendChild(piece);
            }
        };
    }
    
    function iniciarArrastre(e) {
        if (!juegoEnMarchaPuzzle) return;
        isDragging = true;
        draggedPiece = this;
        draggedPiece.classList.add('selected');
        originalX = e.clientX;
        originalY = e.clientY;
        document.addEventListener('mousemove', arrastrarPieza);
        document.addEventListener('mouseup', soltarPieza);
    }
    
    function arrastrarPieza(e) {
        if (!isDragging) return;
        const dx = e.clientX - originalX;
        const dy = e.clientY - originalY;
        draggedPiece.style.transform = `translate(${dx}px, ${dy}px)`;
    }
    
    function soltarPieza(e) {
        if (!isDragging) return;
        isDragging = false;
        draggedPiece.classList.remove('selected');
        
        const rect = draggedPiece.getBoundingClientRect();
        const dropX = rect.left + rect.width / 2;
        const dropY = rect.top + rect.height / 2;
        
        let targetPiece = null;
        pieces.forEach(p => {
            if (p !== draggedPiece) {
                const targetRect = p.getBoundingClientRect();
                if (dropX > targetRect.left && dropX < targetRect.right && dropY > targetRect.top && dropY < targetRect.bottom) {
                    targetPiece = p;
                }
            }
        });
        
        if (targetPiece) {
            intercambiarPiezas(draggedPiece, targetPiece);
            puntajePuzzle++;
            if (puntajePuzzleSpan) puntajePuzzleSpan.textContent = puntajePuzzle;
            setTimeout(verificarVictoriaPuzzle, 500);
        }
        
        draggedPiece.style.transform = '';
        document.removeEventListener('mousemove', arrastrarPieza);
        document.removeEventListener('mouseup', soltarPieza);
    }
    
    function verificarVictoriaPuzzle() {
        let win = true;
        pieces.forEach(p => {
            if (parseInt(p.dataset.originalIndex) !== parseInt(p.dataset.currentIndex)) {
                win = false;
            }
        });
        
        if (win) {
            clearInterval(timerPuzzle);
            if (puntajeFinalPuzzle) {
                puntajeFinalPuzzle.textContent = `¡Felicidades! Completaste el puzzle en ${tiempoPuzzle} segundos y ${puntajePuzzle} movimientos.`;
                puntajeFinalPuzzle.style.display = 'block';
            }
            if (puzzleContainerPuzzle) puzzleContainerPuzzle.classList.add('puzzle-deshabilitado');
            juegoEnMarchaPuzzle = false;
            
            // Enviar puntaje a la base de datos
            enviarPuntaje('puzzle', puntajePuzzle, tiempoPuzzle, gridSizePuzzle);
        }
    }
ballena.jpg
    // JUEGO MEMORAMA
    // Se modifican las variables para usar la dificultad asignada
    let cartas = [];
    let cartasVolteadas = [];
    let parejasEncontradas = 0;
    let tiempoMemorama = 0;
    let puntajeMemorama = 0;
    let timerMemorama;
    let juegoEnMarchaMemorama = false;
    let grid_size_memorama = 4; // Valor por defecto
    const tablero = document.getElementById('tablero');
    const btnIniciarMemorama = document.getElementById('btn-iniciar-memorama');
    const reiniciarMemoramaBtn = document.getElementById('reiniciar-memorama');
    const tiempoMemoramaSpan = document.getElementById('tiempo-memorama');
    const puntajeMemoramaSpan = document.getElementById('puntaje-memorama');
    const puntajeFinalMemorama = document.getElementById('puntaje-final-memorama');
    const mensajeMemorama = document.getElementById('mensaje-memorama');
    const marcadorMemorama = document.getElementById('marcador-memorama');

    const dificultad_memorama_input = document.querySelector('.dificultad-juego[data-juego="memorama"]');
    if (dificultad_memorama_input) {
        const dificultad = parseInt(dificultad_memorama_input.value);
        if (dificultad === 1) grid_size_memorama = 4; // 4x4
        else if (dificultad === 2) grid_size_memorama = 5; // 5x5
        else if (dificultad === 3) grid_size_memorama = 6; // 6x6
        else if (dificultad === 4) grid_size_memorama = 7; // 7x7
    }

    if (btnIniciarMemorama) {
        btnIniciarMemorama.addEventListener('click', iniciarJuegoMemorama);
    }
    if (reiniciarMemoramaBtn) {
        reiniciarMemoramaBtn.addEventListener('click', reiniciarJuegoMemorama);
    }

    function iniciarJuegoMemorama() {
        if (!juegoEnMarchaMemorama) {
            juegoEnMarchaMemorama = true;
            tiempoMemorama = 0;
            puntajeMemorama = 0;
            parejasEncontradas = 0;
            if (tiempoMemoramaSpan) tiempoMemoramaSpan.textContent = tiempoMemorama;
            if (puntajeMemoramaSpan) puntajeMemoramaSpan.textContent = puntajeMemorama;
            if (mensajeMemorama) mensajeMemorama.textContent = '';
            if (puntajeFinalMemorama) puntajeFinalMemorama.style.display = 'none';
            if (tablero) tablero.classList.remove('tablero-deshabilitado');
            if (marcadorMemorama) marcadorMemorama.style.display = 'block';
            if (btnIniciarMemorama) btnIniciarMemorama.style.display = 'none';
            if (reiniciarMemoramaBtn) reiniciarMemoramaBtn.style.display = 'inline-block';
            
            clearInterval(timerMemorama);
            timerMemorama = setInterval(() => {
                tiempoMemorama++;
                if (tiempoMemoramaSpan) tiempoMemoramaSpan.textContent = tiempoMemorama;
            }, 1000);
            
            cartas = [];
            cartasVolteadas = [];
            crearTablero();
        }
    }

    function reiniciarJuegoMemorama() {
        juegoEnMarchaMemorama = false;
        clearInterval(timerMemorama);
        if (tablero) tablero.innerHTML = '';
        cartas = [];
        cartasVolteadas = [];
        parejasEncontradas = 0;
        if (marcadorMemorama) marcadorMemorama.style.display = 'none';
        if (mensajeMemorama) mensajeMemorama.textContent = '';
        if (puntajeFinalMemorama) puntajeFinalMemorama.style.display = 'none';
        if (btnIniciarMemorama) btnIniciarMemorama.style.display = 'inline-block';
        if (reiniciarMemoramaBtn) reiniciarMemoramaBtn.style.display = 'none';
    }

    function crearTablero() {
        if (!tablero) return;
        tablero.innerHTML = '';
        const simbolos = ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁', '🐮', '🐷', '🐸', '🐵', '🐔', '🐧', '🦆', '🦅', '🦉', '🦋', '🐠', '🐳', '🐬'];
        
        const cartasDobles = [];
        for (let i = 0; i < (grid_size_memorama * grid_size_memorama) / 2; i++) {
            cartasDobles.push(simbolos[i], simbolos[i]);
        }
        
        cartas = cartasDobles.sort(() => 0.5 - Math.random());
        
        tablero.style.gridTemplateColumns = `repeat(${grid_size_memorama}, 1fr)`;
        tablero.style.gridTemplateRows = `repeat(${grid_size_memorama}, 1fr)`;

        for (let i = 0; i < cartas.length; i++) {
            const carta = document.createElement('div');
            carta.classList.add('carta');
            carta.dataset.valor = cartas[i];
            carta.addEventListener('click', voltearCarta);
            tablero.appendChild(carta);
        }
    }

    function voltearCarta() {
        if (cartasVolteadas.length < 2 && !this.classList.contains('volteada') && !this.classList.contains('completada')) {
            this.classList.add('volteada');
            this.textContent = this.dataset.valor;
            cartasVolteadas.push(this);
        }

        if (cartasVolteadas.length === 2) {
            setTimeout(verificarCartas, 1000);
        }
    }

    function verificarCartas() {
        const [carta1, carta2] = cartasVolteadas;
        if (carta1.dataset.valor === carta2.dataset.valor) {
            carta1.classList.add('completada');
            carta2.classList.add('completada');
            parejasEncontradas++;
            puntajeMemorama += 100;
            if (puntajeMemoramaSpan) puntajeMemoramaSpan.textContent = puntajeMemorama;
            if (mensajeMemorama) mensajeMemorama.textContent = `¡Pareja encontrada! 🎉`;
            verificarVictoriaMemorama();
        } else {
            carta1.classList.remove('volteada');
            carta2.classList.remove('volteada');
            carta1.textContent = '';
            carta2.textContent = '';
            if (puntajeMemorama > 0) {
                puntajeMemorama -= 10;
                if (puntajeMemoramaSpan) puntajeMemoramaSpan.textContent = puntajeMemorama;
            }
            if (mensajeMemorama) mensajeMemorama.textContent = `¡Intenta de nuevo! 😔`;
        }
        cartasVolteadas = [];
    }

    function verificarVictoriaMemorama() {
        const totalParejas = (grid_size_memorama * grid_size_memorama) / 2;
        if (parejasEncontradas === totalParejas) {
            clearInterval(timerMemorama);
            juegoEnMarchaMemorama = false;
            if (mensajeMemorama) mensajeMemorama.textContent = `¡Felicidades! Completaste el memorama en ${tiempoMemorama} segundos con ${puntajeMemorama} puntos.`;
            if (puntajeFinalMemorama) puntajeFinalMemorama.style.display = 'block';
            if (tablero) tablero.classList.add('tablero-deshabilitado');

            // Enviar puntaje a la base de datos
            enviarPuntaje('memorama', puntajeMemorama, tiempoMemorama, grid_size_memorama);
        }
    }
    
    // Lógica para enviar puntaje al servidor
    function enviarPuntaje(juego, puntaje, tiempo, dificultad) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "guardar_puntaje.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                console.log("Puntaje guardado con éxito.");
            }
        };
        xhr.send(`juego=${juego}&puntaje=${puntaje}&tiempo=${tiempo}&dificultad=${dificultad}`);
    }

    </script>
    <footer class="footer">
  <p>Creado por: Edwin Abraham Hernández Rivero &nbsp;|&nbsp; Axel Abraham Esquivel Saucedo &nbsp;|&nbsp; Janemir Alexander Aguirre Rios</p>
</footer>

</body>
</html>