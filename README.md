# Zebra Puzzle

Esqueleto inicial do projeto Zebra Puzzle, correspondente ao bloco aprovado das fases 0 a 3 do roadmap.

## Estrutura

```text
ZebraPuzzle/
├── public/       # Único diretório exposto pelo Apache
├── src/          # Código PHP, views e componentes internos
├── config/       # Configuração da aplicação e das rotas
├── cron/         # Espaço reservado para as automações da Fase 10
├── sql/          # Schema do banco
└── tests/        # Verificações automatizadas disponíveis
```

## Requisitos

- PHP 8.2 ou superior;
- Apache 2.4 com `mod_rewrite`;
- MariaDB ou MySQL;
- extensões PHP `pdo_mysql` e `mbstring`;
- Composer para gerar o autoload e executar os comandos do projeto.

Na Fase 4 também será necessária a extensão `gd` para o captcha. Ela ainda não é usada neste bloco.

## Configuração local

1. Crie o banco executando `sql/schema.sql` com uma conta que tenha permissão para criar banco e tabelas.
2. Crie um usuário exclusivo para a aplicação e conceda a ele apenas as permissões necessárias no banco `zebraPuzzle`. Não use `root` na aplicação.
3. Defina as variáveis descritas em `.env.example` no Apache, no sistema ou na sessão do terminal. O arquivo `.env.example` é somente um modelo e não é carregado automaticamente.
4. Ajuste o exemplo `config/apache-vhost.conf.example`, apontando o `DocumentRoot` para a pasta `public/`.
5. Adicione `127.0.0.1 zebrapuzzle.local` ao arquivo `hosts` do sistema.
6. Habilite `mod_rewrite`, reinicie o Apache e acesse `http://zebrapuzzle.local/`.

O MariaDB/MySQL precisa ter as tabelas de fusos carregadas para aceitar `America/Sao_Paulo` na sessão. Confirme com `SET time_zone = 'America/Sao_Paulo';`. A aplicação interrompe a conexão se essa configuração obrigatória não puder ser aplicada.

Em XAMPP para Windows, o VirtualHost deve ser incluído em `apache/conf/extra/httpd-vhosts.conf`. Em Linux Debian/Ubuntu, use os diretórios de sites do Apache e os comandos `a2ensite` e `a2enmod rewrite`.

## Variáveis importantes

| Variável | Exemplo | Finalidade |
| --- | --- | --- |
| `APP_ENV` | `development` | Ambiente atual |
| `APP_DEBUG` | `true` | Mostra detalhes de erros somente em desenvolvimento |
| `APP_BASE_PATH` | vazio | Subdiretório da URL, se houver |
| `DB_DATABASE` | `zebraPuzzle` | Banco aprovado |
| `DB_USERNAME` | `zebrapuzzle_app` | Usuário dedicado |
| `DB_PASSWORD` | valor local | Senha nunca versionada |
| `DB_TIMEZONE` | `America/Sao_Paulo` | Fuso da sessão do banco |

## Comandos de verificação

```bash
composer validate --strict
composer dump-autoload
composer test
```

Para validar a sintaxe de todos os arquivos PHP em Linux:

```bash
find public src config cron tests -name '*.php' -print0 | xargs -0 -n1 php -l
```

Para validar o schema de verdade, execute-o em um banco vazio. Uma leitura estática do arquivo não substitui esse teste.

## Estado das fases

- Fase 0: decisões essenciais registradas; existem pendências deliberadamente reservadas às fases correspondentes.
- Fase 1: exemplo de VirtualHost entregue, mas instalação, serviços, fuso e logs precisam ser comprovados na máquina onde o projeto rodará.
- Fase 2: schema escrito, ainda dependendo de execução real em MariaDB/MySQL.
- Fase 3: Front Controller, rotas, PDO, layout, sessão, flash, validação e CSRF implementados; a autenticação real será criada somente na Fase 4.

As fases não devem ser consideradas encerradas enquanto os testes dependentes do ambiente não forem executados e seus critérios de aceitação não forem confirmados.
