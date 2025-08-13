const iconos = ['🍎','🍌','🍇','🍓','🍒','🍍','🥝','🍉','🥑','🍋','🍊','🥭'];
let cartas = [];
let cartaVolteada = null;
let bloqueo = false;
let paresEncontrados = 0;
let totalPares; // Variable para almacenar el número total de pares
let juegoActivo = false;

function crearTableroConDificultad(numCartas) {
    const tablero = document.getElementById('tablero');
    tablero.innerHTML = '';
    
    // Determinar el número de columnas para el grid
    let columnas;
    if (numCartas === 12) {
        columnas = 4;
    } else if (numCartas === 16) {
        columnas = 4;
    } else if (numCartas === 20) {
        columnas = 5;
    } else { // 24 cartas
        columnas = 6;
    }
    tablero.style.gridTemplateColumns = `repeat(${columnas}, 80px)`;

    const iconosParaNivel = iconos.slice(0, numCartas / 2);
    totalPares = iconosParaNivel.length;

    // Duplica los iconos y los mezcla
    cartas = [...iconosParaNivel, ...iconosParaNivel]
        .sort(() => Math.random() - 0.5)
        .map((icono, idx) => ({
            id: idx,
            icono,
            volteada: false,
            completada: false
        }));

    cartas.forEach((carta, idx) => {
        const div = document.createElement('div');
        div.className = 'carta';
        div.dataset.idx = idx;
        div.addEventListener('click', voltearCarta);
        tablero.appendChild(div);
    });
    paresEncontrados = 0;
    document.getElementById('mensaje').textContent = '';
}

function voltearCarta(e) {
    if (bloqueo || !juegoActivo) return;
    const idx = e.target.dataset.idx;
    const carta = cartas[idx];
    if (carta.volteada || carta.completada) return;
    carta.volteada = true;
    actualizarTablero();

    if (!cartaVolteada) {
        cartaVolteada = carta;
    } else {
        bloqueo = true;
        setTimeout(() => {
            if (carta.icono === cartaVolteada.icono) {
                carta.completada = true;
                cartaVolteada.completada = true;
                paresEncontrados++;
                if (paresEncontrados === totalPares) {
                    document.getElementById('mensaje').textContent = '¡Felicidades! Has encontrado todos los pares 🎉';
                    if (typeof terminarJuego === 'function') {
                        terminarJuego();
                    }
                }
            } else {
                carta.volteada = false;
                cartaVolteada.volteada = false;
            }
            cartaVolteada = null;
            bloqueo = false;
            actualizarTablero();
        }, 800);
    }
}

function actualizarTablero() {
    document.querySelectorAll('.carta').forEach((div, idx) => {
        const carta = cartas[idx];
        if (carta.volteada || carta.completada) {
            div.textContent = carta.icono;
            div.classList.add('volteada');
            if (carta.completada) div.classList.add('completada');
        } else {
            div.textContent = '';
            div.classList.remove('volteada', 'completada');
        }
    });
}

function habilitarJuego() {
    juegoActivo = true;
    document.getElementById('tablero').classList.remove('tablero-deshabilitado');
}

function deshabilitarJuego() {
    juegoActivo = false;
    document.getElementById('tablero').classList.add('tablero-deshabilitado');
}