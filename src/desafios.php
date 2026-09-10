<?php

declare(strict_types=1);

function dia_de_referencia(?DateTimeImmutable $agora = null): string
{
    $fuso = new DateTimeZone('America/Sao_Paulo');
    $agora = $agora === null ? new DateTimeImmutable('now', $fuso) : $agora->setTimezone($fuso);
    return $agora->format('Y-m-d');
}

/** @return array{status:string, dia:?string} */
function classificar_dia_desafio(mixed $diaInformado): array
{
    $hoje = dia_de_referencia();
    if ($diaInformado === null || $diaInformado === '') {
        return ['status' => 'ok', 'dia' => $hoje];
    }
    if (!is_string($diaInformado) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $diaInformado) !== 1) {
        return ['status' => 'invalido', 'dia' => null];
    }

    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $diaInformado, new DateTimeZone('America/Sao_Paulo'));
    $erros = DateTimeImmutable::getLastErrors();
    if (!$data instanceof DateTimeImmutable
        || ($erros !== false && ($erros['warning_count'] > 0 || $erros['error_count'] > 0))
        || $data->format('Y-m-d') !== $diaInformado
    ) {
        return ['status' => 'invalido', 'dia' => null];
    }

    return [
        'status' => $diaInformado > $hoje ? 'futuro' : ($diaInformado < $hoje ? 'passado' : 'ok'),
        'dia' => $diaInformado,
    ];
}

function jogador_possui_acesso_no_dia(PDO $pdo, int $jogadorId, string $dia): bool
{
    $statement = $pdo->prepare(
        'SELECT 1 FROM acesso_diario
         WHERE jogador_id = :jogador_id AND dia = :dia
         LIMIT 1'
    );
    $statement->execute(['jogador_id' => $jogadorId, 'dia' => $dia]);
    return $statement->fetchColumn() !== false;
}

/** @return array<string, mixed>|null */
function carregar_desafio_por_dia(PDO $pdo, string $dia): ?array
{
    $statement = $pdo->prepare(
        'SELECT id, dia, criado_em FROM desafio_diario WHERE dia = :dia LIMIT 1'
    );
    $statement->execute(['dia' => $dia]);
    $registro = $statement->fetch();
    return is_array($registro) ? carregar_desafio_normalizado($pdo, $registro) : null;
}

