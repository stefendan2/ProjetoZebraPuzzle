<?php

/**
 * Configuração exclusiva de banco de dados.
 *
 * Este arquivo concentra apenas os dados necessários para abrir a conexão PDO.
 * Ele fica fora de public/ para evitar exposição direta pelo navegador.
 */
return [
    'host' => 'localhost',
    'port' => 3306,
    'dbname' => 'projeto',
    'charset' => 'utf8mb4',
    'username' => 'root',
    'password' => 'senha',
];
