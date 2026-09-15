<?php

declare(strict_types=1);

function normalizar_id_positivo(mixed $valor): ?int
{
    if (is_int($valor)) {
        return $valor > 0 ? $valor : null;
    }
    if (!is_string($valor) || preg_match('/^[1-9][0-9]*$/', $valor) !== 1) {
        return null;
    }
    $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return is_int($id) ? $id : null;
}

function normalizar_instante_mariadb(string $instante): string
{
    if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})(?:\.(\d{1,6}))?$/', $instante, $partes) !== 1) {
        throw new InvalidArgumentException('O instante não está no formato esperado.');
    }
    $normalizado = $partes[1] . '.' . str_pad($partes[2] ?? '', 6, '0');
    $data = DateTimeImmutable::createFromFormat(
        '!Y-m-d H:i:s.u',
        $normalizado,
        new DateTimeZone('America/Sao_Paulo')
    );
    $erros = DateTimeImmutable::getLastErrors();
    if (!$data instanceof DateTimeImmutable
        || ($erros !== false && ($erros['warning_count'] > 0 || $erros['error_count'] > 0))
        || $data->format('Y-m-d H:i:s.u') !== $normalizado
    ) {
        throw new InvalidArgumentException('O instante não representa uma data válida.');
    }
    return $normalizado;
}

function tempo_milisegundos_entre_instantes(string $inicio, string $fim): int
{
    $inicio = normalizar_instante_mariadb($inicio);
    $fim = normalizar_instante_mariadb($fim);
    $fuso = new DateTimeZone('America/Sao_Paulo');
    $dataInicio = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s.u', $inicio, $fuso);
    $dataFim = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s.u', $fim, $fuso);
    if (!$dataInicio instanceof DateTimeImmutable || !$dataFim instanceof DateTimeImmutable) {
        throw new RuntimeException('Não foi possível calcular o tempo da tentativa.');
    }
    $microsInicio = ((int) $dataInicio->format('U') * 1_000_000) + (int) $dataInicio->format('u');
    $microsFim = ((int) $dataFim->format('U') * 1_000_000) + (int) $dataFim->format('u');
    if ($microsFim < $microsInicio) {
        throw new RuntimeException('O término não pode anteceder o início da tentativa.');
    }
    return intdiv(($microsFim - $microsInicio) + 500, 1000);
}

/** @param array<string, mixed> $linha @return array<string, mixed> */
function normalizar_tentativa_persistida(array $linha): array
{
    foreach (['id', 'jogador_id', 'desafio_id', 'tema_id'] as $campo) {
        $linha[$campo] = (int) $linha[$campo];
    }
    $linha['resolucao_id'] = $linha['resolucao_id'] === null ? null : (int) $linha['resolucao_id'];
    return $linha;
}

/** @return array<string, mixed>|null */
function buscar_tentativa_persistida_por_id(PDO $pdo, int $tentativaId): ?array
{
    if ($tentativaId < 1) {
        return null;
    }
    $statement = $pdo->prepare(
        'SELECT td.id, td.jogador_id, td.desafio_id, td.tema_id,
                td.iniciada_em, td.finalizada_em, dd.dia,
                (SELECT r.id FROM resolucao r WHERE r.tentativa_id = td.id LIMIT 1) AS resolucao_id
         FROM tentativa_desafio td
         INNER JOIN desafio_diario dd ON dd.id = td.desafio_id
         WHERE td.id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $tentativaId]);
    $linha = $statement->fetch();
    return is_array($linha) ? normalizar_tentativa_persistida($linha) : null;
}

