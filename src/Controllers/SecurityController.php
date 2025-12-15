<?php

namespace App\Controllers;

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

    private function ensureHttps()
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;

        if (!$isHttps) {
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirect);
            exit();
        }
    }
    public function login()
    {
        $this->ensureHttps();

        if ($this->isGet()) {
            return $this->render("login");
        }

        $loginDto = LoginDTO::fromRequest($_POST);

        if (empty($loginDto->email) || empty($loginDto->password)){
            return $this->render("login", ["message" => "Fill all fields"]);
        }
        if (!filter_var($loginDto->email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('login', ['messages' => 'Invalid email format']);
        }

        $user = $this->userRepository->getUserByEmail($loginDto->email);

        if ((!$user) || !password_verify($loginDto->password, $user->password)) {
            return $this->render("login", ["message" => "Invalid email or password"]);
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;

        $url = "https://" . $_SERVER['HTTP_HOST'];
        header("Location: {$url}/dicegame");
        exit();
    }

    public function register()
    {
        $this->ensureHttps();

        if ($this->isGet()) {
            return $this->render("register");
        }

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


        try {
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

        } catch (\Exception $e) {
            return $this->render('register', ["message" => "Wystąpił błąd serwera. Spróbuj ponownie później."]);
        }
    }
}