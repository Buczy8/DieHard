<?php

namespace App\Controllers;

use App\Database;
use App\DTO\CreateUserDTO;
use App\Models\User;
use App\Repository\UserRepository;
use App\DTO\LoginDTO;

class SecurityController extends AppController
{
    private $userRepository;

    public function __construct()
    {
        $db = Database::getInstance();
        $this->userRepository = new UserRepository($db);
    }

    public function login()
    {
        if ($this->isGet()) {
            return $this->render("login");
        }

        $loginDto = LoginDTO::fromRequest($_POST);

        if (empty($loginDto->email) || empty($loginDto->password)){
            return $this->render("login", ["message" => "Fill all fields"]);
        }

        $user = $this->userRepository->getUserByEmail($loginDto->email);

        if (!$user) {
            return $this->render("login", ["message" => "User not found"]);
        }
        if (!password_verify($loginDto->password, $user->password)) {
            return $this->render("login", ["message" => "Wrong password"]);
        }

        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/dicegame");
    }

    public function register()
    {
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

        $messages = [];

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
            return $this->render('register', [
                'messages' => ['Wystąpił błąd serwera. Spróbuj ponownie później.'],
                'old_values' => ['email' => $formData['email'], 'nickname' => $formData['username']]
            ]);
        }
    }
}