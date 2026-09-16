<?php

declare(strict_types=1);

use ZebraPuzzle\Core\Router;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (in_array('--lint', $argv, true)) {
    $falhasSintaxe = 0;
    $totalSintaxe = 0;
    foreach (['src','config','public','tests','bin','resources'] as $pasta) {
        $arquivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/' . $pasta, FilesystemIterator::SKIP_DOTS));
        foreach ($arquivos as $arquivo) {
            if (!$arquivo->isFile() || $arquivo->getExtension() !== 'php') { continue; }
            $processo = proc_open([PHP_BINARY, '-l', $arquivo->getPathname()], [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes);
            if (!is_resource($processo) || proc_close($processo) !== 0) { $falhasSintaxe++; }
            $totalSintaxe++;
        }
    }
    fwrite(STDOUT, "Sintaxe: {$totalSintaxe} arquivos; {$falhasSintaxe} falhas.\n");
    exit($falhasSintaxe === 0 ? 0 : 1);
}

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
require dirname(__DIR__) . '/src/temas.php';
require dirname(__DIR__) . '/src/desafio_formato.php';
require dirname(__DIR__) . '/src/restricoes_desafio.php';
require dirname(__DIR__) . '/src/verificador_csp.php';
require dirname(__DIR__) . '/src/provedor_desafio.php';
require dirname(__DIR__) . '/src/provedor_desafio_exemplo.php';
require dirname(__DIR__) . '/src/desafios.php';
require dirname(__DIR__) . '/src/elegibilidade.php';
require dirname(__DIR__) . '/src/tentativas.php';
require dirname(__DIR__) . '/src/ofensiva.php';
require dirname(__DIR__) . '/src/leaderboard.php';
require dirname(__DIR__) . '/src/resolucoes.php';
require dirname(__DIR__) . '/src/contas.php';
require dirname(__DIR__) . '/src/captcha.php';
require __DIR__ . '/fase6.php';
require __DIR__ . '/fase7.php';
require __DIR__ . '/fase8.php';

session_id('fase6-' . bin2hex(random_bytes(8)));
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

$temaCompleto = [
    'nome' => 'Tema de teste',
    'descricao' => 'Estrutura usada apenas pelos testes automatizados.',
    'categorias' => [],
];
for ($categoria = 0; $categoria < 5; $categoria++) {
    $valores = [];
    for ($valor = 0; $valor < 5; $valor++) {
        $valores[] = ['nome' => "Valor {$categoria}.{$valor}", 'posicao' => $valor];
    }
    $temaCompleto['categorias'][] = [
        'nome' => 'Categoria ' . $categoria,
        'prefixo' => 'usa a categoria ' . $categoria,
        'posicao' => $categoria,
        'valores' => $valores,
    ];
}

$verificar(validar_estrutura_tema($temaCompleto) === [], 'Um tema exatamente 5 x 5 deve ser aceito.');

$temaQuatroCategorias = $temaCompleto;
array_pop($temaQuatroCategorias['categorias']);
$verificar(validar_estrutura_tema($temaQuatroCategorias) !== [], 'Um tema com 4 categorias deve ser rejeitado.');

$temaSeisCategorias = $temaCompleto;
$temaSeisCategorias['categorias'][] = [
    'nome' => 'Categoria 6',
    'prefixo' => 'usa a categoria extra',
    'posicao' => 5,
    'valores' => $temaCompleto['categorias'][0]['valores'],
];
$verificar(validar_estrutura_tema($temaSeisCategorias) !== [], 'Um tema com 6 categorias deve ser rejeitado.');

$categoriaQuatroValores = $temaCompleto;
array_pop($categoriaQuatroValores['categorias'][0]['valores']);
$verificar(validar_estrutura_tema($categoriaQuatroValores) !== [], 'Uma categoria com 4 valores deve ser rejeitada.');

$categoriaSeisValores = $temaCompleto;
$categoriaSeisValores['categorias'][0]['valores'][] = ['nome' => 'Sexto valor', 'posicao' => 5];
$verificar(validar_estrutura_tema($categoriaSeisValores) !== [], 'Uma categoria com 6 valores deve ser rejeitada.');

$posicaoAusente = $temaCompleto;
$posicaoAusente['categorias'][4]['posicao'] = 5;
$verificar(validar_estrutura_tema($posicaoAusente) !== [], 'Uma posição ausente deve ser rejeitada.');

$posicaoRepetida = $temaCompleto;
$posicaoRepetida['categorias'][1]['posicao'] = 0;
$verificar(validar_estrutura_tema($posicaoRepetida) !== [], 'Uma posição repetida deve ser rejeitada.');

$nomeVazio = $temaCompleto;
$nomeVazio['categorias'][0]['valores'][0]['nome'] = '   ';
$verificar(validar_estrutura_tema($nomeVazio) !== [], 'Um nome vazio deve ser rejeitado.');

$tipoInesperado = $temaCompleto;
$tipoInesperado['categorias'][0]['valores'] = 'não é uma lista';
$verificar(validar_estrutura_tema($tipoInesperado) !== [], 'Um tipo inesperado deve ser rejeitado sem erro fatal.');

$verificar(normalizar_id_tema('2') === 2, 'Um ID inteiro positivo deve ser aceito.');
$verificar(normalizar_id_tema('1 OR 1=1') === null, 'Uma tentativa de injeção no ID deve ser rejeitada.');
$verificar(normalizar_id_tema('') === null, 'Um ID vazio deve ser rejeitado.');
$verificar(normalizar_id_tema('1.5') === null, 'Um ID decimal deve ser rejeitado.');

$schema = file_get_contents(dirname(__DIR__) . '/sql/schema.sql');
$rotas = file_get_contents(dirname(__DIR__) . '/config/routes.php');
$seedTemas = file_get_contents(dirname(__DIR__) . '/sql/seed_temas.sql');
$verificar(is_string($schema), 'O schema.sql deve estar legível.');
$verificar(is_string($rotas), 'O arquivo de rotas deve estar legível.');
$verificar(is_string($seedTemas), 'O seed de temas deve estar legível.');

foreach ([
    'jogador',
    'administrador',
    'tema',
    'tema_categoria',
    'tema_valor',
    'desafio_diario',
    'desafio_atribuicao',
    'relacao_dica',
    'desafio_dica',
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
$verificar(is_string($schema) && str_contains($schema, 'prefixo VARCHAR(80)'), 'O schema deve suportar os prefixos temáticos.');
$verificar(is_string($schema) && str_contains($schema, 'CHECK (posicao BETWEEN 0 AND 4)'), 'As posições temáticas devem usar 0..4.');
$verificar(is_string($schema) && !str_contains($schema, 'IDENTIFIED BY'), 'O schema não deve conter credenciais.');
$verificar(is_string($rotas) && str_contains($rotas, "post('/logout'"), 'O logout deve aceitar POST.');
$verificar(is_string($rotas) && !str_contains($rotas, "get('/logout'"), 'O logout não deve aceitar GET.');
$verificar(is_string($rotas) && str_contains($rotas, 'csrf_exigir_valido()'), 'Formulários mutáveis devem exigir CSRF.');
$verificar(is_string($rotas) && str_contains($rotas, "post('/minha-conta/tema'"), 'A troca de tema deve aceitar POST.');
$verificar(is_string($rotas) && !str_contains($rotas, "get('/minha-conta/tema'"), 'A troca de tema não deve aceitar GET.');
$verificar(is_string($rotas) && str_contains($rotas, "get('/desafio'"), 'O desafio deve possuir rota GET.');
$verificar(is_string($rotas) && str_contains($rotas, "post('/desafio/finalizar'"), 'A finalização deve possuir rota POST.');
$verificar(
    is_string($seedTemas)
    && str_contains($seedTemas, "'Clássico'")
    && str_contains($seedTemas, "'Campus'")
    && str_contains($seedTemas, "'Exploração espacial'"),
    'O seed deve conter os três temas aprovados.'
);
$verificar(is_string($seedTemas) && str_contains($seedTemas, 'START TRANSACTION'), 'O seed deve usar transação.');
$verificar(is_string($seedTemas) && str_contains($seedTemas, 'SIGNAL SQLSTATE'), 'O seed deve rejeitar catálogo incompleto.');
$verificar(is_string($seedTemas) && !str_contains($seedTemas, 'IDENTIFIED BY'), 'O seed não deve conter credenciais.');
$verificar(is_string($seedTemas) && !str_contains($seedTemas, 'TRUNCATE'), 'O seed não deve truncar dados existentes.');

executar_testes_unitarios_fase6($verificar);
executar_testes_unitarios_fase7($verificar);
executar_testes_unitarios_fase8($verificar);

if ($falhas !== []) {
    session_destroy();
    fwrite(STDERR, "Falhas:\n- " . implode("\n- ", $falhas) . "\n");
    exit(1);
}

session_destroy();
fwrite(STDOUT, "Todas as verificações disponíveis passaram.\n");
