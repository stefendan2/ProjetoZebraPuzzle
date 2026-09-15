<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <h1 class="mb-1"><?= !empty($historico) ? 'Desafio anterior' : 'Desafio do dia' ?></h1>
        <p class="text-secondary mb-0">
            Dia <?= e($desafio['dia']) ?> · Tema <?= e($tema['nome']) ?>
        </p>
    </div>
    <div class="cronometro" aria-label="Tempo decorrido">
        <span class="small text-secondary d-block">Tempo no servidor</span>
        <strong id="cronometro-desafio" data-decorrido-ms="<?= e($tempoDecorridoMs) ?>">00:00</strong>
    </div>
</div>

<div class="alert alert-info" role="note">
    Cada linha deve usar suas cinco opções exatamente uma vez. O tempo continua ao atualizar ou sair da conta; o tema desta tentativa permanece o mesmo.
</div>
<?php if (!empty($historico)): ?>
    <p class="alert alert-warning" role="note">Este desafio é anterior a hoje. Sua conclusão ficará no histórico, sem alterar o ranking original.</p>
<?php endif; ?>

<?php if (isset($erros['grade'])): ?>
    <div class="alert alert-danger" role="alert"><?= e($erros['grade']) ?></div>
<?php endif; ?>

<form method="post" action="<?= e(url('/desafio/finalizar')) ?>" id="form-desafio" novalidate>
    <?= csrf_campo() ?>
    <input type="hidden" name="tentativa_id" value="<?= e($tentativa['id']) ?>">
    <div class="table-responsive desafio-grade-wrapper mb-3">
        <table class="table desafio-grade align-middle">
            <thead>
            <tr>
                <th scope="col">Categoria</th>
                <?php for ($casa = 0; $casa < 5; $casa++): ?>
                    <th scope="col" class="text-center">Casa #<?= e($casa) ?></th>
                <?php endfor; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($tema['categorias'] as $categoria): ?>
                <?php $categoriaPosicao = (int) $categoria['posicao']; ?>
                <tr data-categoria="<?= e($categoriaPosicao) ?>">
                    <th scope="row">
                        <?= e($categoria['nome']) ?>
                        <span class="d-block small text-secondary">índice <?= e($categoriaPosicao) ?></span>
                    </th>
                    <?php for ($casa = 0; $casa < 5; $casa++): ?>
                        <?php $selecionado = $grade[$categoriaPosicao][$casa] ?? ''; ?>
                        <td>
                            <label class="visually-hidden" for="grade-<?= e($categoriaPosicao) ?>-<?= e($casa) ?>">
                                <?= e($categoria['nome']) ?> na casa <?= e($casa) ?>
                            </label>
                            <select
                                class="form-select grade-select"
                                id="grade-<?= e($categoriaPosicao) ?>-<?= e($casa) ?>"
                                name="grade[<?= e($categoriaPosicao) ?>][<?= e($casa) ?>]"
                                data-categoria="<?= e($categoriaPosicao) ?>"
                                data-casa="<?= e($casa) ?>"
                                required
                            >
                                <option value="">Selecione</option>
                                <?php foreach ($categoria['valores'] as $valor): ?>
                                    <option
                                        value="<?= e($valor['posicao']) ?>"
                                        <?= (string) $selecionado === (string) $valor['posicao'] ? 'selected' : '' ?>
                                    ><?= e($valor['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    <?php endfor; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <button type="button" id="desfazer-grade" class="btn btn-outline-secondary" disabled aria-label="Desfazer última alteração">↶ Desfazer</button>
        <button type="button" id="refazer-grade" class="btn btn-outline-secondary" disabled aria-label="Refazer última alteração">↷ Refazer</button>
        <button type="submit" class="btn btn-primary ms-md-auto">Finalizar teste</button>
    </div>

    <section class="card card-body" aria-labelledby="titulo-dicas">
        <h2 class="h4" id="titulo-dicas">Dicas</h2>
        <p class="small text-secondary">A marcação é apenas uma ajuda visual. A conferência final sempre acontece no servidor.</p>
        <ol class="lista-dicas row row-cols-1 row-cols-lg-2 g-0 mb-0">
            <?php foreach ($dicas as $dica): ?>
                <li
                    class="col dica-item"
                    data-relacao="<?= e($dica['relacao']) ?>"
                    data-cat1="<?= e($dica['info1'][0]) ?>"
                    data-info1="<?= e($dica['info1'][1]) ?>"
                    data-cat2="<?= e($dica['info2'][0] ?? '') ?>"
                    data-info2="<?= e($dica['info2'][1] ?? '') ?>"
                    data-valor-fixo="<?= e($dica['valor_fixo'] ?? '') ?>"
                >
                    <span class="dica-status" aria-hidden="true"></span>
                    <span><?= e($dica['frase']) ?></span>
                    <span class="visually-hidden dica-status-text">Ainda não verificada.</span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
</form>

<script src="<?= e(url('/assets/js/desafio.js')) ?>" defer></script>
