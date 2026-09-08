<div class="row justify-content-center">
    <div class="col-lg-6">
        <h1 class="mb-3">Entrar</h1>
        <p class="text-secondary">Escolha explicitamente o perfil usado neste acesso.</p>

        <form method="post" action="<?= e(url('/login')) ?>" class="card card-body" novalidate>
            <?= csrf_campo() ?>

            <fieldset class="mb-3">
                <legend class="form-label fs-6">Tipo de conta</legend>
                <?php foreach (['jogador' => 'Jogador', 'administrador' => 'Administrador'] as $valor => $rotulo): ?>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="tipo"
                               id="tipo_<?= e($valor) ?>" value="<?= e($valor) ?>"
                               <?= ($dados['tipo'] ?? '') === $valor ? 'checked' : '' ?> required>
                        <label class="form-check-label" for="tipo_<?= e($valor) ?>"><?= e($rotulo) ?></label>
                    </div>
                <?php endforeach; ?>
                <?php if (isset($erros['tipo'])): ?><div class="invalid-feedback d-block"><?= e($erros['tipo']) ?></div><?php endif; ?>
            </fieldset>

            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input class="form-control<?= isset($erros['email']) ? ' is-invalid' : '' ?>"
                       id="email" name="email" type="email" maxlength="254" required
                       autocomplete="email" value="<?= e($dados['email'] ?? '') ?>">
                <?php if (isset($erros['email'])): ?><div class="invalid-feedback"><?= e($erros['email']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="senha">Senha</label>
                <input class="form-control<?= isset($erros['senha']) ? ' is-invalid' : '' ?>"
                       id="senha" name="senha" type="password" required autocomplete="current-password">
                <?php if (isset($erros['senha'])): ?><div class="invalid-feedback"><?= e($erros['senha']) ?></div><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Continuar</button>
            <a class="mt-3" href="<?= e(url('/reenviar-verificacao')) ?>">Preciso de um novo link de verificação</a>
        </form>
    </div>
</div>
