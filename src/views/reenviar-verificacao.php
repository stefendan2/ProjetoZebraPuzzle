<div class="row justify-content-center">
    <div class="col-lg-7">
        <h1 class="mb-3">Reenviar verificação</h1>
        <p class="text-secondary">Por segurança, a resposta não informa se o e-mail está cadastrado.</p>

        <?php if (is_string($linkVerificacao ?? null)): ?>
            <div class="alert alert-warning" role="alert">
                <strong>Somente em desenvolvimento:</strong>
                <a href="<?= e($linkVerificacao) ?>">usar o novo link de verificação</a>.
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/reenviar-verificacao')) ?>" class="card card-body" novalidate>
            <?= csrf_campo() ?>
            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input class="form-control<?= isset($erros['email']) ? ' is-invalid' : '' ?>"
                       id="email" name="email" type="email" maxlength="254" required
                       autocomplete="email" value="<?= e($dados['email'] ?? '') ?>">
                <?php if (isset($erros['email'])): ?><div class="invalid-feedback"><?= e($erros['email']) ?></div><?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Gerar novo link</button>
        </form>
    </div>
</div>

