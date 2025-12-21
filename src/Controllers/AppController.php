<?php

namespace App\Controllers;
class AppController
{
    protected function isGet(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'GET';
    }

    protected function isPost(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'POST';
    }

    protected function render(?string $template = null, array $variables = []): void
    {
        $templatePath = 'Public/views/' . $template . '.html';
        $output = 'Public/views/404.html';

        if (file_exists($templatePath)) {
            extract($variables);
        }
        ob_start();
        include $templatePath;
        $output = ob_get_clean();
        echo $output;
    }
}
