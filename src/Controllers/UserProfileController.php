<?php

namespace App\Controllers;

use App\Annotation\RequireLogin;
use App\DTO\UserStatisticsResponseDTO;
use App\Repository\UserRepository;
use App\Repository\UserStatisticsRepository;

class UserProfileController extends AppController
{
    private $statsRepository;
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

        $user = $this->getUser();

        if (!$user) {
            header('Location: /logout');
            exit;
        }

        $statsModel = $this->statsRepository->getStatsByUserEmail($user->email);

        $statsDTO = UserStatisticsResponseDTO::fromEntity($statsModel);

        $this->render('profile', [
            'stats' => $statsDTO,
            'email' => $user->email,
        ]);
    }
}