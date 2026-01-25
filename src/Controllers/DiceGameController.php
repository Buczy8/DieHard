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


            $game = $this->initializeGame($action, $input);


            $response = $this->handleAction($game, $action, $input);


            $state = $game->getState();


            $this->handleGameOver($state);


            $this->saveStateToSession($state);


            $response['gameState'] = $state;

            echo json_encode($response);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }


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

        if ($action === 'restart') {
            unset($_SESSION['game_saved']);
            $difficulty = $input['difficulty'] ?? 'medium';
            return new DiceGameService(null, $difficulty);
        }


        if (isset($_SESSION['game_state'])) {
            return new DiceGameService($_SESSION['game_state']);
        }


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


        $opponentName = 'Bot (' . ucfirst($state['difficulty']) . ')';

        $this->gamesRepository->saveGame($userId, $playerScore, $result, $opponentName);
    }
}