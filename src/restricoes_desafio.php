<?php

declare(strict_types=1);

/** @return array<string, array{aridade:int, conectivo:string}> */
function catalogo_relacoes_desafio(): array
{
    return [
        'CERT' => ['aridade' => 1, 'conectivo' => 'fica na casa'],
        'EQ' => ['aridade' => 2, 'conectivo' => 'também'],
        'M1' => ['aridade' => 2, 'conectivo' => 'está imediatamente à esquerda de quem'],
        'PM1' => ['aridade' => 2, 'conectivo' => 'é vizinho de quem'],
    ];
}

/** @return list<string> */
function validar_dicas_desafio(mixed $dicas): array
{
    if (!is_array($dicas) || $dicas === []) {
        return ['O desafio deve possuir uma lista não vazia de dicas.'];
    }

    $erros = [];
    $ordens = [];
    $catalogo = catalogo_relacoes_desafio();
    foreach (array_values($dicas) as $indice => $dica) {
        $numero = $indice + 1;
        if (!is_array($dica)) {
            $erros[] = "A dica {$numero} deve ser uma estrutura.";
            continue;
        }

        $ordem = $dica['ordem'] ?? null;
        if (!is_int($ordem) || $ordem < 1 || $ordem > 255) {
            $erros[] = "A dica {$numero} deve ter ordem inteira entre 1 e 255.";
        } elseif (isset($ordens[$ordem])) {
            $erros[] = "A ordem {$ordem} está repetida nas dicas.";
        } else {
            $ordens[$ordem] = true;
        }

        $relacao = $dica['relacao'] ?? null;
        if (!is_string($relacao) || !isset($catalogo[$relacao])) {
            $erros[] = "A dica {$numero} usa uma relação desconhecida.";
            continue;
        }

        if (!coordenada_informacao_valida($dica['info1'] ?? null)) {
            $erros[] = "A primeira informação da dica {$numero} é inválida.";
        }

        $info2 = $dica['info2'] ?? null;
        $valorFixo = $dica['valor_fixo'] ?? null;
        if ($relacao === 'CERT') {
            if ($info2 !== null) {
                $erros[] = "A dica CERT {$numero} não pode ter segunda informação.";
            }
            if (!indice_desafio_valido($valorFixo)) {
                $erros[] = "A dica CERT {$numero} deve indicar uma casa entre 0 e 4.";
            }
        } else {
            if (!coordenada_informacao_valida($info2)) {
                $erros[] = "A dica binária {$numero} deve ter a segunda informação completa.";
            }
            if ($valorFixo !== null) {
                $erros[] = "A dica binária {$numero} não pode ter casa fixa.";
            }
        }
    }

    if ($ordens !== []) {
        ksort($ordens);
        if (array_keys($ordens) !== range(1, count($ordens))) {
            $erros[] = 'A ordem das dicas deve ser contínua e começar em 1.';
        }
    }

    return array_values(array_unique($erros));
}

function coordenada_informacao_valida(mixed $coordenada): bool
{
    return is_array($coordenada)
        && array_keys($coordenada) === [0, 1]
        && indice_desafio_valido($coordenada[0])
        && indice_desafio_valido($coordenada[1]);
}

/**
 * Retorna null quando ainda não há operandos suficientes, ou booleano quando
 * a relação já pode ser avaliada.
 *
 * @param array<string, mixed> $dica
 * @param array<int, array<int, int>> $atribuicoes
 */
