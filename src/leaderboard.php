<?php

declare(strict_types=1);

/** @param array<string, mixed> $a @param array<string, mixed> $b */
function comparar_linhas_leaderboard(array $a, array $b): int
{
    return ((int) $a['tempo_milisegundos'] <=> (int) $b['tempo_milisegundos'])
        ?: ((string) $a['momento_conclusao'] <=> (string) $b['momento_conclusao'])
        ?: ((int) $a['id'] <=> (int) $b['id']);
}

/** @param list<array<string, mixed>> $linhas @return list<array<string, mixed>> */
function ordenar_e_numerar_leaderboard(array $linhas): array
{
    usort($linhas, 'comparar_linhas_leaderboard');
    foreach ($linhas as $indice => &$linha) {
        $linha['posicao'] = $indice + 1;
    }
    unset($linha);
    return $linhas;
}

/** @return list<array<string, mixed>> */
function listar_leaderboard_por_dia(PDO $pdo, string $dia): array
{
    if (!classificar_data_iso($dia)) {
        throw new InvalidArgumentException('O dia do leaderboard é inválido.');
    }
    $statement = $pdo->prepare(
        'SELECT l.id, l.dia, l.tempo_milisegundos, l.momento_conclusao,
                j.nome_usuario, r.desafio_dia AS resolucao_dia,
                r.tempo_milisegundos AS resolucao_tempo,
                r.concluida_em AS resolucao_conclusao,
                r.elegivel_leaderboard, l.jogador_id, r.jogador_id AS resolucao_jogador,
                r.id AS resolucao_existente, j.id AS jogador_existente
         FROM leaderboard l
         LEFT JOIN jogador j ON j.id = l.jogador_id
         LEFT JOIN resolucao r ON r.id = l.resolucao_id
         WHERE l.dia = :dia
         ORDER BY l.tempo_milisegundos ASC,
                  l.momento_conclusao ASC,
                  l.id ASC'
    );
    $statement->execute(['dia' => $dia]);
    $linhas = [];
    foreach ($statement->fetchAll() as $linha) {
        if ($linha['resolucao_existente'] === null || $linha['jogador_existente'] === null
            || (int) $linha['elegivel_leaderboard'] !== 1
            || (int) $linha['jogador_id'] !== (int) $linha['resolucao_jogador']
            || (string) $linha['resolucao_dia'] !== (string) $linha['dia']
            || (int) $linha['resolucao_tempo'] !== (int) $linha['tempo_milisegundos']
            || (string) $linha['resolucao_conclusao'] !== (string) $linha['momento_conclusao']
            || substr((string) $linha['resolucao_conclusao'], 0, 10) !== (string) $linha['dia']
        ) {
            throw new RuntimeException('Foi detectada divergência entre resolução e leaderboard.');
        }
        $linhas[] = [
            'id' => (int) $linha['id'],
            'nome_usuario' => (string) $linha['nome_usuario'],
            'tempo_milisegundos' => (int) $linha['tempo_milisegundos'],
            'momento_conclusao' => (string) $linha['momento_conclusao'],
        ];
    }
    return ordenar_e_numerar_leaderboard($linhas);
}
