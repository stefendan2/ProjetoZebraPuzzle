<?php

declare(strict_types=1);

use ZebraPuzzle\Core\Router;

$GLOBALS['app_config'] = [
    'base_path' => '',
    'captcha_ttl' => 300,
    'email_token_ttl' => 86400,
    'environment' => 'development',
];

function app_config(?string $key = null): mixed
{
    $config = $GLOBALS['app_config'];
    return $key === null ? $config : ($config[$key] ?? null);
}

require dirname(__DIR__) . '/src/validacao.php';
require dirname(__DIR__) . '/src/Core/Router.php';
require dirname(__DIR__) . '/src/flash.php';
require dirname(__DIR__) . '/src/layout.php';
require dirname(__DIR__) . '/src/csrf.php';
require dirname(__DIR__) . '/src/sessao.php';
require dirname(__DIR__) . '/src/contas.php';
require dirname(__DIR__) . '/src/captcha.php';

session_id('fase4-' . bin2hex(random_bytes(8)));
session_start();
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
$verificar(normalizar_email(' Jogador@Example.COM ') === 'jogador@example.com', 'O e-mail deve ser normalizado.');
$verificar(nome_usuario_valido(str_repeat('a', 50)), 'Nome com 50 caracteres deve ser aceito.');
$verificar(!nome_usuario_valido(str_repeat('a', 51)), 'Nome com 51 caracteres deve ser rejeitado.');
$verificar(senha_valida('12345678'), 'Senha com 8 caracteres deve ser aceita.');
$verificar(!senha_valida('1234567'), 'Senha com 7 caracteres deve ser rejeitada.');

$errosSimultaneos = validar_dados_conta([
    'cpf' => '123',
    'email' => 'inválido',
    'nome_usuario' => str_repeat('a', 51),
    'senha' => 'curta',
]);
$verificar(count($errosSimultaneos) === 4, 'Todos os campos inválidos devem ser informados de uma vez.');
$verificar(
    e('<script>alert("x")</script>') === '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;',
    'A saída HTML deve ser escapada.'
);

$rotaExecutada = false;
$router = new Router();
$router->get('/teste', static function () use (&$rotaExecutada): void {
    $rotaExecutada = true;
});
$router->post('/logout', static function (): void {
});
$router->dispatch('GET', '/teste?origem=teste');
$verificar($rotaExecutada, 'O roteador deve localizar a rota sem usar caminhos físicos.');

ob_start();
http_response_code(200);
$router->dispatch('GET', '/logout');
ob_end_clean();
$verificar(http_response_code() === 405, 'GET /logout deve ser rejeitado com 405.');

ob_start();
http_response_code(200);
$router->dispatch('GET', '/rota-inexistente');
ob_end_clean();
$verificar(http_response_code() === 404, 'Rota inexistente deve retornar 404.');

flash_adicionar('sucesso', 'mensagem de teste');
$mensagens = flash_consumir();
$verificar(count($mensagens) === 1, 'A mensagem flash deve ser consumida uma vez.');
$verificar(flash_consumir() === [], 'A mensagem flash não deve reaparecer após o consumo.');

$tokenCsrf = csrf_token();
$verificar(strlen($tokenCsrf) === 64, 'O token CSRF deve representar 32 bytes aleatórios em hexadecimal.');
$verificar(csrf_valido($tokenCsrf), 'O token CSRF da sessão deve ser aceito.');
$verificar(!csrf_valido(str_repeat('0', 64)), 'Um token CSRF diferente deve ser rejeitado.');

$codigoCaptcha = captcha_gerar_codigo();
$verificar(strlen($codigoCaptcha) === 6, 'O captcha deve conter seis caracteres.');
$verificar(
    preg_match('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}$/', $codigoCaptcha) === 1,
    'O captcha deve excluir caracteres ambíguos.'
);
$codigoCaptcha = captcha_criar();
$verificar(captcha_validar(mb_strtolower($codigoCaptcha, 'UTF-8')), 'O captcha deve aceitar resposta sem diferenciar maiúsculas.');
$verificar(!captcha_validar($codigoCaptcha), 'O captcha deve ser invalidado depois do uso.');
$_SESSION['captcha_login'] = ['codigo' => 'ABC234', 'expira_em' => time() - 1];
$verificar(!captcha_validar('ABC234'), 'Captcha expirado deve ser rejeitado.');
$_SESSION['captcha_login'] = ['codigo' => 'ABC234', 'expira_em' => time() + 60];
$verificar(!captcha_validar('ERRADO'), 'Captcha incorreto deve ser rejeitado.');

$idSessaoAntes = session_id();
autenticar_usuario(123, 'jogador');
$verificar(session_id() !== $idSessaoAntes, 'O login deve regenerar o identificador da sessão.');
$verificar(usuario_logado('jogador'), 'A sessão do jogador deve reconhecer o perfil correto.');
$verificar(!usuario_logado('administrador'), 'A sessão do jogador não pode ser tratada como administrador.');
encerrar_sessao_usuario();
$verificar(!usuario_logado(), 'O logout deve remover a autenticação.');

formulario_guardar('teste', ['email' => 'valor@example.com'], ['email' => 'erro']);
$formulario = formulario_consumir('teste');
$verificar(($formulario['dados']['email'] ?? '') === 'valor@example.com', 'Dados válidos devem sobreviver ao PRG.');
$verificar(formulario_consumir('teste')['dados'] === [], 'Dados de formulário devem ser consumidos uma vez.');

$tokenDesenvolvimento = str_repeat('a', 64);
link_verificacao_desenvolvimento_guardar($tokenDesenvolvimento);
$verificar(link_verificacao_desenvolvimento_consumir() === '/verificar-email', 'O link local não deve expor o token na URL.');
$verificar(link_verificacao_desenvolvimento_consumir() === null, 'O link local deve ser mostrado uma única vez.');
$verificar(token_verificacao_desenvolvimento_consumir() === $tokenDesenvolvimento, 'A verificação local deve recuperar o token somente da sessão.');

$hash = password_hash('SenhaSegura123', PASSWORD_DEFAULT);
$verificar($hash !== 'SenhaSegura123', 'A senha armazenada deve ser um hash.');
$verificar(password_verify('SenhaSegura123', $hash), 'O hash deve validar a senha original.');

$schema = file_get_contents(dirname(__DIR__) . '/sql/schema.sql');
$rotas = file_get_contents(dirname(__DIR__) . '/config/routes.php');
$verificar(is_string($schema), 'O schema.sql deve estar legível.');
$verificar(is_string($rotas), 'O arquivo de rotas deve estar legível.');

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

$verificar(is_string($schema) && str_contains($schema, 'email_pendente VARCHAR(254)'), 'O schema deve suportar e-mail pendente.');
$verificar(is_string($schema) && !str_contains($schema, 'IDENTIFIED BY'), 'O schema não deve conter credenciais.');
$verificar(is_string($rotas) && str_contains($rotas, "post('/logout'"), 'O logout deve aceitar POST.');
$verificar(is_string($rotas) && !str_contains($rotas, "get('/logout'"), 'O logout não deve aceitar GET.');
$verificar(is_string($rotas) && str_contains($rotas, 'csrf_exigir_valido()'), 'Formulários mutáveis devem exigir CSRF.');

if ($falhas !== []) {
    session_destroy();
    fwrite(STDERR, "Falhas:\n- " . implode("\n- ", $falhas) . "\n");
    exit(1);
}

session_destroy();
fwrite(STDOUT, "Todas as verificações disponíveis passaram.\n");
