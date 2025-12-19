// public/scripts/ui.js

const diceClassMap = ['one', 'two', 'three', 'four', 'five', 'six'];
const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms));

// Pobieramy elementy DOM raz, ale wewnątrz funkcji, żeby mieć pewność, że istnieją
const getEls = () => ({
    scoreItems: document.querySelectorAll('.score-item'),
    rollInfoElement: document.querySelector('.roll-info'),
    upperTotalElement: document.getElementById('upper-total'),
    lowerTotalElement: document.getElementById('lower-total'),
    grandTotalElement: document.getElementById('grand-total-value'),
    newGameBtn: document.querySelector('.btn-new-game'),
    rollButton: document.querySelector('.roll-button'),
    diceElements: document.querySelectorAll('.die'),
    turnTitle: document.querySelector('.game-column h2'),
    diceContainer: document.querySelector('.dice-container')
});

// Funkcja pomocnicza do blokowania (używamy klasy CSS)
export const setBoardLocked = (isLocked) => {
    const els = getEls();
    if (isLocked) {
        document.body.classList.add('board-locked');
        if (els.rollButton) els.rollButton.disabled = true;
        if (els.newGameBtn) els.newGameBtn.disabled = true;
    } else {
        document.body.classList.remove('board-locked');
        if (els.rollButton) els.rollButton.disabled = false;
        if (els.newGameBtn) els.newGameBtn.disabled = false;
    }
};

const showComputerPotential = (potentials) => {
    if (!potentials) return;
    for (const [catId, score] of Object.entries(potentials)) {
        const domElement = document.getElementById(`comp-${catId}`);
        if (domElement) {
            const isUsed = domElement.textContent !== '' && !domElement.classList.contains('potential');
            if (!isUsed || domElement.classList.contains('potential')) {
                domElement.textContent = score;
                domElement.classList.add('potential');
            }
        }
    }
};

export const updateUI = (gameState, isBoardLockedGlobal) => {
    if (!gameState) return;
    const els = getEls();
    const { dice, rollsLeft, scorecard, computerScorecard, possibleScores, playerTotals, computerTotals, gameOver } = gameState;

    // Kostki
    els.diceElements.forEach((dieElement, index) => {
        const value = dice[index];
        const icon = dieElement.querySelector('i');
        icon.className = 'fa-solid';
        if (value > 0) {
            icon.classList.add(`fa-dice-${diceClassMap[value - 1]}`);
            icon.style.display = 'inline-block';
        } else {
            icon.style.display = 'none';
        }
    });

    // Przyciski
    if (!gameOver) {
        if (rollsLeft === 3 && dice.every(die => die === 0)) {
            if (els.rollButton) els.rollButton.textContent = 'Start New Turn';
            els.rollInfoElement.textContent = 'Press Start to Roll';
        } else {
            if (els.rollButton) els.rollButton.textContent = rollsLeft === 0 ? 'Select Score' : 'Roll Dice';
            els.rollButton.disabled = rollsLeft === 0;
            els.rollInfoElement.textContent = `Rolls left: ${rollsLeft}`;
        }
    }

    // Tabela wyników
    els.scoreItems.forEach(item => {
        if (item.classList.contains('total')) return;
        const valueSpan = item.querySelector('.score-value');
        if (!valueSpan || valueSpan.id.startsWith('comp-')) return;
        const catId = valueSpan.id;

        if (scorecard[catId] !== null) {
            item.classList.add('used');
            item.classList.remove('potential');
            valueSpan.textContent = scorecard[catId];
        } else {
            item.classList.remove('used');
            // Logika wyświetlania potencjalnych wyników
            if (!gameOver && rollsLeft < 3 && !isBoardLockedGlobal) {
                const potential = possibleScores[catId];
                valueSpan.textContent = potential;
                if (potential > 0) item.classList.add('potential');
            } else {
                valueSpan.textContent = '';
            }
        }
    });

    // Sumy gracza
    if (playerTotals) {
        if (els.upperTotalElement) els.upperTotalElement.textContent = playerTotals.upper;
        if (els.lowerTotalElement) els.lowerTotalElement.textContent = playerTotals.lower;
        if (els.grandTotalElement) els.grandTotalElement.textContent = playerTotals.grand;
    }

    // Tabela komputera
    if (computerScorecard) {
        for (const [catId, score] of Object.entries(computerScorecard)) {
            const compElement = document.getElementById(`comp-${catId}`);
            if (compElement) {
                const parentItem = compElement.parentElement;
                if (score !== null) {
                    compElement.textContent = score;
                    compElement.classList.remove('potential');
                    parentItem.classList.add('used');
                    parentItem.style.color = '#ccc';
                } else {
                    compElement.textContent = '';
                    compElement.classList.remove('potential');
                    parentItem.classList.remove('used');
                    parentItem.style.color = '';
                }
            }
        }
    }

    // Sumy komputera
    if (computerTotals) {
        const compUpper = document.getElementById('comp-upper-total');
        const compLower = document.getElementById('comp-lower-total');
        const compGrand = document.getElementById('comp-grand-total-value');
        if (compUpper) compUpper.textContent = computerTotals.upper;
        if (compLower) compLower.textContent = computerTotals.lower;
        if (compGrand) compGrand.textContent = computerTotals.grand;
    }

    if (gameOver) {
        handleGameOverUI(playerTotals, computerTotals, els);
    }
};

