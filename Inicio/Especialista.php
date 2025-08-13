<?php
session_start();
// Conexión a la base de datos con manejo de errores
$mysqli = new mysqli("localhost", "root", "", "inicio");
if ($mysqli->connect_errno) {
    die("<div style='color:red;'>Error de conexión: " . htmlspecialchars($mysqli->connect_error) . "</div>");
}
$mensaje = "";
$mensaje_eval = "";
$error_eval = "";

// LÓGICA PARA ASIGNAR JUEGOS
$mensaje_asignacion = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["asignar_juegos"])) {
    $id_nino_seleccionado = $_POST["id_nino"] ?? '';
    $juegos_asignados = isset($_POST["juegos"]) ? $_POST["juegos"] : [];
    $dificultad = $_POST["dificultad_general"] ?? 1;

    if (!is_numeric($id_nino_seleccionado) || $id_nino_seleccionado <= 0) {
        $mensaje_asignacion = "Niño no válido para asignar juegos.";
    } else {
        // 1. Eliminar asignaciones anteriores para el niño
        if ($stmt = $mysqli->prepare("DELETE FROM asignaciones_juegos WHERE id_nino = ?")) {
            $stmt->bind_param("i", $id_nino_seleccionado);
            $stmt->execute();
            $stmt->close();
        }

        // 2. Insertar las nuevas asignaciones
        if (!empty($juegos_asignados) && is_array($juegos_asignados)) {
            foreach ($juegos_asignados as $juego_nombre) {
                if (!empty($juego_nombre) && $stmt = $mysqli->prepare("INSERT INTO asignaciones_juegos (id_nino, juego, dificultad) VALUES (?, ?, ?)")) {
                    $stmt->bind_param("isi", $id_nino_seleccionado, $juego_nombre, $dificultad);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            $mensaje_asignacion = "Juegos asignados correctamente al niño.";
        } else {
            $mensaje_asignacion = "No se asignaron juegos. Las asignaciones anteriores fueron borradas.";
        }
    }
}

// LÓGICA PARA RESPONDER NOTAS
$mensaje_respuesta = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["responder_nota"])) {
    $nombre_padre_responder = $_POST['nombre_padre_responder'] ?? '';
    $id_nota = $_POST['id_nota'] ?? '';
    $respuesta = $_POST['respuesta_nota'] ?? '';

    if (empty($nombre_padre_responder) || $id_nota === '' || empty($respuesta)) {
        $mensaje_respuesta = "Todos los campos son obligatorios para responder una nota.";
    } else {
        $notas = [];
        if ($res = $mysqli->prepare("SELECT Notas FROM padres WHERE Nombre = ?")) {
            $res->bind_param("s", $nombre_padre_responder);
            $res->execute();
            $res->bind_result($notas_json);
            if ($res->fetch() && $notas_json) {
                $notas = json_decode($notas_json, true);
                if (!is_array($notas)) $notas = [];
            }
            $res->close();
        }

        if (isset($notas[$id_nota])) {
            $notas[$id_nota]['respuesta'] = $respuesta;
            $nuevo_json = json_encode($notas);
            if ($stmt = $mysqli->prepare("UPDATE padres SET Notas = ? WHERE Nombre = ?")) {
                $stmt->bind_param("ss", $nuevo_json, $nombre_padre_responder);
                if ($stmt->execute()) {
                    $mensaje_respuesta = "Respuesta enviada correctamente.";
                } else {
                    $mensaje_respuesta = "Error al enviar la respuesta.";
                }
                $stmt->close();
            }
        }
    }
}

// NUEVA LÓGICA: ENVIAR NOTA DE ESPECIALISTA A PADRE
$mensaje_nota_especialista = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["enviar_nota_especialista"])) {
    $nombre_padre_destino = $_POST['padre_destino'] ?? '';
    $nota_especialista = trim($_POST['nota_especialista'] ?? '');

    if (empty($nombre_padre_destino) || empty($nota_especialista)) {
        $mensaje_nota_especialista = "Todos los campos son obligatorios para enviar una nota.";
    } else {
        $notas_padre = [];
        if ($res = $mysqli->prepare("SELECT Notas FROM padres WHERE Nombre = ?")) {
            $res->bind_param("s", $nombre_padre_destino);
            $res->execute();
            $res->bind_result($notas_json);
            if ($res->fetch() && $notas_json) {
                $notas_padre = json_decode($notas_json, true);
                if (!is_array($notas_padre)) $notas_padre = [];
            }
            $res->close();
        }

        $nueva_nota = [
            "texto" => $nota_especialista,
            "fecha" => time(),
            "remitente" => "Especialista"
        ];
        $notas_padre[] = $nueva_nota;
        $nuevo_json = json_encode($notas_padre);

        if ($stmt = $mysqli->prepare("UPDATE padres SET Notas = ? WHERE Nombre = ?")) {
            $stmt->bind_param("ss", $nuevo_json, $nombre_padre_destino);
            if ($stmt->execute()) {
                $mensaje_nota_especialista = "Nota enviada correctamente al padre " . htmlspecialchars($nombre_padre_destino) . ".";
            } else {
                $mensaje_nota_especialista = "Error al enviar la nota.";
            }
            $stmt->close();
        }
    }
}

