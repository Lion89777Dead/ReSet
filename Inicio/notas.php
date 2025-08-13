<?php
// 1. INICIAR SESIÓN Y CONECTAR A LA BASE DE DATOS (UNA SOLA VEZ)
//--------------------------------------------------------------------------
session_start();

$db_host = 'localhost';
$db_user = 'root';
$db_name = 'inicio';
$db_pass = '';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    die("Error crítico de conexión a la base de datos: " . $mysqli->connect_error);
}

// 2. MURO DE SEGURIDAD: VERIFICAR QUE EL PADRE HAYA INICIADO SESIÓN
//--------------------------------------------------------------------------
// Si la sesión 'id_padres' no existe, lo redirigimos y detenemos el script.
if (!isset($_SESSION['id_padres']) || !isset($_SESSION['usuario'])) {
    // Puedes cambiar 'login_padres.html' por tu página de login real
    header('Location: login_padres.html');
    exit();
}

// Si llegamos aquí, el login es válido. Asignamos las variables de sesión.
$id_padre = $_SESSION['id_padres'];
$usuario = $_SESSION['usuario'];


// 3. PROCESAMIENTO DE ACCIONES (POST/GET)
//--------------------------------------------------------------------------

// Primero, obtener las notas actuales del padre
$notas = [];
$stmt_get = $mysqli->prepare("SELECT Notas FROM padres WHERE Nombre = ?");
$stmt_get->bind_param("s", $usuario);
$stmt_get->execute();
$resultado = $stmt_get->get_result();
$fila = $resultado->fetch_assoc();
if ($fila && !empty($fila['Notas'])) {
    $notas = json_decode($fila['Notas'], true);
    if (!is_array($notas)) $notas = []; // Asegurar que sea un array
}
$stmt_get->close();

// Acción: Guardar una nota nueva enviada por el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["nota"])) {
    $nota_texto = trim($_POST["nota"]);
    if (!empty($nota_texto)) {
        $notas[] = [
            "texto" => $nota_texto,
            "fecha" => time(),
            "remitente" => "Padre", // Identificador para la nota del padre
            "respuesta" => "" 
        ];
        $nuevo_json = json_encode($notas);
        $stmt_update = $mysqli->prepare("UPDATE padres SET Notas = ? WHERE Nombre = ?");
        $stmt_update->bind_param("ss", $nuevo_json, $usuario);
        $stmt_update->execute();
        $stmt_update->close();
        
        // Redirigir para evitar reenvío del formulario
        header("Location: notas.php");
        exit();
    }
}

// Acción: Eliminar una nota
if (isset($_GET["eliminar"])) {
    $id = intval($_GET["eliminar"]);
    // Se puede eliminar solo si la nota es del padre y tiene menos de 30 minutos
    if (isset($notas[$id]) && isset($notas[$id]["remitente"]) && $notas[$id]["remitente"] == "Padre" && (time() - $notas[$id]["fecha"]) <= 1800) {
        array_splice($notas, $id, 1);
        $nuevo_json = json_encode($notas);
        $stmt_delete = $mysqli->prepare("UPDATE padres SET Notas = ? WHERE Nombre = ?");
        $stmt_delete->bind_param("ss", $nuevo_json, $usuario);
        $stmt_delete->execute();
        $stmt_delete->close();
    }
    header("Location: notas.php");
    exit();
}

// Acción: Editar una nota
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["editar_id"])) {
    $id = intval($_POST["editar_id"]);
    $nuevo_texto = trim($_POST["nuevo_texto"]);
    // Se puede editar solo si la nota es del padre y tiene menos de 30 minutos
    if (isset($notas[$id]) && isset($notas[$id]["remitente"]) && $notas[$id]["remitente"] == "Padre" && (time() - $notas[$id]["fecha"]) <= 1800) {
        $notas[$id]["texto"] = $nuevo_texto;
        $nuevo_json = json_encode($notas);
        $stmt_edit = $mysqli->prepare("UPDATE padres SET Notas = ? WHERE Nombre = ?");
        $stmt_edit->bind_param("ss", $nuevo_json, $usuario);
        $stmt_edit->execute();
        $stmt_edit->close();
    }
    header("Location: notas.php");
    exit();
}


// 4. FUNCIONES AUXILIARES
//--------------------------------------------------------------------------
function mostrarTiempo($segundos) {
    if ($segundos < 60) return "Hace $segundos segundo(s)";
    if ($segundos < 3600) { $min = floor($segundos / 60); return "Hace $min minuto(s)"; }
    if ($segundos < 86400) { $h = floor($segundos / 3600); return $h == 1 ? "Hace 1 hora" : "Hace $h horas"; }
    $d = floor($segundos / 86400);
    return $d == 1 ? "Hace 1 día" : "Hace $d dias";
}

