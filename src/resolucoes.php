<?php

declare(strict_types=1);

function pdo_erro_duplicidade(PDOException $erro): bool
{
    return $erro->getCode() === '23000' && (int) ($erro->errorInfo[1] ?? 0) === 1062;
}

/** @return array<string, mixed>|null */
function buscar_resolucao_jogador_por_id(PDO $pdo, int $resolucaoId, int $jogadorId): ?array
{
    if ($resolucaoId < 1 || $jogadorId < 1) {
        return null;
    }
    $statement = $pdo->prepare(
        'SELECT r.id, r.tentativa_id, r.jogador_id, td.desafio_id,
                r.desafio_dia, r.concluida_em, r.tempo_milisegundos,
                r.tema_id, r.elegivel_leaderboard, t.nome AS tema_nome,
                td.jogador_id AS tentativa_jogador, td.tema_id AS tentativa_tema,
                td.finalizada_em, dd.dia AS tentativa_dia
         FROM resolucao r
         INNER JOIN tentativa_desafio td ON td.id = r.tentativa_id
         INNER JOIN desafio_diario dd ON dd.id = td.desafio_id
         INNER JOIN tema t ON t.id = r.tema_id
         WHERE r.id = :id AND r.jogador_id = :jogador_id
         LIMIT 1'
    );
    $statement->execute(['id' => $resolucaoId, 'jogador_id' => $jogadorId]);
    $linha = $statement->fetch();
    if (!is_array($linha)) {
        return null;
    }
    foreach (['id', 'tentativa_id', 'jogador_id', 'desafio_id', 'tempo_milisegundos', 'tema_id', 'elegivel_leaderboard'] as $campo) {
        $linha[$campo] = (int) $linha[$campo];
    }
    if ((int) $linha['tentativa_jogador'] !== $linha['jogador_id']
        || (int) $linha['tentativa_tema'] !== $linha['tema_id']
        || $linha['tentativa_dia'] !== $linha['desafio_dia']
        || $linha['finalizada_em'] !== $linha['concluida_em']
    ) {
        throw new RuntimeException('A resolução diverge da tentativa registrada.');
    }
    unset($linha['tentativa_jogador'], $linha['tentativa_tema'], $linha['tentativa_dia'], $linha['finalizada_em']);
    $decisao = decidir_elegibilidade_resolucao(true, (string) $linha['desafio_dia'],
        substr((string) $linha['concluida_em'], 0, 10), $linha['elegivel_leaderboard'] !== 1);
    if ($decisao['elegivel'] !== ($linha['elegivel_leaderboard'] === 1)) {
        throw new RuntimeException('A elegibilidade registrada contradiz o dia da conclusão.');
    }
    $linha['motivo_elegibilidade'] = $decisao['motivo'];
    $linha['leaderboard_id'] = null;

    if ($linha['elegivel_leaderboard'] === 1) {
        $consistencia = $pdo->prepare(
            'SELECT id FROM leaderboard
             WHERE resolucao_id = :resolucao_id
               AND jogador_id = :jogador_id
               AND dia = :dia
               AND tempo_milisegundos = :tempo
               AND momento_conclusao = :conclusao
             LIMIT 1'
        );
        $consistencia->execute([
            'resolucao_id' => $linha['id'],
            'jogador_id' => $linha['jogador_id'],
            'dia' => $linha['desafio_dia'],
            'tempo' => $linha['tempo_milisegundos'],
            'conclusao' => $linha['concluida_em'],
        ]);
        $leaderboardId = $consistencia->fetchColumn();
        if ($leaderboardId === false) {
            throw new RuntimeException('A resolução elegível diverge do leaderboard.');
        }
        $linha['leaderboard_id'] = (int) $leaderboardId;
    } else {
        $consistencia = $pdo->prepare('SELECT id FROM leaderboard WHERE resolucao_id = :id LIMIT 1');
        $consistencia->execute(['id' => $linha['id']]);
        if ($consistencia->fetchColumn() !== false) {
            throw new RuntimeException('Existe leaderboard para resolução marcada como inelegível.');
        }
        if ($decisao['motivo'] === ELEGIBILIDADE_REJOGADA) {
            $primeira = $pdo->prepare(
                'SELECT l.id FROM leaderboard l JOIN resolucao r ON r.id = l.resolucao_id
                 WHERE l.dia = :dia AND l.jogador_id = :jogador
                   AND r.jogador_id = l.jogador_id AND r.desafio_dia = l.dia
                   AND r.elegivel_leaderboard = 1 AND DATE(r.concluida_em) = l.dia
                   AND r.tempo_milisegundos = l.tempo_milisegundos
                   AND r.concluida_em = l.momento_conclusao LIMIT 1'
            );
            $primeira->execute(['dia' => $linha['desafio_dia'], 'jogador' => $jogadorId]);
            if ($primeira->fetchColumn() === false) {
                throw new RuntimeException('Resolução do próprio dia sem primeira entrada consistente.');
            }
        }
    }
    return $linha;
}

