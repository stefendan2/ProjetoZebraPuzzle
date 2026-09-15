<?php

declare(strict_types=1);

/** Casos essenciais agrupados, sem banco. */
function executar_testes_unitarios_fase7(callable $verificar): void
{
    foreach ([
        [true, '2026-09-12', '2026-09-12', false, true, ELEGIBILIDADE_PRIMEIRA_VALIDA],
        [true, '2026-09-12', '2026-09-12', true, false, ELEGIBILIDADE_REJOGADA],
        [true, '2026-09-12', '2026-09-13', false, false, ELEGIBILIDADE_CONCLUSAO_TARDIA],
        [true, '2026-09-12', '2026-09-13', true, false, ELEGIBILIDADE_CONCLUSAO_TARDIA],
        [false, '2026-09-12', '2026-09-12', false, false, ELEGIBILIDADE_RESPOSTA_INVALIDA],
    ] as [$correta, $dia, $fim, $jaExiste, $elegivel, $motivo]) {
        $verificar(decidir_elegibilidade_resolucao($correta, $dia, $fim, $jaExiste)
            === ['elegivel' => $elegivel, 'motivo' => $motivo], 'Fase 7: elegibilidade — ' . $motivo);
        $verificar(mensagem_elegibilidade($motivo) !== '', 'Fase 7: motivo deve ter mensagem.');
    }
    $verificar(dia_de_referencia(new DateTimeImmutable('2026-09-13T01:00:00Z')) === '2026-09-12', 'Fase 7: dia de São Paulo, não UTC.');
    $verificar(tempo_milisegundos_entre_instantes('2026-09-12 23:59:59.750000', '2026-09-13 00:00:00.250000') === 500, 'Fase 7: virada de dia.');
    $verificar(tempo_milisegundos_entre_instantes('2026-09-12 10:00:00.000000', '2026-09-12 10:00:01.234500') === 1235, 'Fase 7: arredondamento.');
    foreach (['2026-02-31','12/09/2026','2026-09-12 OR 1=1'] as $data) {
        $verificar(!classificar_data_iso($data), 'Fase 7: data inválida rejeitada.');
    }
    try {
        tempo_milisegundos_entre_instantes('2026-09-12 10:00:01', '2026-09-12 10:00:00');
        $verificar(false, 'Fase 7: relógio regressivo deveria falhar.');
    } catch (RuntimeException $erro) { /* esperado */ }
    foreach ([null, [], true, '0', '-1', '1 OR 1=1', '1.2', '9999999999999999999999999'] as $id) {
        $verificar(normalizar_id_positivo($id) === null, 'Fase 7: ID manipulado rejeitado.');
    }
    $verificar(normalizar_id_positivo('7') === 7, 'Fase 7: ID positivo.');
    $linhas = [
        ['id' => 5, 'tempo_milisegundos' => 2000, 'momento_conclusao' => '2026-09-12 10:00:02.000000'],
        ['id' => 4, 'tempo_milisegundos' => 1000, 'momento_conclusao' => '2026-09-12 10:00:03.000000'],
        ['id' => 3, 'tempo_milisegundos' => 2000, 'momento_conclusao' => '2026-09-12 10:00:01.000000'],
        ['id' => 2, 'tempo_milisegundos' => 2000, 'momento_conclusao' => '2026-09-12 10:00:02.000000'],
    ];
    $ordenadas = ordenar_e_numerar_leaderboard($linhas);
    $verificar(array_column($ordenadas, 'id') === [4,3,2,5], 'Fase 7: ordem tempo, conclusão, ID.');
    $verificar(array_column($ordenadas, 'posicao') === [1,2,3,4], 'Fase 7: posições sem compartilhamento.');
    $verificar(ordenar_e_numerar_leaderboard([]) === [], 'Fase 7: ranking vazio.');
    $verificar(formatar_tempo_resolucao(61234) === '01:01.234' && formatar_tempo_resolucao(3600001) === '01:00:00.001', 'Fase 7: duração formatada.');
    registrar_token_reenvio_conclusao(77, str_repeat('a', 64));
    $verificar(reenvio_conclusao_valido(77, str_repeat('a', 64)), 'Fase 7: reenvio autorizado reconhecido.');
    $verificar(!reenvio_conclusao_valido(77, 'invalido') && !reenvio_conclusao_valido(78, str_repeat('a', 64)), 'Fase 7: reenvio limitado ao token e à tentativa.');
    unset($_SESSION['_fase7_reenvios']);
    $diaSelecionado = null;
    $linhas = null;
    ob_start(); require dirname(__DIR__) . '/src/views/leaderboard.php'; $html = (string) ob_get_clean();
    $verificar(!str_contains($html, '<table') && str_contains($html, 'Selecione um dia'), 'Fase 7: filtro sem tabela inicial.');
    $diaSelecionado = '2026-09-12';
    $linhas = [];
    ob_start(); require dirname(__DIR__) . '/src/views/leaderboard.php'; $html = (string) ob_get_clean();
    $verificar(str_contains($html, 'Não há classificação'), 'Fase 7: estado vazio.');
    $linhas = [['posicao' => 1, 'nome_usuario' => '<script>alert(1)</script>', 'tempo_milisegundos' => 1000,
        'momento_conclusao' => '2026-09-12 10:00:00.000000', 'id' => 987654321]];
    ob_start(); require dirname(__DIR__) . '/src/views/leaderboard.php'; $html = (string) ob_get_clean();
    $verificar(!str_contains($html, '<script>') && str_contains($html, '&lt;script&gt;') && !str_contains($html, '987654321'), 'Fase 7: XSS escapado e ID omitido.');
    $dias = [];
    ob_start(); require dirname(__DIR__) . '/src/views/historico.php'; $html = (string) ob_get_clean();
    $verificar(str_contains($html, 'Ainda não há desafios'), 'Fase 7: histórico vazio.');
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    require dirname(__DIR__) . '/src/bootstrap.php';
    $falhas = [];
    executar_testes_unitarios_fase7(static function (bool $ok, string $mensagem) use (&$falhas): void {
        if (!$ok) { $falhas[] = $mensagem; }
    });
    session_destroy();
    if ($falhas !== []) { fwrite(STDERR, implode("\n", $falhas) . "\n"); exit(1); }
    fwrite(STDOUT, "Testes unitários essenciais da Fase 7 passaram.\n");
}
