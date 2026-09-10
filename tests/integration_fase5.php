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
if ((string) getenv('DB_PASSWORD') === '') {
    fwrite(STDERR, "Nenhuma senha foi recebida. Execute novamente e digite a senha local quando solicitada.\n");
    exit(2);
}

$falhas = [];
$idsJogadores = [];
$emailsJogadores = [];
$idsTemas = [];
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

$montarTema = static function (string $nome): array {
    $tema = [
        'nome' => $nome,
        'descricao' => 'Registro temporário da integração da Fase 5.',
        'categorias' => [],
    ];
    for ($categoria = 0; $categoria < 5; $categoria++) {
        $valores = [];
        for ($valor = 0; $valor < 5; $valor++) {
            $valores[] = [
                'nome' => "Valor {$categoria}.{$valor} de {$nome}",
                'posicao' => $valor,
            ];
        }
        $tema['categorias'][] = [
            'nome' => "Categoria {$categoria} de {$nome}",
            'prefixo' => "usa a categoria {$categoria}",
            'posicao' => $categoria,
            'valores' => $valores,
        ];
    }

    return $tema;
};

$temaDoJogador = static function (PDO $pdo, int $jogadorId): ?int {
    $statement = $pdo->prepare('SELECT tema_preferido_id FROM jogador WHERE id = :id');
    $statement->execute(['id' => $jogadorId]);
    $id = $statement->fetchColumn();
    return $id === false || $id === null ? null : (int) $id;
};

