<?php

declare(strict_types=1);

/**
 * Valida a estrutura relacional exigida para um tema do Zebra Puzzle.
 *
 * @param array<string, mixed> $tema
 * @return array<string, string>
 */
function validar_estrutura_tema(array $tema): array
{
    $erros = [];
    $nome = $tema['nome'] ?? null;
    if (!is_string($nome) || trim($nome) === '') {
        $erros['nome'] = 'Informe o nome do tema.';
    } elseif (mb_strlen(trim($nome), 'UTF-8') > 100) {
        $erros['nome'] = 'O nome do tema deve ter no máximo 100 caracteres.';
    }

    $descricao = $tema['descricao'] ?? null;
    if ($descricao !== null && (!is_string($descricao) || mb_strlen($descricao, 'UTF-8') > 500)) {
        $erros['descricao'] = 'A descrição do tema deve ter no máximo 500 caracteres.';
    }

    $categorias = $tema['categorias'] ?? null;
    if (!is_array($categorias)) {
        $erros['categorias'] = 'As categorias do tema devem ser informadas em uma lista.';
        return $erros;
    }

    if (count($categorias) !== 5) {
        $erros['categorias'] = 'O tema deve possuir exatamente 5 categorias.';
    }

    $posicoesCategorias = [];
    foreach (array_values($categorias) as $indiceCategoria => $categoria) {
        $numeroCategoria = $indiceCategoria + 1;
        if (!is_array($categoria)) {
            $erros['categoria_' . $numeroCategoria] = 'Cada categoria deve possuir nome, posição e valores.';
            continue;
        }

        $nomeCategoria = $categoria['nome'] ?? null;
        if (!is_string($nomeCategoria) || trim($nomeCategoria) === '') {
            $erros['categoria_' . $numeroCategoria . '_nome'] = 'Informe o nome da categoria ' . $numeroCategoria . '.';
        } elseif (mb_strlen(trim($nomeCategoria), 'UTF-8') > 100) {
            $erros['categoria_' . $numeroCategoria . '_nome'] = 'O nome da categoria ' . $numeroCategoria . ' deve ter no máximo 100 caracteres.';
        }

        $posicaoCategoria = $categoria['posicao'] ?? null;
        if (!is_int($posicaoCategoria) || $posicaoCategoria < 1 || $posicaoCategoria > 5) {
            $erros['categoria_' . $numeroCategoria . '_posicao'] = 'A posição da categoria ' . $numeroCategoria . ' deve ser um inteiro entre 1 e 5.';
        } elseif (isset($posicoesCategorias[$posicaoCategoria])) {
            $erros['categoria_' . $numeroCategoria . '_posicao'] = 'As posições das categorias não podem se repetir.';
        } else {
            $posicoesCategorias[$posicaoCategoria] = true;
        }

        $valores = $categoria['valores'] ?? null;
        if (!is_array($valores)) {
            $erros['categoria_' . $numeroCategoria . '_valores'] = 'Os valores da categoria ' . $numeroCategoria . ' devem ser informados em uma lista.';
            continue;
        }

        if (count($valores) !== 5) {
            $erros['categoria_' . $numeroCategoria . '_valores'] = 'A categoria ' . $numeroCategoria . ' deve possuir exatamente 5 valores.';
        }

        $posicoesValores = [];
        foreach (array_values($valores) as $indiceValor => $valor) {
            $numeroValor = $indiceValor + 1;
            $chave = 'categoria_' . $numeroCategoria . '_valor_' . $numeroValor;
            if (!is_array($valor)) {
                $erros[$chave] = 'Cada valor deve possuir nome e posição.';
                continue;
            }

            $nomeValor = $valor['nome'] ?? null;
            if (!is_string($nomeValor) || trim($nomeValor) === '') {
                $erros[$chave . '_nome'] = 'Informe o nome do valor ' . $numeroValor . ' da categoria ' . $numeroCategoria . '.';
            } elseif (mb_strlen(trim($nomeValor), 'UTF-8') > 100) {
                $erros[$chave . '_nome'] = 'O nome do valor deve ter no máximo 100 caracteres.';
            }

            $posicaoValor = $valor['posicao'] ?? null;
            if (!is_int($posicaoValor) || $posicaoValor < 1 || $posicaoValor > 5) {
                $erros[$chave . '_posicao'] = 'A posição do valor deve ser um inteiro entre 1 e 5.';
            } elseif (isset($posicoesValores[$posicaoValor])) {
                $erros[$chave . '_posicao'] = 'As posições dos valores de uma categoria não podem se repetir.';
            } else {
                $posicoesValores[$posicaoValor] = true;
            }
        }
    }

    return $erros;
}

