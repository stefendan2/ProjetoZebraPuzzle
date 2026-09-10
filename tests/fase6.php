<?php

declare(strict_types=1);

/** @param callable(bool, string):void $verificar */
function executar_testes_unitarios_fase6(callable $verificar): void
{
    $fixture = require dirname(__DIR__) . '/resources/desafios/exemplo.php';
    $verificar(count($fixture['solucao']) === 25, 'Fase 6: a fixture deve possuir 25 atribuições.');
    $verificar(count($fixture['dicas']) === 15, 'Fase 6: a fixture deve possuir 15 dicas.');

    $validacao = validar_desafio_gerado($fixture, true);
    $verificar($validacao['ok'], 'Fase 6: a fixture deve ser válida e possuir solução única.');
    $verificar(($validacao['verificacao']['status'] ?? '') === 'unico', 'Fase 6: o CSP deve classificar a fixture como única.');
    $verificar(($validacao['verificacao']['quantidade'] ?? 0) === 1, 'Fase 6: o contador deve encontrar exatamente uma solução.');

    $solucao = solucao_lista_para_matriz($fixture['solucao']);
    $verificar($solucao === [
        [0, 1, 4, 3, 2],
        [3, 1, 2, 0, 4],
        [0, 3, 1, 4, 2],
        [1, 4, 0, 2, 3],
        [4, 1, 0, 2, 3],
    ], 'Fase 6: a conversão das casas para 0..4 não pode reordenar a solução.');
    foreach ($solucao as $categoria) {
        $ordenada = $categoria;
        sort($ordenada);
        $verificar($ordenada === [0, 1, 2, 3, 4], 'Fase 6: cada categoria deve ser uma permutação de 0..4.');
    }
    $verificar(todas_dicas_satisfeitas($fixture['dicas'], $solucao), 'Fase 6: as 15 dicas devem ser verdadeiras na solução oficial.');

    $listaRestaurada = solucao_matriz_para_lista($solucao);
    $verificar($listaRestaurada === $fixture['solucao'], 'Fase 6: a ida e volta da solução não deve perder informação.');
    $grade = resposta_para_grade_visual($solucao);
    $conversao = grade_visual_para_resposta($grade);
    $verificar($conversao['ok'] && $conversao['resposta'] === $solucao, 'Fase 6: grade visual e forma canônica devem ser inversíveis.');

    $solucaoCurta = $fixture['solucao'];
    array_pop($solucaoCurta);
    $verificar(validar_solucao_desafio($solucaoCurta) !== [], 'Fase 6: atribuição ausente deve ser rejeitada.');
    $solucaoDuplicada = $fixture['solucao'];
    $solucaoDuplicada[1] = $solucaoDuplicada[0];
    $verificar(validar_solucao_desafio($solucaoDuplicada) !== [], 'Fase 6: coordenada duplicada deve ser rejeitada.');
    $casaDuplicada = $fixture['solucao'];
    $casaDuplicada[1]['casa'] = $casaDuplicada[0]['casa'];
    $verificar(validar_solucao_desafio($casaDuplicada) !== [], 'Fase 6: casa duplicada na categoria deve ser rejeitada.');
    foreach ([
        ['campo' => 'categoria', 'valor' => -1],
        ['campo' => 'categoria', 'valor' => 5],
        ['campo' => 'informacao', 'valor' => -1],
        ['campo' => 'informacao', 'valor' => 5],
        ['campo' => 'casa', 'valor' => -1],
        ['campo' => 'casa', 'valor' => 5],
        ['campo' => 'casa', 'valor' => '0'],
    ] as $caso) {
        $invalida = $fixture['solucao'];
        $invalida[0][$caso['campo']] = $caso['valor'];
        $verificar(validar_solucao_desafio($invalida) !== [], 'Fase 6: índices fora da faixa ou com tipo inesperado devem ser rejeitados.');
    }

    $gradeIncompleta = $grade;
    $gradeIncompleta[0][0] = '';
    $resultadoIncompleto = grade_visual_para_resposta($gradeIncompleta);
    $verificar(!$resultadoIncompleto['ok'] && $resultadoIncompleto['status'] === 'incompleta', 'Fase 6: grade incompleta deve ser identificada.');
    $gradeSextaCategoria = $grade;
    $gradeSextaCategoria[5] = $grade[0];
    $verificar(!grade_visual_para_resposta($gradeSextaCategoria)['ok'], 'Fase 6: sexta categoria deve ser rejeitada.');
    $gradeSextaCasa = $grade;
    $gradeSextaCasa[0][5] = 0;
    $verificar(!grade_visual_para_resposta($gradeSextaCasa)['ok'], 'Fase 6: sexta casa deve ser rejeitada.');
    $gradeSextaInfo = $grade;
    $gradeSextaInfo[0][0] = 5;
    $verificar(!grade_visual_para_resposta($gradeSextaInfo)['ok'], 'Fase 6: sexta informação deve ser rejeitada.');
    $gradeRepetida = $grade;
    $gradeRepetida[0][1] = $gradeRepetida[0][0];
    $verificar(!grade_visual_para_resposta($gradeRepetida)['ok'], 'Fase 6: informação repetida deve ser rejeitada.');
    $gradeInjecao = $grade;
    $gradeInjecao[0][0] = '0 OR 1=1';
    $verificar(!grade_visual_para_resposta($gradeInjecao)['ok'], 'Fase 6: tentativa de injeção na grade deve ser rejeitada.');
    $verificar(!grade_visual_para_resposta('{"0":[]}')['ok'], 'Fase 6: payload com tipo inesperado deve ser rejeitado.');

    $estado = array_fill(0, 5, []);
    $estado[0][0] = 1;
    $estado[1][0] = 1;
    $estado[1][1] = 2;
    $eq = ['ordem' => 1, 'relacao' => 'EQ', 'info1' => [0, 0], 'info2' => [1, 0], 'valor_fixo' => null];
    $m1 = ['ordem' => 1, 'relacao' => 'M1', 'info1' => [0, 0], 'info2' => [1, 1], 'valor_fixo' => null];
    $pm1 = ['ordem' => 1, 'relacao' => 'PM1', 'info1' => [0, 0], 'info2' => [1, 1], 'valor_fixo' => null];
    $cert = ['ordem' => 1, 'relacao' => 'CERT', 'info1' => [0, 0], 'info2' => null, 'valor_fixo' => 1];
    $verificar(avaliar_dica_desafio($eq, $estado) === true, 'Fase 6: EQ verdadeira deve ser reconhecida.');
    $estado[1][0] = 3;
    $verificar(avaliar_dica_desafio($eq, $estado) === false, 'Fase 6: EQ falsa deve ser reconhecida.');
    $verificar(avaliar_dica_desafio($m1, $estado) === true, 'Fase 6: M1 direta deve ser reconhecida.');
    $m1Invertida = $m1;
    $m1Invertida['info1'] = [1, 1];
    $m1Invertida['info2'] = [0, 0];
    $verificar(avaliar_dica_desafio($m1Invertida, $estado) === false, 'Fase 6: M1 invertida deve ser falsa.');
    $verificar(avaliar_dica_desafio($pm1, $estado) === true, 'Fase 6: PM1 deve aceitar vizinho à direita.');
    $pm1Invertida = $pm1;
    $pm1Invertida['info1'] = [1, 1];
    $pm1Invertida['info2'] = [0, 0];
    $verificar(avaliar_dica_desafio($pm1Invertida, $estado) === true, 'Fase 6: PM1 deve aceitar vizinho à esquerda.');
    $estado[1][1] = 4;
    $verificar(avaliar_dica_desafio($pm1, $estado) === false, 'Fase 6: PM1 deve rejeitar distância maior que um.');
    $verificar(avaliar_dica_desafio($cert, $estado) === true, 'Fase 6: CERT verdadeira deve ser reconhecida.');
    $certFalsa = $cert;
    $certFalsa['valor_fixo'] = 2;
    $verificar(avaliar_dica_desafio($certFalsa, $estado) === false, 'Fase 6: CERT falsa deve ser reconhecida.');

    $formasInvalidas = [];
    foreach (['EQ', 'M1', 'PM1'] as $relacao) {
        $formasInvalidas[] = ['ordem' => 1, 'relacao' => $relacao, 'info1' => [0, 0], 'info2' => null, 'valor_fixo' => null];
        $formasInvalidas[] = ['ordem' => 1, 'relacao' => $relacao, 'info1' => [0, 0], 'info2' => [1, 0], 'valor_fixo' => 1];
    }
    $formasInvalidas[] = ['ordem' => 1, 'relacao' => 'CERT', 'info1' => [0, 0], 'info2' => [1, 0], 'valor_fixo' => 1];
    $formasInvalidas[] = ['ordem' => 1, 'relacao' => 'CERT', 'info1' => [0, 0], 'info2' => null, 'valor_fixo' => null];
    $formasInvalidas[] = ['ordem' => 1, 'relacao' => 'DESCONHECIDA', 'info1' => [0, 0], 'info2' => [1, 0], 'valor_fixo' => null];
    $formasInvalidas[] = ['ordem' => 1, 'relacao' => 'EQ', 'info1' => [5, 0], 'info2' => [1, 0], 'valor_fixo' => null];
    $formasInvalidas[] = ['ordem' => 1, 'relacao' => 'EQ', 'info1' => [0, 0], 'info2' => [1], 'valor_fixo' => null];
    $formasInvalidas[] = ['ordem' => 1, 'relacao' => 'EQ', 'info1' => [0, 0], 'info2' => [1, null], 'valor_fixo' => null];
    foreach ($formasInvalidas as $formaInvalida) {
        $verificar(validar_dicas_desafio([$formaInvalida]) !== [], 'Fase 6: dica com aridade, relação ou coordenada inválida deve ser rejeitada.');
    }

    $montarTema = static function (string $nome): array {
        $categorias = [];
        for ($categoria = 0; $categoria < 5; $categoria++) {
            $valores = [];
            for ($informacao = 0; $informacao < 5; $informacao++) {
                $valores[] = ['posicao' => $informacao, 'nome' => "{$nome}-V{$categoria}{$informacao}"];
            }
            $categorias[] = [
                'posicao' => $categoria,
                'nome' => "{$nome}-C{$categoria}",
                'prefixo' => "{$nome}-P{$categoria}",
                'valores' => $valores,
            ];
        }
        return ['nome' => $nome, 'categorias' => $categorias];
    };
    foreach (['Clássico', 'Campus', 'Exploração espacial'] as $nomeTema) {
        $frase = renderizar_frase_dica($eq, $montarTema($nomeTema));
        $verificar(is_string($frase) && str_contains($frase, $nomeTema . '-P0'), "Fase 6: {$nomeTema} deve renderizar palavras próprias.");
    }
    $temaXss = $montarTema('<script>alert(1)</script>');
    $fraseXss = renderizar_frase_dica($eq, $temaXss);
    $verificar(!str_contains(e($fraseXss), '<script>'), 'Fase 6: a frase temática deve ser escapada ao chegar ao HTML.');
    $dicaConectivoXss = $eq;
    $dicaConectivoXss['conectivo'] = '<img src=x onerror=alert(1)>';
    $fraseConectivoXss = renderizar_frase_dica($dicaConectivoXss, $montarTema('Seguro'));
    $verificar(!str_contains(e($fraseConectivoXss), '<img'), 'Fase 6: conectivo dinâmico deve ser escapado ao chegar ao HTML.');

    $dicasForaDeOrdem = [$fixture['dicas'][2], $fixture['dicas'][0], $fixture['dicas'][1]];
    $renderizadasOrdenadas = renderizar_dicas_para_tema($dicasForaDeOrdem, $montarTema('Ordenado'));
    $verificar(array_column($renderizadasOrdenadas, 'ordem') === [1, 2, 3], 'Fase 6: as dicas devem ser renderizadas em ordem determinística.');

    $impossivel = [
        ['ordem' => 1, 'relacao' => 'CERT', 'info1' => [0, 0], 'info2' => null, 'valor_fixo' => 0],
        ['ordem' => 2, 'relacao' => 'CERT', 'info1' => [0, 0], 'info2' => null, 'valor_fixo' => 1],
    ];
    $verificar(contar_solucoes_desafio($impossivel)['status'] === 'impossivel', 'Fase 6: CSP contraditório deve ser impossível.');
    $ambiguo = [
        ['ordem' => 1, 'relacao' => 'CERT', 'info1' => [0, 0], 'info2' => null, 'valor_fixo' => 0],
    ];
    $resultadoAmbiguo = contar_solucoes_desafio($ambiguo, 2, 10000, 1000);
    $verificar($resultadoAmbiguo['status'] === 'ambiguo' && $resultadoAmbiguo['quantidade'] === 2, 'Fase 6: CSP subdeterminado deve parar após duas soluções.');
    $mesmaCasa = [
        ['ordem' => 1, 'relacao' => 'EQ', 'info1' => [0, 0], 'info2' => [0, 1], 'valor_fixo' => null],
    ];
    $verificar(contar_solucoes_desafio($mesmaCasa)['status'] === 'impossivel', 'Fase 6: AllDifferent deve impedir duas informações da categoria na mesma casa.');
    $verificar(contar_solucoes_desafio($ambiguo, 2, 1, 1000)['status'] === 'limite_excedido', 'Fase 6: o limite de nós deve interromper busca descontrolada.');

    $segundaVerificacao = contar_solucoes_desafio($fixture['dicas']);
    $verificar(
        $segundaVerificacao['status'] === $validacao['verificacao']['status']
        && $segundaVerificacao['quantidade'] === $validacao['verificacao']['quantidade']
        && $segundaVerificacao['solucao'] === $validacao['verificacao']['solucao'],
        'Fase 6: o mesmo conjunto de dicas deve produzir resultado determinístico.'
    );
    $fixtureAlterada = $fixture;
    $fixtureAlterada['dicas'][7]['valor_fixo'] = 3;
    $verificar(!validar_desafio_gerado($fixtureAlterada, true)['ok'], 'Fase 6: alterar uma dica de forma incompatível com a solução deve invalidar a fixture.');

    $fornecido = fornecer_desafio_exemplo('2026-09-09', 123);
    $verificar(($fornecido['metadados']['dia_instancia'] ?? '') === '2026-09-09', 'Fase 6: o provedor separado deve receber o dia.');
    $verificar(($fornecido['metadados']['semente'] ?? null) === 123, 'Fase 6: o contrato deve aceitar semente futura sem alterar a fixture.');

    $verificar(classificar_dia_desafio('2999-01-01')['status'] === 'futuro', 'Fase 6: data futura deve ser bloqueada.');
    $verificar(classificar_dia_desafio('2000-01-01')['status'] === 'passado', 'Fase 6: data passada deve ficar reservada à fase posterior.');
    $verificar(classificar_dia_desafio('2026-02-31')['status'] === 'invalido', 'Fase 6: data inexistente deve ser rejeitada.');

    $_SESSION['_desafios_em_andamento'] = [];
    $tentativa = iniciar_tentativa_desafio(10, 20, '2026-09-09', 30, 1000.0);
    $reaberta = iniciar_tentativa_desafio(10, 20, '2026-09-09', 30, 2000.0);
    $verificar($reaberta['inicio_unix'] === 1000.0, 'Fase 6: atualizar a página não deve reiniciar o cronômetro.');
    $verificar(tempo_decorrido_tentativa($tentativa, 1002.5) === 2500, 'Fase 6: o tempo deve ser calculado a partir de instantes do servidor.');
    encerrar_tentativa_desafio(10, 20);
    $verificar(tentativa_desafio_obter(10, 20) === null, 'Fase 6: tentativa concluída deve sair da sessão.');
    $verificar(tentativa_desafio_obter(10, 999) === null, 'Fase 6: POST sem início registrado não pode obter cronômetro.');

    resultado_desafio_guardar(['jogador_id' => 10, 'desafio_dia' => '2026-09-08']);
    $verificar(resultado_desafio_obter(10, '2026-09-09') === null, 'Fase 6: resultado antigo da sessão não pode aparecer em outro dia.');

    $schema = file_get_contents(dirname(__DIR__) . '/sql/schema.sql');
    $rotas = file_get_contents(dirname(__DIR__) . '/config/routes.php');
    $view = file_get_contents(dirname(__DIR__) . '/src/views/desafio.php');
    $javascript = file_get_contents(dirname(__DIR__) . '/public/assets/js/desafio.js');
    $fixtureTexto = file_get_contents(dirname(__DIR__) . '/resources/desafios/exemplo.php');
    $verificar(is_string($schema) && str_contains($schema, 'desafio_atribuicao'), 'Fase 6: o schema deve declarar atribuições normalizadas.');
    $verificar(is_string($schema) && str_contains($schema, 'desafio_dica'), 'Fase 6: o schema deve declarar dicas normalizadas.');
    $verificar(is_string($schema) && str_contains($schema, 'casa_posicao BETWEEN 0 AND 4'), 'Fase 6: as casas do banco devem usar 0..4.');
    $verificar(is_string($schema) && str_contains($schema, 'solucao_json JSON NULL'), 'Fase 6: JSON legado deve ser anulável e não autoritativo.');
    $verificar(is_string($rotas) && str_contains($rotas, 'csrf_exigir_valido()'), 'Fase 6: finalização deve preservar CSRF.');
    $verificar(is_string($rotas) && !str_contains($rotas, "get('/desafio/finalizar'"), 'Fase 6: finalização não pode aceitar GET.');
    foreach (['jogador_id', 'tema_id', 'dia', 'tempo', 'inicio', 'fim', 'tempo_milisegundos', 'solucao'] as $campoReservado) {
        $verificar(is_string($rotas) && str_contains($rotas, "'{$campoReservado}'"), "Fase 6: o POST deve rejeitar o campo reservado {$campoReservado}.");
    }
    $verificar(is_string($view) && !str_contains($view, "['solucao']"), 'Fase 6: a view não pode imprimir a solução.');
    $verificar(is_string($javascript) && !str_contains(mb_strtolower($javascript), 'solucao'), 'Fase 6: o JavaScript não pode conter ou buscar a solução.');
    $verificar(is_string($fixtureTexto) && !str_contains($fixtureTexto, 'Inglês'), 'Fase 6: a fixture lógica não deve guardar palavras do tema.');
    $verificar(is_string($schema) && !str_contains(mb_strtolower($schema), 'frase_renderizada'), 'Fase 6: frase renderizada não deve ser persistida como fonte lógica.');
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    if (!function_exists('app_config')) {
        function app_config(?string $key = null): mixed
        {
            $config = ['base_path' => '', 'environment' => 'test'];
            return $key === null ? $config : ($config[$key] ?? null);
        }
    }
    require dirname(__DIR__) . '/src/layout.php';
    require dirname(__DIR__) . '/src/desafio_formato.php';
    require dirname(__DIR__) . '/src/restricoes_desafio.php';
    require dirname(__DIR__) . '/src/verificador_csp.php';
    require dirname(__DIR__) . '/src/provedor_desafio_exemplo.php';
    require dirname(__DIR__) . '/src/desafios.php';
    require dirname(__DIR__) . '/src/sessao.php';
    session_id('fase6-' . bin2hex(random_bytes(8)));
    session_start();
    $falhasFase6 = [];
    executar_testes_unitarios_fase6(static function (bool $ok, string $descricao) use (&$falhasFase6): void {
        if (!$ok) {
            $falhasFase6[] = $descricao;
        }
    });
    session_destroy();
    if ($falhasFase6 !== []) {
        fwrite(STDERR, "Falhas unitárias da Fase 6:\n- " . implode("\n- ", $falhasFase6) . "\n");
        exit(1);
    }
    fwrite(STDOUT, "Todas as verificações unitárias da Fase 6 passaram.\n");
}
