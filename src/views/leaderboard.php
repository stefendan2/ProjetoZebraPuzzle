<h1>Leaderboard diário</h1>
<p class="text-secondary">A primeira resolução válida de cada jogador no dia original define sua marca.</p>
<form method="get" action="<?= e(url('/leaderboard')) ?>" class="d-flex flex-wrap align-items-end gap-2 mb-4">
    <div>
        <label for="dia-leaderboard" class="form-label">Dia da classificação</label>
        <input type="date" name="dia" id="dia-leaderboard" class="form-control" required
               max="<?= e(dia_de_referencia()) ?>" value="<?= e($diaSelecionado ?? '') ?>">
    </div>
    <button type="submit" class="btn btn-primary">Consultar</button>
</form>
<?php if ($linhas === null): ?>
    <p role="status">Selecione um dia para consultar a classificação.</p>
<?php elseif ($linhas === []): ?>
    <p class="alert alert-info" role="status">Não há classificação para <?= e($diaSelecionado) ?>.</p>
<?php else: ?>
    <div class="table-responsive" tabindex="0" role="region" aria-label="Classificação diária">
        <table class="table table-striped align-middle tabela-leaderboard">
            <caption>Classificação de <?= e($diaSelecionado) ?>. Ordem: menor tempo, conclusão mais antiga e desempate técnico.</caption>
            <thead><tr><th scope="col">Posição</th><th scope="col">Jogador</th><th scope="col">Tempo</th><th scope="col">Conclusão</th></tr></thead>
            <tbody>
            <?php foreach ($linhas as $linha): ?>
                <tr>
                    <th scope="row"><?= e($linha['posicao']) ?></th>
                    <td><?= e($linha['nome_usuario']) ?></td>
                    <td class="tempo-ranking"><?= e(formatar_tempo_resolucao((int) $linha['tempo_milisegundos'])) ?></td>
                    <td><time><?= e($linha['momento_conclusao']) ?></time></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
