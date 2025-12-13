<?php

use App\Routing;

require_once 'vendor/autoload.php';

$path = trim($_SERVER['REQUEST_URI'], '/');
$path = parse_url($path, PHP_URL_PATH);

Routing::run($path);
?>