// El script PHP termina aquí. Ahora empieza el HTML.
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
            white-space: pre-wrap; /* Para que el texto se ajuste */
        }
        .nota-acciones { margin-top: 16px; display: flex; gap: 12px; }
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
        .respuesta-especialista {
            background: #e6e6ff;
            border-left: 5px solid #6c63ff;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .respuesta-especialista strong {
            color: #4a4a8c;
        }
        .respuesta-especialista pre {
            background: none;
            border: none;
            padding: 0;
            margin-top: 5px;
        }
        .nota-especialista-enviada {
            background: #fff3e0;
            border-left: 5px solid #e67e22;
            border-radius: 8px;
            padding: 20px 22px 12px 22px;
            margin-bottom: 38px;
            box-shadow: 0 2px 12px #ececec;
            animation: fadein 1s;
        }
        .nota-especialista-enviada strong {
            color: #d35400;
        }
        .nota-especialista-enviada pre {
            background: none;
            border: none;
            padding: 0;
            margin-top: 5px;
        }
        @media (max-width:900px) {
            .container { max-width: 98vw; padding: 16px 4vw 10px 4vw;}
        }
        @media (max-width:600px) {
            .container { padding:10px 2vw 7px 2vw;}
            pre { font-size:1em; padding:13px 8px;}
            .nota-box { padding:13px 7px 7px 8px;}
            .nota-acciones { flex-direction: column; }
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
    <a href="htdocs/inicio/padres.php" class="btn-regresar">⬅ Regresar</a>
    <div class="container">
        <h1>Bloc de Notas</h1>

        <h2>Escribir Nueva Nota:</h2>
        <form method="post" action="notas.php">
            <textarea name="nota" placeholder="Escribe una nota para el especialista..."></textarea><br>
            <button type="submit">Guardar Nota</button>
        </form>

        <h2>Notas Recibidas y Enviadas:</h2>
        <?php if (empty($notas)): ?>
            <p>Aún no hay notas para mostrar.</p>
        <?php else: ?>
            <?php foreach (array_reverse($notas) as $i => $n): // Usamos array_reverse para mostrar la más nueva primero ?>
                <?php if (isset($n['remitente']) && $n['remitente'] == 'Especialista'): ?>
                    <div class="nota-especialista-enviada">
                        <strong>Nota del Especialista:</strong>
                        <pre><?php echo htmlspecialchars($n["texto"]); ?></pre>
                        <span class="nota-tiempo"><?php echo mostrarTiempo(time() - $n["fecha"]); ?></span>
                    </div>
                <?php else: ?>
                    <div class="nota-box">
                        <?php if (isset($_GET["editar"]) && $_GET["editar"] == $i): ?>
                            <form method="post" action="notas.php" class="edit-form">
                                <textarea name="nuevo_texto"><?php echo htmlspecialchars($n["texto"]); ?></textarea><br>
                                <input type="hidden" name="editar_id" value="<?php echo $i; ?>">
                                <button type="submit" class="btn">Guardar Cambios</button>
                                <a href="notas.php">Cancelar</a>
                            </form>
                        <?php else: ?>
                            <strong>Tu nota enviada:</strong>
                            <pre><?php echo htmlspecialchars($n["texto"]); ?></pre>
                            <span class="nota-tiempo"><?php echo mostrarTiempo(time() - $n["fecha"]); ?></span>
                            
                            <?php if ((time() - $n["fecha"]) <= 1800): // 30 minutos ?>
                                <div class="nota-acciones">
                                    <a href="notas.php?editar=<?php echo $i; ?>" class="btn" style="background:linear-gradient(90deg,#ffb347 60%,#ff7f50 100%);color:#fff;">Editar</a>
                                    <a href="notas.php?eliminar=<?php echo $i; ?>" class="btn" style="background:linear-gradient(90deg,#e74c3c 60%,#ff7f50 100%);color:#fff;" onclick="return confirm('¿Estás seguro de que quieres eliminar esta nota?');">Eliminar</a>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($n['respuesta'])): ?>
                                <div class="respuesta-especialista">
                                    <strong>Respuesta del Especialista:</strong>
                                    <pre><?php echo htmlspecialchars($n['respuesta']); ?></pre>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <footer class="footer">
  <p>Creado por: Edwin Abraham Hernández Rivero &nbsp;|&nbsp; Axel Abraham Esquivel Saucedo &nbsp;|&nbsp; Janemir Alexander Aguirre Rios</p>
</footer>

</body>
</html>