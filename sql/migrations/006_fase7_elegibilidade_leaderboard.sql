-- Fase 7: Workbench como administrador; Apache parado e backup validado.
-- DDL realiza commit implicito. Em erro, pare; nao execute trechos isolados.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
USE zebraPuzzle;
SET SQL_SAFE_UPDATES = 0;
DELIMITER $$
DROP PROCEDURE IF EXISTS fase7_migrar$$
CREATE PROCEDURE fase7_migrar()
BEGIN
    DECLARE v_fim INT DEFAULT 0;
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_indice VARCHAR(255);
    DECLARE v_resolucao BIGINT UNSIGNED;
    DECLARE v_jogador BIGINT UNSIGNED;
    DECLARE v_desafio BIGINT UNSIGNED;
    DECLARE v_tema BIGINT UNSIGNED;
    DECLARE v_tempo BIGINT UNSIGNED;
    DECLARE v_tentativa BIGINT UNSIGNED;
    DECLARE v_fim_em DATETIME(6);
    DECLARE v_inicio DATETIME(6);
    DECLARE legadas CURSOR FOR
        SELECT r.id, r.jogador_id, dd.id, r.tema_id, r.tempo_milisegundos, r.concluida_em
        FROM resolucao r JOIN desafio_diario dd ON dd.dia = r.desafio_dia
        WHERE r.tentativa_id IS NULL ORDER BY r.id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_fim = 1;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    SELECT COUNT(*) INTO v_total FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name IN ('jogador','tema','desafio_diario','resolucao','leaderboard',
                         'acesso_diario','desafio_atribuicao','desafio_dica') AND engine = 'InnoDB';
    IF v_total <> 8 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: faltam tabelas InnoDB das fases anteriores.';
    END IF;
    SELECT COUNT(*) INTO v_total FROM information_schema.columns
    WHERE table_schema = DATABASE() AND (
        (table_name = 'resolucao' AND column_name IN
            ('id','jogador_id','desafio_dia','concluida_em','tempo_milisegundos','tema_id','elegivel_leaderboard'))
        OR (table_name = 'leaderboard' AND column_name IN
            ('id','dia','jogador_id','resolucao_id','tempo_milisegundos','momento_conclusao'))
        OR (table_name = 'jogador' AND column_name IN ('id','email','ativo','email_verificado'))
        OR (table_name = 'tema' AND column_name IN ('id','ativo'))
        OR (table_name = 'desafio_diario' AND column_name IN ('id','dia'))
    );
    IF v_total <> 21 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: colunas-base ausentes; confira SHOW CREATE TABLE.';
    END IF;
    SELECT COUNT(*) INTO v_total FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND ((table_name = 'jogador' AND column_name IN ('email','nome_usuario'))
        OR (table_name = 'tema' AND column_name = 'nome'))
      AND character_set_name = 'utf8mb4' AND collation_name = 'utf8mb4_unicode_ci';
    IF v_total <> 3 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: collation textual divergente; nao converter automaticamente.';
    END IF;
    SELECT COUNT(*) INTO v_total FROM information_schema.columns
    WHERE table_schema = DATABASE() AND data_type = 'datetime' AND datetime_precision = 6
      AND ((table_name = 'resolucao' AND column_name = 'concluida_em')
        OR (table_name = 'leaderboard' AND column_name = 'momento_conclusao'));
    IF v_total <> 2 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: conclusoes devem usar DATETIME(6).';
    END IF;
    SELECT GROUP_CONCAT(column_name ORDER BY seq_in_index) INTO v_indice
    FROM information_schema.statistics WHERE table_schema = DATABASE()
      AND table_name = 'leaderboard' AND index_name = 'uq_leaderboard_dia_jogador' AND non_unique = 0;
    IF v_indice IS NULL OR v_indice <> 'dia,jogador_id' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: UNIQUE leaderboard(dia,jogador_id) ausente.';
    END IF;
    SELECT GROUP_CONCAT(column_name ORDER BY seq_in_index) INTO v_indice
    FROM information_schema.statistics WHERE table_schema = DATABASE()
      AND table_name = 'leaderboard' AND index_name = 'uq_leaderboard_resolucao' AND non_unique = 0;
    IF v_indice IS NULL OR v_indice <> 'resolucao_id' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: UNIQUE leaderboard(resolucao_id) ausente.';
    END IF;
    SELECT COUNT(*) INTO v_total FROM information_schema.key_column_usage
    WHERE table_schema = DATABASE() AND table_name = 'leaderboard'
      AND referenced_table_schema = DATABASE() AND (
        (column_name = 'dia' AND referenced_table_name = 'desafio_diario' AND referenced_column_name = 'dia')
        OR (column_name = 'jogador_id' AND referenced_table_name = 'jogador' AND referenced_column_name = 'id')
        OR (column_name = 'resolucao_id' AND referenced_table_name = 'resolucao' AND referenced_column_name = 'id')
    );
    IF v_total <> 3 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: FKs do leaderboard divergentes.';
    END IF;
    SELECT COUNT(*) INTO v_total FROM resolucao r
    LEFT JOIN jogador j ON j.id = r.jogador_id
    LEFT JOIN tema t ON t.id = r.tema_id
    LEFT JOIN desafio_diario dd ON dd.dia = r.desafio_dia
    WHERE j.id IS NULL OR t.id IS NULL OR dd.id IS NULL
       OR r.concluida_em IS NULL OR r.tempo_milisegundos IS NULL
       OR DATE(r.concluida_em) < r.desafio_dia OR r.elegivel_leaderboard NOT IN (0,1);
    IF v_total <> 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: resolucoes legadas incompletas ou incoerentes.';
    END IF;
    SELECT COUNT(*) INTO v_total FROM leaderboard l
    LEFT JOIN resolucao r ON r.id = l.resolucao_id
    WHERE r.id IS NULL OR r.jogador_id <> l.jogador_id OR r.desafio_dia <> l.dia
       OR r.tempo_milisegundos <> l.tempo_milisegundos
       OR r.concluida_em <> l.momento_conclusao OR r.elegivel_leaderboard <> 1
       OR DATE(r.concluida_em) <> r.desafio_dia
       OR EXISTS (
            SELECT 1 FROM resolucao a WHERE a.jogador_id = r.jogador_id
              AND a.desafio_dia = r.desafio_dia AND DATE(a.concluida_em) = a.desafio_dia
              AND (a.concluida_em < r.concluida_em OR (a.concluida_em = r.concluida_em AND a.id < r.id))
       );
    IF v_total <> 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: leaderboard diverge da primeira resolucao ou da flag.';
    END IF;
    SELECT COUNT(*) INTO v_total FROM resolucao r
    LEFT JOIN leaderboard l ON l.resolucao_id = r.id
    WHERE r.elegivel_leaderboard = 1 AND l.id IS NULL;
    IF v_total <> 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: flag elegivel sem leaderboard. Solicite diagnostico.';
    END IF;

    CREATE TABLE IF NOT EXISTS tentativa_desafio (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        jogador_id BIGINT UNSIGNED NOT NULL,
        desafio_id BIGINT UNSIGNED NOT NULL,
        tema_id BIGINT UNSIGNED NOT NULL,
        iniciada_em DATETIME(6) NOT NULL,
        finalizada_em DATETIME(6) NULL,
        aberta_chave TINYINT NULL DEFAULT 1,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT ck_tentativa_intervalo CHECK (finalizada_em IS NULL OR finalizada_em >= iniciada_em),
        CONSTRAINT ck_tentativa_aberta
            CHECK ((finalizada_em IS NULL AND aberta_chave IS NOT NULL AND aberta_chave = 1)
                OR (finalizada_em IS NOT NULL AND aberta_chave IS NULL)),
        CONSTRAINT uq_tentativa_aberta UNIQUE (jogador_id, desafio_id, aberta_chave),
        CONSTRAINT fk_tentativa_jogador FOREIGN KEY (jogador_id) REFERENCES jogador (id)
            ON UPDATE CASCADE ON DELETE RESTRICT,
        CONSTRAINT fk_tentativa_desafio FOREIGN KEY (desafio_id) REFERENCES desafio_diario (id)
            ON UPDATE CASCADE ON DELETE RESTRICT,
        CONSTRAINT fk_tentativa_tema FOREIGN KEY (tema_id) REFERENCES tema (id)
            ON UPDATE CASCADE ON DELETE RESTRICT,
        INDEX idx_tentativa_jogador_inicio (jogador_id, iniciada_em),
        INDEX idx_tentativa_desafio_estado (desafio_id, finalizada_em)
    ) ENGINE=InnoDB;
    SELECT COUNT(*) INTO v_total FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'tentativa_desafio' AND engine = 'InnoDB';
    IF v_total <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: tentativa_desafio deve usar InnoDB.';
    END IF;
    SELECT COUNT(*) INTO v_total FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'tentativa_desafio'
      AND column_name IN ('id','jogador_id','desafio_id','tema_id','iniciada_em','finalizada_em',
                          'aberta_chave','criado_em','atualizado_em') AND extra NOT LIKE '%GENERATED%';
    IF v_total <> 9 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: tentativa_desafio preexistente tem estrutura diferente.';
    END IF;
    SELECT GROUP_CONCAT(column_name ORDER BY seq_in_index) INTO v_indice
    FROM information_schema.statistics WHERE table_schema = DATABASE()
      AND table_name = 'tentativa_desafio' AND index_name = 'uq_tentativa_aberta' AND non_unique = 0;
    IF v_indice IS NULL OR v_indice <> 'jogador_id,desafio_id,aberta_chave' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: UNIQUE da tentativa aberta ausente.';
    END IF;
    SELECT COUNT(*) INTO v_total FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE() AND table_name = 'tentativa_desafio'
      AND constraint_type = 'CHECK' AND constraint_name IN ('ck_tentativa_intervalo','ck_tentativa_aberta');
    IF v_total <> 2 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: CHECKs da tentativa ausentes.';
    END IF;
    SELECT COUNT(*) INTO v_total FROM information_schema.key_column_usage
    WHERE table_schema = DATABASE() AND table_name = 'tentativa_desafio'
      AND referenced_table_schema = DATABASE() AND referenced_column_name = 'id'
      AND ((column_name = 'jogador_id' AND referenced_table_name = 'jogador')
        OR (column_name = 'desafio_id' AND referenced_table_name = 'desafio_diario')
        OR (column_name = 'tema_id' AND referenced_table_name = 'tema'));
    IF v_total <> 3 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: FKs da tentativa divergentes.';
    END IF;

    ALTER TABLE resolucao ADD COLUMN IF NOT EXISTS tentativa_id BIGINT UNSIGNED NULL AFTER id;
    START TRANSACTION;
    OPEN legadas;
    leitura: LOOP
        FETCH legadas INTO v_resolucao, v_jogador, v_desafio, v_tema, v_tempo, v_fim_em;
        IF v_fim = 1 THEN LEAVE leitura; END IF;
        SET v_inicio = TIMESTAMPADD(MICROSECOND, -1 * CAST(v_tempo AS SIGNED) * 1000, v_fim_em);
        IF v_inicio IS NULL OR v_inicio > v_fim_em THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: inicio tecnico legado nao pode ser derivado.';
        END IF;
        INSERT INTO tentativa_desafio
            (jogador_id, desafio_id, tema_id, iniciada_em, finalizada_em, aberta_chave)
        VALUES (v_jogador, v_desafio, v_tema, v_inicio, v_fim_em, NULL);
        SET v_tentativa = LAST_INSERT_ID();
        UPDATE resolucao SET tentativa_id = v_tentativa WHERE id = v_resolucao AND tentativa_id IS NULL;
        IF ROW_COUNT() <> 1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: falha ao vincular tentativa legada.';
        END IF;
    END LOOP;
    CLOSE legadas;
    SELECT COUNT(*) INTO v_total FROM resolucao r
    LEFT JOIN tentativa_desafio td ON td.id = r.tentativa_id
    LEFT JOIN desafio_diario dd ON dd.id = td.desafio_id
    WHERE td.id IS NULL OR dd.id IS NULL OR td.finalizada_em IS NULL
       OR td.jogador_id <> r.jogador_id OR td.tema_id <> r.tema_id
       OR dd.dia <> r.desafio_dia OR td.finalizada_em <> r.concluida_em
       OR FLOOR((TIMESTAMPDIFF(MICROSECOND, td.iniciada_em, td.finalizada_em) + 500) / 1000)
            <> r.tempo_milisegundos;
    IF v_total <> 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: tentativa e resolucao vinculadas divergentes.';
    END IF;
    SELECT COUNT(*) INTO v_total FROM (
        SELECT tentativa_id FROM resolucao GROUP BY tentativa_id HAVING COUNT(*) > 1
    ) duplicadas;
    IF v_total <> 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: mais de uma resolucao por tentativa.';
    END IF;
    INSERT INTO leaderboard (dia, jogador_id, resolucao_id, tempo_milisegundos, momento_conclusao)
    SELECT r.desafio_dia, r.jogador_id, r.id, r.tempo_milisegundos, r.concluida_em
    FROM resolucao r
    LEFT JOIN leaderboard l ON l.dia = r.desafio_dia AND l.jogador_id = r.jogador_id
    WHERE DATE(r.concluida_em) = r.desafio_dia AND l.id IS NULL
      AND NOT EXISTS (
        SELECT 1 FROM resolucao a WHERE a.jogador_id = r.jogador_id
          AND a.desafio_dia = r.desafio_dia AND DATE(a.concluida_em) = a.desafio_dia
          AND (a.concluida_em < r.concluida_em OR (a.concluida_em = r.concluida_em AND a.id < r.id))
      );
    UPDATE resolucao r JOIN leaderboard l ON l.resolucao_id = r.id
    SET r.elegivel_leaderboard = 1 WHERE r.elegivel_leaderboard = 0;
    COMMIT;

    SELECT COUNT(*) INTO v_total FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'resolucao'
      AND column_name = 'tentativa_id' AND is_nullable = 'YES';
    IF v_total = 1 THEN
        ALTER TABLE resolucao MODIFY tentativa_id BIGINT UNSIGNED NOT NULL;
    END IF;
    SELECT GROUP_CONCAT(column_name ORDER BY seq_in_index) INTO v_indice
    FROM information_schema.statistics WHERE table_schema = DATABASE()
      AND table_name = 'resolucao' AND index_name = 'uq_resolucao_tentativa' AND non_unique = 0;
    IF v_indice IS NULL THEN
        ALTER TABLE resolucao ADD CONSTRAINT uq_resolucao_tentativa UNIQUE (tentativa_id);
    ELSEIF v_indice <> 'tentativa_id' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: indice preexistente uq_resolucao_tentativa divergente.';
    END IF;
    SELECT COUNT(*) INTO v_total FROM information_schema.referential_constraints
    WHERE constraint_schema = DATABASE() AND table_name = 'resolucao'
      AND constraint_name = 'fk_resolucao_tentativa';
    IF v_total = 0 THEN
        ALTER TABLE resolucao ADD CONSTRAINT fk_resolucao_tentativa
            FOREIGN KEY (tentativa_id) REFERENCES tentativa_desafio (id)
            ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;
    SELECT COUNT(*) INTO v_total FROM information_schema.key_column_usage
    WHERE table_schema = DATABASE() AND table_name = 'resolucao'
      AND constraint_name = 'fk_resolucao_tentativa' AND column_name = 'tentativa_id'
      AND referenced_table_schema = DATABASE() AND referenced_table_name = 'tentativa_desafio'
      AND referenced_column_name = 'id';
    IF v_total <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: FK da resolucao para tentativa divergente.';
    END IF;
    SELECT GROUP_CONCAT(column_name ORDER BY seq_in_index) INTO v_indice
    FROM information_schema.statistics WHERE table_schema = DATABASE()
      AND table_name = 'leaderboard' AND index_name = 'idx_leaderboard_ordem';
    IF v_indice IS NULL THEN
        ALTER TABLE leaderboard ADD INDEX idx_leaderboard_ordem (dia, tempo_milisegundos, momento_conclusao, id);
    ELSEIF v_indice = 'dia,tempo_milisegundos,momento_conclusao' THEN
        ALTER TABLE leaderboard DROP INDEX idx_leaderboard_ordem,
            ADD INDEX idx_leaderboard_ordem (dia, tempo_milisegundos, momento_conclusao, id);
    ELSEIF v_indice <> 'dia,tempo_milisegundos,momento_conclusao,id' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fase 7: indice de ordenacao inesperado; nao foi substituido.';
    END IF;
    SELECT
        (SELECT COUNT(*) FROM tentativa_desafio) AS tentativas,
        (SELECT COUNT(*) FROM resolucao WHERE tentativa_id IS NULL) AS resolucoes_sem_tentativa,
        (SELECT COUNT(*) FROM resolucao WHERE elegivel_leaderboard = 1) AS resolucoes_elegiveis,
        (SELECT COUNT(*) FROM leaderboard) AS entradas_leaderboard;
END$$
DELIMITER ;
CALL fase7_migrar();
DROP PROCEDURE fase7_migrar;
