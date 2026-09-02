<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

try {
    $router = criar_roteador();
    $registrarRotas = require dirname(__DIR__) . '/config/routes.php';
    $registrarRotas($router);
    $router->dispatch(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        $_SERVER['REQUEST_URI'] ?? '/'
    );
} catch (Throwable $error) {
    error_log($error->__toString());
    http_response_code(500);

    $mensagem = app_config('debug')
        ? $error->getMessage()
        : 'Ocorreu um erro interno. Consulte o registro do servidor.';

    renderizar('erro', [
        'titulo' => 'Erro interno',
        'mensagem' => $mensagem,
    ]);
}

