<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

/** Senha somente em memória, sem argumento de processo ou arquivo. */
function fase7_solicitar_senha(): void
{
    if ((string) getenv('DB_PASSWORD') !== '') { return; }
    if (PHP_OS_FAMILY === 'Windows') {
        fwrite(STDOUT, 'Digite a senha MariaDB de zebrapuzzle_app (entrada oculta) e pressione Enter: ');
        $comando = '$s = Read-Host "Senha MariaDB (digitacao oculta)" -AsSecureString; '
            . '$p = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($s); '
            . 'try { $v = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($p); '
            . '[Console]::Out.Write("__F7_PASSWORD__" + [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($v))) } '
            . 'finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($p) }';
        $processo = proc_open(['powershell.exe', '-NoProfile', '-Command', $comando],
            [0 => STDIN, 1 => ['pipe', 'w'], 2 => STDERR], $pipes);
        if (!is_resource($processo)) { throw new RuntimeException('Não foi possível abrir a leitura protegida da senha.'); }
        $saida = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $codigo = proc_close($processo);
        fwrite(STDOUT, "\n");
        if ($codigo !== 0) { throw new RuntimeException('Leitura cancelada. Execute diretamente com php, fora do Composer.'); }
        $partes = explode('__F7_PASSWORD__', (string) $saida);
        $senha = count($partes) === 2 ? base64_decode(trim($partes[1]), true) : false;
        unset($saida, $partes);
    } else {
        if (!function_exists('stream_isatty') || !stream_isatty(STDIN)) {
            throw new RuntimeException('Terminal interativo ausente. Execute: php tests/integration_fase7.php');
        }
        fwrite(STDOUT, 'Senha MariaDB (digitação oculta): ');
        $estadoTerminal = shell_exec('stty -g');
        if (!is_string($estadoTerminal) || trim($estadoTerminal) === '') { throw new RuntimeException('Não foi possível proteger a leitura.'); }
        system('stty -echo', $status);
        if ($status !== 0) { throw new RuntimeException('Não foi possível ocultar a senha.'); }
        try { $senha = fgets(STDIN); }
        finally { system('stty ' . escapeshellarg(trim($estadoTerminal))); fwrite(STDOUT, "\n"); }
        $senha = $senha === false ? '' : rtrim($senha, "\r\n");
    }
    if (!is_string($senha) || $senha === '') { throw new RuntimeException('Nenhuma senha foi recebida. Digite a senha de zebrapuzzle_app, não a de root.'); }
    putenv('DB_PASSWORD=' . $senha);
    unset($senha);
}

function fase7_exigir(bool $ok, string $mensagem): void
{
    if (!$ok) { throw new RuntimeException($mensagem); }
}

function fase7_cpf_sintetico(): string
{
    do {
        $base = '';
        for ($i = 0; $i < 9; $i++) { $base .= (string) random_int(0, 9); }
    } while (preg_match('/^(\d)\1{8}$/', $base) === 1);
    for ($n = 9; $n < 11; $n++) {
        $soma = 0;
        for ($i = 0; $i < $n; $i++) { $soma += (int) $base[$i] * ($n + 1 - $i); }
        $digito = (10 * $soma) % 11;
        $base .= (string) ($digito === 10 ? 0 : $digito);
    }
    return $base;
}

function fase7_dia_livre(PDO $pdo): string
{
    $consulta = $pdo->prepare('SELECT 1 FROM desafio_diario WHERE dia = :dia');
    for ($i = 0; $i < 100; $i++) {
        $dia = (new DateTimeImmutable('1900-01-01'))->modify('+' . random_int(0, 10000) . ' days')->format('Y-m-d');
        $consulta->execute(['dia' => $dia]);
        if ($consulta->fetchColumn() === false) { return $dia; }
    }
    throw new RuntimeException('Não foi encontrada data livre para a fixture.');
}

