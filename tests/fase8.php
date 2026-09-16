<?php

declare(strict_types=1);

function executar_testes_unitarios_fase8(callable $verificar): void
{
    // Casos de negócio agrupados: não é necessário executar um comando por caso.
    foreach ([
        [[], '2026-09-16', 0, 0, 0],
        [['2026-09-16'], '2026-09-16', 0, 1, 1],
        [['2026-09-14','2026-09-15','2026-09-16'], '2026-09-16', 0, 3, 3],
        [['2026-09-14','2026-09-15'], '2026-09-16', 2, 2, 2],
        [['2026-09-14','2026-09-15'], '2026-09-17', 2, 0, 2],
        [['2026-09-13','2026-09-14','2026-09-16'], '2026-09-16', 2, 1, 2],
        [['2026-09-16','2026-09-16','2026-09-16'], '2026-09-16', 0, 1, 1],
        [['2026-09-15','2026-09-16'], '2026-09-16', 7, 2, 7],
        [['2026-09-16','2026-09-14','2026-09-15'], '2026-09-16', 2, 3, 3],
        [['2024-02-28','2024-02-29','2024-03-01'], '2024-03-01', 0, 3, 3],
        [['2025-12-31','2026-01-01'], '2026-01-01', 0, 2, 2],
        [['2026-09-17'], '2026-09-16', 0, 0, 0],
    ] as $i => [$dias, $hoje, $salvo, $atual, $maior]) {
        $r = calcular_ofensiva($dias, $hoje, $salvo);
        $verificar($r['atual'] === $atual && $r['maior'] === $maior, 'Fase 8: calendário/recorde, cenário ' . ($i + 1));
    }
    foreach ([
        ['2026-09-16','2026-09-16 10:00:00','2026-09-16 11:00:00',true],
        ['2026-09-14','2026-09-16 10:00:00','2026-09-16 11:00:00',false],
        ['2026-09-15','2026-09-16 00:00:00','2026-09-16 11:00:00',false],
        ['2026-09-16','2026-09-16 12:00:00','2026-09-16 11:00:00',false],
    ] as [$dia, $fim, $referencia, $conta]) {
        $verificar(ofensiva_resolucao_conta($dia, $fim, $referencia) === $conta, 'Fase 8: qualificação do dia/instante.');
    }
    try {
        calcular_ofensiva(['2026-02-30'], '2026-09-16');
        $verificar(false, 'Fase 8: dia impossível aceito.');
    } catch (InvalidArgumentException $erro) { /* esperado */ }

    $sessaoAnterior = $_SESSION;
    $titulo = 'Teste';
    $mensagensFlash = [];
    foreach ([0, 1] as $quantidade) {
        $_SESSION['autenticacao'] = ['id' => 1, 'tipo' => 'jogador'];
        $indicadoresOfensiva = ['status' => 'ok', 'atual' => $quantidade, 'maior' => $quantidade];
        ob_start(); require dirname(__DIR__) . '/src/views/layout-header.php'; $html = (string) ob_get_clean();
        $verificar(str_contains($html, 'Ofensiva atual:') && str_contains($html, 'Maior ofensiva:'), 'Fase 8: ambos os indicadores, inclusive zero.');
        $verificar(substr_count($html, '<strong>' . $quantidade . '</strong>') === 2, 'Fase 8: valores renderizados.');
    }
    foreach ([null, 'administrador'] as $perfil) {
        $_SESSION = $perfil === null ? [] : ['autenticacao' => ['id' => 1, 'tipo' => $perfil]];
        $verificar(ofensiva_contexto_cabecalho() === null, 'Fase 8: outro perfil não consulta indicadores de jogador.');
        ob_start(); require dirname(__DIR__) . '/src/views/layout-header.php'; $html = (string) ob_get_clean();
        $verificar(!str_contains($html, 'Ofensiva atual:'), 'Fase 8: cabeçalho sem dados do jogador em outro perfil.');
    }
    $_SESSION = $sessaoAnterior;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    require dirname(__DIR__) . '/src/bootstrap.php';
    $falhas = [];
    executar_testes_unitarios_fase8(static function (bool $ok, string $mensagem) use (&$falhas): void {
        if (!$ok) { $falhas[] = $mensagem; }
    });
    session_destroy();
    if ($falhas !== []) { fwrite(STDERR, implode("\n", $falhas) . "\n"); exit(1); }
    fwrite(STDOUT, "Regras essenciais da Fase 8 passaram.\n");
}
