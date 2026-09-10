<?php

declare(strict_types=1);

/**
 * Conta no máximo duas soluções por backtracking com MRV, restrições parciais
 * e AllDifferent por categoria. Este módulo apenas verifica; ele não gera dicas.
 *
 * @param list<array<string, mixed>> $dicas
 * @return array{status:string, quantidade:int, solucao:?array, nos:int, interrompido:bool}
 */
function contar_solucoes_desafio(
    array $dicas,
    int $limiteSolucoes = 2,
    int $limiteNos = 500000,
    int $limiteMilissegundos = 5000
): array {
    $erros = validar_dicas_desafio($dicas);
    if ($erros !== []) {
        throw new InvalidArgumentException(implode(' ', $erros));
    }
    if ($limiteSolucoes < 1 || $limiteNos < 1 || $limiteMilissegundos < 1) {
        throw new InvalidArgumentException('Os limites do verificador devem ser positivos.');
    }

    $atribuicoes = array_fill(0, DESAFIO_TAMANHO, []);
    $casasUsadas = array_fill(0, DESAFIO_TAMANHO, []);
    $variaveis = [];
    $grau = [];
    for ($categoria = 0; $categoria < DESAFIO_TAMANHO; $categoria++) {
        for ($informacao = 0; $informacao < DESAFIO_TAMANHO; $informacao++) {
            $chave = $categoria . ':' . $informacao;
            $variaveis[] = [$categoria, $informacao];
            $grau[$chave] = 0;
        }
    }
    foreach ($dicas as $dica) {
        $grau[$dica['info1'][0] . ':' . $dica['info1'][1]]++;
        if ($dica['info2'] !== null) {
            $grau[$dica['info2'][0] . ':' . $dica['info2'][1]]++;
        }
    }

    $inicio = hrtime(true);
    $quantidade = 0;
    $primeiraSolucao = null;
    $nos = 0;
    $interrompido = false;

    $tempoExcedido = static function () use ($inicio, $limiteMilissegundos): bool {
        return (hrtime(true) - $inicio) / 1_000_000 > $limiteMilissegundos;
    };

    $dominio = static function (
        int $categoria,
        int $informacao,
        array $estado,
        array $usadas
    ) use ($dicas): array {
        $candidatos = [];
        for ($casa = 0; $casa < DESAFIO_TAMANHO; $casa++) {
            if (!isset($usadas[$categoria][$casa])) {
                $candidatos[] = $casa;
            }
        }

        foreach ($dicas as $dica) {
            $ehPrimeira = $dica['info1'][0] === $categoria && $dica['info1'][1] === $informacao;
            $ehSegunda = $dica['info2'] !== null
                && $dica['info2'][0] === $categoria
                && $dica['info2'][1] === $informacao;
            if (!$ehPrimeira && !$ehSegunda) {
                continue;
            }

            if ($dica['relacao'] === 'CERT') {
                $candidatos = array_values(array_intersect($candidatos, [(int) $dica['valor_fixo']]));
                continue;
            }

            $outra = $ehPrimeira ? $dica['info2'] : $dica['info1'];
            $casaOutra = $estado[$outra[0]][$outra[1]] ?? null;
            if (!indice_desafio_valido($casaOutra)) {
                continue;
            }

            $permitidas = match ($dica['relacao']) {
                'EQ' => [$casaOutra],
                'M1' => [$ehPrimeira ? $casaOutra - 1 : $casaOutra + 1],
                'PM1' => [$casaOutra - 1, $casaOutra + 1],
                default => [],
            };
            $permitidas = array_values(array_filter($permitidas, 'indice_desafio_valido'));
            $candidatos = array_values(array_intersect($candidatos, $permitidas));
        }

        sort($candidatos);
        return $candidatos;
    };

    $buscar = function () use (
        &$buscar,
        &$atribuicoes,
        &$casasUsadas,
        &$quantidade,
        &$primeiraSolucao,
        &$nos,
        &$interrompido,
        $variaveis,
        $grau,
        $dicas,
        $dominio,
        $tempoExcedido,
        $limiteSolucoes,
        $limiteNos
    ): void {
        if ($quantidade >= $limiteSolucoes || $interrompido) {
            return;
        }
        $nos++;
        if ($nos > $limiteNos || $tempoExcedido()) {
            $interrompido = true;
            return;
        }

        $escolhida = null;
        $melhorDominio = [];
        $melhorGrau = -1;
        foreach ($variaveis as [$categoria, $informacao]) {
            if (isset($atribuicoes[$categoria][$informacao])) {
                continue;
            }
            $candidatos = $dominio($categoria, $informacao, $atribuicoes, $casasUsadas);
            if ($candidatos === []) {
                return;
            }
            $chave = $categoria . ':' . $informacao;
            if ($escolhida === null
                || count($candidatos) < count($melhorDominio)
                || (count($candidatos) === count($melhorDominio) && $grau[$chave] > $melhorGrau)
            ) {
                $escolhida = [$categoria, $informacao];
                $melhorDominio = $candidatos;
                $melhorGrau = $grau[$chave];
            }
        }

        if ($escolhida === null) {
            if (todas_dicas_satisfeitas($dicas, $atribuicoes)) {
                $quantidade++;
                if ($primeiraSolucao === null) {
                    foreach ($atribuicoes as &$categoriaCompleta) {
                        ksort($categoriaCompleta);
                    }
                    unset($categoriaCompleta);
                    $primeiraSolucao = $atribuicoes;
                }
            }
            return;
        }

        [$categoria, $informacao] = $escolhida;
        foreach ($melhorDominio as $casa) {
            $atribuicoes[$categoria][$informacao] = $casa;
            $casasUsadas[$categoria][$casa] = true;

            $consistente = true;
            foreach ($dicas as $dica) {
                if (avaliar_dica_desafio($dica, $atribuicoes) === false) {
                    $consistente = false;
                    break;
                }
            }
            if ($consistente) {
                $buscar();
            }

            unset($atribuicoes[$categoria][$informacao], $casasUsadas[$categoria][$casa]);
            if ($quantidade >= $limiteSolucoes || $interrompido) {
                return;
            }
        }
    };

    $buscar();

    $status = $interrompido
        ? 'limite_excedido'
        : match (true) {
            $quantidade === 0 => 'impossivel',
            $quantidade === 1 => 'unico',
            default => 'ambiguo',
        };

    return [
        'status' => $status,
        'quantidade' => $quantidade,
        'solucao' => $primeiraSolucao,
        'nos' => $nos,
        'interrompido' => $interrompido,
    ];
}

