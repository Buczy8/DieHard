<?php

namespace App\Controllers;

use App\Annotation\AllowedMethods;
use App\Annotation\RequireLogin;
use App\DTO\CreateUserDTO;
use App\Models\User;
use App\Repository\UserRepository;
use App\DTO\LoginDTO;

class SecurityController extends AppController
{
    private $userRepository;

    public function __construct()
    {
        $this->userRepository = UserRepository::getInstance();
    }

    #[RequireLogin]
    #[AllowedMethods(['GET'])]
    public function getUserInfoAPI()
    {
        header('Content-Type: application/json');
        $user = $this->getUser();

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Not logged in']);
            return;
        }

        echo json_encode([
            'username' => $user->username,
            'avatar' => $user->avatar,
            'role' => $user->role
        ]);
    }

    #[AllowedMethods(['POST', 'GET'])]
    public function login()
    {
        if ($this->isGet()) {
            $this->ensureCsrfToken();
            return $this->render("login");
        }

        if ($this->isPost()) {
            $this->handleLoginThrottling();

            if (!$this->validateCsrfToken()) {
                http_response_code(400);
                return $this->render("login", ["message" => "Session expired or invalid request."]);
            }

            $loginDto = LoginDTO::fromRequest($_POST);

            $validationError = LoginDTO::validate($loginDto);
            if ($validationError) {
                http_response_code(400);
                return $this->render("login", ["message" => $validationError]);
            }

            $user = $this->userRepository->getUserByEmail($loginDto->email);

            if (!$user || !password_verify($loginDto->password, $user->password)) {
                $this->incrementLoginFailures();
                http_response_code(400);
                error_log("Failed login for {$loginDto->email} from IP " . $_SERVER['REMOTE_ADDR']);
                return $this->render("login", ["message" => "Invalid email or password"]);
            }

            $this->resetLoginFailures();
            $this->startUserSession($user);

            $url = "http://" . $_SERVER['HTTP_HOST'];
            header("Location: {$url}/");
            exit();
        }
    }

    #[AllowedMethods(['POST', 'GET'])]
    public function register()
    {
        if ($this->isGet()) {
            $this->ensureCsrfToken();
            return $this->render("register");
        }

        if ($this->isPost()) {
            if (!$this->validateCsrfToken()) {
                http_response_code(400);
                return $this->render("register", ["message" => "Session expired."]);
            }

            $formData = [
                'email' => $_POST["email"] ?? '',
                'password' => $_POST["password"] ?? '',
                'username' => trim($_POST["user-name"] ?? ''),
                'passwordConfirmation' => $_POST["passwordConfirmation"] ?? '',
                'role' => 'user'
            ];

            $validationError = CreateUserDTO::validate($formData);
            if ($validationError) {
                http_response_code(400);
                return $this->render("register", ["message" => $validationError]);
            }

            $userDTO = CreateUserDTO::fromRequest($formData);
            
            if ($this->userRepository->getUserByEmail($userDTO->email)) {
                http_response_code(400);
                return $this->render("register", ["message" => "This account already exists"]);
            }

            if ($this->userRepository->getUserByUserName($userDTO->username)) {
                http_response_code(400);
                return $this->render("register", ["message" => "This username already exists"]);
            }

            $this->createUser($userDTO);

            return $this->render("login", ["message" => "You have registered successfully! Log in", "type" => "success"]);
        }
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        $url = "http://" . $_SERVER['HTTP_HOST'];
        header("Location: {$url}/login");
        exit();
    }

    private function ensureCsrfToken(): void
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
    }

    private function validateCsrfToken(): bool
    {
        return isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf'];
    }

    private function handleLoginThrottling(): void
    {
        $failures = $_SESSION['login_failures'] ?? 0;
        if ($failures > 5) {
            sleep(2);
        }
    }

    private function incrementLoginFailures(): void
    {
        $_SESSION['login_failures'] = ($_SESSION['login_failures'] ?? 0) + 1;
    }

    private function resetLoginFailures(): void
    {
        unset($_SESSION['login_failures']);
    }

    private function startUserSession(User $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_role'] = $user->role;
        unset($_SESSION['csrf']);
    }

    private function createUser(CreateUserDTO $userDTO): void
    {
        $hashedPassword = password_hash($userDTO->password, PASSWORD_DEFAULT);

        $user = new User(
            email: $userDTO->email,
            username: $userDTO->username,
            password: $hashedPassword,
            role: $userDTO->role
        );

        $this->userRepository->createUser($user);
    }
}
