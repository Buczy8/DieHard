<?php

namespace App;

use App\Middleware\CheckAuthRequirements;
use App\Middleware\CheckRequestAllowed;
use App\Middleware\CheckHttps;

class Routing
{
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
        'api/user-info' => [
            'controller' => 'App\Controllers\SecurityController',
            'action' => 'getUserInfoAPI'
        ],
        '' => [
            'controller' => 'App\Controllers\DashboardController',
            'action' => 'index'
        ],
        'api/dashboard' => [
            'controller' => 'App\Controllers\DashboardController',
            'action' => 'getDashboardDataAPI'
        ],
        'history' => [
            'controller' => 'App\Controllers\HistoryController',
            'action' => 'index'
        ],
        'api/history' => [
            'controller' => 'App\Controllers\HistoryController',
            'action' => 'getHistoryDataAPI'
        ],
        'rules' => [
            'controller' => 'App\Controllers\RulesController',
            'action' => 'index'
        ],
        'profile' => [
            'controller' => 'App\Controllers\UserProfileController',
            'action' => 'index'
        ],
        'api/profile' => [
            'controller' => 'App\Controllers\UserProfileController',
            'action' => 'getProfileDataAPI'
        ],
        'update-settings' => [
            'controller' => 'App\Controllers\UserProfileController',
            'action' => 'updateSettings'
        ],
        'admin' => [
            'controller' => 'App\Controllers\AdminController',
            'action' => 'adminPanel'
        ],
        'admin/users' => [
            'controller' => 'App\Controllers\AdminController',
            'action' => 'getAllUsersAPI'
        ],
        'admin/stats' => [
            'controller' => 'App\Controllers\AdminController',
            'action' => 'getStatsAPI'
        ],
        'admin/delete-user' => [
            'controller' => 'App\Controllers\AdminController',
            'action' => 'deleteUserAPI'
        ],
        'admin/change-role' => [
            'controller' => 'App\Controllers\AdminController',
            'action' => 'changeUserRoleAPI'
        ],
    ];

    public static function run(string $path)
    {
        if (array_key_exists($path, self::$routes)) {
            $controller = self::$routes[$path]['controller'];
            $action = self::$routes[$path]['action'];
            $controllerObj = new $controller;

            CheckRequestAllowed::check($controllerObj, $action);
            CheckAuthRequirements::check($controllerObj, $action);
            CheckHttps::check($controllerObj, $action);

            $controllerObj->$action();
        } else {
            include 'Public/views/404.html';
        }
    }
}