<?php

declare(strict_types=1);

const ELEGIBILIDADE_PRIMEIRA_VALIDA = 'primeira_resolucao_valida';
const ELEGIBILIDADE_REJOGADA = 'rejogada';
const ELEGIBILIDADE_CONCLUSAO_TARDIA = 'conclusao_fora_do_dia';
const ELEGIBILIDADE_RESPOSTA_INVALIDA = 'resposta_invalida';

/**
 * Única decisão de elegibilidade da aplicação. Não consulta banco, sessão,
 * requisição nem view.
 *
 * @return array{elegivel:bool, motivo:string}
 */
function decidir_elegibilidade_resolucao(
    bool $respostaCorreta,
    string $diaDesafio,
    string $diaConclusao,
    bool $jogadorJaOcupaLeaderboard
): array {
    if (!classificar_data_iso($diaDesafio) || !classificar_data_iso($diaConclusao)) {
        throw new InvalidArgumentException('A elegibilidade exige datas ISO válidas.');
    }
    if (!$respostaCorreta) {
        return ['elegivel' => false, 'motivo' => ELEGIBILIDADE_RESPOSTA_INVALIDA];
    }
    if (!hash_equals($diaDesafio, $diaConclusao)) {
        return ['elegivel' => false, 'motivo' => ELEGIBILIDADE_CONCLUSAO_TARDIA];
    }
    if ($jogadorJaOcupaLeaderboard) {
        return ['elegivel' => false, 'motivo' => ELEGIBILIDADE_REJOGADA];
    }
    return ['elegivel' => true, 'motivo' => ELEGIBILIDADE_PRIMEIRA_VALIDA];
}

function mensagem_elegibilidade(string $motivo): string
{
    return match ($motivo) {
        ELEGIBILIDADE_PRIMEIRA_VALIDA =>
            'Entrou no leaderboard por ser sua primeira resolução válida concluída no dia do desafio.',
        ELEGIBILIDADE_REJOGADA =>
            'Ficou somente no histórico porque é uma rejogada. O primeiro resultado elegível foi preservado.',
        ELEGIBILIDADE_CONCLUSAO_TARDIA =>
            'Ficou somente no histórico porque o desafio foi concluído depois do dia original.',
        ELEGIBILIDADE_RESPOSTA_INVALIDA =>
            'Uma resposta inválida não gera resolução nem entrada no leaderboard.',
        default => 'A elegibilidade desta resolução não pôde ser classificada.',
    };
}
