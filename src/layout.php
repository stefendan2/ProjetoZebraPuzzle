<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    $basePath = (string) app_config('base_path');
    $path = '/' . ltrim($path, '/');

    return $basePath . ($path === '//' ? '/' : $path);
}

/** @param array<string, mixed> $dados */
function renderizar(string $view, array $dados = []): void
{
    if (preg_match('/^[a-z0-9_-]+$/i', $view) !== 1) {
        throw new InvalidArgumentException('Nome de view inválido.');
    }

    $viewPath = __DIR__ . '/views/' . $view . '.php';
    if (!is_file($viewPath)) {
        throw new RuntimeException('View não encontrada.');
    }

    extract($dados, EXTR_SKIP);
    $titulo = isset($titulo) && is_string($titulo) ? $titulo : (string) app_config('name');
    $mensagensFlash = flash_consumir();

    require __DIR__ . '/views/layout-header.php';
    require $viewPath;
    require __DIR__ . '/views/layout-footer.php';
}