/** @return array<string, mixed>|null */
function carregar_tema_completo(PDO $pdo, int $temaId, bool $somenteAtivo = true): ?array
{
    if ($temaId < 1) {
        return null;
    }

    $sql =
        'SELECT t.id AS tema_id, t.nome AS tema_nome, t.descricao AS tema_descricao,
                t.ativo AS tema_ativo, t.criado_em AS tema_criado_em,
                tc.id AS categoria_id, tc.nome AS categoria_nome, tc.posicao AS categoria_posicao,
                tv.id AS valor_id, tv.nome AS valor_nome, tv.posicao AS valor_posicao
         FROM tema t
         LEFT JOIN tema_categoria tc ON tc.tema_id = t.id
         LEFT JOIN tema_valor tv ON tv.categoria_id = tc.id
         WHERE t.id = :tema_id';
    if ($somenteAtivo) {
        $sql .= ' AND t.ativo = 1';
    }
    $sql .= ' ORDER BY tc.posicao ASC, tc.id ASC, tv.posicao ASC, tv.id ASC';

    $statement = $pdo->prepare($sql);
    $statement->execute(['tema_id' => $temaId]);
    $linhas = $statement->fetchAll();
    if ($linhas === []) {
        return null;
    }

    $primeira = $linhas[0];
    $tema = [
        'id' => (int) $primeira['tema_id'],
        'nome' => (string) $primeira['tema_nome'],
        'descricao' => $primeira['tema_descricao'] === null ? null : (string) $primeira['tema_descricao'],
        'ativo' => (int) $primeira['tema_ativo'],
        'criado_em' => (string) $primeira['tema_criado_em'],
        'categorias' => [],
    ];

    $indicesCategorias = [];
    foreach ($linhas as $linha) {
        if ($linha['categoria_id'] === null) {
            continue;
        }

        $categoriaId = (int) $linha['categoria_id'];
        if (!isset($indicesCategorias[$categoriaId])) {
            $indicesCategorias[$categoriaId] = count($tema['categorias']);
            $tema['categorias'][] = [
                'id' => $categoriaId,
                'nome' => (string) $linha['categoria_nome'],
                'posicao' => (int) $linha['categoria_posicao'],
                'valores' => [],
            ];
        }

        if ($linha['valor_id'] !== null) {
            $indiceCategoria = $indicesCategorias[$categoriaId];
            $tema['categorias'][$indiceCategoria]['valores'][] = [
                'id' => (int) $linha['valor_id'],
                'nome' => (string) $linha['valor_nome'],
                'posicao' => (int) $linha['valor_posicao'],
            ];
        }
    }

    return validar_estrutura_tema($tema) === [] ? $tema : null;
}

function tema_elegivel(PDO $pdo, int $temaId): bool
{
    return carregar_tema_completo($pdo, $temaId, true) !== null;
}

/** @return list<array<string, mixed>> */
function listar_temas_elegiveis(PDO $pdo): array
{
    $statement = $pdo->query('SELECT id FROM tema WHERE ativo = 1 ORDER BY nome ASC, id ASC');
    $temas = [];
    foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $temaId) {
        $tema = carregar_tema_completo($pdo, (int) $temaId, true);
        if ($tema !== null) {
            $temas[] = $tema;
        }
    }

    return $temas;
}

/** @return array<string, mixed>|null */
function buscar_tema_padrao(PDO $pdo): ?array
{
    $statement = $pdo->query(
        'SELECT id FROM tema WHERE ativo = 1 ORDER BY criado_em ASC, id ASC'
    );
    foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $temaId) {
        $tema = carregar_tema_completo($pdo, (int) $temaId, true);
        if ($tema !== null) {
            return $tema;
        }
    }

    return null;
}

