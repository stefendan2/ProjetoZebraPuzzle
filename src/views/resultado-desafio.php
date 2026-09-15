<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="h3">Desafio concluído</h1>
                <p class="lead">Sua resolução foi validada e registrada pelo servidor.</p>
                <dl class="row mb-4">
                    <dt class="col-sm-5">Dia do desafio</dt>
                    <dd class="col-sm-7"><?= e($resultado['desafio_dia']) ?></dd>
                    <dt class="col-sm-5">Conclusão</dt>
                    <dd class="col-sm-7"><?= e($resultado['concluida_em']) ?></dd>
                    <dt class="col-sm-5">Tempo</dt>
                    <dd class="col-sm-7"><?= e(formatar_tempo_resolucao((int) $resultado['tempo_milisegundos'])) ?></dd>
                    <dt class="col-sm-5">Tema usado</dt>
                    <dd class="col-sm-7"><?= e($resultado['tema_nome'] ?? ('#' . $resultado['tema_id'])) ?></dd>
                    <dt class="col-sm-5">Elegibilidade</dt>
                    <dd class="col-sm-7"><?= (int) $resultado['elegivel_leaderboard'] === 1 ? 'Entrou no leaderboard' : 'Somente histórico' ?></dd>
                </dl>
                <p><?= e(mensagem_elegibilidade((string) $resultado['motivo_elegibilidade'])) ?></p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a class="btn btn-primary" href="<?= e(url('/desafio?dia=' . rawurlencode((string) $resultado['desafio_dia']))) ?>">Jogar novamente</a>
                    <a class="btn btn-outline-primary" href="<?= e(url('/leaderboard?dia=' . rawurlencode((string) $resultado['desafio_dia']))) ?>">Ver leaderboard</a>
                    <a class="btn btn-outline-primary" href="<?= e(url('/historico')) ?>">Desafios anteriores</a>
                </div>
                <a class="btn btn-primary" href="<?= e(url('/area-jogador')) ?>">Voltar à área do jogador</a>
            </div>
        </div>
    </div>
</div>
