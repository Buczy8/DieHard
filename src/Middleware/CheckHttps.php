<?php

namespace App\Middleware;

use App\Annotation\RequiresHttps;
use ReflectionMethod;

class CheckHttps
{
    public static function check(object $controller, string $methodName): void
    {
        // 1. Sprawdzamy, czy metoda ma atrybut #[RequiresHttps]
        $reflection = new ReflectionMethod($controller, $methodName);
        $attributes = $reflection->getAttributes(RequiresHttps::class);

        // Jeśli atrybut istnieje, wykonujemy sprawdzenie
        if (!empty($attributes)) {
            self::ensureHttps();
        }
    }

    private static function ensureHttps(): void
    {
        // Twoja logika sprawdzania HTTPS
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;

        // Opcjonalnie: Obsługa proxy (np. jeśli używasz Dockera/Nginx reverse proxy)
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $isHttps = true;
        }

        if (!$isHttps) {
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            http_response_code(301);
            header('Location: ' . $redirect);
            exit();
        }
    }
}