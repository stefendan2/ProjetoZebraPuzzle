<?php

declare(strict_types=1);

/*
 * Fixture abstrata aprovada para a Fase 6.
 *
 * Convenção única: categoria, informação e casa pertencem a 0..4.
 * A passagem das casas originais 1..5 para 0..4 foi uma subtração de 1;
 * nenhuma categoria, informação ou associação foi reordenada.
 */
return [
    'versao_formato' => 1,
    'metadados' => [
        'codigo' => 'fase6-exemplo-v1-0based',
        'provisorio' => true,
        'descricao' => 'Puzzle fixo usado até a integração do gerador definitivo.',
    ],
    'solucao' => [
        ['categoria' => 0, 'informacao' => 0, 'casa' => 0],
        ['categoria' => 0, 'informacao' => 1, 'casa' => 1],
        ['categoria' => 0, 'informacao' => 2, 'casa' => 4],
        ['categoria' => 0, 'informacao' => 3, 'casa' => 3],
        ['categoria' => 0, 'informacao' => 4, 'casa' => 2],
        ['categoria' => 1, 'informacao' => 0, 'casa' => 3],
        ['categoria' => 1, 'informacao' => 1, 'casa' => 1],
        ['categoria' => 1, 'informacao' => 2, 'casa' => 2],
        ['categoria' => 1, 'informacao' => 3, 'casa' => 0],
        ['categoria' => 1, 'informacao' => 4, 'casa' => 4],
        ['categoria' => 2, 'informacao' => 0, 'casa' => 0],
        ['categoria' => 2, 'informacao' => 1, 'casa' => 3],
        ['categoria' => 2, 'informacao' => 2, 'casa' => 1],
        ['categoria' => 2, 'informacao' => 3, 'casa' => 4],
        ['categoria' => 2, 'informacao' => 4, 'casa' => 2],
        ['categoria' => 3, 'informacao' => 0, 'casa' => 1],
        ['categoria' => 3, 'informacao' => 1, 'casa' => 4],
        ['categoria' => 3, 'informacao' => 2, 'casa' => 0],
        ['categoria' => 3, 'informacao' => 3, 'casa' => 2],
        ['categoria' => 3, 'informacao' => 4, 'casa' => 3],
        ['categoria' => 4, 'informacao' => 0, 'casa' => 4],
        ['categoria' => 4, 'informacao' => 1, 'casa' => 1],
        ['categoria' => 4, 'informacao' => 2, 'casa' => 0],
        ['categoria' => 4, 'informacao' => 3, 'casa' => 2],
        ['categoria' => 4, 'informacao' => 4, 'casa' => 3],
    ],
    'dicas' => [
        ['ordem' => 1, 'relacao' => 'EQ', 'info1' => [1, 2], 'info2' => [0, 4], 'valor_fixo' => null],
        ['ordem' => 2, 'relacao' => 'EQ', 'info1' => [1, 4], 'info2' => [4, 0], 'valor_fixo' => null],
        ['ordem' => 3, 'relacao' => 'EQ', 'info1' => [1, 1], 'info2' => [2, 2], 'valor_fixo' => null],
        ['ordem' => 4, 'relacao' => 'M1', 'info1' => [0, 3], 'info2' => [0, 2], 'valor_fixo' => null],
        ['ordem' => 5, 'relacao' => 'EQ', 'info1' => [0, 3], 'info2' => [2, 1], 'valor_fixo' => null],
        ['ordem' => 6, 'relacao' => 'EQ', 'info1' => [3, 3], 'info2' => [4, 3], 'valor_fixo' => null],
        ['ordem' => 7, 'relacao' => 'EQ', 'info1' => [0, 0], 'info2' => [3, 2], 'valor_fixo' => null],
        ['ordem' => 8, 'relacao' => 'CERT', 'info1' => [2, 4], 'info2' => null, 'valor_fixo' => 2],
        ['ordem' => 9, 'relacao' => 'CERT', 'info1' => [1, 3], 'info2' => null, 'valor_fixo' => 0],
        ['ordem' => 10, 'relacao' => 'PM1', 'info1' => [3, 0], 'info2' => [4, 2], 'valor_fixo' => null],
        ['ordem' => 11, 'relacao' => 'PM1', 'info1' => [4, 1], 'info2' => [3, 2], 'valor_fixo' => null],
        ['ordem' => 12, 'relacao' => 'EQ', 'info1' => [3, 1], 'info2' => [2, 3], 'valor_fixo' => null],
        ['ordem' => 13, 'relacao' => 'EQ', 'info1' => [1, 0], 'info2' => [3, 4], 'valor_fixo' => null],
        ['ordem' => 14, 'relacao' => 'PM1', 'info1' => [1, 3], 'info2' => [0, 1], 'valor_fixo' => null],
        ['ordem' => 15, 'relacao' => 'PM1', 'info1' => [3, 0], 'info2' => [2, 0], 'valor_fixo' => null],
    ],
];
