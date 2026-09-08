<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/bootstrap.php';

$ler = static function (string $rotulo): string {
    fwrite(STDOUT, $rotulo);
    $valor = fgets(STDIN);

    return $valor === false ? '' : trim($valor);
};

$lerSenha = static function (string $rotulo): string {
    fwrite(STDOUT, $rotulo);
    $valor = fgets(STDIN);

    return $valor === false ? '' : rtrim($valor, "\r\n");
};

if ((string) getenv('DB_PASSWORD') === '') {
    $senhaBanco = $lerSenha('Senha do usuário MariaDB zebrapuzzle_app: ');
    putenv('DB_PASSWORD=' . $senhaBanco);
    unset($senhaBanco);
}

$nome = $ler('Nome do administrador: ');
$email = normalizar_email($ler('E-mail do administrador: '));
$senha = $lerSenha('Senha do administrador (mínimo de 8 caracteres): ');
$confirmacao = $lerSenha('Repita a senha do administrador: ');

$erros = [];
if (!nome_usuario_valido($nome)) {
    $erros[] = 'O nome deve ter entre 1 e 50 caracteres.';
}
if (!email_valido($email)) {
    $erros[] = 'O e-mail é inválido.';
}
if (!senha_valida($senha)) {
    $erros[] = 'A senha deve ter no mínimo 8 caracteres.';
}
if (!hash_equals($senha, $confirmacao)) {
    $erros[] = 'As senhas não coincidem.';
}

if ($erros !== []) {
    fwrite(STDERR, "Não foi possível criar a conta:\n- " . implode("\n- ", $erros) . "\n");
    exit(1);
}

try {
    $statement = db()->prepare(
        'INSERT INTO administrador (nome_usuario, email, senha_hash)
         VALUES (:nome_usuario, :email, :senha_hash)'
    );
    $statement->execute([
        'nome_usuario' => $nome,
        'email' => $email,
        'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
    ]);
    fwrite(STDOUT, "Conta administrativa criada.\n");
} catch (PDOException $erro) {
    if ($erro->getCode() === '23000') {
        fwrite(STDERR, "Já existe uma conta administrativa com esse e-mail.\n");
        exit(1);
    }
    throw $erro;
} finally {
    unset($senha, $confirmacao);
    putenv('DB_PASSWORD');
}