// Obtener la lista de niños para el formulario
$niños_registrados = [];
if ($res_niños = $mysqli->query("SELECT ID, Nombre FROM inicio_niños ORDER BY Nombre")) {
    while ($row = $res_niños->fetch_assoc()) {
        $niños_registrados[] = $row;
    }
    $res_niños->close();
}

// Obtener la lista de padres para el formulario de notas
$padres_registrados = [];
if ($res_padres = $mysqli->query("SELECT ID, Nombre FROM inicio_padres ORDER BY Nombre")) {
    while ($row = $res_padres->fetch_assoc()) {
        $padres_registrados[] = $row;
    }
    $res_padres->close();
}

// Obtener notas para mostrar en la vista de especialista
$notas_padres_full = [];
if (isset($_GET['mostrar_notas_padre'])) {
    $nombre_padre_selecionado = $_GET['mostrar_notas_padre'];
    if ($res_notas = $mysqli->prepare("SELECT Notas FROM padres WHERE Nombre = ?")) {
        $res_notas->bind_param("s", $nombre_padre_selecionado);
        $res_notas->execute();
        $res_notas->bind_result($notas_json);
        if ($res_notas->fetch() && $notas_json) {
            $notas_padres_full = json_decode($notas_json, true);
            if (!is_array($notas_padres_full)) $notas_padres_full = [];
        }
        $res_notas->close();
    }
}

// --- REGISTRO DE NIÑO CON RELACIÓN AUTOMÁTICA ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["registrar_nino"])) {
    $nombre = isset($_POST["nombre"]) ? trim($_POST["nombre"]) : '';
    $contrasena = isset($_POST["contrasena"]) ? trim($_POST["contrasena"]) : '';
    $genero = isset($_POST["genero"]) ? trim($_POST["genero"]) : '';
    $edad = isset($_POST["edad"]) ? trim($_POST["edad"]) : '';
    $padre = isset($_POST["padre"]) ? trim($_POST["padre"]) : '';
    $madre = isset($_POST["madre"]) ? trim($_POST["madre"]) : '';
    $CURP = isset($_POST["CURP"]) ? trim($_POST["CURP"]) : '';

    if(empty($nombre) || empty($contrasena) || empty($genero) || empty($edad) || empty($padre) || empty($madre) || empty($CURP)){
        $mensaje = "Todos los campos son obligatorios para registrar un niño.";
    } else {
        if ($stmt = $mysqli->prepare("INSERT INTO inicio_niños (Nombre, Contraseña, Genero, Edad, Padre, Madre, CURP) VALUES (?, ?, ?, ?, ?, ?, ?)")) {
            $stmt->bind_param("sssssss", $nombre, $contrasena, $genero, $edad, $padre, $madre, $CURP);

            if ($stmt->execute()) {
                $nuevo_id = $stmt->insert_id;
                $stmt->close();

                if (!empty($nombre)) {
                    if ($stmt2 = $mysqli->prepare("INSERT INTO niños (ID, Nombre) VALUES (?, ?)")) {
                        $stmt2->bind_param("is", $nuevo_id, $nombre);
                        $stmt2->execute();
                        $stmt2->close();
                    }
                }

                $mensaje = "Niño registrado correctamente.";

                // Relación con el padre
                $ids_padre = [];
                if ($res_padre = $mysqli->prepare("SELECT ID FROM inicio_padres WHERE Nombre = ?")) {
                    $res_padre->bind_param("s", $padre);
                    $res_padre->execute();
                    $res_padre->bind_result($id_padre);
                    while ($res_padre->fetch()) { $ids_padre[] = $id_padre; }
                    $res_padre->close();
                }
                foreach ($ids_padre as $id_padre) {
                    if ($stmt_rel = $mysqli->prepare("INSERT INTO relacion_nino_padre (id_niños, id_padres, tipo) VALUES (?, ?, 'Padre')")) {
                        $stmt_rel->bind_param("ii", $nuevo_id, $id_padre);
                        $stmt_rel->execute();
                        $stmt_rel->close();
                    }
                }

                // Relación con la madre
                $ids_madre = [];
                if ($res_madre = $mysqli->prepare("SELECT ID FROM inicio_padres WHERE Nombre = ?")) {
                    $res_madre->bind_param("s", $madre);
                    $res_madre->execute();
                    $res_madre->bind_result($id_madre);
                    while ($res_madre->fetch()) { $ids_madre[] = $id_madre; }
                    $res_madre->close();
                }
                foreach ($ids_madre as $id_madre) {
                    if ($stmt_rel = $mysqli->prepare("INSERT INTO relacion_nino_padre (id_niños, id_padres, tipo) VALUES (?, ?, 'Madre')")) {
                        $stmt_rel->bind_param("ii", $nuevo_id, $id_madre);
                        $stmt_rel->execute();
                        $stmt_rel->close();
                    }
                }
            } else {
                $mensaje = "Error al registrar: " . htmlspecialchars($stmt->error);
                $stmt->close();
            }
        } else {
            $mensaje = "Error interno al preparar la consulta de registro de niño.";
        }
    }
}

