<?php

declare(strict_types=1);

use ZebraPuzzle\Core\Router;

$appConfig = require dirname(__DIR__) . '/config/app.php';
$GLOBALS['app_config'] = $appConfig;

date_default_timezone_set($appConfig['timezone']);
ini_set('default_charset', 'UTF-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($appConfig['session_name']);

    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $appConfig['base_path'] === '' ? '/' : $appConfig['base_path'],
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

require_once __DIR__ . '/Core/Router.php';
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/sessao.php';
require_once __DIR__ . '/validacao.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/contas.php';
require_once __DIR__ . '/captcha.php';
require_once __DIR__ . '/autenticacao.php';

function app_config(?string $key = null): mixed
{
    $config = $GLOBALS['app_config'];

    return $key === null ? $config : ($config[$key] ?? null);
}

function criar_roteador(): Router
{
    return new Router((string) app_config('base_path'));
}
