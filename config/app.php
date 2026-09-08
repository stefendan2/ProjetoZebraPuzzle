<?php

declare(strict_types=1);

$env = static function (string $key, string $default = ''): string {
    $value = getenv($key);

    return $value === false ? $default : $value;
};

$basePath = trim($env('APP_BASE_PATH'));
$basePath = $basePath === '' ? '' : '/' . trim($basePath, '/');

return [
    'name' => 'Zebra Puzzle',
    'environment' => $env('APP_ENV', 'development'),
    'debug' => filter_var($env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'timezone' => 'America/Sao_Paulo',
    'base_path' => $basePath,
    'session_name' => 'zebra_puzzle_session',
    'email_token_ttl' => 86400,
    'captcha_ttl' => 300,
];
