<?php

namespace App\Controllers;
use App\Services\DiceGameService;
use App\Annotation\RequireLogin;

class DiceGameController extends AppController
{
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
}