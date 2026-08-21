<?php

/**
 * Autoload simples e previsível para a estrutura do projeto.
 *
 * Mapeia namespaces da aplicação para a pasta app/core/ usando a convenção:
 * core\Classe -> app/core/Classe.php
 * core\subpasta\Classe -> app/core/subpasta/Classe.php
 */
spl_autoload_register(function (string $className): void {
    $prefix = 'core\\';
    $baseDir = __DIR__ . '/app/core/';

    if (strncmp($className, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($className, strlen($prefix));
    $relativePath = str_replace('\\', '/', $relativeClass) . '.php';
    $file = $baseDir . $relativePath;

    if (is_file($file)) {
        require_once $file;
    }
});