export const playComputerAnimation = async (steps) => {
    const els = getEls();
    setBoardLocked(true); // UI Lock

    if (els.turnTitle) els.turnTitle.textContent = "Computer's Turn";
    els.rollInfoElement.textContent = "Computer is thinking...";

    els.diceElements.forEach(d => d.classList.remove('held', 'selected'));

    for (const step of steps) {
        if (step.type === 'roll') {
            // Używamy teraz funkcji animacji zamiast natychmiastowej zmiany
            await playRollAnimation(step.dice);

            if (step.potential) showComputerPotential(step.potential);
            els.rollInfoElement.textContent = `Computer Roll ${step.rollNumber}`;
            await wait(500); // Skracamy pauzę, bo animacja już trwa

        } else if (step.type === 'hold') {
            els.diceElements.forEach((die, index) => {
                // Używamy 'held' do logiki animacji, a 'selected' do wizualnego podświetlenia
                if (step.heldIndices.includes(index)) {
                    die.classList.add('held');
                } else {
                    die.classList.remove('held');
                }
                if (step.heldIndices.includes(index)) {
                    die.classList.add('selected');
                } else {
                    die.classList.remove('selected');
                }
            });
            await wait(800);

        } else if (step.type === 'finish') {
            document.querySelectorAll('[id^="comp-"]').forEach(el => {
                if (el.classList.contains('potential')) {
                    el.textContent = '';
                    el.classList.remove('potential');
                }
            });
            els.rollInfoElement.textContent = `Computer chose: ${step.score} pts`;
            await wait(500);
        }
    }

    els.diceElements.forEach(d => d.classList.remove('held', 'selected')); // Czyścimy klasy po całej turze
    await wait(800);
    // Tutaj koniec animacji, resztę (odblokowanie) robi main.js
};

// Funkcja wewnętrzna (nie exportujemy jej bezpośrednio, bo jest używana tylko w updateUI)
const handleGameOverUI = (pTotals, cTotals, els) => {
    setBoardLocked(true);
    els.diceElements.forEach(d => d.classList.remove('held', 'selected'));

    if (els.newGameBtn) els.newGameBtn.style.display = 'none';
    if (els.rollButton) els.rollButton.style.display = 'none';
    if (els.rollInfoElement) els.rollInfoElement.style.display = 'none';
    if (els.diceContainer) els.diceContainer.style.display = 'none';

    if (els.turnTitle) {
        els.turnTitle.textContent = "Game Over";
        els.turnTitle.style.color = '#ff4d4d';
    }

    // Sprawdzamy, czy panel już jest, żeby nie dodawać go 10 razy
    if (document.querySelector('.game-over-panel')) return;

    const myScore = pTotals ? pTotals.grand : 0;
    const compScore = cTotals ? cTotals.grand : 0;

    let message, resultClass;
    if (myScore > compScore) {
        message = "🏆 YOU WON!";
        resultClass = "win-color";
    } else if (compScore > myScore) {
        message = "💀 COMPUTER WON!";
        resultClass = "lose-color";
    } else {
        message = "🤝 DRAW!";
        resultClass = "draw-color";
    }

    const template = document.getElementById('game-over-template');
    if (!template) return;

    const clone = template.content.cloneNode(true);
    const panel = clone.querySelector('.game-over-panel');

    const winnerText = panel.querySelector('.winner-text');
    winnerText.textContent = message;
    winnerText.classList.add(resultClass);

    const pScoreEl = panel.querySelector('#p-score');
    pScoreEl.textContent = myScore;
    if (myScore > compScore) pScoreEl.classList.add('win-color');

    const cScoreEl = panel.querySelector('#c-score');
    cScoreEl.textContent = compScore;
    if (compScore > myScore) cScoreEl.classList.add('lose-color');

    // OBSŁUGA RESTARTU - Wysyłamy zdarzenie do main.js
    const restartBtn = panel.querySelector('.btn-restart');
    restartBtn.addEventListener('click', () => {
        panel.remove();

        // Przywracamy UI do stanu "startowego"
        if (els.diceContainer) els.diceContainer.style.display = 'flex';
        if (els.rollButton) els.rollButton.style.display = 'inline-block';
        if (els.rollInfoElement) els.rollInfoElement.style.display = 'block';
        if (els.newGameBtn) els.newGameBtn.style.display = 'inline-block';
        if (els.turnTitle) {
            els.turnTitle.textContent = "Your Turn";
            els.turnTitle.style.color = '';
        }

        // Wysyłamy sygnał do main.js: "Hej, zrestartuj grę!"
        document.dispatchEvent(new CustomEvent('game-request-restart'));
    });

    const gameColumn = document.querySelector('.game-column');
    gameColumn.appendChild(panel);
};

export const playRollAnimation = async (newDiceValues) => {
    const els = getEls();
    if (!els || !els.diceElements) return;

    const animationDuration = 700; // Czas musi być zgodny z animacją w CSS
    const diceToAnimate = [];

    // 1. Dodaj klasę .flipping tylko do kości, które NIE SĄ trzymane
    els.diceElements.forEach((die, index) => {
        if (!die.classList.contains('held')) {
            die.classList.add('flipping');
            diceToAnimate.push({ die, value: newDiceValues[index] });
        }
    });

    // 2. Poczekaj na zakończenie animacji
    await wait(animationDuration);

    // 3. Po animacji usuń klasę .flipping i ustaw prawidłowe ikony
    diceToAnimate.forEach(({ die, value }) => {
        die.classList.remove('flipping');
        const icon = die.querySelector('i');
        if (icon && value > 0) {
            icon.className = `fa-solid fa-dice-${diceClassMap[value - 1]}`;
        }
    });
};