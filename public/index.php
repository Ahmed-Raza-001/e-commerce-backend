<?php

use App\Kernel;

// Sanitize DATABASE_URL to strip surrounding quotes and ensure sslmode=require for Supabase PostgreSQL
foreach (['DATABASE_URL', 'HTTP_DATABASE_URL'] as $key) {
    if (!empty($_ENV[$key])) {
        $url = trim($_ENV[$key], " \"'");
        if (!str_contains($url, 'sslmode=')) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'sslmode=require';
        }
        $_ENV[$key] = $url;
    }
    if (!empty($_SERVER[$key])) {
        $url = trim($_SERVER[$key], " \"'");
        if (!str_contains($url, 'sslmode=')) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'sslmode=require';
        }
        $_SERVER[$key] = $url;
    }
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
