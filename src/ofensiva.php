<?php

declare(strict_types=1);

/** Aritmética de calendário, sem assumir dias de 86.400 segundos. */
function ofensiva_deslocar_dia(string $dia, int $deslocamento): string
{
    if (!classificar_data_iso($dia)) {
        throw new InvalidArgumentException('Dia de ofensiva inválido.');
    }
    return (new DateTimeImmutable($dia . ' 12:00:00', new DateTimeZone('America/Sao_Paulo')))
        ->modify(sprintf('%+d days', $deslocamento))->format('Y-m-d');
}

/** Regra única de qualificação; elegibilidade de ranking não participa. */
function ofensiva_resolucao_conta(string $dia, string $conclusao, string $referencia): bool
{
    if (!classificar_data_iso($dia)) {
        throw new InvalidArgumentException('Dia de resolução inválido.');
    }
    $conclusao = normalizar_instante_mariadb($conclusao);
    $referencia = normalizar_instante_mariadb($referencia);
    if ($dia > substr($conclusao, 0, 10)) {
        throw new RuntimeException('Resolução concluída antes do dia do desafio.');
    }
    return $conclusao <= $referencia && $dia === substr($conclusao, 0, 10);
}

/**
 * @param list<string> $dias Dias já qualificados. Aceita repetição e qualquer ordem.
 * @return array{atual:int,recorde_historico:int,maior:int,ultimo_dia:?string}
 */
function calcular_ofensiva(array $dias, string $hoje, int $recordeSalvo = 0): array
{
    if (!classificar_data_iso($hoje) || $recordeSalvo < 0) {
        throw new InvalidArgumentException('Contexto de ofensiva inválido.');
    }
    $unicos = [];
    foreach ($dias as $dia) {
        if (!is_string($dia) || !classificar_data_iso($dia)) {
            throw new InvalidArgumentException('Histórico de dias inválido.');
        }
        if ($dia <= $hoje) { $unicos[$dia] = true; }
    }
    $ordenados = array_keys($unicos);
    sort($ordenados, SORT_STRING);
    $anterior = null;
    $sequencia = 0;
    $recordeHistorico = 0;
    foreach ($ordenados as $dia) {
        $sequencia = $anterior !== null && ofensiva_deslocar_dia($anterior, 1) === $dia
            ? $sequencia + 1 : 1;
        $recordeHistorico = max($recordeHistorico, $sequencia);
        $anterior = $dia;
    }
    $atual = $anterior === $hoje || $anterior === ofensiva_deslocar_dia($hoje, -1)
        ? $sequencia : 0;
    return ['atual' => $atual, 'recorde_historico' => $recordeHistorico,
        'maior' => max($recordeSalvo, $recordeHistorico), 'ultimo_dia' => $anterior];
}

/**
 * Uma única leitura consistente: conta, acesso, recorde, relógio e histórico.
 * $referencia é exclusivo de chamadores CLI/testes; nenhuma rota o recebe.
 * Não modifica registros e não consulta soluções/grades.
 * @return array<string,mixed>|null
 */
