<?php

declare(strict_types=1);

/** @param array<string, mixed> $dados @param array<string, string> $erros */
function formulario_guardar(string $nome, array $dados, array $erros = []): void
{
    $_SESSION['_formularios'][$nome] = ['dados' => $dados, 'erros' => $erros];
}

/** @return array{dados: array<string, mixed>, erros: array<string, string>} */
function formulario_consumir(string $nome): array
{
    $formulario = $_SESSION['_formularios'][$nome] ?? null;
    unset($_SESSION['_formularios'][$nome]);

    if (!is_array($formulario)) {
        return ['dados' => [], 'erros' => []];
    }

    return [
        'dados' => is_array($formulario['dados'] ?? null) ? $formulario['dados'] : [],
        'erros' => is_array($formulario['erros'] ?? null) ? $formulario['erros'] : [],
    ];
}

function link_verificacao_desenvolvimento_guardar(string $token): void
{
    if (app_config('environment') !== 'development') {
        return;
    }

    $_SESSION['_verificacao_email_desenvolvimento'] = [
        'token' => $token,
        'link_disponivel' => true,
    ];
}

function link_verificacao_desenvolvimento_consumir(): ?string
{
    $verificacao = $_SESSION['_verificacao_email_desenvolvimento'] ?? null;
    if (!is_array($verificacao) || ($verificacao['link_disponivel'] ?? false) !== true) {
        return null;
    }

    $_SESSION['_verificacao_email_desenvolvimento']['link_disponivel'] = false;
    return url('/verificar-email');
}

function token_verificacao_desenvolvimento_consumir(): ?string
{
    if (app_config('environment') !== 'development') {
        return null;
    }

    $verificacao = $_SESSION['_verificacao_email_desenvolvimento'] ?? null;
    unset($_SESSION['_verificacao_email_desenvolvimento']);

    $token = is_array($verificacao) ? ($verificacao['token'] ?? null) : null;
    return is_string($token) ? $token : null;
}

/** @return array<string, mixed>|null */
function buscar_jogador_por_email(PDO $pdo, string $email): ?array
{
    $statement = $pdo->prepare(
        'SELECT id, cpf, nome_usuario, email, senha_hash, email_verificado, ativo
         FROM jogador WHERE email = :email LIMIT 1'
    );
    $statement->execute(['email' => normalizar_email($email)]);
    $jogador = $statement->fetch();

    return is_array($jogador) ? $jogador : null;
}

/** @return array<string, mixed>|null */
function buscar_jogador_por_id(PDO $pdo, int $id): ?array
{
    $statement = $pdo->prepare(
        'SELECT id, cpf, nome_usuario, email, senha_hash, email_verificado, ativo
         FROM jogador WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $id]);
    $jogador = $statement->fetch();

    return is_array($jogador) ? $jogador : null;
}

/** @return array<string, mixed>|null */
function buscar_administrador_por_email(PDO $pdo, string $email): ?array
{
    $statement = $pdo->prepare(
        'SELECT id, nome_usuario, email, senha_hash, ativo
         FROM administrador WHERE email = :email LIMIT 1'
    );
    $statement->execute(['email' => normalizar_email($email)]);
    $administrador = $statement->fetch();

    return is_array($administrador) ? $administrador : null;
}

function buscar_email_pendente(PDO $pdo, int $jogadorId): ?string
{
    $statement = $pdo->prepare(
        'SELECT email_pendente
         FROM verificacao_email
         WHERE jogador_id = :jogador_id
           AND email_pendente IS NOT NULL
           AND utilizado_em IS NULL
           AND expira_em >= CURRENT_TIMESTAMP
         ORDER BY id DESC LIMIT 1'
    );
    $statement->execute(['jogador_id' => $jogadorId]);
    $email = $statement->fetchColumn();

    return is_string($email) ? $email : null;
}

/** @return array<string, string> */
function erros_unicidade_conta(PDO $pdo, string $cpf, string $email, ?int $ignorarId = null): array
{
    $sql = 'SELECT cpf, email FROM jogador WHERE (cpf = :cpf OR email = :email)';
    $parametros = ['cpf' => somente_digitos($cpf), 'email' => normalizar_email($email)];

    if ($ignorarId !== null) {
        $sql .= ' AND id <> :id';
        $parametros['id'] = $ignorarId;
    }

    $statement = $pdo->prepare($sql);
    $statement->execute($parametros);
    $erros = [];

    foreach ($statement->fetchAll() as $conta) {
        if (hash_equals((string) $conta['cpf'], somente_digitos($cpf))) {
            $erros['cpf'] = 'Este CPF já está cadastrado.';
        }
        if (hash_equals(normalizar_email((string) $conta['email']), normalizar_email($email))) {
            $erros['email'] = 'Este e-mail já está cadastrado.';
        }
    }

    return $erros;
}

