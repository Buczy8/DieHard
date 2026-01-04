<?php

namespace App\Controllers;

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
        $userId = $_SESSION['user_id'];

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


        $this->render('history', [
            'games' => $gamesDTOs,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }
}