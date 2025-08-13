let puzzleState = [];
let size = 3;
let juegoIniciado = false;
let tiempo = 0;
let timer = null;
let puntaje = 0;

const TILE_SIZE = 100;
const GAP_SIZE = 2;

document.addEventListener("DOMContentLoaded", function () {
    const puzzle = document.getElementById("puzzle-container");
    puzzle.addEventListener("click", function (e) {
        if (!juegoIniciado) return;

        const tile = e.target;
        if (!tile.classList.contains("tile") || tile.classList.contains("empty")) return;

        const tileIndex = parseInt(tile.dataset.idx);
        const emptyIndex = puzzleState.indexOf(0);

        const isAdjacent = checkAdjacent(tileIndex, emptyIndex);
        if (isAdjacent) {
            swapTiles(tileIndex, emptyIndex);
            if (checkWin()) {
                terminarJuego();
            }
        }
    });

    document.getElementById("btn-nivel1").addEventListener("click", () => seleccionarDificultad(3));
    document.getElementById("btn-nivel2").addEventListener("click", () => seleccionarDificultad(4));
    document.getElementById("btn-nivel3").addEventListener("click", () => seleccionarDificultad(5));
    document.getElementById("btn-nivel4").addEventListener("click", () => seleccionarDificultad(6));
    document.getElementById("btn-iniciar").addEventListener("click", iniciarJuego);
    document.getElementById("reiniciar").addEventListener("click", reiniciarJuegoTotal);

    crearPuzzle(size);
    deshabilitarJuego();
});

function checkAdjacent(i, j) {
    const rowI = Math.floor(i / size);
    const colI = i % size;
    const rowJ = Math.floor(j / size);
    const colJ = j % size;
    return (Math.abs(rowI - rowJ) + Math.abs(colI - colJ)) === 1;
}

function swapTiles(index1, index2) {
    [puzzleState[index1], puzzleState[index2]] = [puzzleState[index2], puzzleState[index1]];
    renderPuzzle();
}

function crearPuzzle(newSize) {
    size = newSize;
    const cont = document.getElementById("puzzle-container");
    const containerDimension = (TILE_SIZE * size) + (GAP_SIZE * (size - 1));
    cont.style.width = `${containerDimension}px`;
    cont.style.height = `${containerDimension}px`;
    cont.style.gridTemplateColumns = `repeat(${size}, 1fr)`;
    cont.style.gridTemplateRows = `repeat(${size}, 1fr)`;

    puzzleState = [];
    for (let i = 1; i <= size * size - 1; i++) {
        puzzleState.push(i);
    }
    puzzleState.push(0);

    do {
        puzzleState = shuffleArray(puzzleState);
    } while (!isSolvable(puzzleState));

    renderPuzzle();
}

function shuffleArray(arr) {
    let a = arr.slice();
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}

function isSolvable(arr) {
    let inv = 0;
    for (let i = 0; i < arr.length; i++) {
        for (let j = i + 1; j < arr.length; j++) {
            if (arr[i] && arr[j] && arr[i] > arr[j]) {
                inv++;
            }
        }
    }
    
    if (size % 2 === 1) {
        return inv % 2 === 0;
    } else {
        let emptyRowFromBottom = size - Math.floor(arr.indexOf(0) / size);
        return emptyRowFromBottom % 2 === 0 ? inv % 2 === 1 : inv % 2 === 0;
    }
}

function renderPuzzle() {
    const cont = document.getElementById("puzzle-container");
    cont.innerHTML = "";
    puzzleState.forEach((num, idx) => {
        const tile = document.createElement("div");
        tile.className = "tile" + (num === 0 ? " empty" : "");
        tile.textContent = num === 0 ? "" : num;
        tile.dataset.idx = idx;
        tile.style.width = `${TILE_SIZE}px`;
        tile.style.height = `${TILE_SIZE}px`;
        cont.appendChild(tile);
    });
}

function checkWin() {
    for (let i = 0; i < size * size - 1; i++) {
        if (puzzleState[i] !== i + 1) return false;
    }
    return puzzleState[size * size - 1] === 0;
}

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
    crearPuzzle(size);
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
    crearPuzzle(size);
    document.getElementById("puntaje-final").style.display = "none";
    deshabilitarJuego();
}

function seleccionarDificultad(newSize) {
    size = newSize;
    reiniciarJuegoTotal();
}

function terminarJuego() {
    detenerCronometro();
    juegoIniciado = false;
    puntaje = Math.max(1000 - tiempo * 10, 100);
    document.getElementById("puntaje").textContent = puntaje;
    const pf = document.getElementById("puntaje-final");
    pf.innerHTML = `¡Felicitaciones!<br>Puntaje final: <span>${puntaje}</span><br>Tiempo: <span>${tiempo}</span> segundos.`;
    pf.style.display = "block";
    fetch("guardar_resultado.php", {
        method: "POST",
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            tipo: "rompecabezas",
            usuario: usuario,
            tiempo: tiempo,
            puntaje: puntaje,
            dificultad: size
        })
    })
   fetch("../guardar_resultado.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(datos)
})
.then(res => res.text())
.then(text => {
    // Muestra la respuesta tal cual la devuelve el servidor (puede ser HTML o JSON)
    alert("Respuesta del servidor:\n" + text);
    // Si quieres también ver en consola:
    console.log("Respuesta del servidor:", text);
})
.catch(err => alert("Error de red al guardar resultado: " + err));
    deshabilitarJuego();
}

function habilitarJuego() {
    juegoIniciado = true;
    document.getElementById("puzzle-container").classList.remove("puzzle-deshabilitado");
}

function deshabilitarJuego() {
    juegoIniciado = false;
    document.getElementById("puzzle-container").classList.add("puzzle-deshabilitado");
}
function terminarJuego() {
    detenerCronometro();
    juegoIniciado = false;
    puntaje = Math.max(1000 - tiempo * 10, 100);
    document.getElementById("puntaje").textContent = puntaje;
    const pf = document.getElementById("puntaje-final");
    pf.innerHTML = `¡Felicitaciones!<br>Puntaje final: <span>${puntaje}</span><br>Tiempo: <span>${tiempo}</span> segundos.`;
    pf.style.display = "block";
    
    // ENVÍA EL RESULTADO A LA VENTANA PADRE (mini.php)
    window.parent.postMessage({
        type: "juegoCompletado",
        puntaje: puntaje,
        tiempo: tiempo,
        dificultad: size,
        juego: "rompecabezas"
    }, "*");

    deshabilitarJuego();
}