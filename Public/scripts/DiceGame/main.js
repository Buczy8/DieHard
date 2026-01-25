import {sendAction} from './api.js';
import * as UI from './ui.js';

document.addEventListener('DOMContentLoaded', () => {
    let currentGameState = null;
    let isBoardLocked = false;
    let isGameOverHandled = false;

    const rollButton = document.querySelector('.roll-button');
    const newGameBtn = document.querySelector('.btn-new-game');
    const diceElements = document.querySelectorAll('.die');
    const scoreItems = document.querySelectorAll('.score-item');
    const difficultyModal = document.getElementById('difficultyModal');
    const difficultyButtons = document.querySelectorAll('.btn-difficulty');
    const closeModalBtn = document.querySelector('.btn-close-modal');

    const handleNetworkAction = async (action, data = {}, options = {}) => {
        const result = await sendAction(action, data);

        if (result.success) {
            if (action === 'computer_turn' && result.steps) {
                isBoardLocked = true;
                await UI.playComputerAnimation(result.steps);

                const newState = await sendAction('get_state');
                if (newState.success) {
                    currentGameState = newState.gameState;

                    if (currentGameState.gameOver) {

                        UI.updateUI(currentGameState, isBoardLocked);
                        return;
                    }
                }

                isBoardLocked = false;
                UI.setBoardLocked(false);

                const turnTitle = document.querySelector('.game-column h2');
                if (turnTitle) turnTitle.textContent = "Your Turn";

                UI.updateUI(currentGameState, isBoardLocked);

            } else if (options.useRollAnimation) {
                const newDiceValues = result.gameState.dice;
                await UI.playRollAnimation(newDiceValues);
                currentGameState = result.gameState;
                UI.updateUI(currentGameState, isBoardLocked);
            } else {
                currentGameState = result.gameState;
                UI.updateUI(currentGameState, isBoardLocked);
            }
        }
    };


    if (rollButton) {
        rollButton.addEventListener('click', async () => {
            if (isBoardLocked) return;

            const turnTitle = document.querySelector('.game-column h2');
            if (turnTitle) turnTitle.textContent = "Your Turn";

            const isNewTurnStart = rollButton.textContent === 'Start New Turn';

            if (isNewTurnStart) {
                diceElements.forEach(d => d.classList.remove('held', 'selected'));
                handleNetworkAction('start_turn', {}, {useRollAnimation: true});
            } else {
                const heldIndices = [];
                diceElements.forEach((die, index) => {
                    if (die.classList.contains('held')) {
                        heldIndices.push(index);
                    }
                });
                handleNetworkAction('roll', {held: heldIndices}, {useRollAnimation: true});
            }
        });
    }

    diceElements.forEach(die => {
        die.addEventListener('click', (event) => {
            if (isBoardLocked) return;
            if (!currentGameState || currentGameState.rollsLeft === 3) return;

            const d = event.currentTarget;
            d.classList.toggle('held');
            d.classList.toggle('selected');
        });
    });

    scoreItems.forEach(item => {
        item.addEventListener('click', async (event) => {
            if (isBoardLocked) return;
            if (!currentGameState || currentGameState.rollsLeft === 3) return;

            const scoreItem = event.currentTarget;
            const valueSpan = scoreItem.querySelector('.score-value');
            if (!valueSpan) return;

            const catId = valueSpan.id;
            if (catId.startsWith('comp-')) return;
            if (scoreItem.classList.contains('used') || scoreItem.classList.contains('total')) return;

            diceElements.forEach(d => d.classList.remove('held', 'selected'));

            await handleNetworkAction('select_score', {categoryId: catId});
            await handleNetworkAction('computer_turn');
        });
    });

    if (newGameBtn) {
        newGameBtn.addEventListener('click', () => {
            if (isBoardLocked && !isGameOverHandled) return;

            if (difficultyModal) {
                difficultyModal.style.display = 'flex';
            }
        });
    }

    difficultyButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const level = btn.getAttribute('data-level');

            if (difficultyModal) {
                difficultyModal.style.display = 'none';
            }
            diceElements.forEach(d => d.classList.remove('held', 'selected'));
            isBoardLocked = false;
            isGameOverHandled = false;
            UI.setBoardLocked(false);

            handleNetworkAction('restart', {difficulty: level});
        });
    });

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => {
            window.location.href = '/';
        });
    }

    document.addEventListener('game-request-restart', () => {
        if (difficultyModal) {
            difficultyModal.style.display = 'flex';
        }
    });

    sendAction('get_state').then(result => {
        if (result.success) {
            currentGameState = result.gameState;
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