// --- REGISTRO DE PADRE CON RELACIÓN AUTOMÁTICA ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["registrar_padre"])) {
    $nombre_padre = isset($_POST["nombre_padre"]) ? trim($_POST["nombre_padre"]) : '';
    $contrasena_padre = isset($_POST["contrasena_padre"]) ? trim($_POST["contrasena_padre"]) : '';
    $genero_padre = isset($_POST["genero_padre"]) ? trim($_POST["genero_padre"]) : '';
    $edad_padre = isset($_POST["edad_padre"]) ? trim($_POST["edad_padre"]) : '';
    $codigo_postal = isset($_POST["codigo_postal"]) ? trim($_POST["codigo_postal"]) : '';
    $telefono = isset($_POST["telefono"]) ? trim($_POST["telefono"]) : '';
    $calle = isset($_POST["calle"]) ? trim($_POST["calle"]) : '';
    $colonia = isset($_POST["colonia"]) ? trim($_POST["colonia"]) : '';
    $numero_vivienda = isset($_POST["numero_vivienda"]) ? trim($_POST["numero_vivienda"]) : '';
    $CURP = isset($_POST["CURP"]) ? trim($_POST["CURP"]) : '';

    if(empty($nombre_padre) || empty($contrasena_padre) || empty($genero_padre) || empty($edad_padre) || empty($codigo_postal) || empty($telefono) || empty($calle) || empty($colonia) || empty($numero_vivienda)){
        $mensaje = "Todos los campos son obligatorios para registrar un padre.";
    } else {
        if ($stmt = $mysqli->prepare("INSERT INTO inicio_padres (Nombre, Contraseña, Genero, Edad, Codigo_Postal, Telefono, Calle, Colonia, Numero_de_Vivienda, CURP) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
            $stmt->bind_param("ssssssssss", $nombre_padre, $contrasena_padre, $genero_padre, $edad_padre, $codigo_postal, $telefono, $calle, $colonia, $numero_vivienda, $CURP);

            if ($stmt->execute()) {
                $nuevo_id = $stmt->insert_id;
                $stmt->close();

                if (!empty($nombre_padre)) {
                    if ($stmt2 = $mysqli->prepare("INSERT INTO padres (ID, Nombre) VALUES (?, ?)")) {
                        $stmt2->bind_param("is", $nuevo_id, $nombre_padre);
                        $stmt2->execute();
                        $stmt2->close();
                    }
                }

                $mensaje = "Padre registrado correctamente.";

                // Relación como "Padre"
                $ids_ninos = [];
                if ($res_ninos = $mysqli->prepare("SELECT ID FROM inicio_niños WHERE Padre = ?")) {
                    $res_ninos->bind_param("s", $nombre_padre);
                    $res_ninos->execute();
                    $res_ninos->bind_result($id_nino);
                    while ($res_ninos->fetch()) { $ids_ninos[] = $id_nino; }
                    $res_ninos->close();
                }
                foreach ($ids_ninos as $id_nino) {
                    if ($stmt_rel = $mysqli->prepare("INSERT INTO relacion_nino_padre (id_niños, id_padres, tipo) VALUES (?, ?, 'Padre')")) {
                        $stmt_rel->bind_param("ii", $id_nino, $nuevo_id);
                        $stmt_rel->execute();
                        $stmt_rel->close();
                    }
                }

                // Relación como "Madre"
                $ids_ninos2 = [];
                if ($res_ninos2 = $mysqli->prepare("SELECT ID FROM inicio_niños WHERE Madre = ?")) {
                    $res_ninos2->bind_param("s", $nombre_padre);
                    $res_ninos2->execute();
                    $res_ninos2->bind_result($id_nino2);
                    while ($res_ninos2->fetch()) { $ids_ninos2[] = $id_nino2; }
                    $res_ninos2->close();
                }
                foreach ($ids_ninos2 as $id_nino2) {
                    if ($stmt_rel = $mysqli->prepare("INSERT INTO relacion_nino_padre (id_niños, id_padres, tipo) VALUES (?, ?, 'Madre')")) {
                        $stmt_rel->bind_param("ii", $id_nino2, $nuevo_id);
                        $stmt_rel->execute();
                        $stmt_rel->close();
                    }
                }
            } else {
                $mensaje = "Error al registrar padre: " . htmlspecialchars($stmt->error);
                $stmt->close();
            }
        } else {
            $mensaje = "Error interno al preparar la consulta de registro de padre.";
        }
    }
}

