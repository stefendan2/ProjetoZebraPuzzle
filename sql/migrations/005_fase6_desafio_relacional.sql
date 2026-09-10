USE zebraPuzzle;

-- Fase 6: normalização de posições e persistência relacional do desafio.
-- IMPORTANTE: faça o backup indicado no README antes de executar. DDL no
-- MariaDB pode confirmar transações implicitamente.

DELIMITER $$

DROP PROCEDURE IF EXISTS migrar_fase6$$

CREATE PROCEDURE migrar_fase6()
BEGIN
    DECLARE categorias_zero INT DEFAULT 0;
    DECLARE categorias_cinco INT DEFAULT 0;
    DECLARE categorias_total INT DEFAULT 0;
    DECLARE valores_zero INT DEFAULT 0;
    DECLARE valores_cinco INT DEFAULT 0;
    DECLARE valores_total INT DEFAULT 0;
    DECLARE atribuicoes_existem INT DEFAULT 0;
    DECLARE dicas_existem INT DEFAULT 0;

    SELECT COUNT(*) INTO atribuicoes_existem
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'desafio_atribuicao';

    SELECT COUNT(*) INTO dicas_existem
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'desafio_dica';

    IF EXISTS (
        SELECT 1
        FROM desafio_diario
        WHERE solucao_json IS NOT NULL OR pistas_json IS NOT NULL
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Migração interrompida: há desafios JSON legados. Preserve-os e prepare uma conversão específica antes da Fase 6.';
    END IF;

    IF EXISTS (SELECT 1 FROM desafio_diario LIMIT 1)
       AND (atribuicoes_existem = 0 OR dicas_existem = 0) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Migração interrompida: há desafio sem todas as tabelas relacionais da Fase 6.';
    END IF;

    IF atribuicoes_existem = 1 AND dicas_existem = 1 THEN
        IF EXISTS (
            SELECT 1
            FROM desafio_diario dd
            LEFT JOIN desafio_atribuicao da ON da.desafio_id = dd.id
            GROUP BY dd.id
            HAVING COUNT(da.desafio_id) <> 25
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Migração interrompida: existe desafio sem as 25 atribuições normalizadas.';
        END IF;
        IF EXISTS (
            SELECT 1
            FROM desafio_diario dd
            LEFT JOIN desafio_dica di ON di.desafio_id = dd.id
            GROUP BY dd.id
            HAVING COUNT(di.id) = 0
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Migração interrompida: existe desafio sem dicas normalizadas.';
        END IF;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'tema_categoria'
          AND COLUMN_NAME = 'prefixo'
    ) THEN
        ALTER TABLE tema_categoria
            ADD COLUMN prefixo VARCHAR(80) NULL AFTER nome;
    END IF;

    SELECT COUNT(*), SUM(posicao = 0), SUM(posicao = 5)
      INTO categorias_total, categorias_zero, categorias_cinco
    FROM tema_categoria;
    SELECT COUNT(*), SUM(posicao = 0), SUM(posicao = 5)
      INTO valores_total, valores_zero, valores_cinco
    FROM tema_valor;

    SET categorias_zero = COALESCE(categorias_zero, 0);
    SET categorias_cinco = COALESCE(categorias_cinco, 0);
    SET valores_zero = COALESCE(valores_zero, 0);
    SET valores_cinco = COALESCE(valores_cinco, 0);

    IF EXISTS (SELECT 1 FROM tema_categoria WHERE posicao > 5)
       OR EXISTS (SELECT 1 FROM tema_valor WHERE posicao > 5) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Migração interrompida: há posição de tema fora das faixas reconhecidas 0..4 ou 1..5.';
    END IF;

    IF (categorias_zero > 0 AND categorias_cinco > 0)
       OR (valores_zero > 0 AND valores_cinco > 0) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Migração interrompida: foram encontradas posições 0-based e 1-based misturadas.';
    END IF;

    IF (categorias_total > 0 AND categorias_zero = 0 AND categorias_cinco = 0)
       OR (valores_total > 0 AND valores_zero = 0 AND valores_cinco = 0) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Migração interrompida: não foi possível identificar com segurança a convenção das posições.';
    END IF;

    IF (categorias_cinco > 0 AND valores_zero > 0)
       OR (categorias_zero > 0 AND valores_cinco > 0) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Migração interrompida: categorias e informações usam convenções diferentes.';
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME = 'tema_categoria'
          AND CONSTRAINT_NAME = 'ck_tema_categoria_posicao'
    ) THEN
        ALTER TABLE tema_categoria DROP CONSTRAINT ck_tema_categoria_posicao;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME = 'tema_valor'
          AND CONSTRAINT_NAME = 'ck_tema_valor_posicao'
    ) THEN
        ALTER TABLE tema_valor DROP CONSTRAINT ck_tema_valor_posicao;
    END IF;

    -- A faixa temporária evita colisões nos índices UNIQUE durante a subtração.
    -- IDs e ordem relativa permanecem exatamente os mesmos.
    IF categorias_cinco > 0 THEN
        UPDATE tema_categoria SET posicao = posicao + 10 WHERE posicao BETWEEN 1 AND 5;
        UPDATE tema_categoria SET posicao = posicao - 11 WHERE posicao BETWEEN 11 AND 15;
    END IF;

    IF valores_cinco > 0 THEN
        UPDATE tema_valor SET posicao = posicao + 10 WHERE posicao BETWEEN 1 AND 5;
        UPDATE tema_valor SET posicao = posicao - 11 WHERE posicao BETWEEN 11 AND 15;
    END IF;

    IF EXISTS (SELECT 1 FROM tema_categoria WHERE posicao NOT BETWEEN 0 AND 4)
       OR EXISTS (SELECT 1 FROM tema_valor WHERE posicao NOT BETWEEN 0 AND 4) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Migração interrompida: a conversão para 0..4 não terminou de forma íntegra.';
    END IF;

    ALTER TABLE tema_categoria
        ADD CONSTRAINT ck_tema_categoria_posicao CHECK (posicao BETWEEN 0 AND 4);
    ALTER TABLE tema_valor
        ADD CONSTRAINT ck_tema_valor_posicao CHECK (posicao BETWEEN 0 AND 4);

    ALTER TABLE desafio_diario
        MODIFY solucao_json JSON NULL,
        MODIFY pistas_json JSON NULL;

    CREATE TABLE IF NOT EXISTS desafio_atribuicao (
        desafio_id BIGINT UNSIGNED NOT NULL,
        categoria_posicao TINYINT UNSIGNED NOT NULL,
        informacao_posicao TINYINT UNSIGNED NOT NULL,
        casa_posicao TINYINT UNSIGNED NOT NULL,
        PRIMARY KEY (desafio_id, categoria_posicao, informacao_posicao),
        CONSTRAINT uq_desafio_atribuicao_casa UNIQUE (desafio_id, categoria_posicao, casa_posicao),
        CONSTRAINT ck_desafio_atribuicao_categoria CHECK (categoria_posicao BETWEEN 0 AND 4),
        CONSTRAINT ck_desafio_atribuicao_informacao CHECK (informacao_posicao BETWEEN 0 AND 4),
        CONSTRAINT ck_desafio_atribuicao_casa CHECK (casa_posicao BETWEEN 0 AND 4),
        CONSTRAINT fk_desafio_atribuicao_desafio
            FOREIGN KEY (desafio_id) REFERENCES desafio_diario (id)
            ON UPDATE CASCADE ON DELETE CASCADE
    ) ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS relacao_dica (
        codigo VARCHAR(5) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
        nome VARCHAR(80) NOT NULL,
        aridade TINYINT UNSIGNED NOT NULL,
        conectivo VARCHAR(100) NOT NULL,
        CONSTRAINT ck_relacao_dica_aridade CHECK (aridade IN (1, 2))
    ) ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS desafio_dica (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        desafio_id BIGINT UNSIGNED NOT NULL,
        ordem TINYINT UNSIGNED NOT NULL,
        relacao_codigo VARCHAR(5) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        cat_info1 TINYINT UNSIGNED NOT NULL,
        pos_info1 TINYINT UNSIGNED NOT NULL,
        cat_info2 TINYINT UNSIGNED NULL,
        pos_info2 TINYINT UNSIGNED NULL,
        valor_fixo TINYINT UNSIGNED NULL,
        CONSTRAINT uq_desafio_dica_ordem UNIQUE (desafio_id, ordem),
        CONSTRAINT ck_desafio_dica_ordem CHECK (ordem BETWEEN 1 AND 255),
        CONSTRAINT ck_desafio_dica_info1
            CHECK (cat_info1 BETWEEN 0 AND 4 AND pos_info1 BETWEEN 0 AND 4),
        CONSTRAINT ck_desafio_dica_info2 CHECK (
            (cat_info2 IS NULL AND pos_info2 IS NULL)
            OR (cat_info2 IS NOT NULL AND pos_info2 IS NOT NULL
                AND cat_info2 BETWEEN 0 AND 4 AND pos_info2 BETWEEN 0 AND 4)
        ),
        CONSTRAINT ck_desafio_dica_forma CHECK (
            (relacao_codigo = 'CERT' AND cat_info2 IS NULL AND pos_info2 IS NULL
                AND valor_fixo IS NOT NULL AND valor_fixo BETWEEN 0 AND 4)
            OR
            (relacao_codigo IN ('EQ', 'M1', 'PM1')
                AND cat_info2 IS NOT NULL AND pos_info2 IS NOT NULL AND valor_fixo IS NULL)
        ),
        CONSTRAINT fk_desafio_dica_desafio
            FOREIGN KEY (desafio_id) REFERENCES desafio_diario (id)
            ON UPDATE CASCADE ON DELETE CASCADE,
        CONSTRAINT fk_desafio_dica_relacao
            FOREIGN KEY (relacao_codigo) REFERENCES relacao_dica (codigo)
            ON UPDATE CASCADE ON DELETE RESTRICT,
        CONSTRAINT fk_desafio_dica_info1
            FOREIGN KEY (desafio_id, cat_info1, pos_info1)
            REFERENCES desafio_atribuicao (desafio_id, categoria_posicao, informacao_posicao)
            ON UPDATE CASCADE ON DELETE CASCADE,
        CONSTRAINT fk_desafio_dica_info2
            FOREIGN KEY (desafio_id, cat_info2, pos_info2)
            REFERENCES desafio_atribuicao (desafio_id, categoria_posicao, informacao_posicao)
            ON UPDATE CASCADE ON DELETE CASCADE,
        INDEX idx_desafio_dica_relacao (relacao_codigo)
    ) ENGINE=InnoDB;
END$$

CALL migrar_fase6()$$
DROP PROCEDURE migrar_fase6$$

DELIMITER ;

-- A migração pode ser repetida quando já estiver integralmente em 0..4 e as
-- tabelas relacionais existirem. Se houver desafio JSON legado sem tabelas
-- normalizadas, ela para antes de modificar os dados.