/** @return array<string, mixed>|null */
function buscar_tentativa_aberta(PDO $pdo, int $jogadorId, int $desafioId, bool $bloquear = false): ?array
{
    $sql =
        'SELECT td.id, td.jogador_id, td.desafio_id, td.tema_id,
                td.iniciada_em, td.finalizada_em, dd.dia, NULL AS resolucao_id
         FROM tentativa_desafio td
         INNER JOIN desafio_diario dd ON dd.id = td.desafio_id
         WHERE td.jogador_id = :jogador_id
           AND td.desafio_id = :desafio_id
           AND td.finalizada_em IS NULL
         ORDER BY td.id ASC
         LIMIT 1';
    $sql .= $bloquear ? ' FOR UPDATE' : '';
    $statement = $pdo->prepare($sql);
    $statement->execute(['jogador_id' => $jogadorId, 'desafio_id' => $desafioId]);
    $linha = $statement->fetch();
    return is_array($linha) ? normalizar_tentativa_persistida($linha) : null;
}

/**
 * Abre uma tentativa usando o relógio do banco e reutiliza a tentativa aberta.
 * O relógio opcional é um ponto controlado para os testes de integração.
 *
 * @param null|callable():string $relogio
 * @return array<string, mixed>
 */
function iniciar_ou_reutilizar_tentativa(
    PDO $pdo,
    int $jogadorId,
    int $desafioId,
    int $temaId,
    ?callable $relogio = null
): array {
    if ($jogadorId < 1 || $desafioId < 1 || $temaId < 1 || $pdo->inTransaction()) {
        throw new InvalidArgumentException('O contexto da tentativa é inválido.');
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
        $acesso = $pdo->prepare(
            'SELECT 1 FROM acesso_diario
             WHERE jogador_id = :jogador_id AND dia = CURRENT_DATE()
             LIMIT 1'
        );
        $acesso->execute(['jogador_id' => $jogadorId]);
        if ($acesso->fetchColumn() === false) {
            throw new RuntimeException('A entrada diária atual não foi concluída.');
        }
        $desafio = $pdo->prepare(
            'SELECT id FROM desafio_diario
             WHERE id = :id AND dia <= CURRENT_DATE()
             LIMIT 1 FOR UPDATE'
        );
        $desafio->execute(['id' => $desafioId]);
        if ($desafio->fetchColumn() === false) {
            throw new RuntimeException('O desafio não está disponível.');
        }
        $existente = buscar_tentativa_aberta($pdo, $jogadorId, $desafioId, true);
        if ($existente !== null) {
            $pdo->commit();
            return $existente;
        }
        if (carregar_desafio_por_id($pdo, $desafioId) === null) {
            throw new RuntimeException('O desafio não pôde ser carregado para uma nova tentativa.');
        }
        if (carregar_tema_completo($pdo, $temaId, true) === null) {
            throw new RuntimeException('O tema não está disponível para uma nova tentativa.');
        }

        $parametros = [
            'jogador_id' => $jogadorId,
            'desafio_id' => $desafioId,
            'tema_id' => $temaId,
        ];
        if ($relogio === null) {
            $sql = 'INSERT INTO tentativa_desafio
                        (jogador_id, desafio_id, tema_id, iniciada_em, finalizada_em)
                    VALUES (:jogador_id, :desafio_id, :tema_id, CURRENT_TIMESTAMP(6), NULL)';
        } else {
            $sql = 'INSERT INTO tentativa_desafio
                        (jogador_id, desafio_id, tema_id, iniciada_em, finalizada_em)
                    VALUES (:jogador_id, :desafio_id, :tema_id, :iniciada_em, NULL)';
            $parametros['iniciada_em'] = normalizar_instante_mariadb((string) $relogio());
        }
        $pdo->prepare($sql)->execute($parametros);
        $tentativaId = (int) $pdo->lastInsertId();
        $tentativa = buscar_tentativa_aberta($pdo, $jogadorId, $desafioId, true);
        if ($tentativa === null || $tentativa['id'] !== $tentativaId) {
            throw new RuntimeException('A tentativa criada não pôde ser confirmada.');
        }
        $pdo->commit();
        return $tentativa;
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $erro;
    }
}

/** @param array<string, mixed> $tentativa */
function tempo_decorrido_tentativa_persistida(array $tentativa, string $agora): int
{
    $inicio = is_string($tentativa['iniciada_em'] ?? null) ? $tentativa['iniciada_em'] : '';
    return tempo_milisegundos_entre_instantes($inicio, $agora);
}
