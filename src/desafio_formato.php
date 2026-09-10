<?php

declare(strict_types=1);

final class DesafioInvalidoException extends RuntimeException
{
}

const DESAFIO_TAMANHO = 5;
const DESAFIO_INDICE_MINIMO = 0;
const DESAFIO_INDICE_MAXIMO = 4;

function indice_desafio_valido(mixed $valor): bool
{
    return is_int($valor)
        && $valor >= DESAFIO_INDICE_MINIMO
        && $valor <= DESAFIO_INDICE_MAXIMO;
}

/** @return list<string> */
function validar_solucao_desafio(mixed $atribuicoes): array
{
    if (!is_array($atribuicoes)) {
        return ['A solução deve ser uma lista de atribuições.'];
    }

    $erros = [];
    if (count($atribuicoes) !== DESAFIO_TAMANHO * DESAFIO_TAMANHO) {
        $erros[] = 'A solução deve possuir exatamente 25 atribuições.';
    }

    $coordenadas = [];
    $casasPorCategoria = [];
    foreach (array_values($atribuicoes) as $indice => $atribuicao) {
        $numero = $indice + 1;
        if (!is_array($atribuicao)) {
            $erros[] = "A atribuição {$numero} deve ser uma estrutura.";
            continue;
        }

        $categoria = $atribuicao['categoria'] ?? null;
        $informacao = $atribuicao['informacao'] ?? null;
        $casa = $atribuicao['casa'] ?? null;
        if (!indice_desafio_valido($categoria)
            || !indice_desafio_valido($informacao)
            || !indice_desafio_valido($casa)
        ) {
            $erros[] = "A atribuição {$numero} deve usar inteiros entre 0 e 4.";
            continue;
        }

        $coordenada = $categoria . ':' . $informacao;
        if (isset($coordenadas[$coordenada])) {
            $erros[] = "A coordenada lógica {$coordenada} está repetida.";
        }
        $coordenadas[$coordenada] = true;

        $chaveCasa = $categoria . ':' . $casa;
        if (isset($casasPorCategoria[$chaveCasa])) {
            $erros[] = "A casa {$casa} está repetida na categoria {$categoria}.";
        }
        $casasPorCategoria[$chaveCasa] = true;
    }

    for ($categoria = 0; $categoria < DESAFIO_TAMANHO; $categoria++) {
        for ($informacao = 0; $informacao < DESAFIO_TAMANHO; $informacao++) {
            if (!isset($coordenadas[$categoria . ':' . $informacao])) {
                $erros[] = "Falta a informação {$informacao} da categoria {$categoria}.";
            }
        }
        for ($casa = 0; $casa < DESAFIO_TAMANHO; $casa++) {
            if (!isset($casasPorCategoria[$categoria . ':' . $casa])) {
                $erros[] = "Falta a casa {$casa} na permutação da categoria {$categoria}.";
            }
        }
    }

    return array_values(array_unique($erros));
}

/**
 * @param list<array{categoria:int, informacao:int, casa:int}> $atribuicoes
 * @return array<int, array<int, int>>
 */
function solucao_lista_para_matriz(array $atribuicoes): array
{
    $erros = validar_solucao_desafio($atribuicoes);
    if ($erros !== []) {
        throw new InvalidArgumentException(implode(' ', $erros));
    }

    $matriz = array_fill(0, DESAFIO_TAMANHO, []);
    foreach ($atribuicoes as $atribuicao) {
        $matriz[$atribuicao['categoria']][$atribuicao['informacao']] = $atribuicao['casa'];
    }
    foreach ($matriz as &$categoria) {
        ksort($categoria);
    }
    unset($categoria);

    return $matriz;
}

/**
 * @param array<int, array<int, int>> $matriz
 * @return list<array{categoria:int, informacao:int, casa:int}>
 */
function solucao_matriz_para_lista(array $matriz): array
{
    $atribuicoes = [];
    for ($categoria = 0; $categoria < DESAFIO_TAMANHO; $categoria++) {
        for ($informacao = 0; $informacao < DESAFIO_TAMANHO; $informacao++) {
            $atribuicoes[] = [
                'categoria' => $categoria,
                'informacao' => $informacao,
                'casa' => $matriz[$categoria][$informacao] ?? null,
            ];
        }
    }

    $erros = validar_solucao_desafio($atribuicoes);
    if ($erros !== []) {
        throw new InvalidArgumentException(implode(' ', $erros));
    }

    return $atribuicoes;
}

