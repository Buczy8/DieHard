<?php

namespace App\Middleware;

use App\Annotation\RequiresHttps;
use ReflectionMethod;

class CheckHttps
{
    public static function check(object $controller, string $methodName): void
    {

        $reflection = new ReflectionMethod($controller, $methodName);
        $attributes = $reflection->getAttributes(RequiresHttps::class);


        if (!empty($attributes)) {
            self::ensureHttps();
        }
    }

    private static function ensureHttps(): void
    {

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;


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