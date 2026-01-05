<?php

namespace App\Controllers;

use App\Annotation\AllowedMethods;
use App\Annotation\RequireAdmin; // Używamy naszego nowego atrybutu
use App\Repository\UserRepository;

class AdminController extends AppController {
    private UserRepository $userRepository;

    public function __construct() {
        $this->userRepository = UserRepository::getInstance();
    }

    #[RequireAdmin]
    #[AllowedMethods(['GET'])]
    public function adminPanel(): void {
        $this->render('admin');
    }

    #[RequireAdmin]
    #[AllowedMethods(['GET'])]
    public function getAllUsersAPI(): void {
        header('Content-Type: application/json');
        try {
            $users = $this->userRepository->getAllUsers();
            echo json_encode($users);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error: ' . $e->getMessage()]);
        }
    }

    #[RequireAdmin]
    #[AllowedMethods(['DELETE'])]
    public function deleteUserAPI(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            return;
        }

        if ($id == $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['message' => 'Cannot delete yourself']);
            return;
        }

        $this->userRepository->deleteUser($id);
        http_response_code(200);
        echo json_encode(['message' => 'User deleted']);
    }
}