/** @param array<string, mixed> $desafio @return array{ok:bool, erros:list<string>, verificacao:?array} */
function validar_desafio_gerado(array $desafio, bool $verificarUnicidade = true): array
{
    $erros = [];
    if (($desafio['versao_formato'] ?? null) !== 1) {
        $erros[] = 'A versão do formato do desafio deve ser 1.';
    }
    $erros = array_merge($erros, validar_solucao_desafio($desafio['solucao'] ?? null));
    $erros = array_merge($erros, validar_dicas_desafio($desafio['dicas'] ?? null));
    if ($erros !== []) {
        return ['ok' => false, 'erros' => array_values(array_unique($erros)), 'verificacao' => null];
    }

    $solucao = solucao_lista_para_matriz($desafio['solucao']);
    if (!todas_dicas_satisfeitas($desafio['dicas'], $solucao)) {
        $erros[] = 'A solução informada não satisfaz todas as dicas.';
    }

    $verificacao = null;
    if ($verificarUnicidade && $erros === []) {
        $verificacao = contar_solucoes_desafio($desafio['dicas']);
        if ($verificacao['status'] !== 'unico') {
            $erros[] = 'As dicas não determinam exatamente uma solução dentro do limite seguro.';
        } elseif ($verificacao['solucao'] !== $solucao) {
            $erros[] = 'A solução única das dicas difere da solução fornecida.';
        }
    }

    return ['ok' => $erros === [], 'erros' => $erros, 'verificacao' => $verificacao];
}
