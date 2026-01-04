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
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

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

    #[RequireLogin]
    #[AllowedMethods(['POST'])]
    public function updateSettings()
    {
        $sessionToken = $_SESSION['csrf'] ?? '';
        // 1. Weryfikacja CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $sessionToken) {
            http_response_code(400);
            return $this->renderProfileWithResponse("Session expired. Please refresh.");
        }

        $user = $this->getUser();
        if (!$user) {
            header('Location: /logout');
            exit;
        }

        $newUsername = trim($_POST['display_name'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $changesMade = false;

        // 2. Walidacja i zmiana nazwy użytkownika
        if (!empty($newUsername) && $newUsername !== $user->username) {
            $existingUser = $this->userRepository->getUserByUserName($newUsername);

            if ($existingUser) {
                http_response_code(400);
                return $this->renderProfileWithResponse("This username already exists");
            }

            $user->username = $newUsername;
            $changesMade = true;
        }

        // 3. Walidacja i zmiana hasła
        if (!empty($newPassword)) {
            // Wymagamy obecnego hasła dla bezpieczeństwa
            if (empty($currentPassword)) {
                http_response_code(400);
                return $this->renderProfileWithResponse("To change password, please provide your current password.");
            }

            // Sprawdzamy, czy obecne hasło jest poprawne
            if (!password_verify($currentPassword, $user->password)) {
                http_response_code(400);
                return $this->renderProfileWithResponse("Current password is incorrect.");
            }

            // Zasada: minimum 8 znaków (jak w register)
            if (strlen($newPassword) < 8) {
                http_response_code(400);
                return $this->renderProfileWithResponse("Password is too weak");
            }

            // Zasada: hasła muszą się zgadzać (jak w register)
            if ($newPassword !== $confirmPassword) {
                http_response_code(400);
                return $this->renderProfileWithResponse("passwords does not match");
            }

            $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
            $changesMade = true;
        }

        // 4. Zapis zmian w bazie
        if ($changesMade) {
            try {
                $this->userRepository->updateUser($user);
                return $this->renderProfileWithResponse("Settings updated successfully.", "success");
            } catch (\Exception $e) {
                error_log($e->getMessage());
                http_response_code(500); // Błąd serwera
                return $this->renderProfileWithResponse("An error occurred while updating settings.");
            }
        }

        // Jeśli nic nie zmieniono
        return $this->renderProfileWithResponse("No changes were made.");
    }

    // Metoda pomocnicza - poprawiona o przekazywanie 'username'
    private function renderProfileWithResponse(string $message = '', string $type = 'error')
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        $user = $this->getUser();
        $statsModel = $this->statsRepository->getStatsByUserEmail($user->email);
        $statsDTO = UserStatisticsResponseDTO::fromEntity($statsModel);

        $this->render('profile', [
            'stats' => $statsDTO,
            'email' => $user->email,
            'message' => $message,
            'type' => $type
        ]);
    }
}