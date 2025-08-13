<?php
session_start();
$usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Puzzle</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
      #marcador { margin-bottom: 12px; font-weight: bold; font-size: 1.1em; }
      .btn-chido { margin: 10px 8px; padding: 12px 28px; background: linear-gradient(90deg, #6c63ff 60%, #ff7f50 100%);
          color: #fff; border: none; border-radius: 14px; font-size: 18px; font-weight: bold; cursor: pointer;
          box-shadow: 1px 2px 8px #ffd6e0; transition: background 0.2s, transform 0.2s; outline: none; }
      .btn-chido:hover { background: linear-gradient(90deg, #ff7f50 60%, #6c63ff 100%); transform: scale(1.07);}
      #puntaje-final { margin-top: 18px; font-size: 1.35em; color: #ff7f50; font-weight: bold; display: none;}
      #puzzle-container {
        position: relative;
        width: 400px;
        height: 400px;
        border: 2px solid #6c63ff;
        margin: 0 auto;
        background: #fafaff;
      }
      .piece {
        position: absolute;
        border-radius: 6px;
        box-shadow: 0 2px 4px #ddd;
        cursor: grab;
        transition: box-shadow 0.2s;
      }
      .piece.selected {
        box-shadow: 0 4px 12px #ffb347;
        z-index: 2;
      }
    </style>
    <script>
    window.PUZZLE_IMG_SRC = "ballena.jpg";
    </script>
</head>
<body>
    <h1>Puzzle</h1>
    <div id="controles-dificultad">
        <button id="btn-nivel1" class="btn-chido">Nivel 1</button>
        <button id="btn-nivel2" class="btn-chido">Nivel 2</button>
        <button id="btn-nivel3" class="btn-chido">Nivel 3</button>
        <button id="btn-nivel4" class="btn-chido">Nivel 4</button>
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
    let tiempo = 0;
    let timer = null;
    let puntaje = 0;
    let juegoIniciado = false;
    let dificultadSeleccionada = { rows: 3, cols: 3 };

    function iniciarJuego() {
        if (juegoIniciado) return;
        juegoIniciado = true;
        habilitarJuego();
        tiempo = 0;
        puntaje = 0;
        document.getElementById("tiempo").textContent = tiempo;
        document.getElementById("puntaje").textContent = puntaje;
        detenerCronometro();
        timer = setInterval(() => {
            tiempo++;
            document.getElementById("tiempo").textContent = tiempo;
        }, 1000);
        resetPuzzle(dificultadSeleccionada.rows, dificultadSeleccionada.cols);
        document.getElementById("puntaje-final").style.display = "none";
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
        document.getElementById("puntaje").textContent = puntaje;
        resetPuzzle(dificultadSeleccionada.rows, dificultadSeleccionada.cols);
        document.getElementById("puntaje-final").style.display = "none";
        deshabilitarJuego();
    }

    function seleccionarDificultad(rows, cols) {
        dificultadSeleccionada = { rows, cols };
        reiniciarJuegoTotal();
    }

    document.getElementById("btn-nivel1").addEventListener("click", () => seleccionarDificultad(3, 3));
    document.getElementById("btn-nivel2").addEventListener("click", () => seleccionarDificultad(4, 4));
    document.getElementById("btn-nivel3").addEventListener("click", () => seleccionarDificultad(5, 5));
    document.getElementById("btn-nivel4").addEventListener("click", () => seleccionarDificultad(6, 6));

    document.getElementById("btn-iniciar").addEventListener("click", iniciarJuego);
    document.getElementById("reiniciar").addEventListener("click", reiniciarJuegoTotal);

    document.addEventListener("DOMContentLoaded", () => {
        resetPuzzle(dificultadSeleccionada.rows, dificultadSeleccionada.cols);
        deshabilitarJuego();
    });

    function terminarJuego() {
        if (!juegoIniciado) return;
        juegoIniciado = false;
        detenerCronometro();
        puntaje = Math.max(1000 - tiempo*10, 100);
        document.getElementById("puntaje").textContent = puntaje;
        const pf = document.getElementById("puntaje-final");
        pf.innerHTML = `¡Felicitaciones!<br>Puntaje final: <span>${puntaje}</span><br>Tiempo: <span>${tiempo}</span> segundos.`;
        pf.style.display = "block";
        fetch("guardar_resultado.php", {
            method: "POST",
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                tipo: "puzzle",
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
    window.terminarJuego = terminarJuego;
    </script>
</body>
</html>