function emitir_token_verificacao(PDO $pdo, int $jogadorId, ?string $emailPendente = null): string
{
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiraEm = (new DateTimeImmutable())
        ->modify('+' . (int) app_config('email_token_ttl') . ' seconds')
        ->format('Y-m-d H:i:s');

    $invalidar = $pdo->prepare(
        'UPDATE verificacao_email
         SET utilizado_em = CURRENT_TIMESTAMP
         WHERE jogador_id = :jogador_id AND utilizado_em IS NULL'
    );
    $invalidar->execute(['jogador_id' => $jogadorId]);

    $inserir = $pdo->prepare(
        'INSERT INTO verificacao_email
            (jogador_id, email_pendente, token_hash, expira_em)
         VALUES
            (:jogador_id, :email_pendente, :token_hash, :expira_em)'
    );
    $inserir->execute([
        'jogador_id' => $jogadorId,
        'email_pendente' => $emailPendente === null ? null : normalizar_email($emailPendente),
        'token_hash' => $tokenHash,
        'expira_em' => $expiraEm,
    ]);

    return $token;
}

/**
 * @param array{cpf?: mixed, email?: mixed, nome_usuario?: mixed, senha?: mixed} $dados
 * @return array{ok: bool, erros: array<string, string>, token?: string}
 */
function criar_jogador_pendente(PDO $pdo, array $dados): array
{
    $erros = validar_dados_conta($dados);
    $cpf = somente_digitos(is_string($dados['cpf'] ?? null) ? $dados['cpf'] : '');
    $email = normalizar_email(is_string($dados['email'] ?? null) ? $dados['email'] : '');
    $nome = trim(is_string($dados['nome_usuario'] ?? null) ? $dados['nome_usuario'] : '');
    $senha = is_string($dados['senha'] ?? null) ? $dados['senha'] : '';

    $erros += erros_unicidade_conta($pdo, $cpf, $email);
    if ($erros !== []) {
        return ['ok' => false, 'erros' => $erros];
    }

    try {
        $pdo->beginTransaction();
        $inserir = $pdo->prepare(
            'INSERT INTO jogador (cpf, nome_usuario, email, senha_hash)
             VALUES (:cpf, :nome_usuario, :email, :senha_hash)'
        );
        $inserir->execute([
            'cpf' => $cpf,
            'nome_usuario' => $nome,
            'email' => $email,
            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
        ]);

        $token = emitir_token_verificacao($pdo, (int) $pdo->lastInsertId());
        $pdo->commit();

        return ['ok' => true, 'erros' => [], 'token' => $token];
    } catch (PDOException $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($erro->getCode() === '23000') {
            $erros = erros_unicidade_conta($pdo, $cpf, $email);
            return ['ok' => false, 'erros' => $erros ?: ['conta' => 'CPF ou e-mail já cadastrado.']];
        }
        throw $erro;
    }
}

function reenviar_token_verificacao(PDO $pdo, string $email): ?string
{
    $jogador = buscar_jogador_por_email($pdo, $email);
    if ($jogador === null || (int) $jogador['ativo'] !== 1 || (int) $jogador['email_verificado'] === 1) {
        return null;
    }

    try {
        $pdo->beginTransaction();
        $token = emitir_token_verificacao($pdo, (int) $jogador['id']);
        $pdo->commit();
        return $token;
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $erro;
    }
}

