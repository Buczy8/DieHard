<?php

namespace App\Middleware;

use App\Annotation\RequireLogin;
use ReflectionMethod;

class CheckAuthRequirements
{
    public static function Check(object|string $controller, string $methodName): void
    {
        $reflection = new ReflectionMethod($controller, $methodName);

        // Pobieramy atrybuty typu RequireLogin
        $attributes = $reflection->getAttributes(RequireLogin::class);

        // Jeśli znaleziono atrybut RequireLogin
        if (!empty($attributes)) {

            // Sprawdzamy czy użytkownik JEST zalogowany
            // (Tutaj wstaw swój warunek, np. sprawdzenie sesji)
            $isLoggedIn = !empty($_SESSION['user_id']);

            if (!$isLoggedIn) {
                // Rzucamy wyjątek 401 (Unauthorized) zamiast die()
                // To pozwoli Ci przekierować użytkownika na login w index.php
                throw new \Exception("Użytkownik nie jest zalogowany", 401);
            }
        }
    }
}