// Notificaciones no leídas
$num_no_leidas = 0;
if (isset($_SESSION['user_id'])) {
    $id_padre = $_SESSION['user_id'];
    if ($res = $mysqli->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_padres = ? AND leido = 0")) {
        $res->bind_param("i", $id_padre);
        $res->execute();
        $res->bind_result($num_no_leidas);
        $res->fetch();
        $res->close();
    }
}

// GRÁFICAS: Obtener datos de todos los juegos desde la tabla 'juego'
function safe_chart_row($nombre, $puntaje, $tiempo) {
    $nombre = addslashes($nombre);
    $puntaje = is_numeric($puntaje) ? intval($puntaje) : 0;
    $tiempo = is_numeric($tiempo) ? intval($tiempo) : 0;
    return "['{$nombre}',{$puntaje},{$tiempo}]";
}

$datos_juegos = [];
$res = $mysqli->query("SELECT Nombre, Puntaje, Tiempo, Juego, Dificultad FROM juegos ORDER BY Juego, Puntaje DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $datos_juegos[] = $row;
    }
    $res->close();
}

$datos_memorama = array_filter($datos_juegos, function($item) { return $item['Juego'] == 'memorama'; });
$datos_puzzle = array_filter($datos_juegos, function($item) { return $item['Juego'] == 'puzzle'; });
$datos_rompecabezas = array_filter($datos_juegos, function($item) { return $item['Juego'] == 'rompecabezas'; });

$dificultad_memorama = !empty($datos_memorama) ? current($datos_memorama)['Dificultad'] : 'No disponible';
$dificultad_puzzle = !empty($datos_puzzle) ? current($datos_puzzle)['Dificultad'] : 'No disponible';
$dificultad_rompecabezas = !empty($datos_rompecabezas) ? current($datos_rompecabezas)['Dificultad'] : 'No disponible';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Módulo Especialista</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
  background: linear-gradient(135deg, #f5f6fa 0%, #e9ecf4 100%); /* degradado suave */
  background-attachment: fixed;
}

