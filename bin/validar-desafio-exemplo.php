<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/desafio_formato.php';
require dirname(__DIR__) . '/src/restricoes_desafio.php';
require dirname(__DIR__) . '/src/verificador_csp.php';

$fixture = require dirname(__DIR__) . '/resources/desafios/exemplo.php';
$resultado = validar_desafio_gerado($fixture, true);
if (!$resultado['ok']) {
    fwrite(STDERR, "Fixture inválida:\n- " . implode("\n- ", $resultado['erros']) . "\n");
    exit(1);
}

$verificacao = $resultado['verificacao'];
fwrite(
    STDOUT,
    "Fixture válida: solução única; 25 atribuições; 15 dicas; índices 0..4; "
    . (int) $verificacao['nos'] . " nós verificados.\n"
);
