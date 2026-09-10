<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if ((string) getenv('DB_PASSWORD') === '') {
    fwrite(STDOUT, 'Senha do usuário MariaDB zebrapuzzle_app: ');
    $senhaBanco = fgets(STDIN);
    putenv('DB_PASSWORD=' . ($senhaBanco === false ? '' : rtrim($senhaBanco, "\r\n")));
    unset($senhaBanco);
}
if ((string) getenv('DB_PASSWORD') === '') {
    fwrite(STDERR, "Nenhuma senha foi recebida. Execute novamente e digite a senha local quando solicitada.\n");
    exit(2);
}

require dirname(__DIR__) . '/src/bootstrap.php';

try {
    $dia = isset($argv[1]) ? (string) $argv[1] : dia_de_referencia();
    if (!classificar_data_iso($dia)) {
        throw new InvalidArgumentException('Informe a data no formato AAAA-MM-DD.');
    }
    $desafio = provisionar_desafio_do_dia(db(), $dia);
    fwrite(
        STDOUT,
        'Desafio provisionado: id=' . (int) $desafio['id']
        . '; dia=' . $desafio['dia']
        . '; atribuições=' . count($desafio['solucao'])
        . '; dicas=' . count($desafio['dicas']) . ".\n"
    );
} catch (Throwable $erro) {
    fwrite(STDERR, 'Falha no provisionamento: ' . $erro->getMessage() . "\n");
    exit(1);
} finally {
    putenv('DB_PASSWORD');
}
