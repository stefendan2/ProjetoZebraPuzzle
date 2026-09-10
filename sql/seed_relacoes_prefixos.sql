USE zebraPuzzle;

-- Fase 6: catálogo lógico de relações e prefixos de apresentação.
-- Idempotente, transacional e sem qualquer credencial.

DELIMITER $$

DROP PROCEDURE IF EXISTS seed_relacoes_prefixos_fase6$$

CREATE PROCEDURE seed_relacoes_prefixos_fase6()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        DROP TEMPORARY TABLE IF EXISTS fase6_prefixo;
        RESIGNAL;
    END;

    CREATE TEMPORARY TABLE fase6_prefixo (
        tema_nome VARCHAR(100) NOT NULL,
        categoria_posicao TINYINT UNSIGNED NOT NULL,
        categoria_nome VARCHAR(100) NOT NULL,
        prefixo VARCHAR(80) NOT NULL,
        PRIMARY KEY (tema_nome, categoria_posicao)
    );

    INSERT INTO fase6_prefixo
        (tema_nome, categoria_posicao, categoria_nome, prefixo)
    VALUES
        ('Clássico', 0, 'Nacionalidade', 'é'),
        ('Clássico', 1, 'Cor', 'mora na casa de cor'),
        ('Clássico', 2, 'Bebida', 'bebe'),
        ('Clássico', 3, 'Animal', 'tem como animal'),
        ('Clássico', 4, 'Profissão', 'trabalha como'),
        ('Campus', 0, 'Curso', 'cursa'),
        ('Campus', 1, 'Bloco', 'estuda no'),
        ('Campus', 2, 'Bebida', 'bebe'),
        ('Campus', 3, 'Transporte', 'vai de'),
        ('Campus', 4, 'Atividade', 'tem como atividade'),
        ('Exploração espacial', 0, 'Explorador', 'é'),
        ('Exploração espacial', 1, 'Planeta', 'explora'),
        ('Exploração espacial', 2, 'Cor da nave', 'pilota a nave'),
        ('Exploração espacial', 3, 'Robô', 'usa o robô'),
        ('Exploração espacial', 4, 'Missão', 'cumpre a missão');

    START TRANSACTION;

    INSERT INTO relacao_dica (codigo, nome, aridade, conectivo)
    VALUES
        ('CERT', 'Casa fixa', 1, 'fica na casa'),
        ('EQ', 'Mesma casa', 2, 'também'),
        ('M1', 'Imediatamente à esquerda', 2, 'está imediatamente à esquerda de quem'),
        ('PM1', 'Vizinhança', 2, 'é vizinho de quem')
    ON DUPLICATE KEY UPDATE
        nome = VALUES(nome),
        aridade = VALUES(aridade),
        conectivo = VALUES(conectivo);

    UPDATE tema_categoria tc
    INNER JOIN tema t ON t.id = tc.tema_id
    INNER JOIN fase6_prefixo p
        ON p.tema_nome = t.nome
       AND p.categoria_posicao = tc.posicao
       AND p.categoria_nome = tc.nome
    SET tc.prefixo = p.prefixo;

    IF (SELECT COUNT(*) FROM relacao_dica WHERE codigo IN ('CERT', 'EQ', 'M1', 'PM1')) <> 4 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'O seed foi cancelado: as quatro relações não foram persistidas.';
    END IF;

    IF (
        SELECT COUNT(*)
        FROM tema_categoria tc
        INNER JOIN tema t ON t.id = tc.tema_id
        INNER JOIN fase6_prefixo p
            ON p.tema_nome = t.nome
           AND p.categoria_posicao = tc.posicao
           AND p.categoria_nome = tc.nome
        WHERE tc.prefixo = p.prefixo
    ) <> 15 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'O seed foi cancelado: os 15 prefixos oficiais não puderam ser confirmados.';
    END IF;

    COMMIT;
    DROP TEMPORARY TABLE fase6_prefixo;
END$$

CALL seed_relacoes_prefixos_fase6()$$
DROP PROCEDURE seed_relacoes_prefixos_fase6$$

DELIMITER ;

-- Reversão: restaure os prefixos do backup. Não apague relações enquanto
-- desafio_dica as referenciar; o README contém a ordem segura de rollback.
