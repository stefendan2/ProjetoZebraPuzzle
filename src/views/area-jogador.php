<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="mb-1">Área do jogador</h1>
        <p class="text-secondary mb-0">Olá, <?= e($jogador['nome_usuario'] ?? 'jogador') ?>.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= e(url('/minha-conta')) ?>">Editar minha conta</a>
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

<form method="post" action="<?= e(url('/area-jogador/flash')) ?>" class="card card-body">
    <?= csrf_campo() ?>
    <p>Este POST comprova a proteção CSRF e o padrão POST → Redirect → GET.</p>
    <div><button type="submit" class="btn btn-primary">Testar mensagem flash</button></div>
</form>
