<?php

require_once 'vendor/autoload.php';

use App\Routing;

session_start();
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
try {
    $path = trim($_SERVER['REQUEST_URI'], '/');
    $path = parse_url($path, PHP_URL_PATH);

    Routing::run($path);
} catch (\Exception $e) {
    if ($e->getCode() === 405) {
        http_response_code(405);
        echo "<h1>Błąd 405</h1>";
        echo "<p>Niedozwolona metoda żądania. " . htmlspecialchars($e->getMessage()) . "</p>";
        exit;
    }
    if ($e->getCode() === 401) {
        // Obsługa błędu braku logowania
        header("Location: /login");
        var_dump($e->getMessage());

    }
}
?>