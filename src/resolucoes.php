<?php

declare(strict_types=1);

/**
 * @param null|callable(string):void $observadorPersistencia
 * @return array<string, mixed>
 */
function registrar_resolucao_concluida(
    PDO $pdo,
    int $jogadorId,
    array $desafio,
    int $temaId,
    float $inicioServidor,
    ?float $fimServidor = null,
    ?callable $observadorPersistencia = null
): array {
    if ($jogadorId < 1 || $temaId < 1 || (int) ($desafio['id'] ?? 0) < 1
        || !classificar_data_iso((string) ($desafio['dia'] ?? ''))
    ) {
        throw new InvalidArgumentException('O contexto da resolução é inválido.');
    }
    $fimServidor ??= microtime(true);
    $tempoMilissegundos = tempo_decorrido_tentativa(['inicio_unix' => $inicioServidor], $fimServidor);

    $instante = DateTimeImmutable::createFromFormat('U.u', sprintf('%.6F', $fimServidor));
    if (!$instante instanceof DateTimeImmutable) {
        throw new RuntimeException('Não foi possível determinar o momento da conclusão.');
    }
    $concluidaEm = $instante
        ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
        ->format('Y-m-d H:i:s.u');

    if ($pdo->inTransaction()) {
        throw new RuntimeException('A resolução exige uma transação isolada.');
    }

    try {
        $pdo->beginTransaction();
        $jogador = $pdo->prepare('SELECT id FROM jogador WHERE id = :id AND ativo = 1 LIMIT 1 FOR UPDATE');
        $jogador->execute(['id' => $jogadorId]);
        if ($jogador->fetchColumn() === false) {
            throw new RuntimeException('A conta do jogador não está disponível.');
        }

        $confirmarDesafio = $pdo->prepare(
            'SELECT id FROM desafio_diario WHERE id = :id AND dia = :dia LIMIT 1 FOR UPDATE'
        );
        $confirmarDesafio->execute(['id' => (int) $desafio['id'], 'dia' => (string) $desafio['dia']]);
        if ($confirmarDesafio->fetchColumn() === false) {
            throw new RuntimeException('O desafio não está disponível.');
        }

        $confirmarTema = $pdo->prepare('SELECT id FROM tema WHERE id = :id LIMIT 1');
        $confirmarTema->execute(['id' => $temaId]);
        if ($confirmarTema->fetchColumn() === false) {
            throw new RuntimeException('O tema usado não está disponível.');
        }

        $inserir = $pdo->prepare(
            'INSERT INTO resolucao
                (jogador_id, desafio_dia, concluida_em, tempo_milisegundos,
                 tema_id, elegivel_leaderboard)
             VALUES
                (:jogador_id, :desafio_dia, :concluida_em, :tempo_milisegundos,
                 :tema_id, 0)'
        );
        $inserir->execute([
            'jogador_id' => $jogadorId,
            'desafio_dia' => (string) $desafio['dia'],
            'concluida_em' => $concluidaEm,
            'tempo_milisegundos' => $tempoMilissegundos,
            'tema_id' => $temaId,
        ]);
        $resolucaoId = (int) $pdo->lastInsertId();
        if ($observadorPersistencia !== null) {
            $observadorPersistencia('resolucao');
        }

        $pdo->commit();
        return [
            'id' => $resolucaoId,
            'jogador_id' => $jogadorId,
            'desafio_id' => (int) $desafio['id'],
            'desafio_dia' => (string) $desafio['dia'],
            'concluida_em' => $concluidaEm,
            'tempo_milisegundos' => $tempoMilissegundos,
            'tema_id' => $temaId,
            'elegivel_leaderboard' => 0,
        ];
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
        'SELECT r.id, r.jogador_id, dd.id AS desafio_id, r.desafio_dia,
                r.concluida_em, r.tempo_milisegundos, r.tema_id,
                r.elegivel_leaderboard, t.nome AS tema_nome
         FROM resolucao r
         INNER JOIN desafio_diario dd ON dd.dia = r.desafio_dia
         INNER JOIN tema t ON t.id = r.tema_id
         WHERE r.jogador_id = :jogador_id AND r.desafio_dia = :dia
         ORDER BY r.concluida_em DESC, r.id DESC
         LIMIT 1'
    );
    $statement->execute(['jogador_id' => $jogadorId, 'dia' => $dia]);
    $linha = $statement->fetch();
    if (!is_array($linha)) {
        return null;
    }
    foreach (['id', 'jogador_id', 'desafio_id', 'tempo_milisegundos', 'tema_id', 'elegivel_leaderboard'] as $campo) {
        $linha[$campo] = (int) $linha[$campo];
    }
    return $linha;
}

function formatar_tempo_resolucao(int $milissegundos): string
{
    $segundosTotais = intdiv(max(0, $milissegundos), 1000);
    $horas = intdiv($segundosTotais, 3600);
    $minutos = intdiv($segundosTotais % 3600, 60);
    $segundos = $segundosTotais % 60;
    return $horas > 0
        ? sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos)
        : sprintf('%02d:%02d', $minutos, $segundos);
}
