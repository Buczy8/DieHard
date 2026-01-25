<?php

namespace App\Controllers;

use App\Annotation\AllowedMethods;
use App\Annotation\RequireAdmin;
use App\Repository\UserRepository;

class AdminController extends AppController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = UserRepository::getInstance();
    }

    #[RequireAdmin]
    #[AllowedMethods(['GET'])]
    public function adminPanel(): void
    {
        $this->render('admin');
    }

    #[RequireAdmin]
    #[AllowedMethods(['GET'])]
    public function getAllUsersAPI(): void
    {
        header('Content-Type: application/json');
        try {

            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;
            if ($page < 1) $page = 1;
            if ($limit < 1) $limit = 8;
            $offset = ($page - 1) * $limit;


            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $role = isset($_GET['role']) ? trim($_GET['role']) : 'all';
            $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'DESC';


            $users = $this->userRepository->getUsersPaginated($limit, $offset, $search, $role, $sort);


            $totalItems = $this->userRepository->countUsersWithFilters($search, $role);
            $totalPages = ceil($totalItems / $limit);

            echo json_encode([
                'users' => $users,
                'pagination' => [
                    'currentPage' => $page,
                    'itemsPerPage' => $limit,
                    'totalItems' => $totalItems,
                    'totalPages' => $totalPages
                ]
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error: ' . $e->getMessage()]);
        }
    }

    #[RequireAdmin]
    #[AllowedMethods(['GET'])]
    public function getStatsAPI(): void
    {
        header('Content-Type: application/json');
        try {
            $totalUsers = $this->userRepository->countAllUsers();
            $admins = $this->userRepository->countAdmins();
            $activePlayers = $this->userRepository->countActivePlayers();

            echo json_encode([
                'totalUsers' => $totalUsers,
                'admins' => $admins,
                'activePlayers' => $activePlayers
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error: ' . $e->getMessage()]);
        }
    }

    #[RequireAdmin]
    #[AllowedMethods(['DELETE'])]
    public function deleteUserAPI(): void
    {
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

    #[RequireAdmin]
    #[AllowedMethods(['POST'])]
    public function changeUserRoleAPI(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        $newRole = $data['role'] ?? null;

        if (!$id || !$newRole) {
            http_response_code(400);
            echo json_encode(['message' => 'Missing parameters']);
            return;
        }

        if ($id == $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['message' => 'Cannot change your own role']);
            return;
        }

        $targetUser = $this->userRepository->getUserById($id);
        if (!$targetUser) {
            http_response_code(404);
            echo json_encode(['message' => 'User not found']);
            return;
        }

        if ($targetUser->role === 'admin') {
            http_response_code(403);
            echo json_encode(['message' => 'Cannot change role of another admin']);
            return;
        }

        $this->userRepository->updateUserRole($id, $newRole);
        http_response_code(200);
        echo json_encode(['message' => 'User role updated']);
    }
}
