<?php

declare(strict_types=1);

/**
 * Fronteira estável para o gerador futuro.
 *
 * Um provedor recebe o dia de referência e uma semente opcional e devolve o
 * objeto canônico documentado em resources/desafios/exemplo.php. Rotas,
 * persistência e views não conhecem se o objeto veio da fixture ou do futuro
 * algoritmo gerador.
 *
 * @param callable(string, ?int): array<string, mixed> $provedor
 * @return array<string, mixed>
 */
function obter_desafio_do_provedor(callable $provedor, string $dia, ?int $semente = null): array
{
    $desafio = $provedor($dia, $semente);
    if (!is_array($desafio)) {
        throw new DesafioInvalidoException('O provedor não devolveu um desafio válido.');
    }

    $validacao = validar_desafio_gerado($desafio, true);
    if (!$validacao['ok']) {
        throw new DesafioInvalidoException('O provedor devolveu um desafio inválido: ' . implode(' ', $validacao['erros']));
    }

    return $desafio;
}
