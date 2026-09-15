<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if ((string) getenv('DB_PASSWORD') === '') {
    fwrite(STDOUT, 'Senha do usuário MariaDB zebrapuzzle_app: ');
    $senhaBanco = fgets(STDIN);
    putenv('DB_PASSWORD=' . ($senhaBanco === false ? '' : rtrim($senhaBanco, "\r\n")));
    unset($senhaBanco);
}
if ((string) getenv('DB_PASSWORD') === '') {
    fwrite(STDERR, "Nenhuma senha foi recebida. Execute novamente e digite a senha local quando solicitada.\n");
    exit(2);
}

require dirname(__DIR__) . '/src/bootstrap.php';

$falhas = [];
$idsDesafios = [];
$idsJogadores = [];
$emailsJogadores = [];
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
    $fixture = require dirname(__DIR__) . '/resources/desafios/exemplo.php';
    $sufixo = bin2hex(random_bytes(5));

    $relacoes = $pdo->query(
        "SELECT codigo, aridade FROM relacao_dica WHERE codigo IN ('CERT','EQ','M1','PM1') ORDER BY codigo"
    )->fetchAll();
    $verificar(count($relacoes) === 4, 'O seed deve conter as quatro relações da Fase 6.');

    $categoriasOficiais = $pdo->query(
        "SELECT tc.posicao, tc.prefixo
         FROM tema_categoria tc
         INNER JOIN tema t ON t.id = tc.tema_id
         WHERE t.nome IN ('Clássico','Campus','Exploração espacial')"
    )->fetchAll();
    $verificar(count($categoriasOficiais) === 15, 'Os três temas oficiais devem manter 15 categorias.');
    foreach ($categoriasOficiais as $categoria) {
        $verificar((int) $categoria['posicao'] >= 0 && (int) $categoria['posicao'] <= 4, 'Categoria oficial deve usar posição 0..4.');
        $verificar(trim((string) $categoria['prefixo']) !== '', 'Categoria oficial deve possuir prefixo.');
    }

    $baseData = new DateTimeImmutable('1900-01-01');
    do {
        $diaPrincipal = $baseData->modify('+' . random_int(0, 20000) . ' days')->format('Y-m-d');
        $diaFalhaAtribuicao = (new DateTimeImmutable($diaPrincipal))->modify('+1 day')->format('Y-m-d');
        $diaFalhaDica = (new DateTimeImmutable($diaPrincipal))->modify('+2 days')->format('Y-m-d');
        $diaInvalido = (new DateTimeImmutable($diaPrincipal))->modify('+3 days')->format('Y-m-d');
        $diasLivres = true;
        foreach ([$diaPrincipal, $diaFalhaAtribuicao, $diaFalhaDica, $diaInvalido] as $diaCandidato) {
            if (carregar_desafio_por_dia($pdo, $diaCandidato) !== null) {
                $diasLivres = false;
                break;
            }
        }
    } while (!$diasLivres);

    $desafio = persistir_desafio_gerado($pdo, $diaPrincipal, $fixture);
    $idsDesafios[] = (int) $desafio['id'];
    $verificar(count($desafio['solucao']) === 25, 'A persistência deve recarregar 25 atribuições.');
    $verificar(count($desafio['dicas']) === 15, 'A persistência deve recarregar 15 dicas ordenadas.');
    $verificar(array_column($desafio['dicas'], 'ordem') === range(1, 15), 'As dicas devem ser recarregadas em ordem determinística.');

    $contagens = $pdo->prepare(
        'SELECT
            (SELECT COUNT(*) FROM desafio_atribuicao WHERE desafio_id = :id1) AS atribuicoes,
            (SELECT COUNT(*) FROM desafio_dica WHERE desafio_id = :id2) AS dicas,
            (SELECT solucao_json IS NULL AND pistas_json IS NULL FROM desafio_diario WHERE id = :id3) AS json_legado_nulo'
    );
    $contagens->execute(['id1' => $desafio['id'], 'id2' => $desafio['id'], 'id3' => $desafio['id']]);
    $linhaContagens = $contagens->fetch();
    $verificar((int) $linhaContagens['atribuicoes'] === 25, 'O banco deve conter 25 atribuições.');
    $verificar((int) $linhaContagens['dicas'] === 15, 'O banco deve conter 15 dicas.');
    $verificar((int) $linhaContagens['json_legado_nulo'] === 1, 'As colunas JSON legadas não devem ser fonte duplicada.');

    $repetido = persistir_desafio_gerado($pdo, $diaPrincipal, $fixture);
    $verificar((int) $repetido['id'] === (int) $desafio['id'], 'Provisionamento repetido deve conservar o mesmo ID.');
    $quantidadeDia = $pdo->prepare('SELECT COUNT(*) FROM desafio_diario WHERE dia = :dia');
    $quantidadeDia->execute(['dia' => $diaPrincipal]);
    $verificar((int) $quantidadeDia->fetchColumn() === 1, 'A chave diária deve deixar uma única linha após tentativa concorrente/repetida.');

    try {
        persistir_desafio_gerado(
            $pdo,
            $diaFalhaAtribuicao,
            $fixture,
            static function (string $etapa, int $numero): void {
                if ($etapa === 'atribuicao' && $numero === 10) {
                    throw new RuntimeException('Falha controlada em atribuição.');
                }
            }
        );
        $verificar(false, 'A falha controlada em atribuições deveria lançar exceção.');
    } catch (RuntimeException $erro) {
        $verificar($erro->getMessage() === 'Falha controlada em atribuição.', 'A falha controlada em atribuição deve ser propagada.');
    }
    $quantidadeDia->execute(['dia' => $diaFalhaAtribuicao]);
    $verificar((int) $quantidadeDia->fetchColumn() === 0, 'Falha no meio das atribuições deve desfazer o desafio inteiro.');

    try {
        persistir_desafio_gerado(
            $pdo,
            $diaFalhaDica,
            $fixture,
            static function (string $etapa, int $numero): void {
                if ($etapa === 'dica' && $numero === 5) {
                    throw new RuntimeException('Falha controlada em dica.');
                }
            }
        );
        $verificar(false, 'A falha controlada em dicas deveria lançar exceção.');
    } catch (RuntimeException $erro) {
        $verificar($erro->getMessage() === 'Falha controlada em dica.', 'A falha controlada em dica deve ser propagada.');
    }
    $quantidadeDia->execute(['dia' => $diaFalhaDica]);
    $verificar((int) $quantidadeDia->fetchColumn() === 0, 'Falha no meio das dicas deve desfazer o desafio inteiro.');

    $fixtureInvalida = $fixture;
    array_pop($fixtureInvalida['solucao']);
    try {
        persistir_desafio_gerado($pdo, $diaInvalido, $fixtureInvalida);
        $verificar(false, 'Uma fixture incompleta deveria ser rejeitada.');
    } catch (InvalidArgumentException) {
        $verificar(true, 'Fixture incompleta rejeitada antes da transação.');
    }
    $quantidadeDia->execute(['dia' => $diaInvalido]);
    $verificar((int) $quantidadeDia->fetchColumn() === 0, 'Fixture inválida não deve criar linha diária.');

    $temas = [];
    foreach (['Clássico', 'Campus'] as $nomeTema) {
        $buscarTema = $pdo->prepare('SELECT id FROM tema WHERE nome = :nome LIMIT 1');
        $buscarTema->execute(['nome' => $nomeTema]);
        $temaId = $buscarTema->fetchColumn();
        if ($temaId === false) {
            throw new RuntimeException("O tema oficial {$nomeTema} não foi encontrado.");
        }
        $tema = carregar_tema_completo($pdo, (int) $temaId, true);
        if ($tema === null) {
            throw new RuntimeException("O tema oficial {$nomeTema} não está elegível.");
        }
        $temas[] = $tema;
    }
    $frasesClassico = array_column(renderizar_dicas_para_tema($desafio['dicas'], $temas[0]), 'frase');
    $frasesCampus = array_column(renderizar_dicas_para_tema($desafio['dicas'], $temas[1]), 'frase');
    $verificar($frasesClassico !== $frasesCampus, 'Dois temas devem produzir textos diferentes para o mesmo desafio abstrato.');
    $verificar((int) carregar_desafio_por_dia($pdo, $diaPrincipal)['id'] === (int) $desafio['id'], 'Trocar apresentação não deve criar outro desafio.');

    $senha = 'T6!' . bin2hex(random_bytes(10)) . 'aA1';
    foreach ([0, 1] as $indiceJogador) {
        $email = "fase6-{$sufixo}-{$indiceJogador}@example.test";
        $emailsJogadores[] = $email;
        $cadastro = criar_jogador_pendente($pdo, [
            'cpf' => $gerarCpf(),
            'email' => $email,
            'nome_usuario' => "Jogador Fase 6 {$indiceJogador}",
            'senha' => $senha,
        ]);
        if (!$cadastro['ok']) {
            throw new RuntimeException('Não foi possível criar jogador temporário da Fase 6.');
        }
        $jogador = buscar_jogador_por_email($pdo, $email);
        if ($jogador === null) {
            throw new RuntimeException('Jogador temporário não foi localizado.');
        }
        $jogadorId = (int) $jogador['id'];
        $idsJogadores[] = $jogadorId;
        $atualizar = $pdo->prepare(
            'UPDATE jogador SET email_verificado = 1, tema_preferido_id = :tema_id WHERE id = :id'
        );
        $atualizar->execute(['tema_id' => (int) $temas[$indiceJogador]['id'], 'id' => $jogadorId]);
    }

    $verificar(!jogador_possui_acesso_no_dia($pdo, $idsJogadores[0], $diaPrincipal), 'Jogador sem entrada diária deve permanecer bloqueado.');
    $inserirAcesso = $pdo->prepare(
        'INSERT INTO acesso_diario (jogador_id, dia, captcha_validado_em)
         VALUES (:jogador_id, :dia, CURRENT_TIMESTAMP)'
    );
    foreach ($idsJogadores as $jogadorId) {
        $inserirAcesso->execute(['jogador_id' => $jogadorId, 'dia' => $diaPrincipal]);
        registrar_acesso_diario($pdo, $jogadorId);
    }
    $verificar(jogador_possui_acesso_no_dia($pdo, $idsJogadores[0], $diaPrincipal), 'Entrada diária registrada deve liberar o jogador.');

    $antesIncorreta = $pdo->prepare(
        'SELECT COUNT(*) FROM resolucao WHERE jogador_id = :jogador_id AND desafio_dia = :dia'
    );
    $antesIncorreta->execute(['jogador_id' => $idsJogadores[0], 'dia' => $diaPrincipal]);
    $totalAntes = (int) $antesIncorreta->fetchColumn();
    $respostaErrada = solucao_lista_para_matriz($fixture['solucao']);
    [$respostaErrada[0][0], $respostaErrada[0][1]] = [$respostaErrada[0][1], $respostaErrada[0][0]];
    $verificar(!resposta_confere_com_solucao($respostaErrada, solucao_lista_para_matriz($desafio['solucao'])), 'Resposta incorreta deve falhar sem revelar a solução.');
    $antesIncorreta->execute(['jogador_id' => $idsJogadores[0], 'dia' => $diaPrincipal]);
    $verificar((int) $antesIncorreta->fetchColumn() === $totalAntes, 'Resposta incorreta não deve inserir resolução.');

    $inicioFase6 = dia_de_referencia() . ' 12:00:00.000000';
    $fimFase6 = dia_de_referencia() . ' 12:00:02.500000';
    $tentativaFase6 = iniciar_ou_reutilizar_tentativa($pdo, $idsJogadores[0], (int) $desafio['id'],
        (int) $temas[0]['id'], static fn (): string => $inicioFase6);
    $resolucao = concluir_tentativa_e_registrar_resolucao($pdo, $idsJogadores[0], $tentativaFase6['id'],
        solucao_lista_para_matriz($desafio['solucao']), static fn (): string => $fimFase6);
    $verificar((int) $resolucao['tempo_milisegundos'] === 2500, 'O tempo deve ser calculado pelo servidor.');
    $verificar((int) $resolucao['elegivel_leaderboard'] === 0, 'BR-008: conclusão histórica permanece fora do ranking.');
    $verificar((int) $resolucao['tema_id'] === (int) $temas[0]['id'], 'A resolução deve guardar o tema realmente usado.');
    $carregada = buscar_ultima_resolucao_jogador_dia($pdo, $idsJogadores[0], $diaPrincipal);
    $verificar($carregada !== null, 'A resolução completa deve ser recuperável no histórico.');
    foreach (['jogador_id', 'desafio_dia', 'concluida_em', 'tempo_milisegundos', 'tema_id', 'elegivel_leaderboard'] as $campo) {
        $verificar(array_key_exists($campo, $carregada ?? []), "A resolução deve conter o campo obrigatório {$campo}.");
    }

    $tentativaFalhaFase6 = iniciar_ou_reutilizar_tentativa($pdo, $idsJogadores[1], (int) $desafio['id'],
        (int) $temas[1]['id'], static fn (): string => $inicioFase6);
    try {
        concluir_tentativa_e_registrar_resolucao(
            $pdo,
            $idsJogadores[1],
            $tentativaFalhaFase6['id'],
            solucao_lista_para_matriz($desafio['solucao']),
            static fn (): string => $fimFase6,
            static function (string $etapa): void {
                throw new RuntimeException('Falha controlada na resolução.');
            }
        );
        $verificar(false, 'A falha controlada da resolução deveria lançar exceção.');
    } catch (RuntimeException $erro) {
        $verificar($erro->getMessage() === 'Falha controlada na resolução.', 'A falha controlada da resolução deve ser propagada.');
    }
    $antesIncorreta->execute(['jogador_id' => $idsJogadores[1], 'dia' => $diaPrincipal]);
    $verificar((int) $antesIncorreta->fetchColumn() === 0, 'Falha em qualquer campo deve desfazer a resolução inteira.');

    $pdo->beginTransaction();
    $pdo->exec('UPDATE tema SET ativo = 0');
    $verificar(carregar_tema_efetivo($pdo, $idsJogadores[0]) === null, 'Ausência de tema válido deve retornar estado controlado.');
    $pdo->rollBack();
} catch (Throwable $erro) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $falhas[] = $erro->getMessage();
} finally {
    if (isset($pdo) && $pdo instanceof PDO) {
        foreach ($idsJogadores as $jogadorId) {
            foreach (['leaderboard', 'resolucao', 'tentativa_desafio'] as $tabela) {
                $statement = $pdo->prepare("DELETE FROM {$tabela} WHERE jogador_id = :id");
                $statement->execute(['id' => $jogadorId]);
            }
        }
        foreach ($idsJogadores as $jogadorId) {
            $statement = $pdo->prepare('DELETE FROM jogador WHERE id = :id');
            $statement->execute(['id' => $jogadorId]);
        }
        foreach ($emailsJogadores as $email) {
            $statement = $pdo->prepare('DELETE FROM jogador WHERE email = :email');
            $statement->execute(['email' => $email]);
        }
        foreach (array_unique($idsDesafios) as $desafioId) {
            $statement = $pdo->prepare('DELETE FROM desafio_diario WHERE id = :id');
            $statement->execute(['id' => $desafioId]);
        }
        foreach ([$diaFalhaAtribuicao ?? null, $diaFalhaDica ?? null, $diaInvalido ?? null] as $diaLimpeza) {
            if (is_string($diaLimpeza)) {
                $statement = $pdo->prepare('DELETE FROM desafio_diario WHERE dia = :dia');
                $statement->execute(['dia' => $diaLimpeza]);
            }
        }
    }
    putenv('DB_PASSWORD');
}

if ($falhas !== []) {
    fwrite(STDERR, "Falhas de integração da Fase 6:\n- " . implode("\n- ", $falhas) . "\n");
    exit(1);
}

fwrite(STDOUT, "Todas as verificações de integração da Fase 6 passaram.\n");
