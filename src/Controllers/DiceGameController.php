<?php

namespace App\Controllers;
use App\Repository\GamesRepository;
use App\Repository\UserStatisticsRepository;
use App\Services\DiceGameService;
use App\Annotation\RequireLogin;

class DiceGameController extends AppController
{

    private GamesRepository $gamesRepository;
    private UserStatisticsRepository $statsRepository;

    public function __construct()
    {
        $this->gamesRepository = GamesRepository::getInstance();
        $this->statsRepository = UserStatisticsRepository::getInstance();
    }
    #[RequireLogin]
    public function game()
    {
        $this->render('dicegame');
    }

    public function gameApi()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        if ($action === 'restart') {
            unset($_SESSION['game_saved']);
        }

        if (!isset($_SESSION['game_state']) || $action === 'restart') {
            $game = new diceGameService();
        } else {
            $game = new diceGameService($_SESSION['game_state']);
        }

        $response = ['success' => false];

        try {
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
                        $response['error'] = 'Invalid category or already taken';
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
            }

            $state = $game->getState();

            if ($state['gameOver']) {
                if (empty($_SESSION['game_saved'])) {
                    $this->saveGameResult($state);
                    $_SESSION['game_saved'] = true;
                }
            }

            $_SESSION['game_state'] = [
                'dice' => $state['dice'],
                'rollsLeft' => $state['rollsLeft'],
                'scorecard' => $state['scorecard'],
                'computerScorecard' => $state['computerScorecard']
            ];

            $response['gameState'] = $state;

        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
        }

        echo json_encode($response);
        exit;
    }
    private function saveGameResult(array $state): void
    {
        // Zakładamy, że user_id jest w sesji (dzięki RequireLogin)
        $userId = $_SESSION['user_id'];

        $playerScore = $state['playerTotals']['grand'];
        $computerScore = $state['computerTotals']['grand'];

        $result = 'draw';
        $isWin = false;

        if ($playerScore > $computerScore) {
            $result = 'win';
            $isWin = true;
        } elseif ($playerScore < $computerScore) {
            $result = 'loss';
        }

        // 1. Zapisujemy historię gry
        $this->gamesRepository->saveGame($userId, $playerScore, $result, 'Bot');

        // 2. Aktualizujemy statystyki globalne gracza
        $this->statsRepository->updateStats($userId, $playerScore, $isWin);
    }
}