function avaliar_dica_desafio(array $dica, array $atribuicoes): ?bool
{
    $relacao = $dica['relacao'] ?? null;
    $info1 = $dica['info1'] ?? null;
    if (!is_string($relacao) || !isset(catalogo_relacoes_desafio()[$relacao])
        || !coordenada_informacao_valida($info1)
    ) {
        throw new InvalidArgumentException('A dica não possui formato lógico válido.');
    }

    $casa1 = $atribuicoes[$info1[0]][$info1[1]] ?? null;
    if (!indice_desafio_valido($casa1)) {
        return null;
    }

    if ($relacao === 'CERT') {
        $valorFixo = $dica['valor_fixo'] ?? null;
        if (!indice_desafio_valido($valorFixo) || ($dica['info2'] ?? null) !== null) {
            throw new InvalidArgumentException('A dica CERT possui operandos incompatíveis.');
        }
        return $casa1 === $valorFixo;
    }

    $info2 = $dica['info2'] ?? null;
    if (!coordenada_informacao_valida($info2) || ($dica['valor_fixo'] ?? null) !== null) {
        throw new InvalidArgumentException('A dica binária possui operandos incompatíveis.');
    }
    $casa2 = $atribuicoes[$info2[0]][$info2[1]] ?? null;
    if (!indice_desafio_valido($casa2)) {
        return null;
    }

    return match ($relacao) {
        'EQ' => $casa1 === $casa2,
        'M1' => $casa1 + 1 === $casa2,
        'PM1' => abs($casa1 - $casa2) === 1,
        default => throw new InvalidArgumentException('Relação desconhecida.'),
    };
}

/** @param list<array<string, mixed>> $dicas @param array<int, array<int, int>> $atribuicoes */
function todas_dicas_satisfeitas(array $dicas, array $atribuicoes): bool
{
    foreach ($dicas as $dica) {
        if (avaliar_dica_desafio($dica, $atribuicoes) !== true) {
            return false;
        }
    }
    return true;
}

/** @return array{prefixo:string, palavra:string}|null */
function fragmento_tema_para_info(array $tema, array $coordenada): ?array
{
    if (!coordenada_informacao_valida($coordenada)) {
        return null;
    }
    foreach (($tema['categorias'] ?? []) as $categoria) {
        if ((int) ($categoria['posicao'] ?? -1) !== $coordenada[0]) {
            continue;
        }
        $prefixo = trim((string) ($categoria['prefixo'] ?? ''));
        foreach (($categoria['valores'] ?? []) as $valor) {
            if ((int) ($valor['posicao'] ?? -1) === $coordenada[1]) {
                $palavra = trim((string) ($valor['nome'] ?? ''));
                if ($prefixo === '' || $palavra === '') {
                    return null;
                }
                return ['prefixo' => $prefixo, 'palavra' => $palavra];
            }
        }
    }
    return null;
}

/** @param array<string, mixed> $dica */
function renderizar_frase_dica(array $dica, array $tema): ?string
{
    $erros = validar_dicas_desafio([array_merge($dica, ['ordem' => 1])]);
    if ($erros !== []) {
        return null;
    }

    $primeiro = fragmento_tema_para_info($tema, $dica['info1']);
    if ($primeiro === null) {
        return null;
    }
    $relacao = (string) $dica['relacao'];
    $conectivo = trim((string) ($dica['conectivo'] ?? catalogo_relacoes_desafio()[$relacao]['conectivo']));
    if ($conectivo === '') {
        return null;
    }
    $fragmento1 = trim($primeiro['prefixo'] . ' ' . $primeiro['palavra']);

    if ($relacao === 'CERT') {
        return 'Quem ' . $fragmento1 . ' ' . $conectivo . ' ' . $dica['valor_fixo'] . '.';
    }

    $segundo = fragmento_tema_para_info($tema, $dica['info2']);
    if ($segundo === null) {
        return null;
    }
    $fragmento2 = trim($segundo['prefixo'] . ' ' . $segundo['palavra']);
    return 'Quem ' . $fragmento1 . ' ' . $conectivo . ' ' . $fragmento2 . '.';
}

/** @param list<array<string, mixed>> $dicas @return list<array<string, mixed>> */
function renderizar_dicas_para_tema(array $dicas, array $tema): array
{
    usort($dicas, static fn (array $a, array $b): int => ((int) $a['ordem']) <=> ((int) $b['ordem']));
    $renderizadas = [];
    foreach ($dicas as $dica) {
        $frase = renderizar_frase_dica($dica, $tema);
        if ($frase === null) {
            throw new RuntimeException('Uma dica não pôde ser renderizada com o tema efetivo.');
        }
        $dica['frase'] = $frase;
        $renderizadas[] = $dica;
    }
    return $renderizadas;
}
