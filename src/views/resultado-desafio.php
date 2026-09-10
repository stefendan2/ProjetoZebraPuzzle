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
                    <dd class="col-sm-7">Pendente da Fase 7 (valor provisório: não elegível)</dd>
                </dl>
                <a class="btn btn-primary" href="<?= e(url('/area-jogador')) ?>">Voltar à área do jogador</a>
            </div>
        </div>
    </div>
</div>
