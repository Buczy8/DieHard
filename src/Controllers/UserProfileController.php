<?php

namespace App\Controllers;

use App\Annotation\AllowedMethods;
use App\Annotation\RequireLogin;
use App\DTO\UserStatisticsResponseDTO;
use App\Repository\UserRepository;
use App\Repository\UserStatisticsRepository;

class UserProfileController extends AppController
{
    const UPLOAD_URL_PATH = '/Public/uploads/avatars/';
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
        // Renderujemy tylko pusty widok, dane pobierze JS
        $this->render('profile');
    }

    #[RequireLogin]
    #[AllowedMethods(['GET'])]
    public function getProfileDataAPI()
    {
        header('Content-Type: application/json');
        
        $user = $this->getUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        try {
            $statsModel = $this->statsRepository->getStatsByUserEmail($user->email);
            $statsDTO = UserStatisticsResponseDTO::fromEntity($statsModel);

            echo json_encode([
                'email' => $user->email,
                'username' => $user->username,
                'avatar' => $user->avatar,
                'stats' => $statsDTO,
                'csrf' => $_SESSION['csrf']
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error: ' . $e->getMessage()]);
        }
    }

    #[RequireLogin]
    #[AllowedMethods(['POST'])]
    public function updateSettings()
    {
        header('Content-Type: application/json');

        $sessionToken = $_SESSION['csrf'] ?? '';
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $sessionToken) {
            http_response_code(400);
            echo json_encode(['message' => 'Session expired. Please refresh.', 'type' => 'error']);
            return;
        }

        $user = $this->getUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized', 'type' => 'error']);
            return;
        }

        $newUsername = trim($_POST['display_name'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $defaultAvatar = $_POST['selected_default_avatar'] ?? '';
        $avatarFile = $_FILES['avatar_file'] ?? null;

        $changesMade = false;
        $message = "No changes were made.";
        $type = "error";

        // 1. Avatar Upload
        if ($avatarFile && $avatarFile['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($avatarFile['type'], $allowedTypes)) {
                echo json_encode(['message' => 'Invalid file type. Only JPG, PNG, GIF, SVG.', 'type' => 'error']);
                return;
            }

            if ($avatarFile['size'] > $maxSize) {
                echo json_encode(['message' => 'File is too large. Max 2MB.', 'type' => 'error']);
                return;
            }

            $targetDir = __DIR__ . '/../../Public/uploads/avatars/';

            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0777, true)) {
                    error_log("Failed to create directory: " . $targetDir);
                    echo json_encode(['message' => 'Server error: Upload directory cannot be created.', 'type' => 'error']);
                    return;
                }
            }
            @chmod($targetDir, 0777);

            if (!is_writable($targetDir)) {
                echo json_encode(['message' => 'Server error: Upload directory is not writable.', 'type' => 'error']);
                return;
            }

            $extension = pathinfo($avatarFile['name'], PATHINFO_EXTENSION);
            $newFileName = 'avatar_' . $user->id . '_' . uniqid() . '.' . $extension;

            if (move_uploaded_file($avatarFile['tmp_name'], $targetDir . $newFileName)) {
                $user->avatar = self::UPLOAD_URL_PATH . $newFileName;
                $changesMade = true;
            } else {
                echo json_encode(['message' => 'Failed to upload file due to server error.', 'type' => 'error']);
                return;
            }
        }
        // 2. Default Avatar
        elseif (!empty($defaultAvatar)) {
            $allowedDefaults = ['avatar1.svg', 'avatar2.svg', 'avatar3.svg', 'avatar4.svg'];

            if (in_array($defaultAvatar, $allowedDefaults)) {
                $newAvatarPath = '/Public/assets/avatars/' . $defaultAvatar;

                if ($user->avatar !== $newAvatarPath) {
                    $user->avatar = $newAvatarPath;
                    $changesMade = true;
                }
            }
        }

        // 3. Username
        if (!empty($newUsername) && $newUsername !== $user->username) {
            $existingUser = $this->userRepository->getUserByUserName($newUsername);

            if ($existingUser) {
                http_response_code(400);
                echo json_encode(['message' => 'This username already exists', 'type' => 'error']);
                return;
            }

            $user->username = $newUsername;
            $changesMade = true;
        }

        // 4. Password
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                http_response_code(400);
                echo json_encode(['message' => 'To change password, please provide your current password.', 'type' => 'error']);
                return;
            }

            if (!password_verify($currentPassword, $user->password)) {
                http_response_code(400);
                echo json_encode(['message' => 'Current password is incorrect.', 'type' => 'error']);
                return;
            }

            if (strlen($newPassword) < 8) {
                http_response_code(400);
                echo json_encode(['message' => 'Password is too weak (min 8 chars).', 'type' => 'error']);
                return;
            }

            if ($newPassword !== $confirmPassword) {
                http_response_code(400);
                echo json_encode(['message' => 'Passwords do not match.', 'type' => 'error']);
                return;
            }

            $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
            $changesMade = true;
        }

        if ($changesMade) {
            try {
                $this->userRepository->updateUser($user);
                $updatedUser = $this->userRepository->getUserById($user->id);
                
                echo json_encode([
                    'message' => 'Settings updated successfully!', 
                    'type' => 'success',
                    'user' => [
                        'username' => $updatedUser->username,
                        'avatar' => $updatedUser->avatar
                    ]
                ]);
            } catch (\Exception $e) {
                error_log("Update Settings Error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['message' => 'An error occurred while updating settings.', 'type' => 'error']);
            }
        } else {
            echo json_encode(['message' => 'No changes were made.', 'type' => 'error']);
        }
    }
}