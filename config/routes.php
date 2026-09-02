<?php

declare(strict_types=1);

use ZebraPuzzle\Core\Router;

return static function (Router $router): void {
    $router->get('/', static function (): void {
        renderizar('home', ['titulo' => 'Início']);
    });

    $router->get('/area-jogador', static function (): void {
        exigir_login_jogador();

        $statement = db()->query(
            'SELECT DATABASE() AS banco, CURRENT_DATE() AS data_atual, @@session.time_zone AS fuso'
        );
        $dadosBanco = $statement->fetch();

        renderizar('area-jogador', [
            'titulo' => 'Área do jogador',
            'dadosBanco' => is_array($dadosBanco) ? $dadosBanco : [],
        ]);
    });

    $router->post('/area-jogador/flash', static function (): void {
        exigir_login_jogador();
        csrf_exigir_valido();
        flash_adicionar('sucesso', 'POST recebido, token CSRF validado e redirecionamento concluído.');
        redirecionar('/area-jogador');
    });
};

