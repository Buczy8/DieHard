<?php

namespace App;

class ErrorHandler
{
    public static function handleException(\Throwable $e, string $environment): void
    {
        $errorCode = $e->getCode();

        if ($errorCode === 405) {
            http_response_code(405);
            echo "<h1>Błąd 405</h1>";
            echo "<p>Niedozwolona metoda żądania. " . htmlspecialchars($e->getMessage()) . "</p>";
            exit;
        }
        if ($errorCode === 403) {
            http_response_code(404);
            include 'Public/views/404.html';
            exit;
        }
        if ($errorCode === 401) {
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
}
