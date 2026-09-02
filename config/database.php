<?php

declare(strict_types=1);

$env = static function (string $key, string $default = ''): string {
    $value = getenv($key);

    return $value === false ? $default : $value;
};

return [
    'driver' => 'mysql',
    'host' => $env('DB_HOST', '127.0.0.1'),
    'port' => (int) $env('DB_PORT', '3306'),
    'database' => $env('DB_DATABASE', 'zebraPuzzle'),
    'username' => $env('DB_USERNAME', 'zebrapuzzle_app'),
    'password' => $env('DB_PASSWORD'),
    'charset' => 'utf8mb4',
    'timezone' => $env('DB_TIMEZONE', 'America/Sao_Paulo'),
];

