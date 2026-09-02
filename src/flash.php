<?php

declare(strict_types=1);

function flash_adicionar(string $tipo, string $mensagem): void
{
    $tiposPermitidos = ['sucesso', 'erro', 'aviso', 'info'];
    if (!in_array($tipo, $tiposPermitidos, true)) {
        $tipo = 'info';
    }

    $_SESSION['flash'][] = [
        'tipo' => $tipo,
        'mensagem' => $mensagem,
    ];
}

/** @return list<array{tipo: string, mensagem: string}> */
function flash_consumir(): array
{
    $mensagens = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return is_array($mensagens) ? $mensagens : [];
}

