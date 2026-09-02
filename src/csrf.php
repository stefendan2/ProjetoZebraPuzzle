<?php

declare(strict_types=1);

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valido(?string $token): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? null;

    return is_string($sessionToken)
        && is_string($token)
        && hash_equals($sessionToken, $token);
}

function csrf_exigir_valido(): void
{
    $token = $_POST['_csrf'] ?? null;
    if (!csrf_valido(is_string($token) ? $token : null)) {
        http_response_code(419);
        renderizar('erro', [
            'titulo' => 'Sessão expirada',
            'mensagem' => 'O formulário expirou ou é inválido. Atualize a página e tente novamente.',
        ]);
        exit;
    }

    unset($_SESSION['csrf_token']);
}

