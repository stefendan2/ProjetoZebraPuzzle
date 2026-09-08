<?php

declare(strict_types=1);

function somente_digitos(string $valor): string
{
    return preg_replace('/\D+/', '', $valor) ?? '';
}

function cpf_valido(string $cpf): bool
{
    $cpf = somente_digitos($cpf);

    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
        return false;
    }

    for ($tamanho = 9; $tamanho < 11; $tamanho++) {
        $soma = 0;
        for ($indice = 0; $indice < $tamanho; $indice++) {
            $soma += ((int) $cpf[$indice]) * (($tamanho + 1) - $indice);
        }

        $digito = (10 * $soma) % 11;
        $digito = $digito === 10 ? 0 : $digito;

        if ((int) $cpf[$tamanho] !== $digito) {
            return false;
        }
    }

    return true;
}

function email_valido(string $email): bool
{
    $email = trim($email);

    return mb_strlen($email, 'UTF-8') <= 254
        && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function normalizar_email(string $email): string
{
    return mb_strtolower(trim($email), 'UTF-8');
}

function nome_usuario_valido(string $nome): bool
{
    $tamanho = mb_strlen(trim($nome), 'UTF-8');

    return $tamanho >= 1 && $tamanho <= 50;
}

function senha_valida(string $senha): bool
{
    return mb_strlen($senha, 'UTF-8') >= 8;
}

/**
 * @param array{cpf?: mixed, email?: mixed, nome_usuario?: mixed, senha?: mixed} $dados
 * @return array<string, string>
 */
function validar_dados_conta(array $dados, bool $senhaObrigatoria = true): array
{
    $erros = [];
    $cpf = is_string($dados['cpf'] ?? null) ? $dados['cpf'] : '';
    $email = is_string($dados['email'] ?? null) ? trim($dados['email']) : '';
    $nome = is_string($dados['nome_usuario'] ?? null) ? trim($dados['nome_usuario']) : '';
    $senha = is_string($dados['senha'] ?? null) ? $dados['senha'] : '';

    if (!cpf_valido($cpf)) {
        $erros['cpf'] = 'Informe um CPF válido.';
    }

    if (!email_valido($email)) {
        $erros['email'] = 'Informe um e-mail válido.';
    }

    if (!nome_usuario_valido($nome)) {
        $erros['nome_usuario'] = 'O nome de usuário deve ter entre 1 e 50 caracteres.';
    }

    if (($senhaObrigatoria || $senha !== '') && !senha_valida($senha)) {
        $erros['senha'] = 'A senha deve ter no mínimo 8 caracteres.';
    }

    return $erros;
}