/** Limpeza limitada aos IDs sintéticos e com conferência do marcador. */
function fase7_limpar(PDO $pdo, array $estado): void
{
    $tag = $estado['tag'] ?? '';
    fase7_exigir(is_string($tag) && preg_match('/^[a-f0-9]{10}$/', $tag) === 1, 'Marcador inválido.');
    $pdo->beginTransaction();
    try {
        foreach ($estado['jogadores'] as $indice => $id) {
            fase7_exigir(is_int($id) && $id > 0, 'ID de limpeza inválido.');
            $s = $pdo->prepare('SELECT email FROM jogador WHERE id = :id');
            $s->execute(['id' => $id]);
            $email = $s->fetchColumn();
            fase7_exigir($email === false || $email === "fase7-{$tag}-{$indice}@example.test", 'A limpeza encontrou jogador que não pertence ao ensaio.');
            foreach (['leaderboard','resolucao','tentativa_desafio','jogador'] as $tabela) {
                $coluna = $tabela === 'jogador' ? 'id' : 'jogador_id';
                $pdo->prepare("DELETE FROM {$tabela} WHERE {$coluna} = :id")->execute(['id' => $id]);
            }
        }
        foreach ($estado['desafios'] as $item) {
            fase7_exigir(is_int($item['id']) && classificar_data_iso($item['dia']), 'Desafio de limpeza inválido.');
            // RESTRICT preserva um dia que passou a ser usado por outra conta.
            $pdo->prepare('DELETE FROM desafio_diario WHERE id = :id AND dia = :dia')->execute($item);
        }
        $pdo->commit();
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $erro;
    }
}

