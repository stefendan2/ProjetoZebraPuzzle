USE zebraPuzzle;

-- Fase 5: catálogo inicial de temas.
-- O procedimento temporário mantém os dados em uma única transação e é removido ao final.
-- Registros existentes com o mesmo nome são preservados. Qualquer conflito ou tema
-- incompleto provoca rollback integral da execução.

DELIMITER $$

DROP PROCEDURE IF EXISTS seed_temas_fase5$$

CREATE PROCEDURE seed_temas_fase5()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        DROP TEMPORARY TABLE IF EXISTS fase5_seed_valor;
        DROP TEMPORARY TABLE IF EXISTS fase5_seed_categoria;
        DROP TEMPORARY TABLE IF EXISTS fase5_seed_tema;
        RESIGNAL;
    END;

    CREATE TEMPORARY TABLE fase5_seed_tema (
        codigo VARCHAR(20) PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        descricao VARCHAR(500) NOT NULL
    );

    CREATE TEMPORARY TABLE fase5_seed_categoria (
        tema_codigo VARCHAR(20) NOT NULL,
        posicao TINYINT UNSIGNED NOT NULL,
        nome VARCHAR(100) NOT NULL,
        PRIMARY KEY (tema_codigo, posicao)
    );

    CREATE TEMPORARY TABLE fase5_seed_valor (
        tema_codigo VARCHAR(20) NOT NULL,
        categoria_posicao TINYINT UNSIGNED NOT NULL,
        posicao TINYINT UNSIGNED NOT NULL,
        nome VARCHAR(100) NOT NULL,
        PRIMARY KEY (tema_codigo, categoria_posicao, posicao)
    );

    INSERT INTO fase5_seed_tema (codigo, nome, descricao) VALUES
        ('classico', 'Clássico', 'Uma releitura genérica do teste de Einstein com pessoas, cores, bebidas, animais e profissões.'),
        ('campus', 'Campus', 'Um desafio ambientado em um campus, combinando cursos, blocos, bebidas, transportes e atividades.'),
        ('espaco', 'Exploração espacial', 'Uma missão lógica com exploradores, planetas, naves, robôs e objetivos espaciais.');

    INSERT INTO fase5_seed_categoria (tema_codigo, posicao, nome) VALUES
        ('classico', 1, 'Nacionalidade'),
        ('classico', 2, 'Cor'),
        ('classico', 3, 'Bebida'),
        ('classico', 4, 'Animal'),
        ('classico', 5, 'Profissão'),
        ('campus', 1, 'Curso'),
        ('campus', 2, 'Bloco'),
        ('campus', 3, 'Bebida'),
        ('campus', 4, 'Transporte'),
        ('campus', 5, 'Atividade'),
        ('espaco', 1, 'Explorador'),
        ('espaco', 2, 'Planeta'),
        ('espaco', 3, 'Cor da nave'),
        ('espaco', 4, 'Robô'),
        ('espaco', 5, 'Missão');

    INSERT INTO fase5_seed_valor (tema_codigo, categoria_posicao, posicao, nome) VALUES
        ('classico', 1, 1, 'Alemão'),
        ('classico', 1, 2, 'Dinamarquês'),
        ('classico', 1, 3, 'Inglês'),
        ('classico', 1, 4, 'Norueguês'),
        ('classico', 1, 5, 'Sueco'),
        ('classico', 2, 1, 'Amarela'),
        ('classico', 2, 2, 'Azul'),
        ('classico', 2, 3, 'Branca'),
        ('classico', 2, 4, 'Verde'),
        ('classico', 2, 5, 'Vermelha'),
        ('classico', 3, 1, 'Água'),
        ('classico', 3, 2, 'Café'),
        ('classico', 3, 3, 'Chá'),
        ('classico', 3, 4, 'Leite'),
        ('classico', 3, 5, 'Suco'),
        ('classico', 4, 1, 'Cachorro'),
        ('classico', 4, 2, 'Cavalo'),
        ('classico', 4, 3, 'Gato'),
        ('classico', 4, 4, 'Pássaro'),
        ('classico', 4, 5, 'Peixe'),
        ('classico', 5, 1, 'Arquiteto'),
        ('classico', 5, 2, 'Fotógrafo'),
        ('classico', 5, 3, 'Médico'),
        ('classico', 5, 4, 'Professor'),
        ('classico', 5, 5, 'Programador'),
        ('campus', 1, 1, 'Administração'),
        ('campus', 1, 2, 'Automação'),
        ('campus', 1, 3, 'Informática'),
        ('campus', 1, 4, 'Mecatrônica'),
        ('campus', 1, 5, 'Redes'),
        ('campus', 2, 1, 'Bloco A'),
        ('campus', 2, 2, 'Bloco B'),
        ('campus', 2, 3, 'Bloco C'),
        ('campus', 2, 4, 'Bloco D'),
        ('campus', 2, 5, 'Bloco E'),
        ('campus', 3, 1, 'Água'),
        ('campus', 3, 2, 'Café'),
        ('campus', 3, 3, 'Chá'),
        ('campus', 3, 4, 'Suco'),
        ('campus', 3, 5, 'Vitamina'),
        ('campus', 4, 1, 'Bicicleta'),
        ('campus', 4, 2, 'Caminhada'),
        ('campus', 4, 3, 'Metrô'),
        ('campus', 4, 4, 'Ônibus'),
        ('campus', 4, 5, 'Trem'),
        ('campus', 5, 1, 'Biblioteca'),
        ('campus', 5, 2, 'Laboratório'),
        ('campus', 5, 3, 'Pesquisa'),
        ('campus', 5, 4, 'Robótica'),
        ('campus', 5, 5, 'Xadrez'),
        ('espaco', 1, 1, 'Aurora'),
        ('espaco', 1, 2, 'Caio'),
        ('espaco', 1, 3, 'Luna'),
        ('espaco', 1, 4, 'Nilo'),
        ('espaco', 1, 5, 'Ravi'),
        ('espaco', 2, 1, 'Aster'),
        ('espaco', 2, 2, 'Boreal'),
        ('espaco', 2, 3, 'Cígnus'),
        ('espaco', 2, 4, 'Duna'),
        ('espaco', 2, 5, 'Érebo'),
        ('espaco', 3, 1, 'Azul'),
        ('espaco', 3, 2, 'Branca'),
        ('espaco', 3, 3, 'Dourada'),
        ('espaco', 3, 4, 'Prateada'),
        ('espaco', 3, 5, 'Vermelha'),
        ('espaco', 4, 1, 'Atlas'),
        ('espaco', 4, 2, 'Byte'),
        ('espaco', 4, 3, 'Cosmo'),
        ('espaco', 4, 4, 'Nexo'),
        ('espaco', 4, 5, 'Órion'),
        ('espaco', 5, 1, 'Cartografia'),
        ('espaco', 5, 2, 'Coleta'),
        ('espaco', 5, 3, 'Comunicação'),
        ('espaco', 5, 4, 'Pesquisa'),
        ('espaco', 5, 5, 'Resgate');

    START TRANSACTION;

    INSERT INTO tema (nome, descricao, ativo)
    SELECT s.nome, s.descricao, 1
    FROM fase5_seed_tema s
    LEFT JOIN tema t ON t.nome = s.nome
    WHERE t.id IS NULL;

    INSERT INTO tema_categoria (tema_id, nome, posicao)
    SELECT t.id, sc.nome, sc.posicao
    FROM fase5_seed_categoria sc
    INNER JOIN fase5_seed_tema st ON st.codigo = sc.tema_codigo
    INNER JOIN tema t ON t.nome = st.nome
    LEFT JOIN tema_categoria tc
        ON tc.tema_id = t.id
       AND tc.nome = sc.nome
       AND tc.posicao = sc.posicao
    WHERE tc.id IS NULL;

    INSERT INTO tema_valor (categoria_id, nome, posicao)
    SELECT tc.id, sv.nome, sv.posicao
    FROM fase5_seed_valor sv
    INNER JOIN fase5_seed_tema st ON st.codigo = sv.tema_codigo
    INNER JOIN tema t ON t.nome = st.nome
    INNER JOIN tema_categoria tc
        ON tc.tema_id = t.id
       AND tc.posicao = sv.categoria_posicao
    LEFT JOIN tema_valor tv
        ON tv.categoria_id = tc.id
       AND tv.nome = sv.nome
       AND tv.posicao = sv.posicao
    WHERE tv.id IS NULL;

    IF EXISTS (
        SELECT 1
        FROM fase5_seed_tema st
        INNER JOIN tema t ON t.nome = st.nome
        WHERE t.ativo <> 1
           OR (SELECT COUNT(*) FROM tema_categoria tc WHERE tc.tema_id = t.id) <> 5
           OR EXISTS (
                SELECT 1
                FROM tema_categoria tc
                WHERE tc.tema_id = t.id
                  AND (SELECT COUNT(*) FROM tema_valor tv WHERE tv.categoria_id = tc.id) <> 5
           )
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'O seed foi cancelado: todos os temas devem permanecer ativos e completos no formato 5 x 5.';
    END IF;

    COMMIT;

    DROP TEMPORARY TABLE fase5_seed_valor;
    DROP TEMPORARY TABLE fase5_seed_categoria;
    DROP TEMPORARY TABLE fase5_seed_tema;
END$$

CALL seed_temas_fase5()$$
DROP PROCEDURE seed_temas_fase5$$

DELIMITER ;

-- REVERSÃO MANUAL SEGURA (não é executada por este arquivo):
-- 1. Confirme antes que nenhum jogador ou resolução referencia os três temas.
-- 2. Apague primeiro tema_valor, depois tema_categoria e, por último, tema,
--    filtrando EXATAMENTE por nome IN ('Clássico', 'Campus', 'Exploração espacial').
-- 3. O roteiro do README contém as consultas completas e as verificações prévias.
