<?php

declare(strict_types=1);

use ZebraPuzzle\Core\Router;

require dirname(__DIR__) . '/src/validacao.php';
require dirname(__DIR__) . '/src/Core/Router.php';
require dirname(__DIR__) . '/src/flash.php';
require dirname(__DIR__) . '/src/csrf.php';

$_SESSION = [];

$falhas = [];

$verificar = static function (bool $condicao, string $descricao) use (&$falhas): void {
    if (!$condicao) {
        $falhas[] = $descricao;
    }
};

$verificar(cpf_valido('529.982.247-25'), 'CPF válido deve ser aceito.');
$verificar(!cpf_valido('111.111.111-11'), 'CPF com todos os dígitos iguais deve ser rejeitado.');
$verificar(!cpf_valido('123'), 'CPF incompleto deve ser rejeitado.');
$verificar(email_valido('jogador@example.com'), 'E-mail válido deve ser aceito.');
$verificar(!email_valido('email-invalido'), 'E-mail inválido deve ser rejeitado.');
$verificar(nome_usuario_valido(str_repeat('a', 50)), 'Nome com 50 caracteres deve ser aceito.');
$verificar(!nome_usuario_valido(str_repeat('a', 51)), 'Nome com 51 caracteres deve ser rejeitado.');
$verificar(senha_valida('12345678'), 'Senha com 8 caracteres deve ser aceita.');
$verificar(!senha_valida('1234567'), 'Senha com 7 caracteres deve ser rejeitada.');

$rotaExecutada = false;
$router = new Router();
$router->get('/teste', static function () use (&$rotaExecutada): void {
    $rotaExecutada = true;
});
$router->dispatch('GET', '/teste?origem=teste');
$verificar($rotaExecutada, 'O roteador deve localizar a rota sem usar caminhos físicos.');

flash_adicionar('sucesso', 'mensagem de teste');
$mensagens = flash_consumir();
$verificar(count($mensagens) === 1, 'A mensagem flash deve ser consumida uma vez.');
$verificar(flash_consumir() === [], 'A mensagem flash não deve reaparecer após o consumo.');

$token = csrf_token();
$verificar(strlen($token) === 64, 'O token CSRF deve representar 32 bytes aleatórios em hexadecimal.');
$verificar(csrf_valido($token), 'O token CSRF da sessão deve ser aceito.');
$verificar(!csrf_valido(str_repeat('0', 64)), 'Um token CSRF diferente deve ser rejeitado.');

$schema = file_get_contents(dirname(__DIR__) . '/sql/schema.sql');
$verificar(is_string($schema), 'O schema.sql deve estar legível.');

foreach ([
    'jogador',
    'administrador',
    'tema',
    'tema_categoria',
    'tema_valor',
    'desafio_diario',
    'resolucao',
    'leaderboard',
    'acesso_diario',
    'verificacao_email',
] as $tabela) {
    $verificar(
        is_string($schema) && str_contains($schema, 'CREATE TABLE IF NOT EXISTS ' . $tabela),
        'O schema deve declarar a tabela ' . $tabela . '.'
    );
}

if ($falhas !== []) {
    fwrite(STDERR, "Falhas:\n- " . implode("\n- ", $falhas) . "\n");
    exit(1);
}

fwrite(STDOUT, "Todas as verificações disponíveis passaram.\n");
