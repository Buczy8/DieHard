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
            return $this->render("login");
        }

        if ($this->isPost()) {
            $loginDto = LoginDTO::fromRequest($_POST);

            if (empty($loginDto->email) || empty($loginDto->password)) {
                return $this->render("login", ["message" => "Fill all fields"]);
            }
            if (!filter_var($loginDto->email, FILTER_VALIDATE_EMAIL)) {
                return $this->render('login', ['message' => 'Invalid email format']);
            }

            $user = $this->userRepository->getUserByEmail($loginDto->email);

            if (!$user || !password_verify($loginDto->password, $user->password)) {
                return $this->render("login", ["message" => "Invalid email or password"]);
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_email'] = $user->email;

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
            return $this->render("register");
        }

        if ($this->isPost()) {
            $formData = [
                'email' => $_POST["email"] ?? '',
                'password' => $_POST["password"] ?? '',
                'username' => trim($_POST["user-name"] ?? ''),
                'role' => 'user'
            ];
            $passwordConfirmation = $_POST["passwordConfirmation"] ?? '';

            if (empty($formData['email']) || empty($formData['password']) || empty($formData['username']) || empty($passwordConfirmation)) {
                return $this->render("register", ["message" => "nie wszytkie pola są wypiełnoine"]);
            }

            if ($formData['password'] !== $passwordConfirmation) {
                return $this->render("register", ["message" => "hasła się nie zgdzaja"]);
            }

            $userDTO = CreateUserDTO::fromRequest($formData);
            $existingUser = $this->userRepository->getUserByEmail($userDTO->email);

            if ($existingUser) {
                return $this->render("register", ["message" => "Użytkownik o tym adresie email już istnieje!"]);
            }

            $hashedPassword = password_hash($userDTO->password, PASSWORD_DEFAULT);

            $user = new User(
                email: $userDTO->email,
                username: $userDTO->username,
                password: $hashedPassword,
                role: $userDTO->role
            );

            $this->userRepository->createUser($user);

            return $this->render("login", ["message" => "Zarejestrowano poprawnie! Zaloguj się."]);
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