function carregar_ofensiva_jogador(PDO $pdo, int $jogadorId, ?string $referencia = null): ?array
{
    if ($jogadorId < 1) { return null; }
    $parametros = ['jogador' => $jogadorId];
    $relogio = 'CURRENT_TIMESTAMP(6)';
    if ($referencia !== null) {
        $parametros['instante'] = normalizar_instante_mariadb($referencia);
        $relogio = 'CAST(:instante AS DATETIME(6))';
    }
    $consulta = $pdo->prepare(
        'SELECT j.id, j.ativo, j.email_verificado, j.ofensiva_atual, j.maior_ofensiva,
                c.instante AS referencia, @@session.time_zone AS fuso,
                EXISTS(SELECT 1 FROM acesso_diario a
                    WHERE a.jogador_id = j.id AND a.dia = DATE(c.instante)) AS acesso_diario,
                r.id AS resolucao_id, r.desafio_dia, r.concluida_em, r.tema_id,
                r.tempo_milisegundos, td.id AS tentativa_id, td.jogador_id AS dono_tentativa,
                td.tema_id AS tema_tentativa, td.iniciada_em, td.finalizada_em,
                dd.dia AS dia_tentativa
         FROM jogador j
         CROSS JOIN (SELECT ' . $relogio . ' AS instante) c
         LEFT JOIN resolucao r ON r.jogador_id = j.id
         LEFT JOIN tentativa_desafio td ON td.id = r.tentativa_id
         LEFT JOIN desafio_diario dd ON dd.id = td.desafio_id
         WHERE j.id = :jogador
         ORDER BY r.desafio_dia, r.id'
    );
    $consulta->execute($parametros);
    $linhas = $consulta->fetchAll(PDO::FETCH_ASSOC);
    if ($linhas === []) { return null; }
    $conta = $linhas[0];
    if ($conta['fuso'] !== 'America/Sao_Paulo') {
        throw new RuntimeException('A sessão MariaDB deve usar America/Sao_Paulo.');
    }
    $instante = normalizar_instante_mariadb((string) $conta['referencia']);
    $dias = [];
    foreach ($linhas as $linha) {
        if ($linha['resolucao_id'] === null) { continue; }
        if ($linha['tentativa_id'] === null
            || (int) $linha['dono_tentativa'] !== $jogadorId
            || $linha['dia_tentativa'] !== $linha['desafio_dia']
            || (int) $linha['tema_tentativa'] !== (int) $linha['tema_id']
            || $linha['finalizada_em'] === null
            || $linha['finalizada_em'] !== $linha['concluida_em']
            || tempo_milisegundos_entre_instantes((string) $linha['iniciada_em'], (string) $linha['finalizada_em'])
                !== (int) $linha['tempo_milisegundos']
        ) {
            throw new RuntimeException('Histórico inconsistente; a ofensiva não foi calculada.');
        }
        if (ofensiva_resolucao_conta((string) $linha['desafio_dia'], (string) $linha['concluida_em'], $instante)) {
            $dias[] = (string) $linha['desafio_dia'];
        }
    }
    $salvo = (int) $conta['maior_ofensiva'];
    $atualSalva = (int) $conta['ofensiva_atual'];
    if ($salvo < 0 || $atualSalva < 0 || $atualSalva > $salvo) {
        throw new RuntimeException('Indicadores persistidos inconsistentes.');
    }
    return calcular_ofensiva($dias, substr($instante, 0, 10), $salvo) + [
        'referencia' => $instante, 'maior_persistida' => $salvo, 'atual_persistida' => $atualSalva,
        'conta_disponivel' => (int) $conta['ativo'] === 1 && (int) $conta['email_verificado'] === 1,
        'entrada_concluida' => (int) $conta['acesso_diario'] === 1,
    ];
}

/** O chamador já deve ter bloqueado o jogador antes de ler o histórico. */
function persistir_ofensiva_na_transacao(PDO $pdo, int $jogadorId, array $estado): void
{
    if (!$pdo->inTransaction() || $jogadorId < 1
        || !is_int($estado['atual'] ?? null) || !is_int($estado['maior'] ?? null)
        || $estado['atual'] < 0 || $estado['maior'] < $estado['atual']
    ) {
        throw new LogicException('A ofensiva exige contexto transacional válido.');
    }
    $atualizar = $pdo->prepare(
        'UPDATE jogador SET maior_ofensiva = GREATEST(maior_ofensiva, :maior),
                            ofensiva_atual = :atual WHERE id = :id'
    );
    $atualizar->execute(['maior' => $estado['maior'], 'atual' => $estado['atual'], 'id' => $jogadorId]);
}

/** Sem escrita: contexto privado do cabeçalho, uma obtenção por sessão na requisição. */
function ofensiva_contexto_cabecalho(): ?array
{
    static $cache = [];
    if (!usuario_logado('jogador')) { return null; }
    $id = usuario_id();
    if ($id === null || $id < 1) { return null; }
    if (array_key_exists($id, $cache)) { return $cache[$id]; }
    // Definido antes da consulta para que a página de erro não repita a falha.
    $cache[$id] = ['status' => 'indisponivel'];
    try {
        $estado = carregar_ofensiva_jogador(db(), $id);
        if ($estado === null || !$estado['conta_disponivel']) {
            encerrar_sessao_usuario();
            flash_adicionar('erro', 'A conta do jogador não está disponível.');
            return $cache[$id] = null;
        }
        if (!$estado['entrada_concluida']) { return $cache[$id] = null; }
        if ($estado['recorde_historico'] > $estado['maior_persistida']) {
            throw new RuntimeException('Reconciliação inicial de ofensivas pendente.');
        }
        return $cache[$id] = ['status' => 'ok', 'atual' => $estado['atual'], 'maior' => $estado['maior_persistida']];
    } catch (Throwable $erro) {
        error_log('Fase 8: indicadores indisponíveis; confira estrutura, fuso e reconciliação. Tipo: ' . get_class($erro));
        return $cache[$id];
    }
}

