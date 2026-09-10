<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> — Zebra Puzzle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(url('/assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-dark navbar-dark">
    <div class="container gap-3">
        <a class="navbar-brand" href="<?= e(url('/')) ?>">Zebra Puzzle</a>
        <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 ms-auto">
            <?php if (usuario_logado('jogador')): ?>
                <a class="btn btn-sm btn-outline-light" href="<?= e(url('/area-jogador')) ?>">Área do jogador</a>
                <a class="btn btn-sm btn-primary" href="<?= e(url('/desafio')) ?>">Desafio</a>
                <a class="btn btn-sm btn-outline-light" href="<?= e(url('/minha-conta')) ?>">Minha conta</a>
            <?php elseif (usuario_logado('administrador')): ?>
                <a class="btn btn-sm btn-outline-light" href="<?= e(url('/area-admin')) ?>">Área administrativa</a>
            <?php else: ?>
                <a class="btn btn-sm btn-outline-light" href="<?= e(url('/cadastro')) ?>">Criar conta</a>
                <a class="btn btn-sm btn-primary" href="<?= e(url('/login')) ?>">Entrar</a>
            <?php endif; ?>

            <?php if (usuario_logado()): ?>
                <form method="post" action="<?= e(url('/logout')) ?>" class="d-inline">
                    <?= csrf_campo() ?>
                    <button type="submit" class="btn btn-sm btn-outline-warning">Sair</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?php foreach ($mensagensFlash as $flash): ?>
        <?php
        $classe = match ($flash['tipo']) {
            'sucesso' => 'success',
            'erro' => 'danger',
            'aviso' => 'warning',
            default => 'info',
        };
        ?>
        <div class="alert alert-<?= e($classe) ?>" role="alert">
            <?= e($flash['mensagem']) ?>
        </div>
    <?php endforeach; ?>
