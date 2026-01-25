<?php

namespace App\Controllers;

use App\Models\User;
use App\Repository\UserRepository;

class AppController
{
    protected ?User $currentUser = null;

    protected function getUser(): ?User
    {
        if ($this->currentUser !== null) {
            return $this->currentUser;
        }

        if (isset($_SESSION['user_id'])) {
            $userRepo = UserRepository::getInstance();
            $this->currentUser = $userRepo->getUserById($_SESSION['user_id']);
        }

        return $this->currentUser;
    }

    protected function isGet(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'GET';
    }

    protected function isPost(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'POST';
    }

    protected function render(?string $template = null, array $variables = []): void
    {
        $templatePath = 'Public/views/' . $template . '.html';
        $user = $this->getUser();
        if ($user) {
            $variables['username'] ??= $user->username;
            $variables['avatar'] ??= $user->avatar;
        }
        $output = 'Public/views/404.html';

        if (file_exists($templatePath)) {
            extract($variables);
        }
        ob_start();
        include $templatePath;
        $output = ob_get_clean();
        echo $output;
    }
}