/** @return array<string, mixed>|null */
function carregar_tema_efetivo(PDO $pdo, int $jogadorId): ?array
{
    $statement = $pdo->prepare('SELECT tema_preferido_id FROM jogador WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $jogadorId]);
    $temaPreferidoId = $statement->fetchColumn();

    if ($temaPreferidoId !== false && $temaPreferidoId !== null) {
        $tema = carregar_tema_completo($pdo, (int) $temaPreferidoId, true);
        if ($tema !== null) {
            $tema['origem'] = 'preferencia';
            return $tema;
        }
    }

    $tema = buscar_tema_padrao($pdo);
    if ($tema !== null) {
        $tema['origem'] = 'padrao';
    }

    return $tema;
}

/**
 * Ponto reutilizável para o futuro CRUD administrativo da Fase 11.
 * A Fase 5 não publica rota ou tela que invoque esta operação.
 *
 * @param array<string, mixed> $tema
 * @return array{ok: bool, erros: array<string, string>, tema_id?: int}
 */
function gravar_tema_validado(PDO $pdo, array $tema): array
{
    $erros = validar_estrutura_tema($tema);
    if ($erros !== []) {
        return ['ok' => false, 'erros' => $erros];
    }
    if ($pdo->inTransaction()) {
        return [
            'ok' => false,
            'erros' => ['tema' => 'Não foi possível iniciar uma gravação isolada para o tema.'],
        ];
    }

    try {
        $pdo->beginTransaction();
        $inserirTema = $pdo->prepare(
            'INSERT INTO tema (nome, descricao, ativo) VALUES (:nome, :descricao, 1)'
        );
        $inserirTema->execute([
            'nome' => trim((string) $tema['nome']),
            'descricao' => ($tema['descricao'] ?? null) === null
                ? null
                : trim((string) $tema['descricao']),
        ]);
        $temaId = (int) $pdo->lastInsertId();

        $inserirCategoria = $pdo->prepare(
            'INSERT INTO tema_categoria (tema_id, nome, posicao)
             VALUES (:tema_id, :nome, :posicao)'
        );
        $inserirValor = $pdo->prepare(
            'INSERT INTO tema_valor (categoria_id, nome, posicao)
             VALUES (:categoria_id, :nome, :posicao)'
        );

        foreach ($tema['categorias'] as $categoria) {
            $inserirCategoria->execute([
                'tema_id' => $temaId,
                'nome' => trim((string) $categoria['nome']),
                'posicao' => (int) $categoria['posicao'],
            ]);
            $categoriaId = (int) $pdo->lastInsertId();

            foreach ($categoria['valores'] as $valor) {
                $inserirValor->execute([
                    'categoria_id' => $categoriaId,
                    'nome' => trim((string) $valor['nome']),
                    'posicao' => (int) $valor['posicao'],
                ]);
            }
        }

        if (carregar_tema_completo($pdo, $temaId, true) === null) {
            $pdo->rollBack();
            return [
                'ok' => false,
                'erros' => ['tema' => 'O tema não permaneceu completo após a gravação. Nenhum dado foi salvo.'],
            ];
        }

        $pdo->commit();
        return ['ok' => true, 'erros' => [], 'tema_id' => $temaId];
    } catch (PDOException) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [
            'ok' => false,
            'erros' => ['tema' => 'O tema conflita com dados existentes. Nenhum dado foi salvo.'],
        ];
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $erro;
    }
}

function normalizar_id_tema(mixed $temaId): ?int
{
    if (is_int($temaId)) {
        return $temaId > 0 ? $temaId : null;
    }
    if (!is_string($temaId)) {
        return null;
    }

    $temaId = trim($temaId);
    if (preg_match('/^[1-9][0-9]*$/', $temaId) !== 1) {
        return null;
    }

    $validado = filter_var($temaId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return is_int($validado) ? $validado : null;
}

/** @return array{ok: bool, erro?: string, tema?: array<string, mixed>} */
function alterar_tema_jogador(PDO $pdo, int $jogadorId, mixed $temaIdInformado): array
{
    $temaId = normalizar_id_tema($temaIdInformado);
    if ($temaId === null) {
        return ['ok' => false, 'erro' => 'Selecione um tema válido.'];
    }

    $iniciouTransacao = !$pdo->inTransaction();
    try {
        if ($iniciouTransacao) {
            $pdo->beginTransaction();
        }

        $jogador = $pdo->prepare(
            'SELECT id FROM jogador WHERE id = :id AND ativo = 1 LIMIT 1 FOR UPDATE'
        );
        $jogador->execute(['id' => $jogadorId]);
        if ($jogador->fetchColumn() === false) {
            if ($iniciouTransacao) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'erro' => 'A conta do jogador não está disponível.'];
        }

        $bloquearTema = $pdo->prepare(
            'SELECT id FROM tema WHERE id = :id AND ativo = 1 LIMIT 1 FOR UPDATE'
        );
        $bloquearTema->execute(['id' => $temaId]);
        if ($bloquearTema->fetchColumn() === false) {
            if ($iniciouTransacao) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'erro' => 'O tema selecionado não está disponível.'];
        }

        $tema = carregar_tema_completo($pdo, $temaId, true);
        if ($tema === null) {
            if ($iniciouTransacao) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'erro' => 'O tema selecionado está incompleto e não pode ser utilizado.'];
        }

        $atualizar = $pdo->prepare(
            'UPDATE jogador SET tema_preferido_id = :tema_id WHERE id = :jogador_id AND ativo = 1'
        );
        $atualizar->execute(['tema_id' => $temaId, 'jogador_id' => $jogadorId]);

        if ($iniciouTransacao) {
            $pdo->commit();
        }

        return ['ok' => true, 'tema' => $tema];
    } catch (Throwable $erro) {
        if ($iniciouTransacao && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $erro;
    }
}
