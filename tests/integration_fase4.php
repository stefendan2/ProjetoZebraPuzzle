<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/bootstrap.php';

if ((string) getenv('DB_PASSWORD') === '') {
    fwrite(STDOUT, 'Senha do usuário MariaDB zebrapuzzle_app: ');
    $senhaBanco = fgets(STDIN);
    putenv('DB_PASSWORD=' . ($senhaBanco === false ? '' : rtrim($senhaBanco, "\r\n")));
    unset($senhaBanco);
}

$falhas = [];
$idsJogadores = [];
$idsAdministradores = [];
$verificar = static function (bool $condicao, string $descricao) use (&$falhas): void {
    if (!$condicao) {
        $falhas[] = $descricao;
    }
};

$gerarCpf = static function (): string {
    do {
        $base = '';
        for ($indice = 0; $indice < 9; $indice++) {
            $base .= (string) random_int(0, 9);
        }
    } while (preg_match('/^(\d)\1{8}$/', $base) === 1);

    for ($tamanho = 9; $tamanho < 11; $tamanho++) {
        $soma = 0;
        for ($indice = 0; $indice < $tamanho; $indice++) {
            $soma += ((int) $base[$indice]) * (($tamanho + 1) - $indice);
        }
        $digito = (10 * $soma) % 11;
        $base .= (string) ($digito === 10 ? 0 : $digito);
    }

    return $base;
};

