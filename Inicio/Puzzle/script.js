const size = 400;
const puzzle = document.getElementById('puzzle-container');
const imgSrc = window.PUZZLE_IMG_SRC;
let pieces = [];
let draggingPiece = null;
let offsetX = 0, offsetY = 0;
let startLeft = 0, startTop = 0;
let juegoActivo = false;
let currentRows, currentCols;

function createPuzzle(rows, cols) {
    puzzle.innerHTML = '';
    pieces = [];
    currentRows = rows;
    currentCols = cols;
    const pieceSize = size / rows;

    let positions = [];
    for (let r = 0; r < rows; r++)
        for (let c = 0; c < cols; c++)
            positions.push({ r, c });

    const shuffled = positions.slice();
    for (let i = shuffled.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }

    for (let i = 0; i < positions.length; i++) {
        const correct = positions[i];
        const current = shuffled[i];
        const piece = document.createElement('div');
        piece.classList.add('piece');
        piece.style.width = piece.style.height = pieceSize + 'px';
        piece.style.left = (current.c * pieceSize) + 'px';
        piece.style.top = (current.r * pieceSize) + 'px';
        piece.style.backgroundImage = `url('${imgSrc}')`;
        piece.style.backgroundSize = `${size}px ${size}px`;
        piece.style.backgroundPosition = `-${correct.c * pieceSize}px -${correct.r * pieceSize}px`;
        piece.dataset.correct = correct.r + '-' + correct.c;
        piece.dataset.current = current.r + '-' + current.c;
        puzzle.appendChild(piece);
        pieces.push(piece);

        piece.addEventListener('mousedown', startDrag);
        piece.addEventListener('touchstart', startDragTouch, { passive: false });
    }

    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchmove', onDragTouch, { passive: false });
    document.addEventListener('touchend', endDragTouch);
}

function startDrag(e) {
    if (!juegoActivo) return;
    draggingPiece = e.target;
    draggingPiece.classList.add('selected');
    startLeft = parseInt(draggingPiece.style.left);
    startTop = parseInt(draggingPiece.style.top);
    offsetX = e.clientX - startLeft;
    offsetY = e.clientY - startTop;
}

function onDrag(e) {
    if (!draggingPiece) return;
    e.preventDefault();
    let x = e.clientX - offsetX;
    let y = e.clientY - offsetY;
    const pieceSize = size / currentRows;
    x = Math.max(0, Math.min(x, size - pieceSize));
    y = Math.max(0, Math.min(y, size - pieceSize));
    draggingPiece.style.left = x + "px";
    draggingPiece.style.top = y + "px";
}

function endDrag(e) {
    if (!draggingPiece) return;
    const pieceSize = size / currentRows;
    let dropPos = getClosestCell(draggingPiece, pieceSize);
    let overlapped = pieces.find(p =>
        p !== draggingPiece &&
        parseInt(p.style.left) === dropPos.c * pieceSize &&
        parseInt(p.style.top) === dropPos.r * pieceSize
    );
    const origLeft = draggingPiece.style.left;
    const origTop = draggingPiece.style.top;

    if (overlapped) {
        const tmpLeft = overlapped.style.left;
        const tmpTop = overlapped.style.top;
        overlapped.style.left = origLeft;
        overlapped.style.top = origTop;
        let tmpCurrent = overlapped.dataset.current;
        overlapped.dataset.current = draggingPiece.dataset.current;
        draggingPiece.dataset.current = tmpCurrent;
    } else {
        draggingPiece.style.left = (dropPos.c * pieceSize) + "px";
        draggingPiece.style.top = (dropPos.r * pieceSize) + "px";
        draggingPiece.dataset.current = dropPos.r + "-" + dropPos.c;
    }
    draggingPiece.classList.remove('selected');
    draggingPiece = null;
    checkWin();
}

function getClosestCell(piece, pieceSize) {
    let left = parseInt(piece.style.left);
    let top = parseInt(piece.style.top);
    let c = Math.round(left / pieceSize);
    let r = Math.round(top / pieceSize);
    c = Math.max(0, Math.min(currentCols - 1, c));
    r = Math.max(0, Math.min(currentRows - 1, r));
    return { r, c };
}

function startDragTouch(e) {
    if (!juegoActivo) return;
    e.preventDefault();
    const touch = e.touches[0];
    draggingPiece = e.target;
    draggingPiece.classList.add('selected');
    startLeft = parseInt(draggingPiece.style.left);
    startTop = parseInt(draggingPiece.style.top);
    offsetX = touch.clientX - startLeft;
    offsetY = touch.clientY - startTop;
}

function onDragTouch(e) {
    if (!draggingPiece) return;
    e.preventDefault();
    const touch = e.touches[0];
    let x = touch.clientX - offsetX;
    let y = touch.clientY - offsetY;
    const pieceSize = size / currentRows;
    x = Math.max(0, Math.min(x, size - pieceSize));
    y = Math.max(0, Math.min(y, size - pieceSize));
    draggingPiece.style.left = x + "px";
    draggingPiece.style.top = y + "px";
}

function endDragTouch(e) {
    if (!draggingPiece) return;
    endDrag(e.changedTouches ? e.changedTouches[0] : {});
}

function checkWin() {
    let ok = true;
    pieces.forEach(piece => {
        if (piece.dataset.correct !== piece.dataset.current) ok = false;
    });
    if (ok) {
        setTimeout(() => {
            alert("¡Felicidades, completaste el puzzle!");
            if (window.terminarJuego) window.terminarJuego();
        }, 100);
    }
}

function resetPuzzle(rows, cols) {
    createPuzzle(rows, cols);
}

function habilitarJuego() {
    juegoActivo = true;
    document.getElementById('puzzle-container').classList.remove('puzzle-deshabilitado');
}

function deshabilitarJuego() {
    juegoActivo = false;
    document.getElementById('puzzle-container').classList.add('puzzle-deshabilitado');
}