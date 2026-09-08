<?php
$classeResultado = match ($tipoResultado ?? 'erro') {
    'sucesso' => 'success',
    'aviso' => 'warning',
    default => 'danger',
};
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <h1 class="mb-4">Verificação de e-mail</h1>
        <div class="alert alert-<?= e($classeResultado) ?>" role="alert">
            <?= e($mensagemResultado ?? 'Não foi possível verificar o e-mail.') ?>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="<?= e(url('/login')) ?>">Ir para o login</a>
            <a class="btn btn-outline-secondary" href="<?= e(url('/reenviar-verificacao')) ?>">Solicitar novo link</a>
        </div>
    </div>
</div>

