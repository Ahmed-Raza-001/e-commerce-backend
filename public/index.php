<?php

use App\Kernel;

// Sanitize DATABASE_URL to strip surrounding quotes and ensure sslmode=require for Supabase PostgreSQL
foreach (['DATABASE_URL', 'HTTP_DATABASE_URL'] as $key) {
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if (!empty($val) && is_string($val)) {
        $url = trim($val, " \"'\r\n\t");
        if (preg_match('/sslmode=[^&]+/i', $url)) {
            $url = preg_replace('/sslmode=[^&]+/i', 'sslmode=require', $url);
        } else {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'sslmode=require';
        }
        $_ENV[$key] = $url;
        $_SERVER[$key] = $url;
        putenv("{$key}={$url}");
    }
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
