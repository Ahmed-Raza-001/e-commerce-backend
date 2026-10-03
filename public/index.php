<?php

use App\Kernel;

// Fix Apache/CGI stripping Authorization header
if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } else {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : (function_exists('getallheaders') ? getallheaders() : []);
        foreach ($headers as $header => $value) {
            if (strtolower($header) === 'authorization') {
                $_SERVER['HTTP_AUTHORIZATION'] = $value;
                break;
            }
        }
    }
}

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