/**
 * Converte a projeção visual grade[categoria][casa] = informação para
 * resposta[categoria][informação] = casa.
 *
 * @return array{
 *   ok:bool,
 *   status:string,
 *   erros:list<string>,
 *   resposta:array<int, array<int, int>>,
 *   grade:array<int, array<int, int|string>>
 * }
 */
function grade_visual_para_resposta(mixed $grade): array
{
    $resultado = [
        'ok' => false,
        'status' => 'invalida',
        'erros' => [],
        'resposta' => [],
        'grade' => array_fill(0, DESAFIO_TAMANHO, array_fill(0, DESAFIO_TAMANHO, '')),
    ];
    if (!is_array($grade)) {
        $resultado['erros'][] = 'A grade enviada é inválida.';
        return $resultado;
    }

    $categorias = normalizar_mapa_indices_desafio($grade);
    if ($categorias === null || array_keys($categorias) !== range(0, 4)) {
        $resultado['erros'][] = 'A grade deve conter exatamente as categorias 0 a 4.';
        return $resultado;
    }

    $incompleta = false;
    for ($categoria = 0; $categoria < DESAFIO_TAMANHO; $categoria++) {
        if (!is_array($categorias[$categoria])) {
            $resultado['erros'][] = "A categoria {$categoria} deve conter cinco casas.";
            continue;
        }
        $casas = normalizar_mapa_indices_desafio($categorias[$categoria]);
        if ($casas === null || array_keys($casas) !== range(0, 4)) {
            $resultado['erros'][] = "A categoria {$categoria} deve conter exatamente as casas 0 a 4.";
            continue;
        }

        $informacoesUsadas = [];
        for ($casa = 0; $casa < DESAFIO_TAMANHO; $casa++) {
            $valor = $casas[$casa];
            if ($valor === '') {
                $incompleta = true;
                continue;
            }
            $informacao = normalizar_indice_desafio($valor);
            if ($informacao === null) {
                $resultado['erros'][] = "A casa {$casa} da categoria {$categoria} contém uma informação inválida.";
                continue;
            }

            $resultado['grade'][$categoria][$casa] = $informacao;
            if (isset($informacoesUsadas[$informacao])) {
                $resultado['erros'][] = "A informação {$informacao} está repetida na categoria {$categoria}.";
                continue;
            }
            $informacoesUsadas[$informacao] = true;
            $resultado['resposta'][$categoria][$informacao] = $casa;
        }
    }

    if ($resultado['erros'] !== []) {
        return $resultado;
    }
    if ($incompleta) {
        $resultado['status'] = 'incompleta';
        $resultado['erros'][] = 'Preencha as 25 células antes de finalizar.';
        return $resultado;
    }

    foreach ($resultado['resposta'] as &$categoria) {
        ksort($categoria);
    }
    unset($categoria);
    ksort($resultado['resposta']);
    $resultado['ok'] = true;
    $resultado['status'] = 'ok';
    return $resultado;
}

function normalizar_indice_desafio(mixed $valor): ?int
{
    if (is_int($valor)) {
        return indice_desafio_valido($valor) ? $valor : null;
    }
    if (!is_string($valor) || preg_match('/^[0-4]$/', $valor) !== 1) {
        return null;
    }

    return (int) $valor;
}

/** @return array<int, mixed>|null */
function normalizar_mapa_indices_desafio(array $mapa): ?array
{
    $normalizado = [];
    foreach ($mapa as $chave => $valor) {
        $indice = normalizar_indice_desafio($chave);
        if ($indice === null || array_key_exists($indice, $normalizado)) {
            return null;
        }
        $normalizado[$indice] = $valor;
    }
    ksort($normalizado);
    return $normalizado;
}

/** @param array<int, array<int, int>> $resposta */
function resposta_para_grade_visual(array $resposta): array
{
    $grade = array_fill(0, DESAFIO_TAMANHO, array_fill(0, DESAFIO_TAMANHO, null));
    for ($categoria = 0; $categoria < DESAFIO_TAMANHO; $categoria++) {
        for ($informacao = 0; $informacao < DESAFIO_TAMANHO; $informacao++) {
            $casa = $resposta[$categoria][$informacao] ?? null;
            if (!indice_desafio_valido($casa) || $grade[$categoria][$casa] !== null) {
                throw new InvalidArgumentException('A resposta canônica não representa cinco permutações 0..4.');
            }
            $grade[$categoria][$casa] = $informacao;
        }
    }

    return $grade;
}

/** @param array<int, array<int, int>> $resposta @param array<int, array<int, int>> $solucao */
function resposta_confere_com_solucao(array $resposta, array $solucao): bool
{
    return $resposta === $solucao;
}
