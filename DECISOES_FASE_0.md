# Decisões da Fase 0

## Confirmadas

- `[DECISÃO DO USUÁRIO]` O nome oficial do projeto é **Zebra Puzzle**.
- `[DECISÃO DO USUÁRIO]` O banco de dados se chama **zebraPuzzle**.
- `[DECISÃO DO USUÁRIO]` A estrutura principal segue o roadmap: `public/`, `src/`, `config/`, `cron/` e `sql/`.
- `[DECISÃO DO USUÁRIO]` Não é necessário publicar diretamente no GitHub; a entrega será feita em arquivos e pastas completos.
- `[CONFIRMADO]` A stack é PHP 8.2+, Apache e MariaDB, mantendo SQL compatível com MySQL sempre que possível.
- `[CONFIRMADO]` A renderização será predominantemente feita no servidor.
- `[CONFIRMADO]` JavaScript puro será usado somente quando necessário, especialmente no cronômetro e no painel do teste.
- `[CONFIRMADO]` O fuso de referência é `America/Sao_Paulo`.
- `[CONFIRMADO]` Uma resolução é elegível quando é a primeira resolução válida do jogador para o desafio do dia atual.
- `[CONFIRMADO]` Rejogar registra uma nova resolução no histórico, mas não substitui nem altera o registro elegível do leaderboard.

## Conceitos aproveitados do Zait

- Front Controller por meio de `public/index.php`.
- Cadastro centralizado de rotas.
- Separação entre recursos públicos e código interno.

Esses conceitos foram reescritos. O roteador não aceita caminhos físicos vindos da URL e não inclui arquivos indicados pelo usuário.

## Pendências para fases futuras

- `[NÃO ESPECIFICADO]` Fluxo completo de verificação de e-mail: duração do token, reenvio e comportamento após expiração.
- `[NÃO ESPECIFICADO]` Regra de validação administrativa de um registro isolado do leaderboard.
- `[NÃO ESPECIFICADO]` Possibilidade de desativar o alerta de ofensiva das 18h.
- `[NÃO ESPECIFICADO]` Caso de uso específico para exibição da ofensiva.
- `[PENDENTE DE DECISÃO]` Algoritmo definitivo de geração das pistas. Com base na pesquisa feita anteriormente, a proposta futura será comparar AC-3 combinado com busca completa e outras alternativas antes da implementação.

Nenhuma pendência acima foi resolvida silenciosamente neste bloco.