/** @return array<string, mixed>|null */
function carregar_desafio_por_id(PDO $pdo, int $desafioId): ?array
{
    $statement = $pdo->prepare(
        'SELECT id, dia, criado_em FROM desafio_diario WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $desafioId]);
    $registro = $statement->fetch();
    return is_array($registro) ? carregar_desafio_normalizado($pdo, $registro) : null;
}

/** @param array<string, mixed> $registro @return array<string, mixed> */
function carregar_desafio_normalizado(PDO $pdo, array $registro): array
{
    $desafioId = (int) $registro['id'];
    $atribuicoesStatement = $pdo->prepare(
        'SELECT categoria_posicao, informacao_posicao, casa_posicao
         FROM desafio_atribuicao
         WHERE desafio_id = :desafio_id
         ORDER BY categoria_posicao ASC, informacao_posicao ASC'
    );
    $atribuicoesStatement->execute(['desafio_id' => $desafioId]);
    $solucao = [];
    foreach ($atribuicoesStatement->fetchAll() as $linha) {
        $solucao[] = [
            'categoria' => (int) $linha['categoria_posicao'],
            'informacao' => (int) $linha['informacao_posicao'],
            'casa' => (int) $linha['casa_posicao'],
        ];
    }

    $dicasStatement = $pdo->prepare(
        'SELECT dd.ordem, dd.relacao_codigo, dd.cat_info1, dd.pos_info1,
                dd.cat_info2, dd.pos_info2, dd.valor_fixo, rd.aridade, rd.conectivo
         FROM desafio_dica dd
         INNER JOIN relacao_dica rd ON rd.codigo = dd.relacao_codigo
         WHERE dd.desafio_id = :desafio_id
         ORDER BY dd.ordem ASC, dd.id ASC'
    );
    $dicasStatement->execute(['desafio_id' => $desafioId]);
    $dicas = [];
    foreach ($dicasStatement->fetchAll() as $linha) {
        $dicas[] = [
            'ordem' => (int) $linha['ordem'],
            'relacao' => (string) $linha['relacao_codigo'],
            'info1' => [(int) $linha['cat_info1'], (int) $linha['pos_info1']],
            'info2' => $linha['cat_info2'] === null
                ? null
                : [(int) $linha['cat_info2'], (int) $linha['pos_info2']],
            'valor_fixo' => $linha['valor_fixo'] === null ? null : (int) $linha['valor_fixo'],
            'aridade' => (int) $linha['aridade'],
            'conectivo' => (string) $linha['conectivo'],
        ];
    }

    $desafio = [
        'id' => $desafioId,
        'dia' => (string) $registro['dia'],
        'criado_em' => (string) $registro['criado_em'],
        'versao_formato' => 1,
        'solucao' => $solucao,
        'dicas' => $dicas,
    ];
    $validacao = validar_desafio_gerado($desafio, false);
    if (!$validacao['ok']) {
        throw new DesafioInvalidoException('O desafio persistido está incompleto ou inconsistente.');
    }
    return $desafio;
}

/**
 * @param array<string, mixed> $desafio
 * @param null|callable(string, int):void $observadorPersistencia
 * @return array<string, mixed>
 */
function persistir_desafio_gerado(
    PDO $pdo,
    string $dia,
    array $desafio,
    ?callable $observadorPersistencia = null
): array {
    $classificacao = classificar_data_iso($dia);
    if (!$classificacao) {
        throw new InvalidArgumentException('O dia do desafio deve usar uma data ISO válida.');
    }
    $validacao = validar_desafio_gerado($desafio, true);
    if (!$validacao['ok']) {
        throw new InvalidArgumentException(implode(' ', $validacao['erros']));
    }
    if ($pdo->inTransaction()) {
        throw new RuntimeException('A persistência do desafio exige uma transação isolada.');
    }

    $existente = carregar_desafio_por_dia($pdo, $dia);
    if ($existente !== null) {
        return $existente;
    }

    try {
        $pdo->beginTransaction();
        $buscar = $pdo->prepare('SELECT id FROM desafio_diario WHERE dia = :dia LIMIT 1 FOR UPDATE');
        $buscar->execute(['dia' => $dia]);
        $idExistente = $buscar->fetchColumn();
        if ($idExistente !== false) {
            $pdo->commit();
            $carregado = carregar_desafio_por_id($pdo, (int) $idExistente);
            if ($carregado === null) {
                throw new RuntimeException('O desafio concorrente não pôde ser carregado.');
            }
            return $carregado;
        }

        $inserirDesafio = $pdo->prepare(
            'INSERT INTO desafio_diario (dia, solucao_json, pistas_json)
             VALUES (:dia, NULL, NULL)'
        );
        $inserirDesafio->execute(['dia' => $dia]);
        $desafioId = (int) $pdo->lastInsertId();
        if ($observadorPersistencia !== null) {
            $observadorPersistencia('desafio', $desafioId);
        }

        $inserirAtribuicao = $pdo->prepare(
            'INSERT INTO desafio_atribuicao
                (desafio_id, categoria_posicao, informacao_posicao, casa_posicao)
             VALUES
                (:desafio_id, :categoria, :informacao, :casa)'
        );
        foreach (array_values($desafio['solucao']) as $indice => $atribuicao) {
            $inserirAtribuicao->execute([
                'desafio_id' => $desafioId,
                'categoria' => $atribuicao['categoria'],
                'informacao' => $atribuicao['informacao'],
                'casa' => $atribuicao['casa'],
            ]);
            if ($observadorPersistencia !== null) {
                $observadorPersistencia('atribuicao', $indice + 1);
            }
        }

        $inserirDica = $pdo->prepare(
            'INSERT INTO desafio_dica
                (desafio_id, ordem, relacao_codigo, cat_info1, pos_info1,
                 cat_info2, pos_info2, valor_fixo)
             VALUES
                (:desafio_id, :ordem, :relacao, :cat_info1, :pos_info1,
                 :cat_info2, :pos_info2, :valor_fixo)'
        );
        foreach (array_values($desafio['dicas']) as $indice => $dica) {
            $inserirDica->execute([
                'desafio_id' => $desafioId,
                'ordem' => $dica['ordem'],
                'relacao' => $dica['relacao'],
                'cat_info1' => $dica['info1'][0],
                'pos_info1' => $dica['info1'][1],
                'cat_info2' => $dica['info2'][0] ?? null,
                'pos_info2' => $dica['info2'][1] ?? null,
                'valor_fixo' => $dica['valor_fixo'],
            ]);
            if ($observadorPersistencia !== null) {
                $observadorPersistencia('dica', $indice + 1);
            }
        }

        $contar = $pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM desafio_atribuicao WHERE desafio_id = :id1) AS atribuicoes,
                (SELECT COUNT(*) FROM desafio_dica WHERE desafio_id = :id2) AS dicas'
        );
        $contar->execute(['id1' => $desafioId, 'id2' => $desafioId]);
        $contagens = $contar->fetch();
        if (!is_array($contagens)
            || (int) $contagens['atribuicoes'] !== 25
            || (int) $contagens['dicas'] !== count($desafio['dicas'])
        ) {
            throw new RuntimeException('A conferência da persistência do desafio falhou.');
        }

        $pdo->commit();
        $carregado = carregar_desafio_por_id($pdo, $desafioId);
        if ($carregado === null) {
            throw new RuntimeException('O desafio salvo não pôde ser recarregado.');
        }
        return $carregado;
    } catch (PDOException $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($erro->getCode() === '23000') {
            $concorrente = carregar_desafio_por_dia($pdo, $dia);
            if ($concorrente !== null) {
                return $concorrente;
            }
        }
        throw $erro;
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $erro;
    }
}

/** @return array<string, mixed> */
function provisionar_desafio_do_dia(PDO $pdo, string $dia, ?callable $provedor = null): array
{
    $existente = carregar_desafio_por_dia($pdo, $dia);
    if ($existente !== null) {
        return $existente;
    }
    $provedor ??= provedor_desafio_padrao();
    $desafio = obter_desafio_do_provedor($provedor, $dia, null);
    return persistir_desafio_gerado($pdo, $dia, $desafio);
}

function classificar_data_iso(string $dia): bool
{
    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $dia, new DateTimeZone('America/Sao_Paulo'));
    $erros = DateTimeImmutable::getLastErrors();
    return $data instanceof DateTimeImmutable
        && ($erros === false || ($erros['warning_count'] === 0 && $erros['error_count'] === 0))
        && $data->format('Y-m-d') === $dia;
}
