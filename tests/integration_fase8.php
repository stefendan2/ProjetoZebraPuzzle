<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/src/cli_local.php';

function fase8_exigir(bool $ok, string $mensagem): void
{
    if (!$ok) { throw new RuntimeException($mensagem); }
}

function fase8_cpf_sintetico(): string
{
    do {
        $base = '';
        for ($i = 0; $i < 9; $i++) { $base .= (string) random_int(0, 9); }
    } while (preg_match('/^(\d)\1{8}$/', $base) === 1);
    for ($n = 9; $n < 11; $n++) {
        $soma = 0;
        for ($i = 0; $i < $n; $i++) { $soma += (int) $base[$i] * ($n + 1 - $i); }
        $digito = (10 * $soma) % 11;
        $base .= (string) ($digito === 10 ? 0 : $digito);
    }
    return $base;
}

/** Limpa somente os IDs criados por esta execução; qualquer dúvida aborta. */
function fase8_limpar(PDO $pdo, array $estado): void
{
    fase8_exigir(preg_match('/^[a-f0-9]{16}$/', $estado['tag']) === 1, 'Marcador inválido.');
    $pdo->beginTransaction();
    try {
        foreach ($estado['jogadores'] as $i => $id) {
            fase8_exigir(is_int($id) && $id > 0, 'ID de limpeza inválido.');
            $q = $pdo->prepare('SELECT email FROM jogador WHERE id = :id FOR UPDATE');
            $q->execute(['id' => $id]);
            $email = $q->fetchColumn();
            fase8_exigir($email === false || $email === "fase8-{$estado['tag']}-{$i}@example.test", 'Jogador fora do ensaio.');
            foreach (['leaderboard','resolucao','tentativa_desafio','acesso_diario','verificacao_email','jogador'] as $tabela) {
                $campo = $tabela === 'jogador' ? 'id' : 'jogador_id';
                $s = $pdo->prepare("DELETE FROM {$tabela} WHERE {$campo} = :id");
                $s->execute(['id' => $id]);
            }
        }
        foreach ($estado['desafios'] as $desafio) {
            // IDs capturados pelo callback de INSERT, nunca de um dia já existente.
            fase8_exigir(is_int($desafio['id']) && classificar_data_iso($desafio['dia']), 'Desafio inválido para limpeza.');
            $s = $pdo->prepare('DELETE FROM desafio_diario WHERE id = :id AND dia = :dia');
            $s->execute($desafio); // RESTRICT impede excluir desafio usado por outra conta.
        }
        $pdo->commit();
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $erro;
    }
}

