<?php

declare(strict_types=1);

/** @return array{status: string, conta?: array<string, mixed>} */
function autenticar_credenciais(PDO $pdo, string $tipo, string $email, string $senha): array
{
    if (!in_array($tipo, ['jogador', 'administrador'], true)
        || !email_valido(normalizar_email($email))
        || $senha === ''
    ) {
        return ['status' => 'invalido'];
    }

    $conta = $tipo === 'jogador'
        ? buscar_jogador_por_email($pdo, $email)
        : buscar_administrador_por_email($pdo, $email);

    if ($conta === null) {
        password_verify(
            $senha,
            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'
        );
        return ['status' => 'invalido'];
    }
    if (!password_verify($senha, (string) $conta['senha_hash'])) {
        return ['status' => 'invalido'];
    }
    if ((int) $conta['ativo'] !== 1) {
        return ['status' => 'invalido'];
    }
    if ($tipo === 'jogador' && (int) $conta['email_verificado'] !== 1) {
        return ['status' => 'nao_verificado'];
    }

    if (password_needs_rehash((string) $conta['senha_hash'], PASSWORD_DEFAULT)) {
        $sql = $tipo === 'jogador'
            ? 'UPDATE jogador SET senha_hash = :senha_hash WHERE id = :id'
            : 'UPDATE administrador SET senha_hash = :senha_hash WHERE id = :id';
        $atualizar = $pdo->prepare($sql);
        $atualizar->execute([
            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
            'id' => (int) $conta['id'],
        ]);
    }

    unset($conta['senha_hash']);
    return ['status' => 'ok', 'conta' => $conta];
}

function jogador_possui_acesso_hoje(PDO $pdo, int $jogadorId): bool
{
    $statement = $pdo->prepare(
        'SELECT 1 FROM acesso_diario
         WHERE jogador_id = :jogador_id AND dia = CURRENT_DATE()
         LIMIT 1'
    );
    $statement->execute(['jogador_id' => $jogadorId]);

    return $statement->fetchColumn() !== false;
}

function registrar_acesso_diario(PDO $pdo, int $jogadorId): void
{
    $statement = $pdo->prepare(
        'INSERT INTO acesso_diario (jogador_id, dia, captcha_validado_em)
         VALUES (:jogador_id, CURRENT_DATE(), CURRENT_TIMESTAMP)
         ON DUPLICATE KEY UPDATE jogador_id = VALUES(jogador_id)'
    );
    $statement->execute(['jogador_id' => $jogadorId]);
}