/** @return array{status: string} */
function verificar_token_email(PDO $pdo, string $token): array
{
    if (preg_match('/^[a-f0-9]{64}$/i', $token) !== 1) {
        return ['status' => 'invalido'];
    }

    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare(
            'SELECT ve.id, ve.jogador_id, ve.email_pendente, ve.utilizado_em,
                    (ve.expira_em < CURRENT_TIMESTAMP) AS expirado
             FROM verificacao_email ve
             WHERE ve.token_hash = :token_hash
             LIMIT 1 FOR UPDATE'
        );
        $statement->execute(['token_hash' => hash('sha256', $token)]);
        $verificacao = $statement->fetch();

        if (!is_array($verificacao)) {
            $pdo->rollBack();
            return ['status' => 'invalido'];
        }
        if ($verificacao['utilizado_em'] !== null) {
            $pdo->rollBack();
            return ['status' => 'utilizado'];
        }
        if ((int) $verificacao['expirado'] === 1) {
            $pdo->rollBack();
            return ['status' => 'expirado'];
        }

        $emailPendente = $verificacao['email_pendente'];
        if (is_string($emailPendente) && $emailPendente !== '') {
            $conflito = $pdo->prepare(
                'SELECT 1 FROM jogador WHERE email = :email AND id <> :id LIMIT 1'
            );
            $conflito->execute([
                'email' => normalizar_email($emailPendente),
                'id' => (int) $verificacao['jogador_id'],
            ]);
            if ($conflito->fetchColumn() !== false) {
                $pdo->rollBack();
                return ['status' => 'conflito_email'];
            }

            $atualizarJogador = $pdo->prepare(
                'UPDATE jogador SET email = :email, email_verificado = 1 WHERE id = :id'
            );
            $atualizarJogador->execute([
                'email' => normalizar_email($emailPendente),
                'id' => (int) $verificacao['jogador_id'],
            ]);
        } else {
            $atualizarJogador = $pdo->prepare(
                'UPDATE jogador SET email_verificado = 1 WHERE id = :id'
            );
            $atualizarJogador->execute(['id' => (int) $verificacao['jogador_id']]);
        }

        $utilizar = $pdo->prepare(
            'UPDATE verificacao_email SET utilizado_em = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $utilizar->execute(['id' => (int) $verificacao['id']]);
        $pdo->commit();

        return ['status' => 'ok'];
    } catch (PDOException $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($erro->getCode() === '23000') {
            return ['status' => 'conflito_email'];
        }
        throw $erro;
    }
}

/**
 * @param array{cpf?: mixed, email?: mixed, nome_usuario?: mixed, senha?: mixed} $dados
 * @return array{ok: bool, erros: array<string, string>, token?: string}
 */
function atualizar_conta_jogador(PDO $pdo, int $jogadorId, array $dados): array
{
    $atual = buscar_jogador_por_id($pdo, $jogadorId);
    if ($atual === null) {
        return ['ok' => false, 'erros' => ['conta' => 'Conta não encontrada.']];
    }

    $erros = validar_dados_conta($dados, false);
    $cpf = somente_digitos(is_string($dados['cpf'] ?? null) ? $dados['cpf'] : '');
    $email = normalizar_email(is_string($dados['email'] ?? null) ? $dados['email'] : '');
    $nome = trim(is_string($dados['nome_usuario'] ?? null) ? $dados['nome_usuario'] : '');
    $senha = is_string($dados['senha'] ?? null) ? $dados['senha'] : '';

    $erros += erros_unicidade_conta($pdo, $cpf, $email, $jogadorId);
    if ($erros !== []) {
        return ['ok' => false, 'erros' => $erros];
    }

    $emailAlterado = !hash_equals(normalizar_email((string) $atual['email']), $email);

    try {
        $pdo->beginTransaction();
        $sql = 'UPDATE jogador SET cpf = :cpf, nome_usuario = :nome_usuario';
        $parametros = ['cpf' => $cpf, 'nome_usuario' => $nome, 'id' => $jogadorId];
        if ($senha !== '') {
            $sql .= ', senha_hash = :senha_hash';
            $parametros['senha_hash'] = password_hash($senha, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id = :id';

        $atualizar = $pdo->prepare($sql);
        $atualizar->execute($parametros);

        $token = $emailAlterado ? emitir_token_verificacao($pdo, $jogadorId, $email) : null;
        $pdo->commit();

        $resultado = ['ok' => true, 'erros' => []];
        if ($token !== null) {
            $resultado['token'] = $token;
        }
        return $resultado;
    } catch (PDOException $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($erro->getCode() === '23000') {
            $erros = erros_unicidade_conta($pdo, $cpf, $email, $jogadorId);
            return ['ok' => false, 'erros' => $erros ?: ['conta' => 'CPF ou e-mail já cadastrado.']];
        }
        throw $erro;
    }
}