$codigo = 0;
$estado = ['tag' => bin2hex(random_bytes(8)), 'jogadores' => [], 'desafios' => []];
try {
    $opcoes = fase8_opcoes_cli($argv, true);
    fase8_preparar_cli($opcoes['banco'], true);
    require dirname(__DIR__) . '/src/bootstrap.php';
    $pdo = db();
    fase8_conferir_conexao($pdo);
    $temas = listar_temas_elegiveis($pdo);
    fase8_exigir($temas !== [], 'É necessário um tema completo já instalado.');
    $temaId = (int) $temas[0]['id'];
    $fixture = require dirname(__DIR__) . '/resources/desafios/exemplo.php';
    $resposta = solucao_lista_para_matriz($fixture['solucao']);

    // Bloco histórico livre; não reutiliza nem modifica o desafio real de hoje.
    $base = null;
    $buscarDias = $pdo->prepare('SELECT COUNT(*) FROM desafio_diario WHERE dia BETWEEN :inicio AND :fim');
    for ($i = 0; $i < 40; $i++) {
        // Junho evita gerar por acaso um horário inexistente de início do horário de verão.
        $candidato = sprintf('%04d-06-%02d', random_int(2000, 2019), random_int(1, 20));
        $buscarDias->execute(['inicio' => $candidato, 'fim' => ofensiva_deslocar_dia($candidato, 6)]);
        if ((int) $buscarDias->fetchColumn() === 0) { $base = $candidato; break; }
    }
    fase8_exigir($base !== null, 'Não há intervalo livre para o ensaio.');
    $dias = [];
    $desafios = [];
    for ($i = 0; $i <= 4; $i++) {
        $dias[$i] = ofensiva_deslocar_dia($base, $i);
        $novoId = null;
        $desafios[$i] = persistir_desafio_gerado($pdo, $dias[$i], $fixture,
            static function (string $etapa, int $valor) use (&$novoId): void {
                if ($etapa === 'desafio') { $novoId = $valor; }
            });
        fase8_exigir($novoId !== null && $novoId === (int) $desafios[$i]['id'], 'Dia concorrente: não pertence à suíte.');
        $estado['desafios'][] = ['id' => $novoId, 'dia' => $dias[$i]];
    }
    for ($i = 0; $i < 2; $i++) {
        $email = "fase8-{$estado['tag']}-{$i}@example.test";
        $cadastro = criar_jogador_pendente($pdo, ['cpf' => fase8_cpf_sintetico(), 'email' => $email,
            'nome_usuario' => "__TESTE_FASE8_{$estado['tag']}_{$i}", 'senha' => 'T8!' . bin2hex(random_bytes(12)) . 'aA1']);
        fase8_exigir($cadastro['ok'], 'Cadastro sintético falhou.');
        $j = buscar_jogador_por_email($pdo, $email);
        fase8_exigir($j !== null, 'Jogador sintético não localizado.');
        $estado['jogadores'][] = (int) $j['id'];
        fase8_exigir(verificar_token_email($pdo, (string) $cadastro['token'])['status'] === 'ok', 'Verificação sintética falhou.');
        fase8_exigir(alterar_tema_jogador($pdo, (int) $j['id'], $temaId)['ok'], 'Tema sintético não aplicado.');
    }
    [$p1, $p2] = $estado['jogadores'];
    $abrir = static fn (int $p, int $d, string $hora = '12:00:00'): array => iniciar_ou_reutilizar_tentativa(
        $pdo, $p, (int) $desafios[$d]['id'], $temaId, static fn (): string => $dias[$d] . ' ' . $hora);
    $concluir = static fn (int $p, array $t, string $fim, ?callable $observador = null): array =>
        concluir_tentativa_e_registrar_resolucao($pdo, $p, $t['id'], $resposta, static fn (): string => $fim, $observador);
    $ler = static fn (int $p, string $quando): array => carregar_ofensiva_jogador($pdo, $p, $quando);
    $contagens = static function (int $p) use ($pdo): array {
        $s = $pdo->prepare('SELECT
            (SELECT COUNT(*) FROM resolucao WHERE jogador_id = :p1) AS r,
            (SELECT COUNT(*) FROM leaderboard WHERE jogador_id = :p2) AS l');
        $s->execute(['p1' => $p, 'p2' => $p]); return $s->fetch();
    };

    // O relógio simulado da tentativa não substitui a autorização de hoje.
    // Confere o bloqueio real antes de preparar o acesso das contas sintéticas.
    try {
        $abrir($p1, 0);
        throw new LogicException('Tentativa aberta sem a entrada diária atual.');
    } catch (RuntimeException $erro) {
        fase8_exigir($erro->getMessage() === 'A entrada diária atual não foi concluída.',
            'Erro inesperado ao verificar o bloqueio da entrada diária.');
    }
    fase8_exigir(!$pdo->inTransaction()
        && buscar_tentativa_aberta($pdo, $p1, (int) $desafios[0]['id']) === null,
        'A entrada recusada deixou uma transação ou tentativa aberta.');
    foreach ($estado['jogadores'] as $jogadorSintetico) {
        registrar_acesso_diario($pdo, $jogadorSintetico);
    }

    for ($i = 0; $i < 3; $i++) {
        $t = $abrir($p1, $i);
        $r = $concluir($p1, $t, $dias[$i] . ' 12:00:05');
    }
    $instante = $dias[2] . ' 15:00:00';
    $a = $ler($p1, $instante);
    fase8_exigir($a['atual'] === 3 && $a['maior_persistida'] === 3, 'Sequência de três dias não persistiu.');
    $rankingAntes = listar_leaderboard_por_dia($pdo, $dias[2]);
    $reenvio = $concluir($p1, $t, $dias[2] . ' 12:01:00');
    fase8_exigir($reenvio['id'] === $r['id'] && $reenvio['reenvio'], 'Reenvio não foi idempotente.');
    $replay = $concluir($p1, $abrir($p1, 2, '13:00:00'), $dias[2] . ' 13:00:01');
    fase8_exigir($replay['elegivel_leaderboard'] === 0 && $ler($p1, $instante)['atual'] === 3
        && listar_leaderboard_por_dia($pdo, $dias[2]) === $rankingAntes, 'Rejogo alterou sequência ou primeira marca.');
    fase8_exigir((int) $contagens($p1)['r'] === 4, 'Reenvio duplicou resolução.');
    fase8_exigir($ler($p2, $instante)['atual'] === 0, 'Indicadores vazaram entre jogadores.');
    // O acesso real de hoje não deve valer para a data histórica consultada.
    fase8_exigir(!$a['entrada_concluida'], 'Acesso de hoje foi aplicado à data histórica.');
    $s = $pdo->prepare('INSERT INTO acesso_diario (jogador_id,dia,captcha_validado_em) VALUES (:id,:dia,:fim)');
    $s->execute(['id' => $p1, 'dia' => $dias[2], 'fim' => $dias[2] . ' 11:00:00']);
    fase8_exigir($ler($p1, $instante)['entrada_concluida'] && !$ler($p2, $instante)['entrada_concluida'], 'Entrada diária não ficou isolada.');

    $falha = $abrir($p1, 3);
    $antes = $contagens($p1);
    try {
        $concluir($p2, $falha, $dias[3] . ' 12:00:05');
        throw new LogicException('Tentativa alheia aceita.');
    } catch (RuntimeException $erro) {
        fase8_exigir($erro->getMessage() === 'A tentativa não está disponível para este jogador.', 'Erro inesperado na propriedade.');
    }
    fase8_exigir($contagens($p1) === $antes && $ler($p2, $instante)['maior_persistida'] === 0,
        'Tentativa alheia alterou dados.');
    try {
        $concluir($p1, $falha, $dias[3] . ' 12:00:05', static function (string $etapa): void {
            if ($etapa === 'ofensiva') { throw new RuntimeException('FALHA_CONTROLADA_F8'); }
        });
        throw new LogicException('A falha controlada não foi disparada.');
    } catch (RuntimeException $erro) { fase8_exigir($erro->getMessage() === 'FALHA_CONTROLADA_F8', 'Falha inesperada.'); }
    $apos = $ler($p1, $dias[3] . ' 15:00:00');
    fase8_exigir($contagens($p1) === $antes && $apos['maior_persistida'] === 3 && $apos['atual_persistida'] === 3
        && buscar_tentativa_aberta($pdo, $p1, (int) $desafios[3]['id']) !== null, 'Rollback deixou gravação parcial.');
    fase8_exigir($ler($p1, $dias[4] . ' 11:00:00')['atual'] === 0, 'Dia perdido não zerou a exibição.');
    $concluir($p1, $abrir($p1, 4), $dias[4] . ' 12:00:05');
    $a = $ler($p1, $dias[4] . ' 15:00:00');
    fase8_exigir($a['atual'] === 1 && $a['maior_persistida'] === 3, 'Reinício reduziu recorde.');
    $atrasada = $abrir($p2, 3, '23:59:59');
    $historica = $concluir($p2, $atrasada, $dias[4] . ' 00:00:01');
    fase8_exigir($historica['elegivel_leaderboard'] === 0 && $ler($p2, $dias[4] . ' 15:00:00')['atual'] === 0,
        'Conclusão tardia preencheu a ofensiva.');

    $snapshot = $pdo->prepare('SELECT ofensiva_atual,maior_ofensiva,atualizado_em FROM jogador WHERE id = :id');
    $snapshot->execute(['id' => $p1]); $registroAntes = $snapshot->fetch();
    $contagemAntes = $contagens($p1);
    $ler($p1, $dias[4] . ' 15:00:00');
    $snapshot->execute(['id' => $p1]);
    fase8_exigir($snapshot->fetch() === $registroAntes && $contagens($p1) === $contagemAntes, 'Consulta alterou dados.');

    // Simula somente nos jogadores sintéticos uma base legada sem contadores.
    $pdo->prepare('UPDATE jogador SET ofensiva_atual = 0, maior_ofensiva = 0 WHERE id = :id')->execute(['id' => $p1]);
    $referencia = ofensiva_deslocar_dia($dias[4], 2) . ' 15:00:00';
    $simulacao = reconciliar_ofensivas($pdo, false, [$p1, $p2], $referencia);
    fase8_exigir($simulacao['alterados'] === 1 && $ler($p1, $referencia)['maior_persistida'] === 0, 'Simulação gravou dados.');
    $aplicacao = reconciliar_ofensivas($pdo, true, [$p1, $p2], $referencia);
    $repeticao = reconciliar_ofensivas($pdo, true, [$p1, $p2], $referencia);
    fase8_exigir($aplicacao['alterados'] === 1 && $repeticao['alterados'] === 0
        && $ler($p1, $referencia)['maior_persistida'] === 3, 'Reconciliação não recuperou o recorde ou não é idempotente.');
    $pdo->prepare('UPDATE jogador SET maior_ofensiva = 7 WHERE id = :id')->execute(['id' => $p1]);
    $preservar = reconciliar_ofensivas($pdo, true, [$p1, $p2], $referencia);
    fase8_exigir($preservar['recordes_preservados'] === 1 && $ler($p1, $referencia)['maior_persistida'] === 7, 'Recorde anterior reduzido.');
    fase8_exigir(reconciliar_ofensivas($pdo, true, [], $referencia)['jogadores'] === 0, 'Seleção vazia falhou.');
    fwrite(STDOUT, "Quatro cenários essenciais de integração da Fase 8 passaram.\n");
} catch (Throwable $erro) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    fwrite(STDERR, 'Fase 8: ' . $erro->getMessage() . "\n");
    $codigo = 1;
} finally {
    if (isset($pdo) && ($estado['jogadores'] !== [] || $estado['desafios'] !== [])) {
        try {
            fase8_limpar($pdo, $estado);
            fwrite(STDOUT, "Dados exclusivos da suíte removidos.\n");
        } catch (Throwable $erro) {
            $arquivo = sys_get_temp_dir() . '/zebrapuzzle-fase8-' . $estado['tag'] . '.json';
            $gravado = file_put_contents($arquivo, json_encode($estado, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX);
            fwrite(STDERR, "Limpeza pendente. Marcador: {$estado['tag']}. Não repita os testes antes de conferir os resíduos.\n");
            fwrite(STDERR, $gravado === false ? "Não foi possível guardar os IDs de limpeza.\n" : "IDs sintéticos para diagnóstico: {$arquivo}\n");
            $codigo = 1;
        }
    }
    putenv('DB_PASSWORD');
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
}
exit($codigo);
