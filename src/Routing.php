<?php
namespace App;
use App\Middleware\CheckAuthRequirements;
use App\Middleware\CheckRequestAllowed;
use App\Middleware\CheckHttps;
class Routing{
    public static $routes = [
        'dicegame' => [
            'controller' => 'App\Controllers\DiceGameController',
            'action' => 'game'
        ],
        'api/dice' => [
            'controller' => 'App\Controllers\DiceGameController',
            'action' => 'gameApi'
        ],
        'login' => [
            'controller' => 'App\Controllers\SecurityController',
            'action' => 'login'
        ],
        'register' => [
            'controller' => 'App\Controllers\SecurityController',
            'action' => 'register'
        ],
        'logout' => [
            'controller' => 'App\Controllers\SecurityController',
            'action' => 'logout'
        ],
        '' => [
            'controller' => 'App\Controllers\DashboardController',
            'action' => 'index'
        ],
        'history' => [
            'controller' => 'App\Controllers\HistoryController',
            'action' => 'index'
        ],
        'rules' => [
            'controller' => 'App\Controllers\RulesController',
            'action' => 'index'
        ],
        'profile' => [
            'controller' => 'App\Controllers\UserProfileController',
            'action' => 'index'
        ],
        'update-settings' => [
            'controller' => 'App\Controllers\UserProfileController',
            'action' => 'updateSettings'
        ],
    ];
    public static function run(string $path)
    {
        if (array_key_exists($path, self::$routes)) {
            $controller = self::$routes[$path]['controller'];
            $action = self::$routes[$path]['action'];
            $controllerObj = new $controller;

            CheckRequestAllowed::check($controllerObj, $action);
            CheckAuthRequirements::Check($controllerObj, $action);
            CheckHttps::check($controllerObj, $action);

            $controllerObj->$action();
        } else {
            include 'Public/views/404.html';
        }
    }
}