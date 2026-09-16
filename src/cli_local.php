<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

/** Entrada protegida, compartilhada pelos dois comandos novos. */
function fase8_solicitar_senha(string $usuario): void
{
    if ((string) getenv('DB_PASSWORD') !== '') { return; }
    fwrite(STDOUT, "Senha MariaDB de {$usuario} (digitação oculta): ");
    if (PHP_OS_FAMILY === 'Windows') {
        $comando = '$s = Read-Host "Senha MariaDB" -AsSecureString; '
            . '$p = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($s); '
            . 'try { $v = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($p); '
            . '[Console]::Out.Write("__F8_PASSWORD__" + [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($v))) } '
            . 'finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($p) }';
        $processo = proc_open(['powershell.exe', '-NoProfile', '-Command', $comando],
            [0 => STDIN, 1 => ['pipe', 'w'], 2 => STDERR], $pipes);
        if (!is_resource($processo)) { throw new RuntimeException('Leitura protegida indisponível.'); }
        $saida = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $codigo = proc_close($processo);
        $partes = explode('__F8_PASSWORD__', (string) $saida);
        $senha = $codigo === 0 && count($partes) === 2 ? base64_decode(trim($partes[1]), true) : false;
        unset($saida, $partes);
    } else {
        if (!function_exists('stream_isatty') || !stream_isatty(STDIN)) {
            throw new RuntimeException('Execute diretamente com PHP em um terminal interativo.');
        }
        $estadoTerminal = shell_exec('stty -g');
        if (!is_string($estadoTerminal) || trim($estadoTerminal) === '') { throw new RuntimeException('Terminal indisponível.'); }
        system('stty -echo', $codigo);
        if ($codigo !== 0) { throw new RuntimeException('Não foi possível proteger a digitação.'); }
        try { $entrada = fgets(STDIN); }
        finally { system('stty ' . escapeshellarg(trim($estadoTerminal))); }
        $senha = $entrada === false ? '' : rtrim($entrada, "\r\n");
    }
    fwrite(STDOUT, "\n");
    if (!is_string($senha) || $senha === '') {
        throw new RuntimeException('Nenhuma senha recebida. Execute diretamente com PHP e digite a senha da conta MariaDB indicada.');
    }
    putenv('DB_PASSWORD=' . $senha);
    unset($senha);
}

/** @return array{aplicar:bool,banco:?string} */
function fase8_opcoes_cli(array $argumentos, bool $integracao = false): array
{
    $opcoes = ['aplicar' => false, 'banco' => null];
    foreach (array_slice($argumentos, 1) as $arg) {
        if ($arg === '--aplicar' && !$integracao && !$opcoes['aplicar']) { $opcoes['aplicar'] = true; }
        elseif (str_starts_with($arg, '--confirmar-banco=') && $opcoes['banco'] === null) {
            $opcoes['banco'] = substr($arg, strlen('--confirmar-banco='));
        } else { throw new InvalidArgumentException('Opção inválida: use somente --aplicar (reconciliação) e --confirmar-banco=NOME.'); }
    }
    return $opcoes;
}

/** Valida o destino antes de solicitar senha ou iniciar a aplicação. */
function fase8_preparar_cli(?string $bancoConfirmado, bool $escreve): void
{
    $app = require dirname(__DIR__) . '/config/app.php';
    $config = require dirname(__DIR__) . '/config/database.php';
    if ($app['environment'] !== 'development'
        || !in_array($config['host'], ['127.0.0.1', 'localhost', '::1'], true)
        || $config['timezone'] !== 'America/Sao_Paulo'
        || $config['username'] === 'root'
    ) {
        throw new RuntimeException('Comando exclusivo da base local de desenvolvimento, com usuário dedicado e fuso America/Sao_Paulo.');
    }
    if ($escreve && ($bancoConfirmado === null || strcasecmp($bancoConfirmado, $config['database']) !== 0)) {
        throw new RuntimeException('Informe --confirmar-banco com o nome do banco local configurado.');
    }
    fwrite(STDOUT, sprintf("Destino: %s:%d / %s / usuário %s\n", $config['host'], $config['port'], $config['database'], $config['username']));
    fase8_solicitar_senha((string) $config['username']);
}

function fase8_conferir_conexao(PDO $pdo): void
{
    $config = require dirname(__DIR__) . '/config/database.php';
    $bancoReal = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    if (strcasecmp($bancoReal, $config['database']) !== 0) {
        throw new RuntimeException('A conexão não corresponde ao banco configurado.');
    }
    validar_estrutura_ofensiva($pdo);
}
