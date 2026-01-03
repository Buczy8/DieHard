<?php
namespace App\Controllers;
use App\Annotation\RequireLogin;
use App\DTO\UserStatisticsResponseDTO;
use App\Repository\UserRepository;
use App\Repository\UserStatisticsRepository;

class DashboardController extends AppController {

    private UserStatisticsRepository $statsRepository;
    private $userRepository;

    public function __construct()
    {

        $this->statsRepository = UserStatisticsRepository::getInstance();
        $this->userRepository = UserRepository::getInstance();
    }

    #[RequireLogin]
    public function index()
    {
        $userId = $_SESSION['user_id'] ?? null;
        $user = $this->userRepository->getUserById($userId);

        if (!$user) {
            header('Location: /logout');
            exit;
        }

        $statsModel = $this->statsRepository->getStatsByUserEmail($user->email);

        $statsDTO = UserStatisticsResponseDTO::fromEntity($statsModel);

        $this->render('dashboard', [
            'username' => $user->username,
            'stats' => $statsDTO,
        ]);
    }
}