/** Verificação somente leitura; nenhuma migração/seed é executada. */
function validar_estrutura_ofensiva(PDO $pdo): void
{
    $tabelas = $pdo->query("SELECT table_name, engine FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name IN
        ('jogador','resolucao','tentativa_desafio','desafio_diario','leaderboard','acesso_diario')")->fetchAll(PDO::FETCH_KEY_PAIR);
    if (count($tabelas) !== 6 || count(array_filter($tabelas, static fn ($engine): bool => $engine === 'InnoDB')) !== 6) {
        throw new RuntimeException('A base deve conter as seis tabelas InnoDB da Fase 7.');
    }
    $colunas = $pdo->query("SELECT column_name, column_type FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'jogador'
          AND column_name IN ('ofensiva_atual','maior_ofensiva')")->fetchAll(PDO::FETCH_KEY_PAIR);
    if (count($colunas) !== 2) { throw new RuntimeException('Colunas de ofensiva ausentes.'); }
    foreach ($colunas as $tipo) {
        if (preg_match('/^int(?:\(\d+\))? unsigned$/i', $tipo) !== 1) {
            throw new RuntimeException('Tipo das colunas de ofensiva divergente.');
        }
    }
    $check = $pdo->query("SELECT check_clause FROM information_schema.check_constraints
        WHERE constraint_schema = DATABASE() AND table_name = 'jogador'
          AND constraint_name = 'ck_jogador_ofensiva'")->fetchColumn();
    $normalizado = preg_replace('/[\s`()]+/', '', strtolower((string) $check));
    if ($normalizado !== 'maior_ofensiva>=ofensiva_atual') {
        throw new RuntimeException('CHECK de ofensiva ausente ou divergente.');
    }
    $indice = $pdo->query("SELECT GROUP_CONCAT(column_name ORDER BY seq_in_index)
        FROM information_schema.statistics WHERE table_schema = DATABASE()
        AND table_name = 'resolucao' AND index_name = 'idx_resolucao_jogador_dia'")->fetchColumn();
    if ($indice !== 'jogador_id,desafio_dia') { throw new RuntimeException('Índice de histórico divergente.'); }
    // Preparação e execução detectam colunas-base ausentes mesmo sem jogadores.
    carregar_ofensiva_jogador($pdo, 1);
}

/**
 * Reconciliação explícita. Sem DDL e sem escrita no histórico/ranking.
 * Lista de IDs/referência opcionais são usadas apenas pela integração CLI.
 * @param null|list<int> $jogadores
 * @return array{jogadores:int,alterados:int,recordes_preservados:int,referencia:string,aplicado:bool}
 */
function reconciliar_ofensivas(PDO $pdo, bool $aplicar = false, ?array $jogadores = null, ?string $referencia = null): array
{
    if ($pdo->inTransaction()) { throw new LogicException('Reconciliação exige transação própria.'); }
    $ids = $jogadores;
    if ($ids !== null) {
        foreach ($ids as $id) { if (!is_int($id) || $id < 1) { throw new InvalidArgumentException('Jogador inválido.'); } }
        $ids = array_values(array_unique($ids));
        sort($ids, SORT_NUMERIC);
    }
    $pdo->beginTransaction();
    try {
        $sql = 'SELECT id FROM jogador';
        if ($ids !== null) { $sql .= $ids === [] ? ' WHERE 1 = 0' : ' WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')'; }
        $sql .= ' ORDER BY id' . ($aplicar ? ' FOR UPDATE' : '');
        $consulta = $pdo->prepare($sql);
        $consulta->execute($ids ?? []);
        $existentes = array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));
        if ($ids !== null && $existentes !== $ids) { throw new RuntimeException('Jogador da reconciliação ausente.'); }
        $instante = normalizar_instante_mariadb($referencia ?? (string) $pdo->query('SELECT CURRENT_TIMESTAMP(6)')->fetchColumn());
        $resumo = ['jogadores' => count($existentes), 'alterados' => 0, 'recordes_preservados' => 0,
            'referencia' => $instante, 'aplicado' => $aplicar];
        foreach ($existentes as $id) {
            $estado = carregar_ofensiva_jogador($pdo, $id, $instante);
            if ($estado === null) { throw new RuntimeException('Conta não localizada na reconciliação.'); }
            if ($estado['maior_persistida'] > $estado['recorde_historico']) { $resumo['recordes_preservados']++; }
            if ($estado['atual'] !== $estado['atual_persistida'] || $estado['maior'] !== $estado['maior_persistida']) {
                $resumo['alterados']++;
                if ($aplicar) { persistir_ofensiva_na_transacao($pdo, $id, $estado); }
            }
        }
        $pdo->commit();
        return $resumo;
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $erro;
    }
}
