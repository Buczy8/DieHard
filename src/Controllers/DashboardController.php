<?php

namespace App\Controllers;

use App\Annotation\RequireLogin;
use App\Annotation\AllowedMethods;
use App\DTO\GameResponseDTO;
use App\DTO\UserStatisticsResponseDTO;
use App\Repository\UserStatisticsRepository;
use App\Repository\GamesRepository;

class DashboardController extends AppController
{

    private $statsRepository;
    private $gamesRepository;

    public function __construct()
    {
        $this->statsRepository = UserStatisticsRepository::getInstance();
        $this->gamesRepository = GamesRepository::getInstance();
    }

    #[RequireLogin]
    public function index()
    {

        $this->render('dashboard');
    }

    #[RequireLogin]
    #[AllowedMethods(['GET'])]
    public function getDashboardDataAPI()
    {
        header('Content-Type: application/json');

        $user = $this->getUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        try {

            $statsModel = $this->statsRepository->getStatsByUserEmail($user->email);
            $statsDTO = UserStatisticsResponseDTO::fromEntity($statsModel);


            $gamesModels = $this->gamesRepository->getGamesByUserId($user->id, 4);
            $recentGamesDTOs = [];
            foreach ($gamesModels as $gameModel) {
                $recentGamesDTOs[] = GameResponseDTO::fromEntity($gameModel);
            }


            $leaderboard = $this->statsRepository->getLeaderboard(5);

            echo json_encode([
                'stats' => $statsDTO,
                'recentGames' => $recentGamesDTOs,
                'leaderboard' => $leaderboard,
                'username' => $user->username
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error: ' . $e->getMessage()]);
        }
    }
}