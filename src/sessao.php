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

function usuario_tipo(): ?string
{
    return usuario_logado() ? (string) $_SESSION['autenticacao']['tipo'] : null;
}

function autenticar_usuario(int $id, string $tipo): void
{
    if (!in_array($tipo, ['jogador', 'administrador'], true)) {
        throw new InvalidArgumentException('Tipo de conta inválido.');
    }

    session_regenerate_id(true);
    $_SESSION['autenticacao'] = ['id' => $id, 'tipo' => $tipo];
    unset($_SESSION['login_pendente'], $_SESSION['captcha_login']);
}

function login_pendente_definir(int $jogadorId): void
{
    $_SESSION['login_pendente'] = [
        'jogador_id' => $jogadorId,
        'expira_em' => time() + (int) app_config('captcha_ttl'),
    ];
    unset($_SESSION['captcha_login']);
}

function login_pendente_id(): ?int
{
    $pendente = $_SESSION['login_pendente'] ?? null;
    if (!is_array($pendente)
        || !isset($pendente['jogador_id'], $pendente['expira_em'])
        || (int) $pendente['expira_em'] < time()
    ) {
        login_pendente_limpar();
        return null;
    }

    return (int) $pendente['jogador_id'];
}

function login_pendente_limpar(): void
{
    unset($_SESSION['login_pendente'], $_SESSION['captcha_login']);
}

function encerrar_sessao_usuario(): void
{
    unset(
        $_SESSION['autenticacao'],
        $_SESSION['login_pendente'],
        $_SESSION['captcha_login'],
        $_SESSION['csrf_token'],
        $_SESSION['_formularios'],
        $_SESSION['_verificacao_email_desenvolvimento']
    );
    session_regenerate_id(true);
}

function exigir_login_jogador(): void
{
    if (!usuario_logado('jogador')) {
        flash_adicionar('erro', 'Entre como jogador para acessar esta página.');
        redirecionar('/login');
    }
}

function exigir_login_admin(): void
{
    if (!usuario_logado('administrador')) {
        flash_adicionar('erro', 'Entre como administrador para acessar esta página.');
        redirecionar('/login');
    }
}

function redirecionar(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}