/* Opcional: efecto de patrón muy sutil para dar textura */
body::before {
  content: "";
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: radial-gradient(rgba(255,255,255,0.4) 1px, transparent 1px);
  background-size: 40px 40px;
  z-index: -1;
}
        :root { --accent: #6c63ff; --accent2: #ff7f50; --bg: #f5f6fa; --panel: #fff; --border: #ececec; }
        body { font-family: 'Inter', Arial, sans-serif; background: var(--bg); margin: 0; min-height: 100vh;}
        .container { max-width: 880px; min-width: 340px; margin: 48px auto 0 auto; background: var(--panel); border-radius: 20px; box-shadow: 0 6px 32px rgba(108,99,255,0.07); padding: 48px 48px 30px 48px; animation: fadein 1s; display: flex; flex-direction: column; gap: 0px;}
        @keyframes fadein { from {transform:translateY(30px);opacity:0;} to {transform:translateY(0);opacity:1;} }
        h1 { color: var(--accent); font-weight: 800; margin-bottom: 18px; font-size: 2.3em; letter-spacing:0.02em;}
        h2 { font-weight: 600; color: #222; margin-top:38px; margin-bottom:18px; font-size: 1.38em;}
        .actions { margin-top: 30px; display: flex; flex-direction: column; align-items: center; gap: 10px;}
        .action-row { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .actions a, .actions button { margin: 0; padding: 14px 38px; background: linear-gradient(90deg, var(--accent) 60%, var(--accent2) 100%); color: #fff; border-radius: 13px; text-decoration: none; font-weight: 700; transition: background 0.2s, transform 0.17s; border: none; outline: none; cursor: pointer; box-shadow: 0 2px 12px rgba(108,99,255,0.08); font-size: 1.13em;}
        .actions a:hover, .actions button:hover { background: linear-gradient(90deg, var(--accent2) 60%, var(--accent) 100%); transform: scale(1.05);}
        .actions button[data-grafica] { background: linear-gradient(90deg,#ffb347 60%,#ff7f50 100%); color: #fff;}
        .actions button[data-grafica]:hover { background: linear-gradient(90deg,#ff7f50 60%,#ffb347 100%);}
        .actions button[data-nino] { background: linear-gradient(90deg,#27ae60 60%,#6c63ff 100%); color: #fff;}
        .actions button[data-nino]:hover { background: linear-gradient(90deg,#6c63ff 60%,#27ae60 100%);}
        .actions button[data-padre] { background: linear-gradient(90deg,#2980b9 60%,#6c63ff 100%); color: #fff;}
        .actions button[data-padre]:hover { background: linear-gradient(90deg,#6c63ff 60%,#2980b9 100%);}
        .actions button[data-juegos] { background: linear-gradient(90deg,#8e44ad 60%,#9b59b6 100%); color: #fff;}
        .actions button[data-juegos]:hover { background: linear-gradient(90deg,#9b59b6 60%,#8e44ad 100%);}
        .actions button[data-notas] { background: linear-gradient(90deg,#34495e 60%,#6c7a89 100%); color: #fff;}
        .actions button[data-notas]:hover { background: linear-gradient(90deg,#6c7a89 60%,#34495e 100%);}
        .actions button[data-notas-especialista] { background: linear-gradient(90deg,#e67e22 60%,#f39c12 100%); color: #fff;}
        .actions button[data-notas-especialista]:hover { background: linear-gradient(90deg,#f39c12 60%,#e67e22 100%);}
        form.registro-nino { margin-top: 40px; text-align: left; background: #fafaff; padding: 28px 18px; border-radius: 14px; box-shadow: 0 2px 12px rgba(108,99,255,0.08); border:1.5px solid #f2f2f2;}
        form.registro-nino label { font-weight: bold; color: var(--accent);}
        form.registro-nino input, form.registro-nino select { width: 100%; padding: 10px; margin-bottom: 20px; border-radius: 8px; border: 1.5px solid #ccc; font-size:1.05em; background: #f8f8ff;}
        form.registro-nino input:focus, form.registro-nino select:focus { border-color: var(--accent2);}
        .mensaje { margin: 18px 0; padding: 15px; background-color: #e8f5e9; color: #388e3c; border: 1px solid #c8e6c9; border-radius: 8px; font-size: 1.1em; text-align: center;}
        #graficas-container, #formRevisarNotas, #formNotasEspecialista { display: none; margin-top: 40px; padding: 24px 18px; background: #fafaff; border-radius: 14px; box-shadow: 0 2px 12px rgba(108,99,255,0.08); border:1.5px solid #f2f2f2;}
        .grafica-box { margin-bottom: 34px;}
        .grafica-titulo { font-size: 1.25em; color: var(--accent); margin-bottom: 10px; font-weight: bold;}
        .notas-list { margin-top: 20px;}
        .nota-especialista { background-color: #f2f4f6; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px;}
        .nota-especialista pre { white-space: pre-wrap; font-family: 'Inter', Arial, sans-serif;}
        .nota-especialista h4 { color: #34495e; margin-top: 0; margin-bottom: 10px;}
        @media (max-width:900px) { .container { max-width: 98vw; padding: 18px 4vw 10px 4vw;} .action-row { flex-direction: column; } }
        @media (max-width:600px) { .container { padding:10px 2vw 7px 2vw;} .grafica-box {padding:2px;} form.registro-nino {padding:10px 2vw;} }
        table{width:100%;border-collapse:collapse;margin:20px 0 30px 0;background:var(--panel);border-radius:12px;overflow:hidden;box-shadow:0 4px 24px var(--border);font-size:1em;}
th,td{padding:13px 18px;text-align:left;}
th{background:linear-gradient(90deg,var(--accent) 70%,var(--accent2) 100%);color:#fff;font-weight:bold;letter-spacing:.5px;border-bottom:2px solid #e4e4e4;}
tr:nth-child(even){background-color:#f7f7fa;}
tr:hover{background-color:#f0ebfa;}
td{border-bottom:1px solid #ededed;color:#333;}
caption{caption-side:top;font-size:1.1em;margin-bottom:8px;font-weight:bold;color:var(--accent);}
form.registro-nino label { font-weight: bold; color: var(--accent);}
form.registro-nino input, form.registro-nino select, form.registro-nino textarea {
  width: 100%; padding: 10px; margin-bottom: 20px; border-radius: 8px; border: 1.5px solid #ccc; font-size:1.05em; background: #f8f8ff;
}
form.registro-nino input:focus, form.registro-nino select:focus, form.registro-nino textarea:focus { border-color: var(--accent2);}
.main-logo {
  display: block;                  /* Para poder centrar */
  margin: 0 auto;                   /* Centrado horizontal */
  max-width: 400px;                 /* Ajusta el tamaño si quieres */
  animation: fadeInUp 1.2s ease-out; /* Aplica la animación */
  position: relative;                        /* Centrado vertical */
}
.logo-container {
  display: flex;
  justify-content: center;
  margin-top: 40px; /* Ajusta este valor para bajarlo más */
}
.main-logo {
  margin-top: 60px; /* Ajusta este valor para bajar más el logo */
  top: auto;        /* Evita el centrado vertical forzado */
  transform: none;  /* Quita el desplazamiento -50% */
}

/* Animación personalizada */
@keyframes fadeInUp {
  0% {
    opacity: 0;
    transform: translateY(20px);
  }
  100% {
    opacity: 1;
    transform: translateY(-50%);
  }
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

.footer p {
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
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
</head>
<body>
    <div class="container">
        <img src="img/logo_reset.png" alt="Logo ReSet" class="main-logo">
        <?php if ($mensaje): ?>
            <div class="mensaje"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        
        <?php if ($mensaje_asignacion): ?>
            <div class="mensaje"><?php echo htmlspecialchars($mensaje_asignacion); ?></div>
        <?php endif; ?>
        <?php if ($mensaje_respuesta): ?>
            <div class="mensaje"><?php echo htmlspecialchars($mensaje_respuesta); ?></div>
        <?php endif; ?>
        <?php if ($mensaje_nota_especialista): ?>
            <div class="mensaje"><?php echo htmlspecialchars($mensaje_nota_especialista); ?></div>
        <?php endif; ?>

        <div class="actions">
            <div class="action-row">
                <a href="register.php">Registrar Usuario</a>
                <a href="Modificacion.php">Modificar Usuario</a>
            </div>
            <div class="action-row">
                <button id="toggleRegistroNino" type="button" data-nino>Registrar Niño</button>
                <button id="toggleRegistroPadre" type="button" data-padre>Registrar Padre</button>
            </div>
            <div class="action-row">
                <button id="toggleAsignarJuegos" type="button" data-juegos>Asignar Minijuegos</button>
                <button id="toggleGraficas" type="button" data-grafica>Ver gráficas de puntajes</button>
            </div>
            <div class="action-row">
                <button id="toggleRevisarNotas" type="button" data-notas>Revisar Notas</button>
                <button id="toggleNotasEspecialista" type="button" data-notas-especialista>Enviar Notas a Padres</button>
            </div>
            <div class="action-row">
                <a href="htdocs/inicio/psicologa.php" class="btn-regresar">⬅ Regresar</a>
            </div>
        </div>

        <!-- Botones fijos en la esquina superior derecha -->
        <div style="position:fixed;top:20px;right:30px;display:flex;gap:15px;z-index:999;">
            <!-- Botón de Notificaciones -->
            <a href="notificaciones.php" style="position:relative;display:inline-block;">
                <img src="img/campana.png" alt="Notificaciones" style="width:36px;">
                <?php if (isset($num_no_leidas) && $num_no_leidas > 0): ?>
                    <span style="position:absolute;top:-8px;right:-8px;background:#e74c3c;color:#fff;border-radius:50%;padding:2px 8px;font-size:14px;"><?php echo $num_no_leidas; ?></span>
                <?php endif; ?>
            </a>
            <!-- Botón de Buscar Usuarios -->
            <a href="buscar_usuarios.php" 
               title="Buscar Usuarios"
               style="display:inline-block;width:36px;height:36px;border-radius:50%;overflow:hidden;box-shadow:0 2px 8px #ececec;background:url('img/libro.png') center center/cover no-repeat;border:2px solid #fafafaff;">
               <!-- Puedes agregar un icono encima si deseas:
               <img src="img/lupa.png" style="width:22px;margin:7px;filter:drop-shadow(0 2px 5px #fff7);" alt="Buscar"> 
               -->
            </a>
        </div>
        
        <div id="formAsignarJuegos" style="display:none;">
            <form class="registro-nino" method="POST">
                <h2 style="color:var(--accent);font-weight:700;">Asignar Juegos a un Niño</h2>
                <label for="id_nino">Seleccionar Niño:</label>
                <select name="id_nino" id="id_nino" required>
                    <option value="">-- Seleccione un niño --</option>
                    <?php foreach ($niños_registrados as $nino): ?>
                        <option value="<?php echo htmlspecialchars($nino['ID']); ?>"><?php echo htmlspecialchars($nino['Nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <div style="margin-top: 20px;">
                    <label>Seleccionar Juegos:</label>
                    <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 10px;">
                        <div>
                            <input type="checkbox" id="juego_memorama" name="juegos[]" value="memorama">
                            <label for="juego_memorama">Memorama</label>
                        </div>
                        <div>
                            <input type="checkbox" id="juego_puzzle" name="juegos[]" value="puzzle">
                            <label for="juego_puzzle">Puzzle</label>
                        </div>
                        <div>
                            <input type="checkbox" id="juego_rompecabezas" name="juegos[]" value="rompecabezas">
                            <label for="juego_rompecabezas">Rompecabezas</label>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <label for="dificultad_general">Dificultad:</label>
                    <select name="dificultad_general" id="dificultad_general" style="width: 100%; margin-top: 10px;">
                        <option value="1">Dificultad 1</option>
                        <option value="2">Dificultad 2</option>
                        <option value="3">Dificultad 3</option>
                        <option value="4">Dificultad 4</option>
                    </select>
                </div>
                
                <button type="submit" name="asignar_juegos" style="background:linear-gradient(90deg,#8e44ad 60%,#9b59b6 100%);color:#fff;">Asignar Juegos</button>
                <button type="button" id="ocultarAsignarJuegos" style="background:linear-gradient(90deg,#e74c3c 60%,#ff7f50 100%);color:#fff;margin-top:10px;">Ocultar</button>
            </form>
        </div>


        <div id="formRegistroNino" style="display:none;">
            <form class="registro-nino" method="POST">
                <h2 style="color:var(--accent);font-weight:700;">Registrar Niño</h2>
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

                <label for="CURP">CURP:</label>
                <input type="text" name="CURP" id="CURP" required>

                <button type="submit" name="registrar_nino" style="background:linear-gradient(90deg,#27ae60 60%,#6c63ff 100%);color:#fff;">Registrar Niño</button>
                <button type="button" id="ocultarRegistroNino" style="background:linear-gradient(90deg,#e74c3c 60%,#ff7f50 100%);color:#fff;margin-left:10px;margin-top:10px;">Ocultar</button>
            </form>
        </div>

        <div id="formRegistroPadre" style="display:none;">
            <form class="registro-nino" method="POST">
                <h2 style="color:var(--accent);font-weight:700;">Registrar Padre</h2>
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

                <label for="CURP">CURP</label>
                <input type="text" name="CURP" id="CURP" required>

                <button type="submit" name="registrar_padre" style="background:linear-gradient(90deg,#2980b9 60%,#6c63ff 100%);color:#fff;">Registrar Padre</button>
                <button type="button" id="ocultarRegistroPadre" style="background:linear-gradient(90deg,#e74c3c 60%,#ff7f50 100%);color:#fff;margin-left:10px;margin-top:10px;">Ocultar</button>
            </form>
        </div>

        <div id="formRevisarNotas">
            <h2 style="color:var(--accent);font-weight:700;">Revisar y Responder Notas</h2>
            <form method="GET">
                <label for="padre_selector">Seleccionar Padre:</label>
                <select name="mostrar_notas_padre" id="padre_selector" onchange="this.form.submit()">
                    <option value="">-- Seleccione un padre --</option>
                    <?php foreach ($padres_registrados as $padre): ?>
                        <option value="<?php echo htmlspecialchars($padre['Nombre']); ?>" <?php if(isset($_GET['mostrar_notas_padre']) && $_GET['mostrar_notas_padre'] == $padre['Nombre']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($padre['Nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <div class="notas-list">
                <?php if (!empty($notas_padres_full)): ?>
                    <?php foreach ($notas_padres_full as $id_nota => $nota): ?>
                        <div class="nota-especialista">
                            <h4>Nota de <?php echo htmlspecialchars($_GET['mostrar_notas_padre']); ?>:</h4>
                            <pre><?php echo htmlspecialchars($nota['texto']); ?></pre>
                            <?php if (isset($nota['respuesta'])): ?>
                                <div style="background-color: #e6e6ff; border: 1px solid #c0c0ff; border-radius: 8px; padding: 10px; margin-top: 10px;">
                                    <strong>Respuesta del especialista:</strong>
                                    <pre style="background:transparent; border:none; padding:0;"><?php echo htmlspecialchars($nota['respuesta']); ?></pre>
                                </div>
                            <?php else: ?>
                                <form method="POST" style="margin-top: 15px;">
                                    <input type="hidden" name="nombre_padre_responder" value="<?php echo htmlspecialchars($_GET['mostrar_notas_padre']); ?>">
                                    <input type="hidden" name="id_nota" value="<?php echo $id_nota; ?>">
                                    <textarea name="respuesta_nota" placeholder="Escribe tu respuesta..." style="width:100%; min-height:80px;"></textarea>
                                    <button type="submit" name="responder_nota" style="margin-top: 10px; padding: 8px 20px; font-size: 1em; background:linear-gradient(90deg,#2980b9 60%,#6c63ff 100%);">Enviar Respuesta</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" id="ocultarRevisarNotas" style="background:linear-gradient(90deg,#e74c3c 60%,#ff7f50 100%);color:#fff;margin-left:10px;margin-top:10px;">Ocultar</button>
        </div>

        <div id="formNotasEspecialista">
            <h2 style="color:var(--accent);font-weight:700;">Enviar Nota a un Padre</h2>
            <form class="registro-nino" method="POST">
                <label for="padre_destino">Seleccionar Padre:</label>
                <select name="padre_destino" id="padre_destino" required>
                    <option value="">-- Seleccione un padre --</option>
                    <?php foreach ($padres_registrados as $padre): ?>
                        <option value="<?php echo htmlspecialchars($padre['Nombre']); ?>">
                            <?php echo htmlspecialchars($padre['Nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="nota_especialista">Escribir nota:</label>
                <textarea name="nota_especialista" id="nota_especialista" required style="min-height: 150px;"></textarea>
                <button type="submit" name="enviar_nota_especialista" style="background:linear-gradient(90deg,#e67e22 60%,#f39c12 100%);color:#fff;">Enviar Nota</button>
                <button type="button" id="ocultarNotasEspecialista" style="background:linear-gradient(90deg,#e74c3c 60%,#ff7f50 100%);color:#fff;margin-left:10px;margin-top:10px;">Ocultar</button>
            </form>
        </div>


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
   <footer class="footer">
  <p>Creado por: Edwin Abraham Hernández Rivero &nbsp;|&nbsp; Axel Abraham Esquivel Saucedo &nbsp;|&nbsp; Janemir Alexander Aguirre Rios</p>
</footer>
</body>
</html>

<script>
    // Ventanas desplegables
    const toggleNinoBtn = document.getElementById('toggleRegistroNino');
    const togglePadreBtn = document.getElementById('toggleRegistroPadre');
    const toggleAsignarJuegosBtn = document.getElementById('toggleAsignarJuegos');
    const toggleRevisarNotasBtn = document.getElementById('toggleRevisarNotas'); 
    const toggleNotasEspecialistaBtn = document.getElementById('toggleNotasEspecialista'); // Nuevo botón
    const formNinoDiv = document.getElementById('formRegistroNino');
    const formPadreDiv = document.getElementById('formRegistroPadre');
    const formAsignarJuegosDiv = document.getElementById('formAsignarJuegos');
    const formRevisarNotasDiv = document.getElementById('formRevisarNotas'); 
    const formNotasEspecialistaDiv = document.getElementById('formNotasEspecialista'); // Nuevo formulario
    const ocultarNinoBtn = document.getElementById('ocultarRegistroNino');
    const ocultarPadreBtn = document.getElementById('ocultarRegistroPadre');
    const ocultarAsignarJuegosBtn = document.getElementById('ocultarAsignarJuegos');
    const ocultarRevisarNotasBtn = document.getElementById('ocultarRevisarNotas'); 
    const ocultarNotasEspecialistaBtn = document.getElementById('ocultarNotasEspecialista'); // Nuevo botón de ocultar
    const toggleGraficasBtn = document.getElementById('toggleGraficas');
    const graficasContainer = document.getElementById('graficas-container');

    toggleNinoBtn.addEventListener('click', () => {
        formNinoDiv.style.display = 'block';
        formPadreDiv.style.display = 'none';
        formAsignarJuegosDiv.style.display = 'none';
        formRevisarNotasDiv.style.display = 'none'; 
        formNotasEspecialistaDiv.style.display = 'none'; // Oculta la nueva ventana
        graficasContainer.style.display = 'none';
    });

    togglePadreBtn.addEventListener('click', () => {
        formPadreDiv.style.display = 'block';
        formNinoDiv.style.display = 'none';
        formAsignarJuegosDiv.style.display = 'none';
        formRevisarNotasDiv.style.display = 'none'; 
        formNotasEspecialistaDiv.style.display = 'none'; // Oculta la nueva ventana
        graficasContainer.style.display = 'none';
    });
    
    toggleAsignarJuegosBtn.addEventListener('click', () => {
        formAsignarJuegosDiv.style.display = 'block';
        formNinoDiv.style.display = 'none';
        formPadreDiv.style.display = 'none';
        formRevisarNotasDiv.style.display = 'none'; 
        formNotasEspecialistaDiv.style.display = 'none'; // Oculta la nueva ventana
        graficasContainer.style.display = 'none';
    });
    
    toggleRevisarNotasBtn.addEventListener('click', () => { 
        formRevisarNotasDiv.style.display = 'block';
        formNinoDiv.style.display = 'none';
        formPadreDiv.style.display = 'none';
        formAsignarJuegosDiv.style.display = 'none';
        formNotasEspecialistaDiv.style.display = 'none'; // Oculta la nueva ventana
        graficasContainer.style.display = 'none';
    });

    toggleNotasEspecialistaBtn.addEventListener('click', () => { // Nuevo evento
        formNotasEspecialistaDiv.style.display = 'block';
        formNinoDiv.style.display = 'none';
        formPadreDiv.style.display = 'none';
        formAsignarJuegosDiv.style.display = 'none';
        formRevisarNotasDiv.style.display = 'none'; 
        graficasContainer.style.display = 'none';
    });


    ocultarNinoBtn.addEventListener('click', () => {
        formNinoDiv.style.display = 'none';
    });

    ocultarPadreBtn.addEventListener('click', () => {
        formPadreDiv.style.display = 'none';
    });

    ocultarAsignarJuegosBtn.addEventListener('click', () => {
        formAsignarJuegosDiv.style.display = 'none';
    });

    ocultarRevisarNotasBtn.addEventListener('click', () => { 
        formRevisarNotasDiv.style.display = 'none';
    });

    ocultarNotasEspecialistaBtn.addEventListener('click', () => { // Nuevo evento para ocultar
        formNotasEspecialistaDiv.style.display = 'none';
    });


    toggleGraficasBtn.addEventListener('click', () => {
        graficasContainer.style.display = 'block';
        formNinoDiv.style.display = 'none';
        formPadreDiv.style.display = 'none';
        formAsignarJuegosDiv.style.display = 'none';
        formRevisarNotasDiv.style.display = 'none';
        formNotasEspecialistaDiv.style.display = 'none';
        drawAllCharts();
    });

    // Google Charts
    google.charts.load('current', {'packages':['corechart']});
    function drawAllCharts() {
        google.charts.setOnLoadCallback(drawMemoramaChart);
        google.charts.setOnLoadCallback(drawPuzzleChart);
        google.charts.setOnLoadCallback(drawRompecabezasChart);
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



