<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="mb-1">Área do jogador</h1>
        <p class="text-secondary mb-0">Olá, <?= e($jogador['nome_usuario'] ?? 'jogador') ?>.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="<?= e(url('/desafio')) ?>">Jogar desafio do dia</a>
        <a class="btn btn-outline-primary" href="<?= e(url('/minha-conta')) ?>">Editar minha conta</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5">Acesso confirmado</h2>
        <p>Seu e-mail está verificado e o acesso diário foi liberado.</p>
        <dl class="row mb-0">
            <dt class="col-sm-4">Data do banco</dt>
            <dd class="col-sm-8"><?= e($dadosBanco['data_atual'] ?? 'não identificada') ?></dd>
            <dt class="col-sm-4">Fuso da sessão</dt>
            <dd class="col-sm-8"><?= e($dadosBanco['fuso'] ?? 'não identificado') ?></dd>
        </dl>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5">Tema do teste</h2>
        <?php if (is_array($temaEfetivo ?? null)): ?>
            <p class="mb-1">
                <strong><?= e($temaEfetivo['nome']) ?></strong>
                <span class="badge text-bg-secondary">
                    <?= ($temaEfetivo['origem'] ?? '') === 'preferencia' ? 'preferência salva' : 'tema padrão' ?>
                </span>
            </p>
            <?php if (is_string($temaEfetivo['descricao'] ?? null) && $temaEfetivo['descricao'] !== ''): ?>
                <p class="text-secondary mb-3"><?= e($temaEfetivo['descricao']) ?></p>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/minha-conta')) ?>">Ver ou trocar tema</a>
            <a class="btn btn-sm btn-primary" href="<?= e(url('/desafio')) ?>">Abrir desafio</a>
        <?php else: ?>
            <div class="alert alert-warning mb-0" role="alert">
                Nenhum tema válido está disponível. As telas dependentes de tema permanecerão indisponíveis até a correção do catálogo.
            </div>
        <?php endif; ?>
    </div>
</div>

<form method="post" action="<?= e(url('/area-jogador/flash')) ?>" class="card card-body">
    <?= csrf_campo() ?>
    <p>Este POST comprova a proteção CSRF e o padrão POST → Redirect → GET.</p>
    <div><button type="submit" class="btn btn-primary">Testar mensagem flash</button></div>
</form>
