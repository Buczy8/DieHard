<?php

namespace App\Middleware;

use App\Annotation\RequireLogin;
use App\Annotation\RequireAdmin;
use ReflectionMethod;

class CheckAuthRequirements
{
    public static function check(object|string $controller, string $methodName): void
    {
        $reflection = new ReflectionMethod($controller, $methodName);

        $loginAttributes = $reflection->getAttributes(RequireLogin::class);
        if (!empty($loginAttributes)) {
            if (empty($_SESSION['user_id'])) {
                throw new \Exception("User not logged in", 401);
            }
        }


        $adminAttributes = $reflection->getAttributes(RequireAdmin::class);
        if (!empty($adminAttributes)) {

            if (empty($_SESSION['user_id'])) {
                throw new \Exception("User not logged in", 401);
            }


            $role = $_SESSION['user_role'] ?? '';
            if ($role !== 'admin') {
                throw new \Exception("Access denied: Admins only", 403);
            }
        }
    }
}