<?php

namespace App\Middleware;

use App\Annotation\RequireLogin;
use App\Annotation\RequireAdmin;
use ReflectionMethod;

class CheckAuthRequirements
{
    public static function Check(object|string $controller, string $methodName): void
    {
        $reflection = new ReflectionMethod($controller, $methodName);

        // 1. Sprawdzenie RequireLogin
        $loginAttributes = $reflection->getAttributes(RequireLogin::class);
        if (!empty($loginAttributes)) {
            if (empty($_SESSION['user_id'])) {
                throw new \Exception("User not logged in", 401);
            }
        }

        // 2. Sprawdzenie RequireAdmin
        $adminAttributes = $reflection->getAttributes(RequireAdmin::class);
        if (!empty($adminAttributes)) {
            // Admin wymaga logowania
            if (empty($_SESSION['user_id'])) {
                throw new \Exception("User not logged in", 401);
            }
            
            // Sprawdzenie roli
            $role = $_SESSION['user_role'] ?? '';
            if ($role !== 'admin') {
                throw new \Exception("Access denied: Admins only", 403);
            }
        }
    }
}