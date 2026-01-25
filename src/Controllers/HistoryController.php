<?php

namespace App\Controllers;

use App\Annotation\AllowedMethods;
use App\Annotation\RequireLogin;
use App\DTO\GameResponseDTO;
use App\Repository\GamesRepository;
use App\Repository\UserRepository;

class HistoryController extends AppController
{
    private GamesRepository $gamesRepository;

    public function __construct()
    {
        $this->gamesRepository = GamesRepository::getInstance();
    }

    #[RequireLogin]
    public function index()
    {
        $this->render('history');
    }

    #[RequireLogin]
    #[AllowedMethods(['GET'])]
    public function getHistoryDataAPI()
    {
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        try {
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;

            $totalGames = $this->gamesRepository->countGamesByUserId($userId);
            $gamesModels = $this->gamesRepository->getGamesByUserId($userId, $limit, $offset);
            $totalPages = ceil($totalGames / $limit);

            $gamesDTOs = [];
            foreach ($gamesModels as $gameModel) {
                $gamesDTOs[] = GameResponseDTO::fromEntity($gameModel);
            }

            echo json_encode([
                'games' => $gamesDTOs,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalGames' => $totalGames
                ]
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error: ' . $e->getMessage()]);
        }
    }
}