<?php

namespace App\Controllers;

use App\Annotation\AllowedMethods;
use App\Annotation\RequiresHttps;
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

    #[RequiresHttps]
    #[AllowedMethods(['POST', 'GET'])]
    public function login()
    {

        if ($this->isGet()) {
            if (empty($_SESSION['csrf'])) {
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
            }
            return $this->render("login");
        }

        if ($this->isPost()) {

            $failures = $_SESSION['login_failures'] ?? 0;

            if ($failures > 5) {
                sleep(2);
            }

            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf']) {
                http_response_code(400);
                return $this->render("login", ["message" => "Session expired or invalid request."]);
            }
            $loginDto = LoginDTO::fromRequest($_POST);

            if (empty($loginDto->email) || empty($loginDto->password)) {
                http_response_code(400);
                return $this->render("login", ["message" => "Fill all fields"]);
            }

            if (strlen($loginDto->email) > 100) {
                http_response_code(400);
                return $this->render("login", ["message" => "Email is too long"]);
            }

            if (!filter_var($loginDto->email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                return $this->render('login', ['message' => 'Invalid email format']);
            }

            $user = $this->userRepository->getUserByEmail($loginDto->email);

            if (!$user || !password_verify($loginDto->password, $user->password)) {
                $_SESSION['login_failures'] = $failures + 1;
                http_response_code(400);
                error_log("Failed login for {$loginDto->email} from IP ".$_SERVER['REMOTE_ADDR']);
                return $this->render("login", ["message" => "Invalid email or password"]);
            }

            unset($_SESSION['login_failures']);

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_email'] = $user->email;

            unset($_SESSION['csrf']);

            $url = "https://" . $_SERVER['HTTP_HOST'];
            header("Location: {$url}/dicegame");
            exit();
        }
    }

    #[RequiresHttps]
    #[AllowedMethods(['POST', 'GET'])]
    public function register()
    {

        if ($this->isGet()) {
            if (empty($_SESSION['csrf'])) {
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
            }
            return $this->render("register");
        }

        if ($this->isPost()) {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf']) {
                http_response_code(400);
                return $this->render("register", ["message" => "Session expired."]);
            }
            $formData = [
                'email' => $_POST["email"] ?? '',
                'password' => $_POST["password"] ?? '',
                'username' => trim($_POST["user-name"] ?? ''),
                'role' => 'user'
            ];
            $passwordConfirmation = $_POST["passwordConfirmation"] ?? '';

            if (empty($formData['email']) || empty($formData['password']) || empty($formData['username']) || empty($passwordConfirmation)) {
                http_response_code(400);
                return $this->render("register", ["message" => "Fill all fields."]);
            }

            if (strlen($formData['email']) > 100) {
                http_response_code(400);
                return $this->render("register", ["message" => "Email is too long"]);
            }
            if (strlen($formData['password']) < 8) {
                http_response_code(400);
                return $this->render("register", ["message" => "Password is too weak"]);
            }

            if ($formData['password'] !== $passwordConfirmation) {
                http_response_code(400);
                return $this->render("register", ["message" => "passwords does not match"]);
            }

            $userDTO = CreateUserDTO::fromRequest($formData);
            $existingUser = $this->userRepository->getUserByEmail($userDTO->email);

            if ($existingUser) {
                http_response_code(400);
                return $this->render("register", ["message" => "This account already exists"]);
            }

            $existingUsername = $this->userRepository->getUserByUserName($userDTO->username);
            if ($existingUsername) {
                http_response_code(400);
                return $this->render("register", ["message" => "This username already exists"]);
            }

            $hashedPassword = password_hash($userDTO->password, PASSWORD_DEFAULT);

            $user = new User(
                email: $userDTO->email,
                username: $userDTO->username,
                password: $hashedPassword,
                role: $userDTO->role
            );

            $this->userRepository->createUser($user);

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

        $url = "https://" . $_SERVER['HTTP_HOST'];
        header("Location: {$url}/login");
        exit();
    }

}