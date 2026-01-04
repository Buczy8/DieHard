<?php
namespace App\Controllers;
use App\Annotation\RequireLogin;
use App\DTO\GameResponseDTO;
use App\DTO\UserStatisticsResponseDTO;
use App\Repository\UserRepository;
use App\Repository\UserStatisticsRepository;
use App\Repository\GamesRepository;

class DashboardController extends AppController {

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
        $userId = $_SESSION['user_id'] ?? null;

        $user = $this->getUser();

        if (!$user) {
            header('Location: /logout');
            exit;
        }

        $statsModel = $this->statsRepository->getStatsByUserEmail($user->email);

        $statsDTO = UserStatisticsResponseDTO::fromEntity($statsModel);

        $gamesModels = $this->gamesRepository->getGamesByUserId($userId, 4);

        $recentGamesDTOs = [];

        foreach ($gamesModels as $gameModel) {
            $recentGamesDTOs[] = GameResponseDTO::fromEntity($gameModel);
        }

        $this->render('dashboard', [
            'stats' => $statsDTO,
            'recentGames' => $recentGamesDTOs,
        ]);
    }
}