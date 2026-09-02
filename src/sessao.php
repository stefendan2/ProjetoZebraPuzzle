<?php

declare(strict_types=1);

function usuario_logado(?string $tipo = null): bool
{
    $autenticacao = $_SESSION['autenticacao'] ?? null;
    if (!is_array($autenticacao) || !isset($autenticacao['id'], $autenticacao['tipo'])) {
        return false;
    }

    return $tipo === null || hash_equals($tipo, (string) $autenticacao['tipo']);
}

function usuario_id(): ?int
{
    return usuario_logado() ? (int) $_SESSION['autenticacao']['id'] : null;
}

function exigir_login_jogador(): void
{
    if (!usuario_logado('jogador')) {
        flash_adicionar('erro', 'Entre como jogador para acessar esta página.');
        redirecionar('/');
    }
}

function exigir_login_admin(): void
{
    if (!usuario_logado('administrador')) {
        flash_adicionar('erro', 'Entre como administrador para acessar esta página.');
        redirecionar('/');
    }
}

function redirecionar(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}

