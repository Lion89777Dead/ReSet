<?php
session_start();
$usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Juego de Memorama</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
      #marcador {
          margin-bottom: 12px;
          font-weight: bold;
          font-size: 1.1em;
      }
      .btn-chido {
          margin: 10px 8px;
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
      }
      .btn-chido:hover {
          background: linear-gradient(90deg, #ff7f50 60%, #6c63ff 100%);
          transform: scale(1.07);
      }
      #puntaje-final {
          margin-top: 18px;
          font-size: 1.35em;
          color: #ff7f50;
          font-weight: bold;
          display: none;
      }
      .btn-chido {
          margin: 10px 8px;
          padding: 12px 28px;
          border: none;
          border-radius: 14px;
          font-size: 18px;
          font-weight: bold;
          cursor: pointer;
          box-shadow: 1px 2px 8px #ffd6e0;
          transition: background 0.2s, transform 0.2s;
          outline: none;
      }
      .btn-chido.iniciar {
          background: linear-gradient(90deg, #6c63ff 60%, #a084ee 100%);
          color: #fff;
      }
      .btn-chido.iniciar:hover {
          background: linear-gradient(90deg, #a084ee 60%, #6c63ff 100%);
          transform: scale(1.07);
      }
      .btn-chido.reiniciar {
          background: linear-gradient(90deg, #6c63ff 60%, #a084ee 100%);
          color: #fff;
      }
      .btn-chido.reiniciar:hover {
          background: linear-gradient(90deg, #a084ee 60%, #6c63ff 100%);
          transform: scale(1.07);
      }
    </style>
</head>
<body>
    <h1>Memorama</h1>
    <div id="controles">
        <button id="btn-facil" class="btn-chido">Fácil (12 cartas)</button>
        <button id="btn-medio" class="btn-chido">Medio (16 cartas)</button>
        <button id="btn-dificil" class="btn-chido">Difícil (20 cartas)</button>
        <button id="btn-experto" class="btn-chido">Experto (24 cartas)</button>
    </div>
    <div id="acciones">
        <button id="btn-iniciar" class="btn-chido iniciar">Iniciar Juego</button>
        <button id="reiniciar" class="btn-chido reiniciar">Reiniciar</button>
    </div>
    <div id="marcador">
      Tiempo: <span id="tiempo">0</span> seg |
      Puntaje: <span id="puntaje">0</span>
    </div>
    <div id="tablero"></div>
    <div id="mensaje"></div>
    <div id="puntaje-final"></div>
    <script src="script.js"></script>
    <script>
    // --------- MARCADOR Y TIEMPO ----------
    const usuario = <?php echo json_encode($usuario); ?>;
    let tiempo = 0;
    let timer = null;
    let puntaje = 0;
    let juegoIniciado = false;
    let dificultadSeleccionada = 12; // Dificultad por defecto

    function iniciarJuego() {
        if (juegoIniciado) return;
        juegoIniciado = true;
        habilitarJuego(); // Función en script.js para habilitar la interacción
        tiempo = 0;
        document.getElementById("tiempo").textContent = tiempo;
        detenerCronometro();
        timer = setInterval(() => {
            tiempo++;
            document.getElementById("tiempo").textContent = tiempo;
        }, 1000);
    }

    function detenerCronometro() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    function reiniciarJuegoTotal() {
        juegoIniciado = false;
        detenerCronometro();
        tiempo = 0;
        puntaje = 0;
        document.getElementById("tiempo").textContent = tiempo;
        document.getElementById("puntaje").textContent = "0";
        if (typeof crearTableroConDificultad === "function") crearTableroConDificultad(dificultadSeleccionada);
        document.getElementById("puntaje-final").style.display = "none";
        deshabilitarJuego(); // Función en script.js para deshabilitar la interacción
    }

    function seleccionarDificultad(dificultad) {
        dificultadSeleccionada = dificultad;
        reiniciarJuegoTotal();
    }

    document.getElementById("btn-facil").addEventListener("click", () => seleccionarDificultad(12));
    document.getElementById("btn-medio").addEventListener("click", () => seleccionarDificultad(16));
    document.getElementById("btn-dificil").addEventListener("click", () => seleccionarDificultad(20));
    document.getElementById("btn-experto").addEventListener("click", () => seleccionarDificultad(24));
    document.getElementById("btn-iniciar").addEventListener("click", iniciarJuego);
    document.getElementById("reiniciar").addEventListener("click", reiniciarJuegoTotal);

    document.addEventListener("DOMContentLoaded", () => {
        // Cargar el tablero inicial y deshabilitar la interacción
        if (typeof crearTableroConDificultad === "function") crearTableroConDificultad(dificultadSeleccionada);
        if (typeof deshabilitarJuego === "function") deshabilitarJuego();
    });

    // Cuando el usuario termine el juego (todas las parejas encontradas)
    function terminarJuego() {
        if (!juegoIniciado) return;
        juegoIniciado = false;
        detenerCronometro();
        puntaje = Math.max(1000 - tiempo * 10, 100);
        document.getElementById("puntaje").textContent = puntaje;

        // Mostrar el puntaje final
        const pf = document.getElementById("puntaje-final");
        pf.innerHTML = `¡Felicitaciones!<br>Puntaje final: <span>${puntaje}</span><br>Tiempo: <span>${tiempo}</span> segundos.`;
        pf.style.display = "block";

        // Envía el resultado al servidor
        fetch("guardar_resultado.php", {
            method: "POST",
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                tipo: "memorama",
                usuario: usuario,
                tiempo: tiempo,
                puntaje: puntaje
            })
        })
        .then(r=>r.json())
        .then(d=>{
            if(d.ok) console.log("Resultado guardado!");
            else alert("Error al guardar resultado");
        });
        deshabilitarJuego();
    }

    // --------- AUTOAJUSTE IFRAME (opcional) ----------
    function ajustarAltura() {
        parent.postMessage({
            type: "setHeight",
            id: window.frameElement ? window.frameElement.id : "",
            height: document.body.scrollHeight
        }, "*");
    }
    window.onload = ajustarAltura;
    window.addEventListener("message", function(event) {
        if (event.data && event.data.type === "getHeight") {
            ajustarAltura();
        }
    });
    try {
        new ResizeObserver(ajustarAltura).observe(document.body);
    } catch(e) {}
    </script>
</body>
</html>