<?php

use core\utils\Router;

/**
 * Ponto de entrada principal da aplicação pública.
 *
 * Carrega a configuração base, o autoload PSR-4 simplificado e encaminha
 * a requisição para o roteador existente do protótipo.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rootPath = dirname(__DIR__);
$config = [];

require_once $rootPath . '/app/etc/config.php';
require_once $rootPath . '/autoload.php';

$router = new Router();
$uri = $_GET['uri'] ?? '';
$module = ($uri === '' || str_starts_with($uri, 'index.php')) ? 'home' : $uri;

$router->dispatch($module);