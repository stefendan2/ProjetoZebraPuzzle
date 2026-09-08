USE zebraPuzzle;

-- Aplicação (preserva os registros existentes):
ALTER TABLE verificacao_email
    ADD COLUMN IF NOT EXISTS email_pendente VARCHAR(254) NULL AFTER jogador_id;

-- Rollback manual, somente se nenhum token de troca de e-mail precisar ser preservado:
-- ALTER TABLE verificacao_email DROP COLUMN email_pendente;