/**
 * Deve ser chamado somente depois que a resposta correta for validada.
 * Repete a validação no limite transacional para proteger os demais chamadores.
 *
 * @param array<int, array<int, int>> $resposta
 * @param null|callable():string $relogio Ponto controlado para testes.
 * @param null|callable(string):void $observadorPersistencia
 * @return array<string, mixed>
 */
function concluir_tentativa_e_registrar_resolucao(
    PDO $pdo,
    int $jogadorId,
    int $tentativaId,
    array $resposta,
    ?callable $relogio = null,
    ?callable $observadorPersistencia = null
): array {
    if ($jogadorId < 1 || $tentativaId < 1 || $pdo->inTransaction()) {
        throw new InvalidArgumentException('O contexto da conclusão é inválido.');
    }
    try {
        $pdo->beginTransaction();
        $jogador = $pdo->prepare(
            'SELECT id FROM jogador
             WHERE id = :id AND ativo = 1 AND email_verificado = 1
             LIMIT 1 FOR UPDATE'
        );
        $jogador->execute(['id' => $jogadorId]);
        if ($jogador->fetchColumn() === false) {
            throw new RuntimeException('A conta do jogador não está disponível.');
        }

        $buscarTentativa = $pdo->prepare(
            'SELECT td.id, td.jogador_id, td.desafio_id, td.tema_id,
                    td.iniciada_em, td.finalizada_em, dd.dia,
                    (SELECT r.id FROM resolucao r WHERE r.tentativa_id = td.id LIMIT 1) AS resolucao_id
             FROM tentativa_desafio td
             INNER JOIN desafio_diario dd ON dd.id = td.desafio_id
             WHERE td.id = :id
             LIMIT 1 FOR UPDATE'
        );
        $buscarTentativa->execute(['id' => $tentativaId]);
        $registro = $buscarTentativa->fetch();
        if (!is_array($registro) || (int) $registro['jogador_id'] !== $jogadorId) {
            throw new RuntimeException('A tentativa não está disponível para este jogador.');
        }
        $tentativa = normalizar_tentativa_persistida($registro);
        if ($tentativa['finalizada_em'] !== null) {
            if ($tentativa['resolucao_id'] === null) {
                throw new RuntimeException('A tentativa encerrada não possui resolução consistente.');
            }
            $pdo->commit();
            $existente = buscar_resolucao_jogador_por_id($pdo, $tentativa['resolucao_id'], $jogadorId);
            if ($existente === null) {
                throw new RuntimeException('A resolução já registrada não foi localizada.');
            }
            $existente['reenvio'] = true;
            return $existente;
        }

        $desafio = carregar_desafio_por_id($pdo, (int) $tentativa['desafio_id']);
        if ($desafio === null
            || !resposta_confere_com_solucao($resposta, solucao_lista_para_matriz($desafio['solucao']))
        ) {
            throw new InvalidArgumentException('A resposta não resolve o desafio da tentativa.');
        }
        if (carregar_tema_completo($pdo, (int) $tentativa['tema_id'], false) === null) {
            throw new RuntimeException('O tema congelado da tentativa não está disponível.');
        }
        $concluidaEm = $relogio === null
            ? (string) $pdo->query('SELECT CURRENT_TIMESTAMP(6)')->fetchColumn()
            : (string) $relogio();
        $concluidaEm = normalizar_instante_mariadb($concluidaEm);
        $tempo = tempo_milisegundos_entre_instantes((string) $tentativa['iniciada_em'], $concluidaEm);
        $diaConclusao = substr($concluidaEm, 0, 10);
        if ((string) $tentativa['dia'] > $diaConclusao) {
            throw new RuntimeException('Não é possível concluir um desafio futuro.');
        }

        $buscarLeaderboard = $pdo->prepare(
            'SELECT resolucao_id FROM leaderboard
             WHERE dia = :dia AND jogador_id = :jogador_id
             LIMIT 1 FOR UPDATE'
        );
        $buscarLeaderboard->execute(['dia' => $tentativa['dia'], 'jogador_id' => $jogadorId]);
        $leaderboardExistente = $buscarLeaderboard->fetchColumn();
        if ($leaderboardExistente !== false
            && buscar_resolucao_jogador_por_id($pdo, (int) $leaderboardExistente, $jogadorId) === null
        ) {
            throw new RuntimeException('O leaderboard existente não possui resolução consistente.');
        }

        if ($diaConclusao === $tentativa['dia'] && $leaderboardExistente === false) {
            $anterior = $pdo->prepare(
                'SELECT id FROM resolucao
                 WHERE jogador_id = :jogador_id
                   AND desafio_dia = :dia
                   AND DATE(concluida_em) = desafio_dia
                 ORDER BY concluida_em ASC, id ASC
                 LIMIT 1 FOR UPDATE'
            );
            $anterior->execute(['jogador_id' => $jogadorId, 'dia' => $tentativa['dia']]);
            if ($anterior->fetchColumn() !== false) {
                throw new RuntimeException('Existe resolução válida sem leaderboard correspondente.');
            }
        }

        $decisao = decidir_elegibilidade_resolucao(
            true,
            (string) $tentativa['dia'],
            $diaConclusao,
            $leaderboardExistente !== false
        );
        $inserir = $pdo->prepare(
            'INSERT INTO resolucao
                (tentativa_id, jogador_id, desafio_dia, concluida_em,
                 tempo_milisegundos, tema_id, elegivel_leaderboard)
             VALUES
                (:tentativa_id, :jogador_id, :dia, :conclusao, :tempo, :tema_id, 0)'
        );
        $inserir->execute([
            'tentativa_id' => $tentativaId,
            'jogador_id' => $jogadorId,
            'dia' => $tentativa['dia'],
            'conclusao' => $concluidaEm,
            'tempo' => $tempo,
            'tema_id' => $tentativa['tema_id'],
        ]);
        $resolucaoId = (int) $pdo->lastInsertId();
        if ($observadorPersistencia !== null) {
            $observadorPersistencia('resolucao');
        }

        if ($decisao['elegivel']) {
            if ($observadorPersistencia !== null) {
                $observadorPersistencia('antes_leaderboard');
            }
            try {
                $leaderboard = $pdo->prepare(
                    'INSERT INTO leaderboard
                        (dia, jogador_id, resolucao_id, tempo_milisegundos, momento_conclusao)
                     VALUES (:dia, :jogador_id, :resolucao_id, :tempo, :conclusao)'
                );
                $leaderboard->execute([
                    'dia' => $tentativa['dia'],
                    'jogador_id' => $jogadorId,
                    'resolucao_id' => $resolucaoId,
                    'tempo' => $tempo,
                    'conclusao' => $concluidaEm,
                ]);
                $pdo->prepare('UPDATE resolucao SET elegivel_leaderboard = 1 WHERE id = :id')
                    ->execute(['id' => $resolucaoId]);
                if ($observadorPersistencia !== null) {
                    $observadorPersistencia('leaderboard');
                }
            } catch (PDOException $erro) {
                if (!pdo_erro_duplicidade($erro)
                    || !str_contains((string) ($erro->errorInfo[2] ?? ''), 'uq_leaderboard_dia_jogador')
                ) {
                    throw $erro;
                }
                $buscarLeaderboard->execute(['dia' => $tentativa['dia'], 'jogador_id' => $jogadorId]);
                if ($buscarLeaderboard->fetchColumn() === false) {
                    throw $erro;
                }
            }
        }

        $fechar = $pdo->prepare(
            'UPDATE tentativa_desafio
             SET finalizada_em = :conclusao, aberta_chave = NULL
             WHERE id = :id AND finalizada_em IS NULL'
        );
        $fechar->execute(['conclusao' => $concluidaEm, 'id' => $tentativaId]);
        if ($fechar->rowCount() !== 1) {
            throw new RuntimeException('A tentativa não pôde ser encerrada.');
        }
        if ($observadorPersistencia !== null) {
            $observadorPersistencia('tentativa');
        }
        $resolucao = buscar_resolucao_jogador_por_id($pdo, $resolucaoId, $jogadorId);
        if ($resolucao === null) {
            throw new RuntimeException('A resolução concluída não pôde ser recarregada.');
        }
        $pdo->commit();
        $resolucao['reenvio'] = false;
        return $resolucao;
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $erro;
    }
}

/** @return array<string, mixed>|null */
function buscar_ultima_resolucao_jogador_dia(PDO $pdo, int $jogadorId, string $dia): ?array
{
    $statement = $pdo->prepare(
        'SELECT id FROM resolucao
         WHERE jogador_id = :jogador_id AND desafio_dia = :dia
         ORDER BY concluida_em DESC, id DESC
         LIMIT 1'
    );
    $statement->execute(['jogador_id' => $jogadorId, 'dia' => $dia]);
    $id = $statement->fetchColumn();
    return $id === false ? null : buscar_resolucao_jogador_por_id($pdo, (int) $id, $jogadorId);
}

function formatar_tempo_resolucao(int $milissegundos): string
{
    $milissegundos = max(0, $milissegundos);
    $segundos = intdiv($milissegundos, 1000);
    $horas = intdiv($segundos, 3600);
    $minutos = intdiv($segundos % 3600, 60);
    $restoSegundos = $segundos % 60;
    $base = $horas > 0
        ? sprintf('%02d:%02d:%02d', $horas, $minutos, $restoSegundos)
        : sprintf('%02d:%02d', $minutos, $restoSegundos);
    return $base . sprintf('.%03d', $milissegundos % 1000);
}
