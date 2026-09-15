<h1>Desafios anteriores</h1>
<p class="text-secondary">Escolha um dia para jogar. Conclusões feitas depois do dia original ficam somente no histórico.</p>
<?php if ($dias === []): ?>
    <p class="alert alert-info" role="status">Ainda não há desafios anteriores disponíveis.</p>
<?php else: ?>
    <ul class="list-group mb-4">
        <?php foreach ($dias as $item): ?>
            <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                <time datetime="<?= e($item['dia']) ?>"><?= e($item['dia']) ?></time>
                <a class="btn btn-outline-primary" href="<?= e(url('/desafio?dia=' . rawurlencode((string) $item['dia']))) ?>"
                   aria-label="Abrir desafio de <?= e($item['dia']) ?>">Abrir desafio</a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<a href="<?= e(url('/desafio')) ?>">Jogar o desafio de hoje</a>
