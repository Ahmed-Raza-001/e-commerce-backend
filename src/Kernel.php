<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug)
    {
        self::sanitizeDatabaseUrl();
        parent::__construct($environment, $debug);
    }

    private static function sanitizeDatabaseUrl(): void
    {
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
    }

    /**
     * @return list<string> An array of allowed values for APP_ENV
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }
}
