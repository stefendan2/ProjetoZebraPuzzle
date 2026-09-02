<h1 class="mb-4">Área de teste da Fase 3</h1>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5">Conexão com o banco</h2>
        <dl class="row mb-0">
            <dt class="col-sm-4">Banco selecionado</dt>
            <dd class="col-sm-8"><?= e($dadosBanco['banco'] ?? 'não identificado') ?></dd>
            <dt class="col-sm-4">Data do banco</dt>
            <dd class="col-sm-8"><?= e($dadosBanco['data_atual'] ?? 'não identificada') ?></dd>
            <dt class="col-sm-4">Fuso da sessão</dt>
            <dd class="col-sm-8"><?= e($dadosBanco['fuso'] ?? 'não identificado') ?></dd>
        </dl>
    </div>
</div>

<form method="post" action="<?= e(url('/area-jogador/flash')) ?>" class="card card-body">
    <?= csrf_campo() ?>
    <p>Este POST valida o token CSRF e usa o padrão POST → Redirect → GET.</p>
    <div>
        <button type="submit" class="btn btn-primary">Testar mensagem flash</button>
    </div>
</form>
