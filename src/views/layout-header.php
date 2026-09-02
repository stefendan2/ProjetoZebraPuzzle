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
    <div class="container">
        <a class="navbar-brand" href="<?= e(url('/')) ?>">Zebra Puzzle</a>
        <div class="d-flex align-items-center gap-3 text-light">
            <?php if (usuario_logado()): ?>
                <span>Conta ativa</span>
            <?php endif; ?>
            <span class="badge text-bg-secondary" aria-label="Indicador de ofensiva ainda indisponível">
                Ofensiva: disponível na Fase 8
            </span>
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