try {
    $pdo = db();
    $coluna = $pdo->query("SHOW COLUMNS FROM verificacao_email LIKE 'email_pendente'")->fetch();
    if (!is_array($coluna)) {
        throw new RuntimeException(
            'A migração sql/migrations/004_fase4_conta_acesso.sql ainda não foi aplicada.'
        );
    }

    $sufixo = bin2hex(random_bytes(6));
    $senha = 'SenhaTeste!123';
    $email = "fase4-{$sufixo}@example.test";
    $novoEmail = "fase4-novo-{$sufixo}@example.test";
    $cpf = $gerarCpf();

    $cadastro = criar_jogador_pendente($pdo, [
        'cpf' => $cpf,
        'email' => $email,
        'nome_usuario' => 'Teste Fase 4',
        'senha' => $senha,
    ]);
    $verificar($cadastro['ok'], 'O cadastro válido deve criar uma conta pendente.');
    if (!$cadastro['ok']) {
        throw new RuntimeException('Não foi possível preparar a conta de integração.');
    }

    $jogador = buscar_jogador_por_email($pdo, $email);
    $idsJogadores[] = (int) ($jogador['id'] ?? 0);
    $verificar(is_array($jogador), 'O jogador criado deve ser localizado.');
    $verificar((int) ($jogador['email_verificado'] ?? 1) === 0, 'A conta nova deve começar não verificada.');
    $verificar(($jogador['senha_hash'] ?? '') !== $senha, 'A senha em texto puro não pode ser armazenada.');
    $verificar(password_verify($senha, (string) ($jogador['senha_hash'] ?? '')), 'O hash deve validar a senha original.');
    $verificar(
        autenticar_credenciais($pdo, 'jogador', $email, $senha)['status'] === 'nao_verificado',
        'O jogador não verificado deve ser impedido de entrar.'
    );
    $duplicadoCpf = criar_jogador_pendente($pdo, [
        'cpf' => $cpf,
        'email' => "cpf-duplicado-{$sufixo}@example.test",
        'nome_usuario' => 'CPF repetido',
        'senha' => $senha,
    ]);
    $verificar(!$duplicadoCpf['ok'] && isset($duplicadoCpf['erros']['cpf']), 'O cadastro deve rejeitar CPF repetido.');
    $duplicadoEmail = criar_jogador_pendente($pdo, [
        'cpf' => $gerarCpf(),
        'email' => $email,
        'nome_usuario' => 'E-mail repetido',
        'senha' => $senha,
    ]);
    $verificar(!$duplicadoEmail['ok'] && isset($duplicadoEmail['erros']['email']), 'O cadastro deve rejeitar e-mail repetido.');

    $primeiroToken = (string) $cadastro['token'];
    $tokenArmazenado = $pdo->prepare(
        'SELECT token_hash FROM verificacao_email WHERE jogador_id = :id ORDER BY id DESC LIMIT 1'
    );
    $tokenArmazenado->execute(['id' => (int) $jogador['id']]);
    $hashBanco = (string) $tokenArmazenado->fetchColumn();
    $verificar($hashBanco === hash('sha256', $primeiroToken), 'O banco deve guardar somente o hash do token.');
    $verificar($hashBanco !== $primeiroToken, 'O token bruto não pode ser armazenado.');
    $verificar(verificar_token_email($pdo, str_repeat('0', 64))['status'] === 'invalido', 'Token incorreto deve falhar.');

    $segundoToken = reenviar_token_verificacao($pdo, $email);
    $verificar(is_string($segundoToken), 'O reenvio deve gerar outro token para a conta pendente.');
    $verificar(verificar_token_email($pdo, $primeiroToken)['status'] === 'utilizado', 'O reenvio deve invalidar o token anterior.');
    $verificar(verificar_token_email($pdo, (string) $segundoToken)['status'] === 'ok', 'O token mais recente deve verificar a conta.');
    $verificar(verificar_token_email($pdo, (string) $segundoToken)['status'] === 'utilizado', 'O token não pode ser reutilizado.');

    $verificar(autenticar_credenciais($pdo, 'jogador', $email, 'errada')['status'] === 'invalido', 'Senha incorreta deve falhar.');
    $verificar(autenticar_credenciais($pdo, 'jogador', "inexistente-{$sufixo}@example.test", $senha)['status'] === 'invalido', 'E-mail inexistente deve falhar.');
    $verificar(autenticar_credenciais($pdo, 'jogador', "' OR 1=1 --@example.test", $senha)['status'] === 'invalido', 'Entrada semelhante a SQL não pode autenticar.');
    $verificar(autenticar_credenciais($pdo, 'jogador', $email, $senha)['status'] === 'ok', 'A conta verificada deve entrar.');
    $desativar = $pdo->prepare('UPDATE jogador SET ativo = 0 WHERE id = :id');
    $desativar->execute(['id' => (int) $jogador['id']]);
    $verificar(autenticar_credenciais($pdo, 'jogador', $email, $senha)['status'] === 'invalido', 'Jogador inativo deve ser impedido de entrar.');
    $reativar = $pdo->prepare('UPDATE jogador SET ativo = 1 WHERE id = :id');
    $reativar->execute(['id' => (int) $jogador['id']]);

    $acessoAnterior = $pdo->prepare(
        'INSERT INTO acesso_diario (jogador_id, dia, captcha_validado_em)
         VALUES (:id, DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY), DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY))'
    );
    $acessoAnterior->execute(['id' => (int) $jogador['id']]);
    $verificar(!jogador_possui_acesso_hoje($pdo, (int) $jogador['id']), 'O primeiro acesso do dia deve exigir captcha.');
    registrar_acesso_diario($pdo, (int) $jogador['id']);
    registrar_acesso_diario($pdo, (int) $jogador['id']);
    $verificar(jogador_possui_acesso_hoje($pdo, (int) $jogador['id']), 'O acesso diário deve dispensar novo captcha no mesmo dia.');

    $hashAntes = (string) $jogador['senha_hash'];
    $edicaoSemSenha = atualizar_conta_jogador($pdo, (int) $jogador['id'], [
        'cpf' => $cpf,
        'email' => $email,
        'nome_usuario' => 'Nome atualizado',
        'senha' => '',
    ]);
    $depoisSemSenha = buscar_jogador_por_id($pdo, (int) $jogador['id']);
    $verificar($edicaoSemSenha['ok'], 'A conta deve aceitar alteração válida com senha vazia.');
    $verificar((string) $depoisSemSenha['senha_hash'] === $hashAntes, 'Senha vazia deve preservar o hash atual.');

    $novaSenha = 'OutraSenha!456';
    $edicaoEmail = atualizar_conta_jogador($pdo, (int) $jogador['id'], [
        'cpf' => $cpf,
        'email' => $novoEmail,
        'nome_usuario' => 'Nome atualizado',
        'senha' => $novaSenha,
    ]);
    $antesDaConfirmacao = buscar_jogador_por_id($pdo, (int) $jogador['id']);
    $verificar($edicaoEmail['ok'] && isset($edicaoEmail['token']), 'A troca de e-mail deve gerar verificação.');
    $verificar((string) $antesDaConfirmacao['email'] === $email, 'O e-mail atual deve permanecer antes da confirmação.');
    $verificar(password_verify($novaSenha, (string) $antesDaConfirmacao['senha_hash']), 'A nova senha deve gerar um hash válido.');
    $verificar(verificar_token_email($pdo, (string) $edicaoEmail['token'])['status'] === 'ok', 'O novo e-mail deve ser confirmado pelo token.');
    $depoisDaConfirmacao = buscar_jogador_por_id($pdo, (int) $jogador['id']);
    $verificar((string) $depoisDaConfirmacao['email'] === $novoEmail, 'O novo e-mail deve substituir o anterior após confirmação.');

    $tokenExpirado = emitir_token_verificacao($pdo, (int) $jogador['id']);
    $expirar = $pdo->prepare(
        'UPDATE verificacao_email SET expira_em = DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 SECOND)
         WHERE token_hash = :token_hash'
    );
    $expirar->execute(['token_hash' => hash('sha256', $tokenExpirado)]);
    $verificar(verificar_token_email($pdo, $tokenExpirado)['status'] === 'expirado', 'Token expirado deve falhar.');

    $cpfOutro = $gerarCpf();
    $emailOutro = "fase4-outro-{$sufixo}@example.test";
    $outroCadastro = criar_jogador_pendente($pdo, [
        'cpf' => $cpfOutro,
        'email' => $emailOutro,
        'nome_usuario' => 'Outro jogador',
        'senha' => $senha,
    ]);
    $outro = buscar_jogador_por_email($pdo, $emailOutro);
    $idsJogadores[] = (int) ($outro['id'] ?? 0);
    $verificar($outroCadastro['ok'], 'A segunda conta de teste deve ser criada.');
    $duplicado = atualizar_conta_jogador($pdo, (int) $jogador['id'], [
        'cpf' => $cpfOutro,
        'email' => $novoEmail,
        'nome_usuario' => 'Nome atualizado',
        'senha' => '',
    ]);
    $verificar(!$duplicado['ok'] && isset($duplicado['erros']['cpf']), 'A edição deve rejeitar CPF de outro jogador.');
    $emailDuplicado = atualizar_conta_jogador($pdo, (int) $jogador['id'], [
        'cpf' => $cpf,
        'email' => $emailOutro,
        'nome_usuario' => 'Nome atualizado',
        'senha' => '',
    ]);
    $verificar(!$emailDuplicado['ok'] && isset($emailDuplicado['erros']['email']), 'A edição deve rejeitar e-mail de outro jogador.');

    $emailAdmin = "fase4-admin-{$sufixo}@example.test";
    $inserirAdmin = $pdo->prepare(
        'INSERT INTO administrador (nome_usuario, email, senha_hash)
         VALUES (:nome, :email, :hash)'
    );
    $inserirAdmin->execute([
        'nome' => 'Admin de teste',
        'email' => $emailAdmin,
        'hash' => password_hash($senha, PASSWORD_DEFAULT),
    ]);
    $idsAdministradores[] = (int) $pdo->lastInsertId();
    $totalAcessosAntes = (int) $pdo->query('SELECT COUNT(*) FROM acesso_diario')->fetchColumn();
    $verificar(autenticar_credenciais($pdo, 'administrador', $emailAdmin, $senha)['status'] === 'ok', 'Administrador deve autenticar sem captcha.');
    $verificar(autenticar_credenciais($pdo, 'jogador', $emailAdmin, $senha)['status'] === 'invalido', 'Administrador não pode ser autenticado como jogador.');
    $verificar(autenticar_credenciais($pdo, 'administrador', $novoEmail, $novaSenha)['status'] === 'invalido', 'Jogador não pode ser autenticado como administrador.');
    $totalAcessosDepois = (int) $pdo->query('SELECT COUNT(*) FROM acesso_diario')->fetchColumn();
    $verificar($totalAcessosAntes === $totalAcessosDepois, 'Login administrativo não pode criar acesso diário.');
} catch (Throwable $erro) {
    $falhas[] = $erro->getMessage();
} finally {
    if (isset($pdo) && $pdo instanceof PDO) {
        foreach (array_filter($idsAdministradores) as $id) {
            $statement = $pdo->prepare('DELETE FROM administrador WHERE id = :id');
            $statement->execute(['id' => $id]);
        }
        foreach (array_filter($idsJogadores) as $id) {
            $statement = $pdo->prepare('DELETE FROM jogador WHERE id = :id');
            $statement->execute(['id' => $id]);
        }
    }
    putenv('DB_PASSWORD');
}

if ($falhas !== []) {
    fwrite(STDERR, "Falhas de integração:\n- " . implode("\n- ", $falhas) . "\n");
    exit(1);
}

fwrite(STDOUT, "Todas as verificações de integração da Fase 4 passaram.\n");
