<?php

declare(strict_types=1);

namespace ZebraPuzzle\Core;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function __construct(private readonly string $basePath = '')
    {
    }

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? rawurldecode($path) : '/';
        $path = $this->removeBasePath($path);
        $path = $this->normalizePath($path);

        if (isset($this->routes[$method][$path])) {
            ($this->routes[$method][$path])();
            return;
        }

        $allowedMethods = [];
        foreach ($this->routes as $registeredMethod => $routes) {
            if (isset($routes[$path])) {
                $allowedMethods[] = $registeredMethod;
            }
        }

        if ($allowedMethods !== []) {
            http_response_code(405);
            header('Allow: ' . implode(', ', $allowedMethods));
            $this->renderError('Método não permitido.');
            return;
        }

        http_response_code(404);
        $this->renderError('Página não encontrada.');
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function removeBasePath(string $path): string
    {
        if ($this->basePath === '') {
            return $path;
        }

        if ($path === $this->basePath) {
            return '/';
        }

        $prefix = $this->basePath . '/';
        if (str_starts_with($path, $prefix)) {
            return substr($path, strlen($this->basePath));
        }

        return $path;
    }

    private function renderError(string $message): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        echo '<!doctype html><html lang="pt-BR"><meta charset="UTF-8">';
        echo '<title>Erro</title><body><h1>' . $safeMessage . '</h1></body></html>';
    }
}

