<?php

declare(strict_types=1);

use ZebraPuzzle\Core\Router;

return static function (Router $router): void {
    $lerDadosConta = static function (): array {
        return [
            'cpf' => is_string($_POST['cpf'] ?? null) ? $_POST['cpf'] : '',
            'email' => is_string($_POST['email'] ?? null) ? $_POST['email'] : '',
            'nome_usuario' => is_string($_POST['nome_usuario'] ?? null) ? $_POST['nome_usuario'] : '',
            'senha' => is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '',
        ];
    };

    $router->get('/', static function (): void {
        renderizar('home', ['titulo' => 'Início']);
    });

    $router->get('/cadastro', static function (): void {
        $formulario = formulario_consumir('cadastro');
        renderizar('cadastro', [
            'titulo' => 'Criar conta',
            'dados' => $formulario['dados'],
            'erros' => $formulario['erros'],
            'linkVerificacao' => link_verificacao_desenvolvimento_consumir(),
        ]);
    });

    $router->post('/cadastro', static function () use ($lerDadosConta): void {
        csrf_exigir_valido();
        $dados = $lerDadosConta();
        $resultado = criar_jogador_pendente(db(), $dados);

        if (!$resultado['ok']) {
            unset($dados['senha']);
            formulario_guardar('cadastro', $dados, $resultado['erros']);
            flash_adicionar('erro', 'Revise os campos indicados.');
            redirecionar('/cadastro');
        }

        link_verificacao_desenvolvimento_guardar((string) $resultado['token']);
        flash_adicionar('sucesso', 'Cadastro recebido. Verifique seu e-mail antes de entrar.');
        redirecionar('/cadastro');
    });

    $router->get('/verificar-email', static function (): void {
        $token = is_string($_GET['token'] ?? null) ? $_GET['token'] : '';
        if ($token === '') {
            $token = token_verificacao_desenvolvimento_consumir() ?? '';
        }
        $resultado = verificar_token_email(db(), $token);
        $mensagens = [
            'ok' => ['sucesso', 'E-mail verificado. Sua conta já pode ser utilizada.'],
            'expirado' => ['aviso', 'O link expirou. Solicite uma nova verificação.'],
            'utilizado' => ['aviso', 'Este link já foi utilizado ou substituído por um reenvio.'],
            'conflito_email' => ['erro', 'O novo e-mail não está mais disponível. Informe outro endereço na sua conta.'],
            'invalido' => ['erro', 'O link de verificação é inválido.'],
        ];
        [$tipo, $mensagem] = $mensagens[$resultado['status']] ?? $mensagens['invalido'];

        renderizar('verificacao-email', [
            'titulo' => 'Verificação de e-mail',
            'tipoResultado' => $tipo,
            'mensagemResultado' => $mensagem,
        ]);
    });

    $router->get('/reenviar-verificacao', static function (): void {
        $formulario = formulario_consumir('reenviar-verificacao');
        renderizar('reenviar-verificacao', [
            'titulo' => 'Reenviar verificação',
            'dados' => $formulario['dados'],
            'erros' => $formulario['erros'],
            'linkVerificacao' => link_verificacao_desenvolvimento_consumir(),
        ]);
    });

    $router->post('/reenviar-verificacao', static function (): void {
        csrf_exigir_valido();
        $email = is_string($_POST['email'] ?? null) ? normalizar_email($_POST['email']) : '';
        if (!email_valido($email)) {
            formulario_guardar(
                'reenviar-verificacao',
                ['email' => $email],
                ['email' => 'Informe um e-mail válido.']
            );
            flash_adicionar('erro', 'Revise o campo indicado.');
            redirecionar('/reenviar-verificacao');
        }

        $token = reenviar_token_verificacao(db(), $email);
        if ($token !== null) {
            link_verificacao_desenvolvimento_guardar($token);
        }
        flash_adicionar(
            'sucesso',
            'Se existir uma conta pendente para esse e-mail, uma nova verificação foi gerada.'
        );
        redirecionar('/reenviar-verificacao');
    });

    $router->get('/login', static function (): void {
        if (usuario_logado()) {
            redirecionar(usuario_tipo() === 'administrador' ? '/area-admin' : '/area-jogador');
        }

        $formulario = formulario_consumir('login');
        renderizar('login', [
            'titulo' => 'Entrar',
            'dados' => $formulario['dados'],
            'erros' => $formulario['erros'],
        ]);
    });

    $router->post('/login', static function (): void {
        csrf_exigir_valido();
        login_pendente_limpar();
        $tipo = is_string($_POST['tipo'] ?? null) ? $_POST['tipo'] : '';
        $email = is_string($_POST['email'] ?? null) ? normalizar_email($_POST['email']) : '';
        $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';
        $erros = [];

        if (!in_array($tipo, ['jogador', 'administrador'], true)) {
            $erros['tipo'] = 'Selecione o tipo de conta.';
        }
        if (!email_valido($email)) {
            $erros['email'] = 'Informe um e-mail válido.';
        }
        if ($senha === '') {
            $erros['senha'] = 'Informe a senha.';
        }

        if ($erros === []) {
            $resultado = autenticar_credenciais(db(), $tipo, $email, $senha);
            if ($resultado['status'] === 'ok') {
                $conta = $resultado['conta'];
                $id = (int) $conta['id'];

                if ($tipo === 'administrador') {
                    autenticar_usuario($id, 'administrador');
                    flash_adicionar('sucesso', 'Login administrativo realizado.');
                    redirecionar('/area-admin');
                }

                if (jogador_possui_acesso_hoje(db(), $id)) {
                    autenticar_usuario($id, 'jogador');
                    flash_adicionar('sucesso', 'Login realizado. O captcha de hoje já estava validado.');
                    redirecionar('/area-jogador');
                }

                login_pendente_definir($id);
                redirecionar('/login/captcha');
            }
        }

        formulario_guardar('login', ['tipo' => $tipo, 'email' => $email], $erros);
        flash_adicionar(
            'erro',
            'Não foi possível entrar. Verifique as credenciais e a situação da conta.'
        );
        redirecionar('/login');
    });

    $router->get('/login/captcha', static function (): void {
        if (login_pendente_id() === null) {
            flash_adicionar('aviso', 'Inicie novamente o login do jogador.');
            redirecionar('/login');
        }

        renderizar('captcha-login', ['titulo' => 'Confirmação diária']);
    });

    $router->get('/captcha/imagem', static function (): void {
        if (login_pendente_id() === null) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Login pendente não encontrado.';
            return;
        }

        captcha_renderizar_imagem(captcha_criar());
    });

    $router->post('/login/captcha', static function (): void {
        csrf_exigir_valido();
        $jogadorId = login_pendente_id();
        if ($jogadorId === null) {
            flash_adicionar('aviso', 'A confirmação expirou. Inicie novamente o login.');
            redirecionar('/login');
        }

        $resposta = is_string($_POST['captcha'] ?? null) ? $_POST['captcha'] : null;
        if (!captcha_validar($resposta)) {
            flash_adicionar('erro', 'Captcha incorreto ou expirado. Uma nova imagem foi gerada.');
            redirecionar('/login/captcha');
        }

        $jogador = buscar_jogador_por_id(db(), $jogadorId);
        if ($jogador === null || (int) $jogador['ativo'] !== 1 || (int) $jogador['email_verificado'] !== 1) {
            login_pendente_limpar();
            flash_adicionar('erro', 'Não foi possível concluir o login.');
            redirecionar('/login');
        }

        registrar_acesso_diario(db(), $jogadorId);
        autenticar_usuario($jogadorId, 'jogador');
        flash_adicionar('sucesso', 'Captcha diário validado e login realizado.');
        redirecionar('/area-jogador');
    });

    $router->post('/logout', static function (): void {
        csrf_exigir_valido();
        encerrar_sessao_usuario();
        flash_adicionar('sucesso', 'Sessão encerrada.');
        redirecionar('/');
    });

    $router->get('/minha-conta', static function (): void {
        exigir_login_jogador();
        $pdo = db();
        $jogador = buscar_jogador_por_id($pdo, (int) usuario_id());
        if ($jogador === null) {
            throw new RuntimeException('Conta autenticada não encontrada.');
        }

        $formulario = formulario_consumir('minha-conta');
        $dados = $formulario['dados'] ?: [
            'cpf' => $jogador['cpf'],
            'email' => $jogador['email'],
            'nome_usuario' => $jogador['nome_usuario'],
        ];

        renderizar('minha-conta', [
            'titulo' => 'Minha conta',
            'dados' => $dados,
            'erros' => $formulario['erros'],
            'emailAtual' => (string) $jogador['email'],
            'emailPendente' => buscar_email_pendente($pdo, (int) $jogador['id']),
            'linkVerificacao' => link_verificacao_desenvolvimento_consumir(),
            'temas' => listar_temas_elegiveis($pdo),
            'temaEfetivo' => carregar_tema_efetivo($pdo, (int) $jogador['id']),
            'temaPreferidoId' => $jogador['tema_preferido_id'],
        ]);
    });

    $router->post('/minha-conta', static function () use ($lerDadosConta): void {
        exigir_login_jogador();
        csrf_exigir_valido();
        $dados = $lerDadosConta();
        $resultado = atualizar_conta_jogador(db(), (int) usuario_id(), $dados);

        if (!$resultado['ok']) {
            unset($dados['senha']);
            formulario_guardar('minha-conta', $dados, $resultado['erros']);
            flash_adicionar('erro', 'Revise os campos indicados.');
            redirecionar('/minha-conta');
        }

        if (isset($resultado['token'])) {
            link_verificacao_desenvolvimento_guardar((string) $resultado['token']);
            flash_adicionar(
                'sucesso',
                'Os demais dados foram atualizados. O e-mail atual será mantido até a confirmação do novo endereço.'
            );
        } else {
            flash_adicionar('sucesso', 'Conta atualizada.');
        }
        redirecionar('/minha-conta');
    });

    $router->post('/minha-conta/tema', static function (): void {
        exigir_login_jogador();
        csrf_exigir_valido();

        $resultado = alterar_tema_jogador(
            db(),
            (int) usuario_id(),
            $_POST['tema_id'] ?? null
        );
        if (!$resultado['ok']) {
            flash_adicionar('erro', (string) ($resultado['erro'] ?? 'Não foi possível alterar o tema.'));
            redirecionar('/minha-conta');
        }

        flash_adicionar('sucesso', 'Tema alterado para ' . (string) $resultado['tema']['nome'] . '.');
        redirecionar('/minha-conta');
    });

    $router->get('/area-jogador', static function (): void {
        exigir_login_jogador();
        $pdo = db();
        $statement = $pdo->query(
            'SELECT DATABASE() AS banco, CURRENT_DATE() AS data_atual, @@session.time_zone AS fuso'
        );
        $dadosBanco = $statement->fetch();
        $jogador = buscar_jogador_por_id($pdo, (int) usuario_id());

        renderizar('area-jogador', [
            'titulo' => 'Área do jogador',
            'dadosBanco' => is_array($dadosBanco) ? $dadosBanco : [],
            'jogador' => $jogador ?? [],
            'temaEfetivo' => carregar_tema_efetivo($pdo, (int) usuario_id()),
        ]);
    });

    $router->post('/area-jogador/flash', static function (): void {
        exigir_login_jogador();
        csrf_exigir_valido();
        flash_adicionar('sucesso', 'POST recebido, token CSRF validado e redirecionamento concluído.');
        redirecionar('/area-jogador');
    });

    $router->get('/area-admin', static function (): void {
        exigir_login_admin();
        $statement = db()->prepare(
            'SELECT nome_usuario, email FROM administrador WHERE id = :id AND ativo = 1'
        );
        $statement->execute(['id' => (int) usuario_id()]);
        $administrador = $statement->fetch();

        renderizar('area-admin', [
            'titulo' => 'Área administrativa',
            'administrador' => is_array($administrador) ? $administrador : [],
        ]);
    });
};
