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
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

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
            'avatar' => $user->avatar,
            'csrf' => $_SESSION['csrf'] ?? ''
        ]);
    }

    #[RequireLogin]
    #[AllowedMethods(['POST'])]
    public function updateSettings()
    {
        $sessionToken = $_SESSION['csrf'] ?? '';
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
        $defaultAvatar = $_POST['selected_default_avatar'] ?? '';
        $avatarFile = $_FILES['avatar_file'] ?? null;

        $changesMade = false;

        // --- BEZPIECZNY UPLOAD ---
        if ($avatarFile && $avatarFile['error'] === UPLOAD_ERR_OK) {
            $maxSize = 2 * 1024 * 1024; // 2MB

            if ($avatarFile['size'] > $maxSize) {
                return $this->renderProfileWithResponse("File is too large. Max 2MB.");
            }

            // 1. Sprawdzamy faktyczny typ pliku (MIME z zawartości, nie z nagłówka)
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($avatarFile['tmp_name']);

            // 2. Mapa dozwolonych typów -> rozszerzenie
            // UWAGA: Usunąłem SVG ze względów bezpieczeństwa (ryzyko XSS)
            $allowedMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp'
            ];

            if (!array_key_exists($mimeType, $allowedMimeTypes)) {
                return $this->renderProfileWithResponse("Invalid file type. Only JPG, PNG, GIF, WEBP.");
            }

            // 3. Bezpieczne tworzenie katalogu (0755 zamiast 0777)
            $targetDir = __DIR__ . '/../../Public/uploads/avatars/';
            if (!is_dir($targetDir)) {
                // Jeśli to Docker i folder nie istnieje, PHP (www-data) musi mieć prawo zapisu do rodzica (/Public/uploads)
                if (!mkdir($targetDir, 0755, true)) {
                    error_log("Failed to create directory: " . $targetDir);
                    return $this->renderProfileWithResponse("Server error: Cannot create upload directory.");
                }
            }

            // 4. Generowanie bezpiecznej nazwy z PEWNYM rozszerzeniem
            // Używamy rozszerzenia z naszej mapy, a nie tego co wysłał user
            $extension = $allowedMimeTypes[$mimeType];
            $newFileName = 'avatar_' . $user->id . '_' . bin2hex(random_bytes(8)) . '.' . $extension;

            // 5. Zapis
            if (move_uploaded_file($avatarFile['tmp_name'], $targetDir . $newFileName)) {
                $user->avatar = self::UPLOAD_URL_PATH . $newFileName;
                $changesMade = true;
            } else {
                return $this->renderProfileWithResponse("Failed to upload file. Check folder permissions.");
            }
        }
        // --- KONIEC UPLOADU ---

        elseif (!empty($defaultAvatar)) {
            // Tutaj SVG jest OK, bo to pliki serwerowe, którym ufamy
            $allowedDefaults = ['avatar1.svg', 'avatar2.svg', 'avatar3.svg', 'avatar4.svg'];

            if (in_array($defaultAvatar, $allowedDefaults)) {
                $newAvatarPath = '/Public/assets/avatars/' . $defaultAvatar;

                if ($user->avatar !== $newAvatarPath) {
                    $user->avatar = $newAvatarPath;
                    $changesMade = true;
                }
            }
        }

        // ... Reszta logiki (Username, Password) bez zmian ...
        if (!empty($newUsername) && $newUsername !== $user->username) {
            $existingUser = $this->userRepository->getUserByUserName($newUsername);
            if ($existingUser) {
                http_response_code(400);
                return $this->renderProfileWithResponse("This username already exists");
            }
            $user->username = $newUsername;
            $changesMade = true;
        }

        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                http_response_code(400);
                return $this->renderProfileWithResponse("To change password, please provide your current password.");
            }
            if (!password_verify($currentPassword, $user->password)) {
                http_response_code(400);
                return $this->renderProfileWithResponse("Current password is incorrect.");
            }
            if (strlen($newPassword) < 8) {
                http_response_code(400);
                return $this->renderProfileWithResponse("Password is too weak (min 8 chars).");
            }
            if ($newPassword !== $confirmPassword) {
                http_response_code(400);
                return $this->renderProfileWithResponse("Passwords do not match.");
            }
            $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
            $changesMade = true;
        }

        if ($changesMade) {
            try {
                $this->userRepository->updateUser($user);
                $updatedUser = $this->userRepository->getUserById($user->id);
                return $this->renderProfileWithResponse("Settings updated successfully!", "success", $updatedUser);
            } catch (\Exception $e) {
                error_log("Update Settings Error: " . $e->getMessage());
                http_response_code(500);
                return $this->renderProfileWithResponse("An error occurred while updating settings.");
            }
        }

        return $this->renderProfileWithResponse("No changes were made.");
    }

    private function renderProfileWithResponse(string $message = '', string $type = 'error', $userOverride = null)
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        $user = $userOverride ?? $this->getUser();

        $statsModel = $this->statsRepository->getStatsByUserEmail($user->email);
        $statsDTO = UserStatisticsResponseDTO::fromEntity($statsModel);

        $this->render('profile', [
            'stats' => $statsDTO,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'message' => $message,
            'type' => $type,
            'csrf' => $_SESSION['csrf']
        ]);
    }
}