try {
    $pdo = db();
    $sufixo = bin2hex(random_bytes(5));

    foreach (['Clássico', 'Campus', 'Exploração espacial'] as $nomeOficial) {
        $buscar = $pdo->prepare('SELECT id FROM tema WHERE nome = :nome LIMIT 1');
        $buscar->execute(['nome' => $nomeOficial]);
        $idOficial = $buscar->fetchColumn();
        $verificar($idOficial !== false, "O seed deve conter o tema {$nomeOficial}.");
        if ($idOficial !== false) {
            $completo = carregar_tema_completo($pdo, (int) $idOficial, true);
            $verificar($completo !== null, "O tema oficial {$nomeOficial} deve estar ativo e completo.");
            $verificar(count($completo['categorias'] ?? []) === 5, "O tema {$nomeOficial} deve ter 5 categorias.");
            $totalValores = 0;
            foreach (($completo['categorias'] ?? []) as $categoria) {
                $totalValores += count($categoria['valores'] ?? []);
            }
            $verificar($totalValores === 25, "O tema {$nomeOficial} deve ter 25 valores.");
        }
    }

    $nomeA = "__TESTE_FASE5_{$sufixo}_A";
    $nomeB = "__TESTE_FASE5_{$sufixo}_B";
    $nomeInativo = "__TESTE_FASE5_{$sufixo}_INATIVO";
    $nomeIncompleto = "__TESTE_FASE5_{$sufixo}_INCOMPLETO";

    $resultadoA = gravar_tema_validado($pdo, $montarTema($nomeA));
    if (!$resultadoA['ok']) {
        throw new RuntimeException('Não foi possível preparar o primeiro tema completo de integração.');
    }
    $temaAId = (int) $resultadoA['tema_id'];
    $idsTemas[] = $temaAId;

    $resultadoB = gravar_tema_validado($pdo, $montarTema($nomeB));
    if (!$resultadoB['ok']) {
        throw new RuntimeException('Não foi possível preparar o segundo tema completo de integração.');
    }
    $temaBId = (int) $resultadoB['tema_id'];
    $idsTemas[] = $temaBId;

    $resultadoInativo = gravar_tema_validado($pdo, $montarTema($nomeInativo));
    if (!$resultadoInativo['ok']) {
        throw new RuntimeException('Não foi possível preparar o tema inativo de integração.');
    }
    $temaInativoId = (int) $resultadoInativo['tema_id'];
    $idsTemas[] = $temaInativoId;

    $inserirIncompleto = $pdo->prepare(
        'INSERT INTO tema (nome, descricao, ativo, criado_em)
         VALUES (:nome, :descricao, 1, :criado_em)'
    );
    $inserirIncompleto->execute([
        'nome' => $nomeIncompleto,
        'descricao' => 'Tema incompleto usado somente pelo teste de fallback.',
        'criado_em' => '1000-01-01 00:00:00',
    ]);
    $temaIncompletoId = (int) $pdo->lastInsertId();
    $idsTemas[] = $temaIncompletoId;
    $inserirCategoria = $pdo->prepare(
        'INSERT INTO tema_categoria (tema_id, nome, prefixo, posicao)
         VALUES (:tema_id, :nome, :prefixo, :posicao)'
    );
    $inserirValor = $pdo->prepare(
        'INSERT INTO tema_valor (categoria_id, nome, posicao) VALUES (:categoria_id, :nome, :posicao)'
    );
    for ($categoria = 0; $categoria < 4; $categoria++) {
        $inserirCategoria->execute([
            'tema_id' => $temaIncompletoId,
            'nome' => 'Categoria incompleta ' . $categoria,
            'prefixo' => 'usa a categoria incompleta ' . $categoria,
            'posicao' => $categoria,
        ]);
        $categoriaId = (int) $pdo->lastInsertId();
        for ($valor = 0; $valor < 5; $valor++) {
            $inserirValor->execute([
                'categoria_id' => $categoriaId,
                'nome' => "Valor incompleto {$categoria}.{$valor}",
                'posicao' => $valor,
            ]);
        }
    }

    $datas = $pdo->prepare('UPDATE tema SET criado_em = :criado_em, ativo = :ativo WHERE id = :id');
    $datas->execute(['criado_em' => '1000-01-02 00:00:00', 'ativo' => 1, 'id' => $temaAId]);
    $datas->execute(['criado_em' => '1000-01-02 00:00:00', 'ativo' => 1, 'id' => $temaBId]);
    $datas->execute(['criado_em' => '1000-01-01 00:00:01', 'ativo' => 0, 'id' => $temaInativoId]);

    $verificar(carregar_tema_completo($pdo, $temaAId, true) !== null, 'Um tema 5 x 5 ativo deve ser carregado.');
    $verificar(carregar_tema_completo($pdo, $temaIncompletoId, true) === null, 'Um tema incompleto deve ser rejeitado.');
    $verificar(carregar_tema_completo($pdo, $temaInativoId, true) === null, 'Um tema inativo deve ser rejeitado.');

    $elegiveis = listar_temas_elegiveis($pdo);
    $idsElegiveis = array_map(static fn (array $tema): int => (int) $tema['id'], $elegiveis);
    $verificar(in_array($temaAId, $idsElegiveis, true), 'A listagem deve incluir tema completo e ativo.');
    $verificar(!in_array($temaIncompletoId, $idsElegiveis, true), 'A listagem deve excluir tema incompleto.');
    $verificar(!in_array($temaInativoId, $idsElegiveis, true), 'A listagem deve excluir tema inativo.');

    $carregadoA = carregar_tema_completo($pdo, $temaAId, true);
    $verificar(
        array_column($carregadoA['categorias'] ?? [], 'posicao') === [0, 1, 2, 3, 4],
        'As categorias devem ser carregadas por posição.'
    );
    foreach (($carregadoA['categorias'] ?? []) as $categoria) {
        $verificar(
            array_column($categoria['valores'], 'posicao') === [0, 1, 2, 3, 4],
            'Os valores devem ser carregados por posição.'
        );
    }

    $padrao = buscar_tema_padrao($pdo);
    $verificar((int) ($padrao['id'] ?? 0) === min($temaAId, $temaBId), 'O desempate do tema padrão deve usar o menor ID.');

    $temaComConflito = $montarTema("__TESTE_FASE5_{$sufixo}_ROLLBACK");
    $temaComConflito['categorias'][1]['nome'] = $temaComConflito['categorias'][0]['nome'];
    $resultadoRollback = gravar_tema_validado($pdo, $temaComConflito);
    $verificar(!$resultadoRollback['ok'], 'Um conflito ocorrido no meio da gravação deve ser informado.');
    $confirmarRollback = $pdo->prepare('SELECT COUNT(*) FROM tema WHERE nome = :nome');
    $confirmarRollback->execute(['nome' => $temaComConflito['nome']]);
    $verificar((int) $confirmarRollback->fetchColumn() === 0, 'A falha de gravação deve desfazer integralmente o tema.');

    $senha = 'T5!' . bin2hex(random_bytes(10)) . 'aA1';
    $emailUm = "fase5-{$sufixo}-um@example.test";
    $emailDois = "fase5-{$sufixo}-dois@example.test";
    array_push($emailsJogadores, $emailUm, $emailDois);
    $cadastroUm = criar_jogador_pendente($pdo, [
        'cpf' => $gerarCpf(),
        'email' => $emailUm,
        'nome_usuario' => 'Jogador Fase 5 Um',
        'senha' => $senha,
    ]);
    $cadastroDois = criar_jogador_pendente($pdo, [
        'cpf' => $gerarCpf(),
        'email' => $emailDois,
        'nome_usuario' => 'Jogador Fase 5 Dois',
        'senha' => $senha,
    ]);
    $verificar($cadastroUm['ok'] && $cadastroDois['ok'], 'Os jogadores temporários devem ser criados.');
    $jogadorUm = buscar_jogador_por_email($pdo, $emailUm);
    $jogadorDois = buscar_jogador_por_email($pdo, $emailDois);
    if ($jogadorUm === null || $jogadorDois === null) {
        throw new RuntimeException('Não foi possível localizar os jogadores temporários.');
    }
    $jogadorUmId = (int) $jogadorUm['id'];
    $jogadorDoisId = (int) $jogadorDois['id'];
    array_push($idsJogadores, $jogadorUmId, $jogadorDoisId);

    $temaPadraoId = min($temaAId, $temaBId);
    $verificar($temaDoJogador($pdo, $jogadorUmId) === $temaPadraoId, 'Jogador novo deve receber o tema padrão disponível.');

    $zerarPreferencia = $pdo->prepare('UPDATE jogador SET tema_preferido_id = NULL WHERE id = :id');
    $zerarPreferencia->execute(['id' => $jogadorUmId]);
    $efetivoSemPreferencia = carregar_tema_efetivo($pdo, $jogadorUmId);
    $verificar((int) ($efetivoSemPreferencia['id'] ?? 0) === $temaPadraoId, 'Jogador sem preferência deve receber fallback padrão.');
    $verificar(($efetivoSemPreferencia['origem'] ?? '') === 'padrao', 'O fallback deve identificar sua origem como padrão.');

    $trocaValida = alterar_tema_jogador($pdo, $jogadorUmId, (string) $temaBId);
    $verificar($trocaValida['ok'], 'Jogador ativo deve conseguir escolher um tema elegível.');
    $verificar($temaDoJogador($pdo, $jogadorUmId) === $temaBId, 'A escolha válida deve persistir tema_preferido_id.');
    $efetivoPreferido = carregar_tema_efetivo($pdo, $jogadorUmId);
    $verificar((int) ($efetivoPreferido['id'] ?? 0) === $temaBId, 'O carregador deve devolver a preferência válida.');
    $verificar(($efetivoPreferido['origem'] ?? '') === 'preferencia', 'O tema escolhido deve identificar a origem como preferência.');

    $temaDoOutroAntes = $temaDoJogador($pdo, $jogadorDoisId);
    foreach ([null, '', '1 OR 1=1', '1.5', '999999999', (string) $temaInativoId, (string) $temaIncompletoId] as $entradaInvalida) {
        $resultadoInvalido = alterar_tema_jogador($pdo, $jogadorUmId, $entradaInvalida);
        $verificar(!$resultadoInvalido['ok'], 'Entrada inválida, inexistente, inativa ou incompleta deve ser rejeitada.');
        $verificar($temaDoJogador($pdo, $jogadorUmId) === $temaBId, 'Uma falha deve preservar a preferência anterior.');
    }
    $verificar($temaDoJogador($pdo, $jogadorDoisId) === $temaDoOutroAntes, 'A troca não pode alterar outro jogador.');

    $repetida = alterar_tema_jogador($pdo, $jogadorUmId, (string) $temaBId);
    $verificar($repetida['ok'] && $temaDoJogador($pdo, $jogadorUmId) === $temaBId, 'O reenvio da mesma escolha deve ser seguro.');

    $datas->execute(['criado_em' => '1000-01-02 00:00:00', 'ativo' => 0, 'id' => $temaBId]);
    $fallbackInativo = carregar_tema_efetivo($pdo, $jogadorUmId);
    $verificar((int) ($fallbackInativo['id'] ?? 0) === $temaPadraoId, 'Preferência que se tornou inativa deve usar o padrão.');
    $datas->execute(['criado_em' => '1000-01-02 00:00:00', 'ativo' => 1, 'id' => $temaBId]);

    $preferirIncompleto = $pdo->prepare('UPDATE jogador SET tema_preferido_id = :tema_id WHERE id = :id');
    $preferirIncompleto->execute(['tema_id' => $temaIncompletoId, 'id' => $jogadorUmId]);
    $fallbackIncompleto = carregar_tema_efetivo($pdo, $jogadorUmId);
    $verificar((int) ($fallbackIncompleto['id'] ?? 0) === $temaPadraoId, 'Preferência que se tornou malformada deve usar o padrão.');

    $nomeRemovido = "__TESTE_FASE5_{$sufixo}_REMOVIDO";
    $resultadoRemovido = gravar_tema_validado($pdo, $montarTema($nomeRemovido));
    if (!$resultadoRemovido['ok']) {
        throw new RuntimeException('Não foi possível preparar o tema removível de integração.');
    }
    $temaRemovidoId = (int) $resultadoRemovido['tema_id'];
    $idsTemas[] = $temaRemovidoId;
    $preferirIncompleto->execute(['tema_id' => $temaRemovidoId, 'id' => $jogadorUmId]);
    $apagarValoresRemovido = $pdo->prepare(
        'DELETE tv FROM tema_valor tv
         INNER JOIN tema_categoria tc ON tc.id = tv.categoria_id
         WHERE tc.tema_id = :id'
    );
    $apagarValoresRemovido->execute(['id' => $temaRemovidoId]);
    $apagarCategoriasRemovido = $pdo->prepare('DELETE FROM tema_categoria WHERE tema_id = :id');
    $apagarCategoriasRemovido->execute(['id' => $temaRemovidoId]);
    $apagarTemaRemovido = $pdo->prepare('DELETE FROM tema WHERE id = :id');
    $apagarTemaRemovido->execute(['id' => $temaRemovidoId]);
    $verificar($temaDoJogador($pdo, $jogadorUmId) === null, 'A FK deve transformar preferência removida em NULL.');
    $fallbackRemovido = carregar_tema_efetivo($pdo, $jogadorUmId);
    $verificar((int) ($fallbackRemovido['id'] ?? 0) === $temaPadraoId, 'Preferência removida deve usar o padrão.');

    $pdo->beginTransaction();
    $pdo->exec('UPDATE tema SET ativo = 0');
    $verificar(buscar_tema_padrao($pdo) === null, 'A ausência total de tema válido deve retornar estado controlado.');
    $pdo->rollBack();
} catch (Throwable $erro) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $falhas[] = $erro->getMessage();
} finally {
    if (isset($pdo) && $pdo instanceof PDO) {
        foreach ($emailsJogadores as $email) {
            $statement = $pdo->prepare('DELETE FROM jogador WHERE email = :email');
            $statement->execute(['email' => $email]);
        }
        foreach (array_filter($idsJogadores) as $id) {
            $statement = $pdo->prepare('DELETE FROM jogador WHERE id = :id');
            $statement->execute(['id' => $id]);
        }
        foreach (array_reverse(array_filter($idsTemas)) as $id) {
            $apagarValores = $pdo->prepare(
                'DELETE tv FROM tema_valor tv
                 INNER JOIN tema_categoria tc ON tc.id = tv.categoria_id
                 WHERE tc.tema_id = :id'
            );
            $apagarValores->execute(['id' => $id]);
            $apagarCategorias = $pdo->prepare('DELETE FROM tema_categoria WHERE tema_id = :id');
            $apagarCategorias->execute(['id' => $id]);
            $apagarTema = $pdo->prepare('DELETE FROM tema WHERE id = :id');
            $apagarTema->execute(['id' => $id]);
        }
    }
    putenv('DB_PASSWORD');
}

if ($falhas !== []) {
    fwrite(STDERR, "Falhas de integração da Fase 5:\n- " . implode("\n- ", $falhas) . "\n");
    exit(1);
}

fwrite(STDOUT, "Todas as verificações de integração da Fase 5 passaram.\n");
