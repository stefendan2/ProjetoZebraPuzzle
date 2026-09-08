CREATE DATABASE IF NOT EXISTS zebraPuzzle
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE zebraPuzzle;

CREATE TABLE IF NOT EXISTS tema (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao VARCHAR(500) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_tema_nome UNIQUE (nome),
    CONSTRAINT ck_tema_ativo CHECK (ativo IN (0, 1))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tema_categoria (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tema_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    posicao TINYINT UNSIGNED NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_tema_categoria_nome UNIQUE (tema_id, nome),
    CONSTRAINT uq_tema_categoria_posicao UNIQUE (tema_id, posicao),
    CONSTRAINT ck_tema_categoria_posicao CHECK (posicao BETWEEN 1 AND 5),
    CONSTRAINT fk_tema_categoria_tema
        FOREIGN KEY (tema_id) REFERENCES tema (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- A faixa e a unicidade limitam as posições a 1..5. A Fase 5 deverá
-- validar em transação que cada tema termine com exatamente 5 categorias.

CREATE TABLE IF NOT EXISTS tema_valor (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    posicao TINYINT UNSIGNED NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_tema_valor_nome UNIQUE (categoria_id, nome),
    CONSTRAINT uq_tema_valor_posicao UNIQUE (categoria_id, posicao),
    CONSTRAINT ck_tema_valor_posicao CHECK (posicao BETWEEN 1 AND 5),
    CONSTRAINT fk_tema_valor_categoria
        FOREIGN KEY (categoria_id) REFERENCES tema_categoria (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- A Fase 5 deverá validar em transação que cada categoria termine com
-- exatamente 5 valores; o CHECK isolado não consegue contar outras linhas.

CREATE TABLE IF NOT EXISTS jogador (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cpf CHAR(11) NOT NULL,
    nome_usuario VARCHAR(50) NOT NULL,
    email VARCHAR(254) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    email_verificado TINYINT(1) NOT NULL DEFAULT 0,
    tema_preferido_id BIGINT UNSIGNED NULL,
    ofensiva_atual INT UNSIGNED NOT NULL DEFAULT 0,
    maior_ofensiva INT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_jogador_cpf UNIQUE (cpf),
    CONSTRAINT uq_jogador_email UNIQUE (email),
    CONSTRAINT ck_jogador_email_verificado CHECK (email_verificado IN (0, 1)),
    CONSTRAINT ck_jogador_ativo CHECK (ativo IN (0, 1)),
    CONSTRAINT ck_jogador_ofensiva CHECK (maior_ofensiva >= ofensiva_atual),
    CONSTRAINT fk_jogador_tema_preferido
        FOREIGN KEY (tema_preferido_id) REFERENCES tema (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS administrador (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome_usuario VARCHAR(50) NOT NULL,
    email VARCHAR(254) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_administrador_email UNIQUE (email),
    CONSTRAINT ck_administrador_ativo CHECK (ativo IN (0, 1))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS desafio_diario (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dia DATE NOT NULL,
    solucao_json JSON NOT NULL,
    pistas_json JSON NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_desafio_diario_dia UNIQUE (dia)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resolucao (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jogador_id BIGINT UNSIGNED NOT NULL,
    desafio_dia DATE NOT NULL,
    concluida_em DATETIME(6) NOT NULL,
    tempo_milisegundos BIGINT UNSIGNED NOT NULL,
    tema_id BIGINT UNSIGNED NOT NULL,
    elegivel_leaderboard TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_resolucao_elegivel CHECK (elegivel_leaderboard IN (0, 1)),
    CONSTRAINT fk_resolucao_jogador
        FOREIGN KEY (jogador_id) REFERENCES jogador (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_resolucao_desafio
        FOREIGN KEY (desafio_dia) REFERENCES desafio_diario (dia)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_resolucao_tema
        FOREIGN KEY (tema_id) REFERENCES tema (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_resolucao_jogador_dia (jogador_id, desafio_dia),
    INDEX idx_resolucao_conclusao (concluida_em)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS leaderboard (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dia DATE NOT NULL,
    jogador_id BIGINT UNSIGNED NOT NULL,
    resolucao_id BIGINT UNSIGNED NOT NULL,
    tempo_milisegundos BIGINT UNSIGNED NOT NULL,
    momento_conclusao DATETIME(6) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_leaderboard_dia_jogador UNIQUE (dia, jogador_id),
    CONSTRAINT uq_leaderboard_resolucao UNIQUE (resolucao_id),
    CONSTRAINT fk_leaderboard_desafio
        FOREIGN KEY (dia) REFERENCES desafio_diario (dia)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_leaderboard_jogador
        FOREIGN KEY (jogador_id) REFERENCES jogador (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_leaderboard_resolucao
        FOREIGN KEY (resolucao_id) REFERENCES resolucao (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_leaderboard_ordem (dia, tempo_milisegundos, momento_conclusao)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS acesso_diario (
    jogador_id BIGINT UNSIGNED NOT NULL,
    dia DATE NOT NULL,
    captcha_validado_em DATETIME NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (jogador_id, dia),
    CONSTRAINT fk_acesso_diario_jogador
        FOREIGN KEY (jogador_id) REFERENCES jogador (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS verificacao_email (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jogador_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expira_em DATETIME NOT NULL,
    utilizado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    email_pendente VARCHAR(254) NULL,
    CONSTRAINT uq_verificacao_email_token UNIQUE (token_hash),
    CONSTRAINT fk_verificacao_email_jogador
        FOREIGN KEY (jogador_id) REFERENCES jogador (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_verificacao_email_jogador (jogador_id),
    INDEX idx_verificacao_email_expiracao (expira_em)
) ENGINE=InnoDB;