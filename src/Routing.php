<?php
namespace App;
class Routing{
    public static $routes = [
        'dicegame' => [
            'controller' => 'App\Controllers\DiceGameController',
            'action' => 'index'
        ],
        'login' => [
            'controller' => 'App\Controllers\SecurityController',
            'action' => 'login'
        ],
        'register' => [
            'controller' => 'App\Controllers\SecurityController',
            'action' => 'register'
        ]
    ];
    public static function run(string $path)
    {
        if (array_key_exists($path, self::$routes)) {
            $controller = self::$routes[$path]['controller'];
            $action = self::$routes[$path]['action'];

            $controllerObj = new $controller;
            $controllerObj->$action();
        } else {
            include 'public/views/404.html';
        }
    }
}