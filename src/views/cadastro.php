<div class="row justify-content-center">
    <div class="col-lg-7">
        <h1 class="mb-3">Criar conta de jogador</h1>
        <p class="text-secondary">Todos os campos são obrigatórios. A conta fica pendente até a confirmação do e-mail.</p>

        <?php if (is_string($linkVerificacao ?? null)): ?>
            <div class="alert alert-warning" role="alert">
                <strong>Somente em desenvolvimento:</strong>
                <a href="<?= e($linkVerificacao) ?>">confirmar este e-mail</a>.
                O link é mostrado uma única vez nesta sessão.
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/cadastro')) ?>" class="card card-body" novalidate>
            <?= csrf_campo() ?>

            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input class="form-control<?= isset($erros['email']) ? ' is-invalid' : '' ?>"
                       id="email" name="email" type="email" maxlength="254" required
                       autocomplete="email" value="<?= e($dados['email'] ?? '') ?>">
                <?php if (isset($erros['email'])): ?><div class="invalid-feedback"><?= e($erros['email']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="cpf">CPF</label>
                <input class="form-control<?= isset($erros['cpf']) ? ' is-invalid' : '' ?>"
                       id="cpf" name="cpf" inputmode="numeric" maxlength="14" required
                       value="<?= e($dados['cpf'] ?? '') ?>">
                <?php if (isset($erros['cpf'])): ?><div class="invalid-feedback"><?= e($erros['cpf']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="nome_usuario">Nome de usuário</label>
                <input class="form-control<?= isset($erros['nome_usuario']) ? ' is-invalid' : '' ?>"
                       id="nome_usuario" name="nome_usuario" maxlength="50" required
                       autocomplete="username" value="<?= e($dados['nome_usuario'] ?? '') ?>">
                <?php if (isset($erros['nome_usuario'])): ?><div class="invalid-feedback"><?= e($erros['nome_usuario']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="senha">Senha</label>
                <input class="form-control<?= isset($erros['senha']) ? ' is-invalid' : '' ?>"
                       id="senha" name="senha" type="password" minlength="8" required
                       autocomplete="new-password">
                <div class="form-text">Use pelo menos 8 caracteres.</div>
                <?php if (isset($erros['senha'])): ?><div class="invalid-feedback"><?= e($erros['senha']) ?></div><?php endif; ?>
            </div>

            <?php if (isset($erros['conta'])): ?><div class="alert alert-danger"><?= e($erros['conta']) ?></div><?php endif; ?>
            <button type="submit" class="btn btn-primary">Cadastrar</button>
        </form>
    </div>
</div>

