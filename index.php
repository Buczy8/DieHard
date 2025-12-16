<?php

require_once 'vendor/autoload.php';

use App\Routing;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$environment = $_ENV['APP_ENV'] ?? 'production';

ini_set('error_log', __DIR__ . '/php-errors.log');

if ($environment === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

session_set_cookie_params([
    'lifetime' => 0,            // Sesja wygasa po zamknięciu przeglądarki
    'path' => '/',              // Dostępne w całej domenie
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,           // Ciasteczko jest wysyłane tylko przez HTTPS
    'httponly' => true,         // chroni przed XSS
    'samesite' => 'Strict'      // Dodatkowa ochrona przed CSRF
]);


session_start();

$dotenv->load();
try {
    $path = trim($_SERVER['REQUEST_URI'], '/');
    $path = parse_url($path, PHP_URL_PATH);

    Routing::run($path);
} catch (\Exception $e) {
    $errorCode = $e->getCode();

    if ($errorCode === 405) {
        http_response_code(405);
        echo "<h1>Błąd 405</h1>";
        echo "<p>Niedozwolona metoda żądania. " . htmlspecialchars($e->getMessage()) . "</p>";
        exit;
    }
    if ($errorCode === 401 || $errorCode === 403) {
        header("Location: /login");
        exit;
    }

    http_response_code(500);

    error_log("Critical Error: " . htmlspecialchars($e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine()));

    if ($environment === 'production') {
        echo "<h1>Wystąpił błąd serwera</h1>";
        echo "<p>Przepraszamy, coś poszło nie tak. Spróbuj ponownie później.</p>";
    } else {
        echo "<h1>Error 500</h1>";
        echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
        echo "<strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    exit;
}
?>