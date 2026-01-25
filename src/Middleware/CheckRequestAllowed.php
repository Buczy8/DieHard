<?php

namespace App\Middleware;

use App\Annotation\AllowedMethods;
use ReflectionMethod;
use Exception;

class  CheckRequestAllowed
{
    public static function check(object $controller, string $methodName): void
    {
        $reflection = new ReflectionMethod($controller, $methodName);
        $attributes = $reflection->getAttributes(AllowedMethods::class);

        if (!empty($attributes)) {
            $instance = $attributes[0]->newInstance();
            $allowed = $instance->methods;

            if (!in_array($_SERVER['REQUEST_METHOD'], $allowed)) {
                header('Allow: ' . implode(', ', $allowed));

                throw new Exception("Method Not Allowed", 405);
            }
        }
    }
}