// public/scripts/main.js

import { sendAction } from './api.js';
import * as UI from './ui.js';

document.addEventListener('DOMContentLoaded', () => {
    // === STAN APLIKACJI ===
    let currentGameState = null;
    let isBoardLocked = false;
    let isGameOverHandled = false;

    // Pobieramy elementy tylko do event listenerów
    const rollButton = document.querySelector('.roll-button');
    const newGameBtn = document.querySelector('.btn-new-game');
    const diceElements = document.querySelectorAll('.die');
    const scoreItems = document.querySelectorAll('.score-item');
    const difficultyModal = document.getElementById('difficultyModal');
    const difficultyButtons = document.querySelectorAll('.btn-difficulty');
    const closeModalBtn = document.querySelector('.btn-close-modal');

    // === GŁÓWNA FUNKCJA STERUJĄCA ===
    const handleNetworkAction = async (action, data = {}, options = {}) => {
        const result = await sendAction(action, data);

        if (result.success) {
            if (action === 'computer_turn' && result.steps) {
                // 1. Tura Komputera
                isBoardLocked = true;
                // Wywołujemy animację z UI
                //await UI.playComputerAnimation(result.steps);

                // 2. Pobieramy stan po zakończeniu ruchów komputera
                const newState = await sendAction('get_state');
                if (newState.success) {
                    currentGameState = newState.gameState;

                    // Jeśli gra się skończyła, flaga w stanie to wskaże
                    if (currentGameState.gameOver) {
                        // Nie odblokowujemy, UI samo obsłuży Game Over
                        UI.updateUI(currentGameState, isBoardLocked);
                        return;
                    }
                }

                // 3. Odblokowanie planszy dla gracza
                isBoardLocked = false;
                UI.setBoardLocked(false);

                const turnTitle = document.querySelector('.game-column h2');
                if (turnTitle) turnTitle.textContent = "Your Turn";

                // Aktualizujemy widok nowym stanem
                UI.updateUI(currentGameState, isBoardLocked);

            } else if (options.useRollAnimation) {
                // 4a. Aktualizacja z animacją rzutu
                const newDiceValues = result.gameState.dice;
                await UI.playRollAnimation(newDiceValues); // Przekazujemy nowe wartości do animacji
                currentGameState = result.gameState;
                UI.updateUI(currentGameState, isBoardLocked); // Aktualizujemy resztę UI
            } else {
                // 4b. Standardowa aktualizacja bez animacji (np. po restarcie)
                currentGameState = result.gameState;
                UI.updateUI(currentGameState, isBoardLocked);
            }
        }
    };

    // === EVENT LISTENERY (LOGIKA) ===

    // 1. Obsługa przycisku ROLL
    if (rollButton) {
        rollButton.addEventListener('click', async () => {
            if (isBoardLocked) return;

            const turnTitle = document.querySelector('.game-column h2');
            if (turnTitle) turnTitle.textContent = "Your Turn";

            const isNewTurnStart = rollButton.textContent === 'Start New Turn';

            if (isNewTurnStart) {
                diceElements.forEach(d => d.classList.remove('held', 'selected'));
                handleNetworkAction('start_turn', {}, { useRollAnimation: true });
            } else {
                const heldIndices = [];
                diceElements.forEach((die, index) => {
                    if (die.classList.contains('held')) {
                        heldIndices.push(index);
                    }
                });
                handleNetworkAction('roll', { held: heldIndices }, { useRollAnimation: true });
            }
        });
    }

    // 2. Klikanie w kostki (HOLD)
    diceElements.forEach(die => {
        die.addEventListener('click', (event) => {
            if (isBoardLocked) return;
            if (!currentGameState || currentGameState.rollsLeft === 3) return;

            const d = event.currentTarget;
            d.classList.toggle('held');
            d.classList.toggle('selected');
        });
    });

    // 3. Wybieranie wyniku (Score)
    scoreItems.forEach(item => {
        item.addEventListener('click', async (event) => {
            if (isBoardLocked) return;
            if (!currentGameState || currentGameState.rollsLeft === 3) return;

            const scoreItem = event.currentTarget;
            const valueSpan = scoreItem.querySelector('.score-value');
            if (!valueSpan) return;

            const catId = valueSpan.id;
            // Ignorujemy kliknięcia w sekcję komputera
            if (catId.startsWith('comp-')) return;
            if (scoreItem.classList.contains('used') || scoreItem.classList.contains('total')) return;

            // Logika tury
            diceElements.forEach(d => d.classList.remove('held', 'selected'));

            // Najpierw zapisujemy wynik gracza
            await handleNetworkAction('select_score', { categoryId: catId });
            // Potem odpalamy turę komputera
            await handleNetworkAction('computer_turn');
        });
    });

    // 4. Przycisk New Game (w headerze)
    if (newGameBtn) {
        newGameBtn.addEventListener('click', () => {
            if (isBoardLocked && !isGameOverHandled) return;
            
            // Pokaż modal wyboru trudności
            if (difficultyModal) {
                difficultyModal.style.display = 'flex';
            }
        });
    }

    // 5. Obsługa wyboru poziomu trudności
    difficultyButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const level = btn.getAttribute('data-level');
            
            // Ukryj modal
            if (difficultyModal) {
                difficultyModal.style.display = 'none';
            }

            // Reset gry z wybranym poziomem
            diceElements.forEach(d => d.classList.remove('held', 'selected'));
            isBoardLocked = false;
            isGameOverHandled = false;
            UI.setBoardLocked(false);

            handleNetworkAction('restart', { difficulty: level });
        });
    });

    // 6. Obsługa zamknięcia modala (X) -> Powrót do Home
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => {
            window.location.href = '/';
        });
    }

    // 7. Odbiór sygnału RESTART z pliku UI.js (z modala Game Over)
    document.addEventListener('game-request-restart', () => {
        // Pokaż modal wyboru trudności zamiast od razu restartować
        if (difficultyModal) {
            difficultyModal.style.display = 'flex';
        }
    });

    // START GRY - Sprawdź czy gra już trwa, jeśli nie, pokaż modal
    sendAction('get_state').then(result => {
        if (result.success) {
            currentGameState = result.gameState;
            // Jeśli to nowa gra (wszystko puste), pokaż modal
            const isNewGame = Object.values(currentGameState.scorecard).every(v => v === null) && 
                              Object.values(currentGameState.computerScorecard).every(v => v === null);
            
            if (isNewGame && difficultyModal) {
                difficultyModal.style.display = 'flex';
            } else {
                UI.updateUI(currentGameState, isBoardLocked);
            }
        }
    });
});