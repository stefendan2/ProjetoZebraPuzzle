<section class="p-4 p-md-5 mb-4 rounded-3 bg-body-tertiary">
    <div class="container-fluid py-2">
        <h1 class="display-5 fw-bold">Zebra Puzzle</h1>
        <p class="col-md-9 fs-5">
            Desafios lógicos diários modelados como Problemas de Satisfação de Restrições.
        </p>
        <?php if (!usuario_logado()): ?>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="<?= e(url('/cadastro')) ?>">Criar conta de jogador</a>
                <a class="btn btn-outline-secondary" href="<?= e(url('/login')) ?>">Entrar</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="row g-4">
    <div class="col-md-6">
        <article class="card h-100">
            <div class="card-body">
                <h2 class="h4">Conta e acesso</h2>
                <p class="mb-0">
                    Cadastro, verificação de e-mail, login por perfil, captcha diário do jogador,
                    edição da conta e logout estão disponíveis nesta fase.
                </p>
            </div>
        </article>
    </div>
    <div class="col-md-6">
        <article class="card h-100">
            <div class="card-body">
                <h2 class="h4">Escopo preservado</h2>
                <p class="mb-0">
                    Temas, puzzle, cronômetro, leaderboard e ofensiva permanecem reservados
                    às próximas fases do roadmap.
                </p>
            </div>
        </article>
    </div>
</div>

