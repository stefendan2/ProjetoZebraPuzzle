USE zebraPuzzle;

-- Catálogo oficial das Fases 5 e 6.
-- Categorias, informações e casas usam a mesma convenção lógica 0..4.
-- O procedimento temporário garante transação, validação integral e idempotência.

DELIMITER $$

DROP PROCEDURE IF EXISTS seed_temas_fase6$$

CREATE PROCEDURE seed_temas_fase6()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        DROP TEMPORARY TABLE IF EXISTS fase6_seed_valor;
        DROP TEMPORARY TABLE IF EXISTS fase6_seed_categoria;
        DROP TEMPORARY TABLE IF EXISTS fase6_seed_tema;
        RESIGNAL;
    END;

    CREATE TEMPORARY TABLE fase6_seed_tema (
        codigo VARCHAR(20) PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        descricao VARCHAR(500) NOT NULL
    );

    CREATE TEMPORARY TABLE fase6_seed_categoria (
        tema_codigo VARCHAR(20) NOT NULL,
        posicao TINYINT UNSIGNED NOT NULL,
        nome VARCHAR(100) NOT NULL,
        prefixo VARCHAR(80) NOT NULL,
        PRIMARY KEY (tema_codigo, posicao)
    );

    CREATE TEMPORARY TABLE fase6_seed_valor (
        tema_codigo VARCHAR(20) NOT NULL,
        categoria_posicao TINYINT UNSIGNED NOT NULL,
        posicao TINYINT UNSIGNED NOT NULL,
        nome VARCHAR(100) NOT NULL,
        PRIMARY KEY (tema_codigo, categoria_posicao, posicao)
    );

    INSERT INTO fase6_seed_tema (codigo, nome, descricao) VALUES
        ('classico', 'Clássico', 'Uma releitura genérica do teste de Einstein com pessoas, cores, bebidas, animais e profissões.'),
        ('campus', 'Campus', 'Um desafio ambientado em um campus, combinando cursos, blocos, bebidas, transportes e atividades.'),
        ('espaco', 'Exploração espacial', 'Uma missão lógica com exploradores, planetas, naves, robôs e objetivos espaciais.');

    INSERT INTO fase6_seed_categoria (tema_codigo, posicao, nome, prefixo) VALUES
        ('classico', 0, 'Nacionalidade', 'é'),
        ('classico', 1, 'Cor', 'mora na casa de cor'),
        ('classico', 2, 'Bebida', 'bebe'),
        ('classico', 3, 'Animal', 'tem como animal'),
        ('classico', 4, 'Profissão', 'trabalha como'),
        ('campus', 0, 'Curso', 'cursa'),
        ('campus', 1, 'Bloco', 'estuda no'),
        ('campus', 2, 'Bebida', 'bebe'),
        ('campus', 3, 'Transporte', 'vai de'),
        ('campus', 4, 'Atividade', 'tem como atividade'),
        ('espaco', 0, 'Explorador', 'é'),
        ('espaco', 1, 'Planeta', 'explora'),
        ('espaco', 2, 'Cor da nave', 'pilota a nave'),
        ('espaco', 3, 'Robô', 'usa o robô'),
        ('espaco', 4, 'Missão', 'cumpre a missão');

    INSERT INTO fase6_seed_valor (tema_codigo, categoria_posicao, posicao, nome) VALUES
        ('classico', 0, 0, 'Alemão'), ('classico', 0, 1, 'Dinamarquês'),
        ('classico', 0, 2, 'Inglês'), ('classico', 0, 3, 'Norueguês'),
        ('classico', 0, 4, 'Sueco'),
        ('classico', 1, 0, 'Amarela'), ('classico', 1, 1, 'Azul'),
        ('classico', 1, 2, 'Branca'), ('classico', 1, 3, 'Verde'),
        ('classico', 1, 4, 'Vermelha'),
        ('classico', 2, 0, 'Água'), ('classico', 2, 1, 'Café'),
        ('classico', 2, 2, 'Chá'), ('classico', 2, 3, 'Leite'),
        ('classico', 2, 4, 'Suco'),
        ('classico', 3, 0, 'Cachorro'), ('classico', 3, 1, 'Cavalo'),
        ('classico', 3, 2, 'Gato'), ('classico', 3, 3, 'Pássaro'),
        ('classico', 3, 4, 'Peixe'),
        ('classico', 4, 0, 'Arquiteto'), ('classico', 4, 1, 'Fotógrafo'),
        ('classico', 4, 2, 'Médico'), ('classico', 4, 3, 'Professor'),
        ('classico', 4, 4, 'Programador'),
        ('campus', 0, 0, 'Administração'), ('campus', 0, 1, 'Automação'),
        ('campus', 0, 2, 'Informática'), ('campus', 0, 3, 'Mecatrônica'),
        ('campus', 0, 4, 'Redes'),
        ('campus', 1, 0, 'Bloco A'), ('campus', 1, 1, 'Bloco B'),
        ('campus', 1, 2, 'Bloco C'), ('campus', 1, 3, 'Bloco D'),
        ('campus', 1, 4, 'Bloco E'),
        ('campus', 2, 0, 'Água'), ('campus', 2, 1, 'Café'),
        ('campus', 2, 2, 'Chá'), ('campus', 2, 3, 'Suco'),
        ('campus', 2, 4, 'Vitamina'),
        ('campus', 3, 0, 'Bicicleta'), ('campus', 3, 1, 'Caminhada'),
        ('campus', 3, 2, 'Metrô'), ('campus', 3, 3, 'Ônibus'),
        ('campus', 3, 4, 'Trem'),
        ('campus', 4, 0, 'Biblioteca'), ('campus', 4, 1, 'Laboratório'),
        ('campus', 4, 2, 'Pesquisa'), ('campus', 4, 3, 'Robótica'),
        ('campus', 4, 4, 'Xadrez'),
        ('espaco', 0, 0, 'Aurora'), ('espaco', 0, 1, 'Caio'),
        ('espaco', 0, 2, 'Luna'), ('espaco', 0, 3, 'Nilo'),
        ('espaco', 0, 4, 'Ravi'),
        ('espaco', 1, 0, 'Aster'), ('espaco', 1, 1, 'Boreal'),
        ('espaco', 1, 2, 'Cígnus'), ('espaco', 1, 3, 'Duna'),
        ('espaco', 1, 4, 'Érebo'),
        ('espaco', 2, 0, 'Azul'), ('espaco', 2, 1, 'Branca'),
        ('espaco', 2, 2, 'Dourada'), ('espaco', 2, 3, 'Prateada'),
        ('espaco', 2, 4, 'Vermelha'),
        ('espaco', 3, 0, 'Atlas'), ('espaco', 3, 1, 'Byte'),
        ('espaco', 3, 2, 'Cosmo'), ('espaco', 3, 3, 'Nexo'),
        ('espaco', 3, 4, 'Órion'),
        ('espaco', 4, 0, 'Cartografia'), ('espaco', 4, 1, 'Coleta'),
        ('espaco', 4, 2, 'Comunicação'), ('espaco', 4, 3, 'Pesquisa'),
        ('espaco', 4, 4, 'Resgate');

    START TRANSACTION;

    INSERT INTO tema (nome, descricao, ativo)
    SELECT s.nome, s.descricao, 1
    FROM fase6_seed_tema s
    LEFT JOIN tema t ON t.nome = s.nome
    WHERE t.id IS NULL;

    INSERT INTO tema_categoria (tema_id, nome, prefixo, posicao)
    SELECT t.id, sc.nome, sc.prefixo, sc.posicao
    FROM fase6_seed_categoria sc
    INNER JOIN fase6_seed_tema st ON st.codigo = sc.tema_codigo
    INNER JOIN tema t ON t.nome = st.nome
    LEFT JOIN tema_categoria tc
        ON tc.tema_id = t.id AND tc.nome = sc.nome AND tc.posicao = sc.posicao
    WHERE tc.id IS NULL;

    UPDATE tema_categoria tc
    INNER JOIN tema t ON t.id = tc.tema_id
    INNER JOIN fase6_seed_tema st ON st.nome = t.nome
    INNER JOIN fase6_seed_categoria sc
        ON sc.tema_codigo = st.codigo
       AND sc.posicao = tc.posicao
       AND sc.nome = tc.nome
    SET tc.prefixo = sc.prefixo;

    INSERT INTO tema_valor (categoria_id, nome, posicao)
    SELECT tc.id, sv.nome, sv.posicao
    FROM fase6_seed_valor sv
    INNER JOIN fase6_seed_tema st ON st.codigo = sv.tema_codigo
    INNER JOIN tema t ON t.nome = st.nome
    INNER JOIN tema_categoria tc
        ON tc.tema_id = t.id AND tc.posicao = sv.categoria_posicao
    LEFT JOIN tema_valor tv
        ON tv.categoria_id = tc.id AND tv.nome = sv.nome AND tv.posicao = sv.posicao
    WHERE tv.id IS NULL;

    IF EXISTS (
        SELECT 1
        FROM fase6_seed_tema st
        INNER JOIN tema t ON t.nome = st.nome
        WHERE t.ativo <> 1
           OR (SELECT COUNT(*) FROM tema_categoria tc WHERE tc.tema_id = t.id) <> 5
           OR EXISTS (
                SELECT 1
                FROM tema_categoria tc
                WHERE tc.tema_id = t.id
                  AND (
                      tc.prefixo IS NULL OR TRIM(tc.prefixo) = ''
                      OR (SELECT COUNT(*) FROM tema_valor tv WHERE tv.categoria_id = tc.id) <> 5
                  )
           )
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'O seed foi cancelado: todos os temas devem permanecer ativos, completos, prefixados e no formato lógico 0..4.';
    END IF;

    COMMIT;

    DROP TEMPORARY TABLE fase6_seed_valor;
    DROP TEMPORARY TABLE fase6_seed_categoria;
    DROP TEMPORARY TABLE fase6_seed_tema;
END$$

CALL seed_temas_fase6()$$
DROP PROCEDURE seed_temas_fase6$$

DELIMITER ;

-- REVERSÃO MANUAL SEGURA (não executada por este arquivo): consulte o roteiro
-- do README. Não remova temas oficiais referenciados por jogadores/resoluções.
