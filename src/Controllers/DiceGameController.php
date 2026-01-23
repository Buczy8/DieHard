<?php

namespace App\Controllers;

use App\Repository\GamesRepository;
use App\Services\DiceGameService;
use App\Annotation\RequireLogin;

class DiceGameController extends AppController
{
    private GamesRepository $gamesRepository;

    public function __construct()
    {
        // UserStatisticsRepository usunięty, skoro nie był używany bezpośrednio
        $this->gamesRepository = GamesRepository::getInstance();
    }

    #[RequireLogin]
    public function game()
    {
        $this->render('dicegame');
    }

    public function gameApi()
    {
        $this->ensureSession();
        $this->setJsonHeader();

        try {
            $input = $this->getJsonInput();
            $action = $input['action'] ?? '';

            // 1. Inicjalizacja lub odtworzenie gry
            $game = $this->initializeGame($action, $input);

            // 2. Wykonanie akcji
            $response = $this->handleAction($game, $action, $input);

            // 3. Pobranie stanu po akcji
            $state = $game->getState();

            // 4. Obsługa końca gry (zapis do bazy)
            $this->handleGameOver($state);

            // 5. Zapisanie stanu do sesji
            $this->saveStateToSession($state);

            // 6. Dołączenie stanu do odpowiedzi
            $response['gameState'] = $state;

            echo json_encode($response);

        } catch (\Exception $e) {
            http_response_code(400); // Bad Request w przypadku błędu
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // --- Metody pomocnicze (Private) ---

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function setJsonHeader(): void
    {
        header('Content-Type: application/json');
    }

    private function getJsonInput(): array
    {
        $content = file_get_contents('php://input');
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON input');
        }

        return $decoded ?? [];
    }

    private function initializeGame(string $action, array $input): DiceGameService
    {
        // Jeśli restart, czyścimy flagę zapisu i wymuszamy nową grę
        if ($action === 'restart') {
            unset($_SESSION['game_saved']);
            $difficulty = $input['difficulty'] ?? 'medium';
            return new DiceGameService(null, $difficulty);
        }

        // Jeśli mamy stan w sesji, odtwarzamy go
        if (isset($_SESSION['game_state'])) {
            return new DiceGameService($_SESSION['game_state']);
        }

        // Fallback: Nowa gra (np. pierwsze wejście)
        $difficulty = $input['difficulty'] ?? 'medium';
        return new DiceGameService(null, $difficulty);
    }

    private function handleAction(DiceGameService $game, string $action, array $input): array
    {
        $response = ['success' => false];

        switch ($action) {
            case 'start_turn':
                $game->startNewTurn();
                $game->roll([]);
                $response['success'] = true;
                break;

            case 'roll':
                $heldIndices = $input['held'] ?? [];
                // Walidacja, czy heldIndices to tablica intów (opcjonalne, ale dobre dla bezpieczeństwa)
                $game->roll($heldIndices);
                $response['success'] = true;
                break;

            case 'select_score':
                $categoryId = $input['categoryId'] ?? '';
                if ($game->selectCategory($categoryId)) {
                    $response['success'] = true;
                } else {
                    throw new \Exception('Invalid category or already taken');
                }
                break;

            case 'computer_turn':
                $steps = $game->playComputerTurn();
                $response['success'] = true;
                $response['steps'] = $steps;
                break;

            case 'get_state':
            case 'restart':
                $response['success'] = true;
                break;

            default:
                throw new \Exception("Unknown action: $action");
        }

        return $response;
    }

    private function handleGameOver(array $state): void
    {
        if ($state['gameOver'] && empty($_SESSION['game_saved'])) {
            $this->saveGameResult($state);
            $_SESSION['game_saved'] = true;
        }
    }

    private function saveStateToSession(array $state): void
    {
        // Zamiast ręcznie przepisywać pola, zapisujemy to, co zwrócił DiceGameService.
        // Dzięki temu kontroler nie musi wiedzieć, jak wygląda struktura danych gry.
        $_SESSION['game_state'] = [
            'dice' => $state['dice'],
            'rollsLeft' => $state['rollsLeft'],
            'scorecard' => $state['scorecard'],
            'computerScorecard' => $state['computerScorecard'],
            'difficulty' => $state['difficulty']
        ];
    }

    private function saveGameResult(array $state): void
    {
        if (!isset($_SESSION['user_id'])) {
            // Logujemy błąd lub ignorujemy, jeśli to gra gościa
            return;
        }

        $userId = $_SESSION['user_id'];
        $playerScore = $state['playerTotals']['grand'];
        $computerScore = $state['computerTotals']['grand'];

        $result = match (true) {
            $playerScore > $computerScore => 'win',
            $playerScore < $computerScore => 'loss',
            default => 'draw'
        };

        // Zapis z uwzględnieniem poziomu trudności w nazwie bota
        $opponentName = 'Bot (' . ucfirst($state['difficulty']) . ')';

        $this->gamesRepository->saveGame($userId, $playerScore, $result, $opponentName);
    }
}