<?php

declare(strict_types=1);

/** @return array<string, mixed> */
function fornecer_desafio_exemplo(string $dia, ?int $semente = null): array
{
    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $dia, new DateTimeZone('America/Sao_Paulo'));
    $erros = DateTimeImmutable::getLastErrors();
    if (!$data instanceof DateTimeImmutable
        || ($erros !== false && ($erros['warning_count'] > 0 || $erros['error_count'] > 0))
        || $data->format('Y-m-d') !== $dia
    ) {
        throw new InvalidArgumentException('O provedor recebeu um dia inválido.');
    }

    $fixture = require dirname(__DIR__) . '/resources/desafios/exemplo.php';
    if (!is_array($fixture)) {
        throw new DesafioInvalidoException('A fixture da Fase 6 não pôde ser carregada.');
    }
    $fixture['metadados']['dia_instancia'] = $dia;
    $fixture['metadados']['semente'] = $semente;

    return $fixture;
}

/** @return callable(string, ?int): array<string, mixed> */
function provedor_desafio_padrao(): callable
{
    return 'fornecer_desafio_exemplo';
}
