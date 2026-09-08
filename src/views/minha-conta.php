<div class="row justify-content-center">
    <div class="col-lg-9">
        <h1 class="mb-3">Minha conta</h1>
        <p class="text-secondary">
            O e-mail atual é <strong><?= e($emailAtual) ?></strong>.
            <?php if (is_string($emailPendente ?? null)): ?>
                A confirmação de <strong><?= e($emailPendente) ?></strong> está pendente.
            <?php endif; ?>
        </p>

        <?php if (is_string($linkVerificacao ?? null)): ?>
            <div class="alert alert-warning" role="alert">
                <strong>Somente em desenvolvimento:</strong>
                <a href="<?= e($linkVerificacao) ?>">confirmar o novo e-mail</a>.
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/minha-conta')) ?>" class="card card-body mb-4" novalidate>
            <?= csrf_campo() ?>

            <div class="mb-3">
                <label class="form-label" for="email">E-mail desejado</label>
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
                <label class="form-label" for="senha">Nova senha</label>
                <input class="form-control<?= isset($erros['senha']) ? ' is-invalid' : '' ?>"
                       id="senha" name="senha" type="password" minlength="8" autocomplete="new-password">
                <div class="form-text">Deixe em branco para manter a senha atual.</div>
                <?php if (isset($erros['senha'])): ?><div class="invalid-feedback"><?= e($erros['senha']) ?></div><?php endif; ?>
            </div>

            <?php if (isset($erros['conta'])): ?><div class="alert alert-danger"><?= e($erros['conta']) ?></div><?php endif; ?>
            <button type="submit" class="btn btn-primary">Salvar alterações</button>
        </form>

        <section class="card card-body" aria-labelledby="titulo-tema">
            <h2 class="h4" id="titulo-tema">Tema do teste de Einstein</h2>

            <?php if (!is_array($temaEfetivo ?? null)): ?>
                <div class="alert alert-warning mb-0" role="alert">
                    Nenhum tema completo e ativo está disponível. A seleção ficará bloqueada até que o catálogo seja corrigido.
                </div>
            <?php else: ?>
                <p class="mb-1">
                    Tema em uso: <strong><?= e($temaEfetivo['nome']) ?></strong>
                    <span class="badge text-bg-secondary">
                        <?= ($temaEfetivo['origem'] ?? '') === 'preferencia' ? 'preferência salva' : 'tema padrão' ?>
                    </span>
                </p>
                <?php if (($temaEfetivo['origem'] ?? '') === 'padrao' && ($temaPreferidoId ?? null) !== null): ?>
                    <div class="alert alert-warning mt-3" role="alert">
                        A preferência anteriormente gravada não está mais elegível. O tema padrão foi aplicado sem apagar o registro anterior.
                    </div>
                <?php endif; ?>
                <?php if (is_string($temaEfetivo['descricao'] ?? null) && $temaEfetivo['descricao'] !== ''): ?>
                    <p class="text-secondary"><?= e($temaEfetivo['descricao']) ?></p>
                <?php endif; ?>

                <form method="post" action="<?= e(url('/minha-conta/tema')) ?>" class="mb-4">
                    <?= csrf_campo() ?>
                    <label class="form-label" for="tema_id">Escolha um tema completo</label>
                    <div class="input-group">
                        <select class="form-select" id="tema_id" name="tema_id" required>
                            <?php foreach (($temas ?? []) as $tema): ?>
                                <option value="<?= e($tema['id']) ?>"<?= (int) $tema['id'] === (int) $temaEfetivo['id'] ? ' selected' : '' ?>>
                                    <?= e($tema['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">Salvar tema</button>
                    </div>
                    <div class="form-text">A alteração só ocorre depois de pressionar “Salvar tema”.</div>
                </form>

                <h3 class="h5">Estrutura do tema em uso</h3>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                        <tr><th scope="col">Posição</th><th scope="col">Categoria</th><th scope="col">Valores em ordem</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($temaEfetivo['categorias'] as $categoria): ?>
                            <tr>
                                <td><?= e($categoria['posicao']) ?></td>
                                <th scope="row"><?= e($categoria['nome']) ?></th>
                                <td><?= e(implode(', ', array_column($categoria['valores'], 'nome'))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
