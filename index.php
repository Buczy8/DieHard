<?php

require_once 'vendor/autoload.php';
use App\Routing;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$path = trim($_SERVER['REQUEST_URI'], '/');
$path = parse_url($path, PHP_URL_PATH);

Routing::run($path);
?>