$estado = ['tag' => bin2hex(random_bytes(5)), 'jogadores' => [], 'desafios' => []];
$manter = false;
$codigoSaida = 0;
try {
    fase7_solicitar_senha();
    require dirname(__DIR__) . '/src/bootstrap.php';
    fase7_exigir(app_config('environment') !== 'production', 'Não execute fixtures em produção.');
    $pdo = db();
    fase7_exigir(count($argv) <= 2, 'Use apenas uma opção por execução.');
    $opcao = $argv[1] ?? '';
    $limpar = str_starts_with($opcao, '--limpar-manual=') ? substr($opcao, 16) : null;
    fase7_exigir($limpar !== null || in_array($opcao, ['', '--preparar-manual'], true), 'Opção desconhecida.');
    if ($limpar !== null) {
        fase7_exigir(preg_match('/^[a-f0-9]{10}$/', $limpar) === 1, 'Marcador de limpeza inválido.');
        $arquivoEstado = sys_get_temp_dir() . '/zebrapuzzle-fase7-' . $limpar . '.json';
        fase7_exigir(is_file($arquivoEstado), 'Arquivo do ensaio ausente. Não será feita limpeza por aproximação.');
        $limpeza = json_decode((string) file_get_contents($arquivoEstado), true, 512, JSON_THROW_ON_ERROR);
        fase7_exigir(($limpeza['tag'] ?? '') === $limpar, 'Marcador não confere.');
        fase7_limpar($pdo, $limpeza);
        unlink($arquivoEstado);
        fwrite(STDOUT, "Dados exclusivos do ensaio manual removidos. O desafio de hoje foi preservado.\n");
    } else {
        $indices = $pdo->query("SELECT table_name AS tabela, index_name AS nome_indice, non_unique AS nao_unico,
            GROUP_CONCAT(column_name ORDER BY seq_in_index) AS colunas
            FROM information_schema.statistics WHERE table_schema = DATABASE()
            AND index_name IN ('uq_tentativa_aberta','uq_resolucao_tentativa','uq_leaderboard_dia_jogador','uq_leaderboard_resolucao')
            GROUP BY table_name, index_name, non_unique")->fetchAll();
        $esperados = ['uq_tentativa_aberta' => 'jogador_id,desafio_id,aberta_chave',
            'uq_resolucao_tentativa' => 'tentativa_id', 'uq_leaderboard_dia_jogador' => 'dia,jogador_id',
            'uq_leaderboard_resolucao' => 'resolucao_id'];
        foreach ($indices as $indice) {
            fase7_exigir((int) $indice['nao_unico'] === 0 && ($esperados[$indice['nome_indice']] ?? null) === $indice['colunas'], 'Índice único divergente.');
            unset($esperados[$indice['nome_indice']]);
        }
        fase7_exigir($esperados === [], 'Execute primeiro a migração 006 no Workbench.');
        fase7_exigir((int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()
            AND table_name IN ('tentativa_desafio','resolucao','leaderboard') AND engine = 'InnoDB'")->fetchColumn() === 3, 'As tabelas devem usar InnoDB.');
        fase7_exigir((string) $pdo->query('SELECT CURRENT_DATE()')->fetchColumn() === dia_de_referencia(), 'PHP e MariaDB divergem no dia. Confira o fuso.');
        $temas = listar_temas_elegiveis($pdo);
        fase7_exigir(count($temas) >= 2, 'São necessários dois temas completos da Fase 5.');
        $fixture = require dirname(__DIR__) . '/resources/desafios/exemplo.php';
        $manual = $opcao === '--preparar-manual';
        $senhaJogador = 'T7!' . bin2hex(random_bytes(8)) . 'aA1';
        for ($i = 0; $i < ($manual ? 2 : 4); $i++) {
            $email = "fase7-{$estado['tag']}-{$i}@example.test";
            $cadastro = criar_jogador_pendente($pdo, ['cpf' => fase7_cpf_sintetico(), 'email' => $email,
                'nome_usuario' => "__TESTE_FASE7_{$estado['tag']}_{$i}", 'senha' => $senhaJogador]);
            fase7_exigir($cadastro['ok'], 'Falha no cadastro sintético.');
            $j = buscar_jogador_por_email($pdo, $email);
            fase7_exigir($j !== null, 'Jogador do ensaio não encontrado.');
            $estado['jogadores'][] = (int) $j['id'];
            fase7_exigir(verificar_token_email($pdo, (string) $cadastro['token'])['status'] === 'ok', 'Não foi possível verificar a conta sintética.');
            fase7_exigir(alterar_tema_jogador($pdo, (int) $j['id'], $temas[0]['id'])['ok'], 'Tema não aplicado.');
            if (!$manual) { registrar_acesso_diario($pdo, (int) $j['id']); }
        }
        $diaA = fase7_dia_livre($pdo);
        $desafioA = persistir_desafio_gerado($pdo, $diaA, $fixture);
        $estado['desafios'][] = ['id' => (int) $desafioA['id'], 'dia' => $diaA];
        if ($manual) {
            $arquivoEstado = sys_get_temp_dir() . '/zebrapuzzle-fase7-' . $estado['tag'] . '.json';
            $handle = fopen($arquivoEstado, 'x');
            fase7_exigir(is_resource($handle), 'Não foi possível guardar os IDs de limpeza.');
            $json = json_encode($estado, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            $gravado = fwrite($handle, $json);
            fclose($handle);
            fase7_exigir($gravado === strlen($json), 'Arquivo de limpeza incompleto.');
            $manter = true;
            fwrite(STDOUT, "Ensaio manual criado. MARCADOR: {$estado['tag']}\n");
            foreach ($estado['jogadores'] as $i => $id) {
                fwrite(STDOUT, "Jogador {$i}: fase7-{$estado['tag']}-{$i}@example.test | ID {$id}\n");
            }
            fwrite(STDOUT, "Senha TEMPORÁRIA das contas: {$senhaJogador}\nDIA HISTÓRICO: {$diaA}\nTema inicial: {$temas[0]['nome']}\nIDs de limpeza: {$arquivoEstado}\nConclua o captcha pelo navegador.\n");
        } else {
            [$p1, $p2, $p3, $p4] = $estado['jogadores'];
            $resposta = solucao_lista_para_matriz($desafioA['solucao']);
            $abrir = static fn (int $p, string $hora): array => iniciar_ou_reutilizar_tentativa($pdo, $p,
                (int) $desafioA['id'], (int) carregar_tema_efetivo($pdo, $p)['id'], static fn (): string => $diaA . ' ' . $hora);
            $concluir = static fn (int $p, array $t, string $hora): array => concluir_tentativa_e_registrar_resolucao(
                $pdo, $p, (int) $t['id'], $resposta, static fn (): string => $diaA . ' ' . $hora);
            $contar = static function (string $tabela, int $p) use ($pdo): int {
                fase7_exigir(in_array($tabela, ['resolucao','leaderboard','tentativa_desafio'], true), 'Tabela inválida.');
                $s = $pdo->prepare("SELECT COUNT(*) FROM {$tabela} WHERE jogador_id = :p");
                $s->execute(['p' => $p]); return (int) $s->fetchColumn();
            };
            $t = $abrir($p1, '12:00:00.000000');
            fase7_exigir(alterar_tema_jogador($pdo, $p1, $temas[1]['id'])['ok'], 'Troca de preferência falhou.');
            $_SESSION = [];
            $reaberta = $abrir($p1, '12:00:01.000000');
            fase7_exigir($reaberta['id'] === $t['id'] && $reaberta['iniciada_em'] === $t['iniciada_em']
                && $reaberta['tema_id'] === $t['tema_id'], 'Retomada alterou a tentativa.');
            $errada = $resposta;
            [$errada[0][0], $errada[0][1]] = [$errada[0][1], $errada[0][0]];
            foreach ([[], $errada] as $invalida) {
                try { concluir_tentativa_e_registrar_resolucao($pdo, $p1, $t['id'], $invalida); throw new LogicException('Resposta inválida aceita.'); }
                catch (InvalidArgumentException $erro) { /* esperado */ }
            }
            fase7_exigir($contar('resolucao', $p1) === 0 && $contar('leaderboard', $p1) === 0, 'Resposta inválida causou mutação.');
            $primeira = $concluir($p1, $t, '12:00:05.000000');
            fase7_exigir($primeira['elegivel_leaderboard'] === 1 && $primeira['tempo_milisegundos'] === 5000
                && $primeira['leaderboard_id'] !== null, 'Primeira conclusão sem ranking coerente.');
            $reenvio = $concluir($p1, $t, '12:00:06.000000');
            fase7_exigir($reenvio['id'] === $primeira['id'] && $reenvio['reenvio']
                && $contar('resolucao', $p1) === 1, 'Reenvio duplicou resultado.');
            fase7_exigir(buscar_resolucao_jogador_por_id($pdo, $primeira['id'], $p2) === null, 'Resultado alheio acessível.');
            try { $concluir($p2, $t, '12:00:06.000000'); throw new LogicException('Tentativa alheia aceita.'); }
            catch (RuntimeException $erro) { /* esperado */ }
            foreach ([['12:01:00.000000','12:01:01.000000'], ['12:02:00.000000','12:02:10.000000']] as [$inicio, $fim]) {
                $nova = $abrir($p1, $inicio);
                fase7_exigir($nova['id'] !== $t['id'] && $nova['tema_id'] === (int) $temas[1]['id'], 'Rejogo sem nova tentativa/tema.');
                $replay = $concluir($p1, $nova, $fim);
                fase7_exigir($replay['elegivel_leaderboard'] === 0 && $replay['motivo_elegibilidade'] === ELEGIBILIDADE_REJOGADA, 'Rejogo alterou elegibilidade.');
            }
            fase7_exigir($contar('resolucao', $p1) === 3 && $contar('leaderboard', $p1) === 1, 'Rejogos duplicaram ranking.');
            $primeiraEsperada = array_diff_key($primeira, ['reenvio' => true]);
            fase7_exigir(buscar_resolucao_jogador_por_id($pdo, $primeira['id'], $p1) === $primeiraEsperada, 'Primeira resolução alterada.');
            $segundo = $concluir($p2, $abrir($p2, '13:00:00.000000'), '13:00:05.000000');
            $terceiro = $concluir($p3, $abrir($p3, '14:00:00.000000'), '14:00:01.000000');
            $ranking = listar_leaderboard_por_dia($pdo, $diaA);
            fase7_exigir(array_column($ranking, 'id') === [$terceiro['leaderboard_id'], $primeira['leaderboard_id'], $segundo['leaderboard_id']]
                && array_column($ranking, 'posicao') === [1,2,3], 'Ordenação SQL incorreta.');
            $tFalha = $abrir($p4, '15:00:00.000000');
            foreach (['resolucao','leaderboard','tentativa'] as $etapaFalha) {
                try {
                    concluir_tentativa_e_registrar_resolucao($pdo, $p4, $tFalha['id'], $resposta,
                        static fn (): string => $diaA . ' 15:00:05.000000',
                        static function (string $etapa) use ($etapaFalha): void {
                            if ($etapa === $etapaFalha) { throw new RuntimeException('Falha controlada'); }
                        });
                    throw new LogicException('A falha controlada não ocorreu.');
                } catch (RuntimeException $erro) { fase7_exigir($erro->getMessage() === 'Falha controlada', 'Erro inesperado no rollback.'); }
                fase7_exigir($contar('resolucao', $p4) === 0 && $contar('leaderboard', $p4) === 0
                    && buscar_tentativa_aberta($pdo, $p4, (int) $desafioA['id']) !== null, 'Rollback não restaurou as entidades.');
            }
            foreach ([
                "INSERT INTO tentativa_desafio (jogador_id,desafio_id,tema_id,iniciada_em) SELECT jogador_id,desafio_id,tema_id,iniciada_em FROM tentativa_desafio WHERE id = {$tFalha['id']}",
                "INSERT INTO resolucao (tentativa_id,jogador_id,desafio_dia,concluida_em,tempo_milisegundos,tema_id) SELECT tentativa_id,jogador_id,desafio_dia,concluida_em,tempo_milisegundos,tema_id FROM resolucao WHERE id = {$primeira['id']}",
                "INSERT INTO leaderboard (dia,jogador_id,resolucao_id,tempo_milisegundos,momento_conclusao) SELECT desafio_dia,jogador_id,id,tempo_milisegundos,concluida_em FROM resolucao WHERE id = {$replay['id']}",
            ] as $duplicado) {
                try { $pdo->exec($duplicado); throw new LogicException('UNIQUE ausente.'); }
                catch (PDOException $erro) { fase7_exigir(pdo_erro_duplicidade($erro), 'Erro diferente da duplicidade esperada.'); }
            }
            try {
                $s = $pdo->prepare('INSERT INTO tentativa_desafio (jogador_id,desafio_id,tema_id,iniciada_em,aberta_chave)
                    SELECT jogador_id,desafio_id,tema_id,iniciada_em,NULL FROM tentativa_desafio WHERE id = :id');
                $s->execute(['id' => $tFalha['id']]); throw new LogicException('CHECK ausente.');
            } catch (PDOException $erro) {
                fase7_exigir((int) ($erro->errorInfo[1] ?? 0) === 4025
                    && str_contains((string) ($erro->errorInfo[2] ?? ''), 'ck_tentativa_aberta'), 'Falha inesperada no CHECK.');
            }
            $diaB = fase7_dia_livre($pdo);
            $desafioB = persistir_desafio_gerado($pdo, $diaB, $fixture);
            $estado['desafios'][] = ['id' => (int) $desafioB['id'], 'dia' => $diaB];
            fase7_exigir(listar_leaderboard_por_dia($pdo, $diaB) === [], 'Dia novo deveria ter ranking vazio.');
            $tardia = iniciar_ou_reutilizar_tentativa($pdo, $p1, (int) $desafioB['id'], (int) $temas[1]['id']);
            $historica = concluir_tentativa_e_registrar_resolucao($pdo, $p1, $tardia['id'], solucao_lista_para_matriz($desafioB['solucao']));
            fase7_exigir($historica['elegivel_leaderboard'] === 0 && listar_leaderboard_por_dia($pdo, $diaB) === [], 'Histórico alterou ranking.');
            $virada = iniciar_ou_reutilizar_tentativa($pdo, $p2, (int) $desafioB['id'], (int) $temas[0]['id'], static fn (): string => $diaB . ' 23:59:59.000000');
            $seguinte = (new DateTimeImmutable($diaB))->modify('+1 day')->format('Y-m-d');
            $fimVirada = concluir_tentativa_e_registrar_resolucao($pdo, $p2, $virada['id'], solucao_lista_para_matriz($desafioB['solucao']), static fn (): string => $seguinte . ' 00:00:01.000000');
            fase7_exigir($fimVirada['elegivel_leaderboard'] === 0 && $fimVirada['tempo_milisegundos'] === 2000, 'Virada incorreta.');
            $antes = listar_leaderboard_por_dia($pdo, $diaA);
            fase7_exigir(in_array($diaB, array_column(listar_desafios_anteriores_disponiveis($pdo, $p1), 'dia'), true), 'Histórico não listado.');
            fase7_exigir(listar_leaderboard_por_dia($pdo, $diaA) === $antes, 'Consulta alterou classificação.');
            fwrite(STDOUT, "Blocos essenciais de integração da Fase 7 passaram.\n");
        }
    }
} catch (Throwable $erro) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    fwrite(STDERR, 'Fase 7: ' . $erro->getMessage() . "\n");
    $codigoSaida = 1;
} finally {
    if (!$manter && isset($pdo) && ($estado['jogadores'] !== [] || $estado['desafios'] !== [])) {
        try { fase7_limpar($pdo, $estado); fwrite(STDOUT, "Dados exclusivos da suíte removidos.\n"); }
        catch (Throwable $erro) { fwrite(STDERR, "Limpeza pendente do marcador {$estado['tag']}. Pare e solicite diagnóstico.\n"); $codigoSaida = 1; }
    }
    putenv('DB_PASSWORD');
}
exit($codigoSaida);
