<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/src/cli_local.php';
$codigo = 0;
try {
    $opcoes = fase8_opcoes_cli($argv);
    fase8_preparar_cli($opcoes['banco'], $opcoes['aplicar']);
    require dirname(__DIR__) . '/src/bootstrap.php';
    $pdo = db();
    fase8_conferir_conexao($pdo);
    $resultado = reconciliar_ofensivas($pdo, $opcoes['aplicar']);
    fwrite(STDOUT, ($resultado['aplicado'] ? 'APLICADO' : 'SIMULAÇÃO — nenhum registro alterado') . "\n");
    fwrite(STDOUT, "Referência em Brasília: {$resultado['referencia']}\n");
    fwrite(STDOUT, "Jogadores: {$resultado['jogadores']}; ajustes " . ($resultado['aplicado'] ? 'aplicados' : 'previstos')
        . ": {$resultado['alterados']}; recordes maiores preservados: {$resultado['recordes_preservados']}.\n");
    if ($resultado['recordes_preservados'] > 0) {
        fwrite(STDOUT, "Há recordes superiores ao histórico disponível. Foram preservados, sem criar resoluções.\n");
    }
} catch (Throwable $erro) {
    fwrite(STDERR, 'Fase 8: ' . $erro->getMessage() . "\n");
    $codigo = 1;
} finally {
    putenv('DB_PASSWORD');
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
}
exit($codigo);
