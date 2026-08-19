# Roadmap de Desenvolvimento e Hospedagem — Geniooo

## 1. O que é este documento

Este é o **mapa de sequenciamento** para tirar o Geniooo do papel: em que ordem aprender as tecnologias que faltam, em que ordem construir as funcionalidades e em que momento colocar o site no ar.

Ele **não é uma apostila**. Não ensina PHP, Linux ou Apache passo a passo — indica *o que* pesquisar, *quando* pesquisar e *por que aquilo importa neste projeto*. Os detalhes de cada tecnologia você busca por conta própria (não há links aqui de propósito: nomes de comandos, funções e conceitos são o que você deve procurar).

**Para quem é**: um aluno de curso técnico em informática, trabalhando sozinho, responsável tanto pelo desenvolvimento quanto pela hospedagem.

**Premissa de conhecimento adotada** (o roadmap foi montado em cima dela):

| Tecnologia | Nível assumido | Consequência no roadmap |
|---|---|---|
| HTML | sabe | Nenhuma fase de aprendizado dedicada |
| CSS puro | sabe | Nenhuma fase de aprendizado dedicada |
| Bootstrap | se vira | Usado para o layout, sem investir tempo em design do zero |
| JS puro | se vira | Usado **só** onde é indispensável (cronômetro, interação do painel do teste) |
| PHP | básico | Toda a lógica no servidor, com padrões simples e repetitivos |
| AJAX | não sabe | Evitado como caminho principal; aparece tarde e em pontos isolados |
| jQuery | não sabe | **Não será usado.** Não vale a pena aprender uma biblioteca inteira para o que JS puro já resolve aqui |
| Linux | não sabe | Fase 1 inteira dedicada ao mínimo de sobrevivência, **antes** de qualquer deploy |
| Apache | não sabe | Idem — instalado e configurado localmente primeiro, no servidor só depois |

**Fonte da verdade do produto**: o repositório ainda **não tem código nenhum**. O que deve ser construído está inteiramente em:

- [`business_rules/BR-001.md`](business_rules/BR-001.md) a [`business_rules/BR-020.md`](business_rules/BR-020.md) — as 20 regras de negócio;
- [`use_cases/`](use_cases/) — os casos de uso de jogador e administrador, incluindo o login compartilhado em [`use_cases/fazer_login.md`](use_cases/fazer_login.md);
- [`historias_de_usuario.md`](historias_de_usuario.md) — as 34 histórias com critérios de aceitação e dependências entre si.

> **Como usar as referências a histórias**: o arquivo `historias_de_usuario.md` não tem âncoras por ID. Quando este roadmap citar, por exemplo, `HU-JOG-06`, abra o arquivo e **busque o texto do ID** (Ctrl+F) para ler a história completa e seus critérios de aceitação. Os critérios de aceitação são, na prática, o seu roteiro de teste manual de cada fase.

## 2. Organizando o projeto no workspace do Eclipse

Antes de tocar em qualquer fase, vale organizar a ferramenta que você vai usar todo dia. Isso evita um retrabalho chato lá na frente: reorganizar um projeto Eclipse depois que ele já está bagunçado é mais irritante do que configurar direito uma vez.

**Aprender/pesquisar antes**:
- O pacote **"Eclipse IDE for PHP Developers"** — é o que já vem com PDT (PHP Development Tools) e suporte a HTML/CSS/JS (WTP). Não instale o "Eclipse IDE for Java Developers": ele não edita PHP direito.
- A diferença entre **workspace** e **project** no Eclipse: o *workspace* é uma pasta de metadados/configuração pessoal da sua instalação do Eclipse; o *project* é a pasta do código em si. São coisas diferentes e não devem se misturar.
- **EGit** — o plugin de Git embutido no Eclipse (normalmente já vem instalado no pacote PHP Developers; se não vier, procure no Eclipse Marketplace).
- **Xdebug** — extensão do PHP (não do Eclipse) que permite colocar *breakpoint* e inspecionar variáveis passo a passo dentro do próprio Eclipse.

**Tarefas**:
1. Instale o Eclipse IDE for PHP Developers.
2. Ao abrir pela primeira vez, o Eclipse pede uma pasta de *workspace* — crie essa pasta **fora** de `/home/cardoos/codigos/Geniooo` (ex.: `~/eclipse-workspace`). O workspace é configuração da sua máquina, não faz parte do projeto, e não deve ir para o Git.
3. **Importe o repositório já existente como projeto Git — não crie um projeto do zero.** Use `File > Import > Git > Projects from Git > Existing local repository`, apontando para `/home/cardoos/codigos/Geniooo`. Isso faz o Eclipse reconhecer o histórico, os branches e o remoto que já existem, em vez de você ter que reconfigurar o Git na mão depois.
4. Quando a estrutura de pastas da seção 4.5 existir (`public/`, `src/`, `config/`, `cron/`, `sql/`), o projeto no Eclipse deve apontar para a **raiz do repositório** (onde fica o `.git`), não só para `public/` — mesmo que o Apache sirva o site a partir de `public/` apenas. São coisas diferentes: o Eclipse edita o projeto inteiro; o Apache só expõe uma parte dele.
5. Depois que a Fase 1 tiver o VirtualHost do Apache configurado, evite ter duas cópias do código se desencontrando. O caminho mais simples: mantenha o repositório Git na própria pasta que o Apache serve (ex.: `/var/www/geniooo`, com permissão de escrita para o seu usuário) e abra **essa mesma pasta** no Eclipse. Isso evita o hábito de editar num lugar, esquecer de copiar para o outro, e passar meia hora procurando por que a mudança "não apareceu".
6. Force a codificação do workspace para **UTF-8**: `Window > Preferences > General > Workspace > Text file encoding`. O projeto tem textos em português com acentuação, e o banco de dados já foi definido como `utf8mb4` (seção 4.1) — se o Eclipse salvar arquivos em outra codificação, os acentos corrompem de forma silenciosa e só aparecem quebrados na tela do jogador.
7. Configure um **PHP Server** apontando para a URL local que você validou na Fase 1 (ex. `http://geniooo.local/`): `Window > Preferences > PHP > PHP Servers`. É o que permite rodar/depurar uma página a partir do próprio Eclipse.
8. Ative a integração com o **Xdebug** em `Window > Preferences > PHP > Debug`, depois de instalar a extensão no PHP (Fase 1). Colocar um *breakpoint* e inspecionar variável por variável é, de longe, a ferramenta mais valiosa para quem só sabe o básico de PHP — muito mais direto que espalhar `var_dump()` pelo código para descobrir por que uma consulta não trouxe o que devia.
9. Adicione ao `.gitignore` (o mesmo que a Fase 0 manda criar) os metadados que o Eclipse cria dentro da pasta do projeto: `.project`, `.buildpath`, `.settings/`. São configuração local da sua instalação do Eclipse — não fazem parte do código, e variam de máquina para máquina.

**Armadilhas**:
- Criar o workspace do Eclipse **dentro** da pasta do repositório. Além de poluir o `git status` com uma pasta `.metadata/` enorme, corre o risco de acabar commitando configuração pessoal da IDE.
- Usar `File > New > PHP Project` para começar um projeto do zero, em vez de importar o repositório existente via EGit. Isso cria uma pasta nova, desconectada do Git, e você acaba com duas cópias do projeto.
- Editar os arquivos por um caminho (por exemplo, direto na pasta que o Apache serve, com outro editor) e pelo Eclipse por outro caminho diferente. Escolha **uma** pasta como fonte da verdade e abra sempre essa mesma pasta.
- Deixar a codificação do workspace no padrão do sistema operacional em vez de forçar UTF-8 — é o tipo de erro que só aparece depois, quando um nome de tema ou uma mensagem salva com acento vira caractere estranho no banco.

## 3. O que está fora de escopo (não implemente, mesmo que pareça óbvio)

Estas ausências são **decisões de produto**, não esquecimentos. Se você adicionar qualquer uma delas, está fugindo da especificação:

- monetização / pagamentos;
- funcionalidades sociais, compartilhamento de resultado, amigos, chat;
- notificações push (o único aviso previsto é o **e-mail** das 18:00, BR-014);
- múltiplos idiomas / i18n;
- aplicativo móvel nativo;
- dificuldade progressiva ou adaptativa (todo dia tem um desafio, sem níveis);
- múltiplos fusos horários — **tudo** é horário de Brasília (America/Sao_Paulo), sem opção de escolha.

Este é um projeto de **estudo/portfólio**. Escopo enxuto é um requisito, não uma limitação.

## 4. Decisões técnicas recomendadas (e por que elas foram tomadas assim)

A documentação **não especifica** stack, banco de dados nem hospedagem. As decisões abaixo são recomendações deste roadmap, calibradas para o seu perfil. Elas estão explicitadas aqui para você poder discordar conscientemente, não para serem seguidas no automático.

### 4.1 Banco de dados: MySQL ou MariaDB

Nenhum documento define banco. A escolha natural para um stack PHP + Apache é **MySQL** ou **MariaDB** (são praticamente intercambiáveis para o que você vai fazer; MariaDB costuma ser o padrão nas distribuições Linux). Motivos práticos:

- é o que acompanha qualquer tutorial de PHP que você encontrar;
- é o que qualquer hospedagem compartilhada ou VPS oferece pronto;
- suporta bem o que as regras exigem: unicidade de CPF (BR-001), consultas ordenadas por tempo e data (BR-005), agregações por dia.

Acesse o banco pelo PHP usando **PDO** com **prepared statements** — não use `mysqli` em modo de concatenação de string, e nunca monte SQL grudando variáveis no meio da query.

### 4.2 Arquitetura de páginas: renderização no servidor, com POST → Redirect → GET

**Decisão: o caminho principal do site é PHP renderizado no servidor, com formulários HTML comuns e recarregamento de página.**

Por que isso, e não uma interface que atualiza sozinha via AJAX:

1. **Você sabe o básico de PHP e não sabe AJAX.** Aprender AJAX no meio de um projeto que ainda não existe multiplica os pontos de falha: quando algo não funciona, você não sabe se o erro está no SQL, no PHP, no JS, no formato da resposta ou no navegador. Com formulário + recarregamento, o erro está sempre em um lugar só: no PHP que processou o POST.
2. **A documentação não pede nenhuma interface em tempo real.** Releia os casos de uso: eles descrevem cliques em botões que levam a validações e a novas telas ("O ator clica no ***Botão Salvar Alterações***", "A aplicação valida os dados", "A aplicação atualiza..."). Isso é literalmente a descrição de um formulário que dá POST e recarrega.
3. **Depurar é trivial**: você olha o HTML gerado, dá `var_dump()`, lê o log de erro do PHP. Sem camada intermediária.

O padrão a repetir em toda página que altera dados é **POST → Redirect → GET**:

1. o formulário dá `POST` para um arquivo PHP que processa;
2. o PHP valida, grava (ou não) no banco, guarda a mensagem de sucesso/erro na `$_SESSION`;
3. o PHP faz `header('Location: ...')` e `exit;` — **não imprime HTML na resposta do POST**;
4. a página de destino lê a mensagem da sessão, exibe e limpa.

Isso resolve de graça o problema clássico de "atualizei a página e o cadastro foi feito duas vezes".

### 4.3 Onde JavaScript é realmente necessário

JS puro, sem jQuery, e apenas nestes pontos:

- **Cronômetro visível durante o teste** — mostrar o tempo correndo na tela. É a única coisa que não dá para fazer com recarregamento de página.
- **Interação do ***Painel do Teste***** — marcar/desmarcar células da grade do teste de Einstein sem recarregar a cada clique. Isso é manipulação de DOM local, **não** é AJAX: nada é enviado ao servidor até o jogador clicar em ***Botão Finalizar Teste***, e nesse momento o estado da grade vai como campos de formulário num POST normal.

**Regra de ouro, e o erro mais caro que você pode cometer neste projeto**: o cronômetro em JS é **enfeite visual**. O tempo que vale (BR-009, BR-005) é calculado **no servidor**, pela diferença entre o momento em que o teste foi aberto e o momento em que o POST de finalização chegou. Se você confiar num campo `<input type="hidden" name="tempo">` preenchido pelo JS, qualquer jogador com o inspetor do navegador aberto lidera o leaderboard com 00:00:01.

### 4.4 Quando (e se) usar `fetch`

Só depois de tudo funcionar, e só se você quiser, como melhoria opcional:

- salvar automaticamente o progresso parcial da grade do teste;
- atualizar o leaderboard do dia sem recarregar.

Use `fetch()` do JS puro, que responde JSON gerado por `json_encode()` no PHP. **Nada disso é requisito.** Se o prazo apertar, corte sem culpa.

### 4.5 Organização de arquivos sugerida

Uma estrutura simples, mas com uma decisão importante — **só a pasta pública fica acessível pelo navegador**:

```
geniooo/
├── public/            <- DocumentRoot do Apache aponta AQUI
│   ├── index.php
│   ├── login.php
│   ├── cadastro.php
│   ├── jogo.php
│   ├── leaderboard.php
│   ├── historico.php
│   ├── configuracoes.php
│   ├── admin/
│   └── assets/        (css, js, imagens)
├── src/               <- funções PHP: banco, validações, regras de negócio
├── config/            <- credenciais do banco (NUNCA dentro de public/)
├── cron/              <- scripts das rotinas automáticas
└── sql/               <- scripts de criação do banco
```

Se você deixar a senha do banco em um arquivo dentro de `public/`, e o PHP der pau, o servidor pode entregar o arquivo como texto puro para qualquer visitante. Manter `config/` fora do `DocumentRoot` elimina esse risco de vez.

### 4.6 Versionamento

O repositório já é um projeto Git. Commite desde o primeiro arquivo. E crie um `.gitignore` **antes** do primeiro commit de código, contendo pelo menos o arquivo de credenciais do banco.

## 5. Visão geral das fases

| Fase | Título | Porte | Entrega principal |
|---|---|---|---|
| 0 | Preparação e decisões | P | Repositório organizado, stack decidida |
| 1 | Sobrevivência em Linux e Apache | M | Stack LAMP rodando na sua máquina |
| 2 | Modelagem do banco de dados | M | Script SQL completo, criado a partir das BRs |
| 3 | Esqueleto da aplicação PHP | M | Layout, conexão, sessão, padrão de formulário |
| 4 | Conta e acesso | G | Cadastro, verificação de e-mail, login, captcha diário |
| 5 | Temas do teste | M | Catálogo de temas 5×5 e preferência do jogador |
| 6 | Desafio diário e resolução | G | O jogo funcionando de ponta a ponta |
| 7 | Elegibilidade, leaderboard e dias anteriores | G | Ranking diário correto e histórico jogável |
| 8 | Ofensiva | M | Ofensiva atual e recorde no header |
| 9 | Primeiro deploy real | G | Site no ar, com HTTPS |
| 10 | Automações agendadas (cron) | M | Geração diária e alerta das 18:00 |
| 11 | Painel administrativo | G | CRUD dos 4 recursos administrativos |
| 12 | Endurecimento, backup e operação | M | Backup automático, segurança, logs |

O **porte** é uma noção relativa de esforço (P = pequeno, M = médio, G = grande), não uma estimativa de prazo. Não invente datas antes de terminar a Fase 3 — você não tem base para estimar ainda.

Duas ordens foram escolhidas de propósito e vale entender o motivo:

- **A hospedagem real (Fase 9) vem depois do jogo funcionar (Fases 4-8), mas antes das automações (Fase 10) e do painel admin (Fase 11).** Fazer deploy cedo demais significa brigar com Linux sem ter o que colocar no ar; fazer tarde demais significa descobrir problemas de servidor com o projeto inteiro pronto e o prazo estourado. O ponto de equilíbrio é: assim que o fluxo do jogador funciona localmente, sobe. As automações da Fase 10 dependem de cron, que só faz sentido de verdade num servidor ligado 24h — por isso vêm logo depois do deploy.
- **O painel administrativo é o penúltimo.** Segundo BR-017, BR-018 e BR-020, em operação normal quem cria desafio, leaderboard e histórico é **o sistema**, automaticamente. O CRUD administrativo desses três recursos existe como **ferramenta de correção manual** — se ele não existir ainda, o site funciona. A única parte administrativa que é fluxo primário de verdade é a gestão de temas (BR-016), e por isso ela recebe um atalho na Fase 5.

---

## 6. As fases

### Fase 0 — Preparação e decisões

**Objetivo**: sair do zero absoluto com decisões tomadas e ambiente de trabalho organizado, antes de escrever qualquer linha de código.

**Aprender/pesquisar antes**:
- Git básico: `git add`, `git commit`, `git status`, `git log`, `.gitignore`, branches.
- Diferença entre MySQL e MariaDB (leitura de 10 minutos, só para saber que dá no mesmo).

**Tarefas**:
1. Ler, inteiros e nesta ordem: `CLAUDE.md` (na raiz do repositório), as 20 regras em [`business_rules/`](business_rules/), depois [`historias_de_usuario.md`](historias_de_usuario.md). Sem isso, todo o resto deste roadmap fica solto.
2. Ler especialmente a **seção 4 de `historias_de_usuario.md`** ("Dúvidas, inconsistências e pontos em aberto"). Ali estão os seis pontos que a documentação **não** define — e três deles vão te morder: o fluxo de verificação de e-mail (item 6), o algoritmo de geração do desafio (item 5) e a validação de ordenação ao editar um registro isolado de leaderboard (item 3). Você vai ter que decidir esses três por conta própria; anote suas decisões em algum lugar do repositório.
3. Criar a estrutura de pastas da seção 4.5 e o `.gitignore`.
4. Registrar por escrito as decisões de stack: PHP + Apache + MySQL/MariaDB, renderização no servidor, JS puro só no cronômetro e no painel do teste.

**Critério de pronto**: você consegue explicar em voz alta, sem consultar, o que é a "elegibilidade" de uma resolução (BR-006) e por que rejogar não muda o leaderboard (BR-007).

**Armadilhas**:
- Pular a leitura das regras e "ir codando". Metade das BRs são regras de exclusão (o que **não** pode acontecer) — elas não são óbvias a partir das telas, e retrofitar isso depois dá muito mais trabalho.

---

### Fase 1 — Sobrevivência em Linux e Apache

**Objetivo**: conseguir instalar, iniciar, parar e diagnosticar um stack LAMP na sua própria máquina. Não é um curso de Linux — é o conjunto mínimo para não travar.

**Aprender/pesquisar antes** (pesquise cada item pelo nome, nesta ordem):

*Linux — o mínimo:*
- Navegação e arquivos: `pwd`, `ls -l`, `cd`, `cp`, `mv`, `rm`, `mkdir`, `cat`, `less`, `tail -f`, `nano` (ou `vim`, se quiser sofrer um pouco mais).
- Permissões: `chmod`, `chown`, o que significam `755` e `644`, o que são usuário e grupo de um arquivo. **Entenda isto de verdade** — 90% dos "meu site não abre" de iniciante é permissão errada.
- Superusuário: `sudo`, e por que você quase nunca deve rodar como `root`.
- Pacotes: `apt` (Debian/Ubuntu) ou `dnf` (Fedora/RHEL) — saiba qual sua distribuição usa.
- Serviços: `systemctl start|stop|restart|status|enable`.
- Logs: onde ficam (`/var/log/`), como acompanhar em tempo real com `tail -f`.
- Fuso horário do sistema: `timedatectl`.

*Apache — o mínimo:*
- O que é `DocumentRoot`.
- O que é um **VirtualHost** e onde ficam os arquivos de configuração (`/etc/apache2/` no Debian/Ubuntu, `/etc/httpd/` no Fedora/RHEL).
- Como habilitar site e módulo: `a2ensite`, `a2dissite`, `a2enmod` (Debian/Ubuntu).
- `.htaccess` e a diretiva `AllowOverride`.
- Onde estão `error.log` e `access.log` — e o hábito de olhar o `error.log` **antes** de perguntar "por que não funciona".

*PHP no servidor:*
- Onde fica o `php.ini`, e as diretivas `display_errors`, `error_reporting`, `log_errors`, `error_log`.
- Como instalar extensões: `php-mysql` (para PDO), `php-mbstring`, `php-gd` (você vai usar GD no captcha da Fase 4).

**Tarefas**:
1. Instalar Apache, PHP e MySQL/MariaDB na sua máquina.
2. Servir uma página `<?php phpinfo(); ?>` e abri-la no navegador.
3. Configurar um VirtualHost local apontando para `.../geniooo/public`, com um nome tipo `geniooo.local` no arquivo `/etc/hosts`. **Não** desenvolva jogando arquivos em `/var/www/html` na mão — configure direito agora, porque no servidor será igual.
4. Rodar `mysql_secure_installation`, criar o banco `geniooo` e um usuário de banco dedicado (não use `root` na aplicação).
5. Escrever um `.php` que conecta ao banco via PDO e imprime "conectado".
6. Ajustar o fuso do sistema e do PHP para `America/Sao_Paulo` — isso é requisito de BR-002, BR-010, BR-014 e BR-020, não um detalhe cosmético.
7. Provocar um erro de propósito (um `;` faltando) e **achar o erro no log do Apache**. Se você não sabe achar o log, você não vai conseguir hospedar nada.

**Critério de pronto**: você derruba e sobe os serviços na mão, e sabe dizer em qual log procurar quando uma página dá 500 ou 403.

**Armadilhas**:
- **`chmod 777` nunca.** É a "solução" que aparece em toda resposta de fórum e é sempre errada. Se der erro de permissão, o problema é o **dono** do arquivo, não a falta de permissão para todo mundo.
- Fuso horário: configurar só o do sistema e esquecer o do PHP (`date_default_timezone_set` / `date.timezone` no `php.ini`) e o da sessão do MySQL. Os três precisam concordar, senão a virada de dia às 00:00 de Brasília vai acontecer na hora errada e quebrar leaderboard, captcha e ofensiva ao mesmo tempo.
- Desenvolver com `display_errors = Off`. Localmente, deixe **ligado**. Em produção, **desligado** (Fase 9).

---

### Fase 2 — Modelagem do banco de dados

**Objetivo**: transformar as 20 regras de negócio em tabelas. Esta fase é curta em linhas de código e longa em pensamento — e é a fase em que os erros custam mais caro depois.

**Aprender/pesquisar antes**:
- SQL: `CREATE TABLE`, tipos `INT`, `VARCHAR`, `DATE`, `DATETIME`, `BOOLEAN`/`TINYINT`.
- Chave primária, chave estrangeira, `UNIQUE`, `NOT NULL`, `AUTO_INCREMENT`, índice.
- `UNIQUE` composto (duas colunas juntas formando uma restrição de unicidade).
- Diferença entre `DATE` e `DATETIME` — você vai usar as duas coisas, e por motivos diferentes.

**Tarefas**: escrever um `sql/schema.sql` cobrindo, no mínimo:

| Tabela | Origem | Pontos de atenção |
|---|---|---|
| `jogador` | [`BR-001`](business_rules/BR-001.md), [`BR-012`](business_rules/BR-012.md) | CPF com restrição `UNIQUE`; nome de usuário `VARCHAR(50)`; senha guardada como **hash** (nunca texto puro); flag de e-mail verificado; coluna do tema preferido (FK para `tema`); colunas de ofensiva atual e maior ofensiva ([`BR-010`](business_rules/BR-010.md)) |
| `administrador` | [`fazer_login.md`](use_cases/fazer_login.md), `HU-ADM-00` | Conta separada da de jogador — o login é o mesmo caso de uso, mas os perfis são distintos e o captcha diário **não** se aplica ao admin |
| `tema` | [`BR-016`](business_rules/BR-016.md), [`BR-019`](business_rules/BR-019.md) | Precisa de data de cadastro: o "tema padrão" é definido como **o mais antigo restante do catálogo** (BR-016) |
| `tema_categoria` | [`BR-019`](business_rules/BR-019.md) | Exatamente 5 por tema |
| `tema_valor` | [`BR-019`](business_rules/BR-019.md) | Exatamente 5 por categoria |
| `desafio_diario` | [`BR-020`](business_rules/BR-020.md) | Uma linha por dia (`DATE` com `UNIQUE`); guarda a solução e as pistas do dia |
| `resolucao` (histórico) | [`BR-009`](business_rules/BR-009.md) | **Os seis campos obrigatórios juntos**: jogador, dia do desafio, momento da conclusão (`DATETIME`), tempo de resolução, tema utilizado, flag de elegibilidade |
| `leaderboard` | [`BR-005`](business_rules/BR-005.md), [`BR-017`](business_rules/BR-017.md) | Um registro por (dia, jogador). `UNIQUE` composto em (dia, jogador) — é a garantia física de BR-006 |
| `acesso_diario` | [`BR-002`](business_rules/BR-002.md) | Registro de que o jogador resolveu o captcha naquele dia; `UNIQUE` em (jogador, dia) |
| `verificacao_email` | [`BR-001`](business_rules/BR-001.md), ponto em aberto #6 | Token e validade — o fluxo não está especificado, você decide o formato |

**Decisões de modelagem importantes e o porquê**:

- **O `dia` do desafio é `DATE`, o "momento da conclusão" é `DATETIME`.** São coisas diferentes e as regras dependem dessa diferença: BR-008 diz que resolver hoje um desafio de três dias atrás **não** entra no leaderboard daquele dia — o que só é detectável comparando o `DATE` do desafio com o `DATE` do momento da conclusão.
- **`leaderboard` é uma tabela de verdade, não uma `VIEW`.** Tecnicamente daria para calcular o ranking direto de `resolucao`, mas BR-017 exige que o administrador possa **criar, editar e excluir registros de leaderboard** — ou seja, os registros precisam existir como linhas editáveis.
- **A posição/colocação não é armazenada; é calculada na consulta.** BR-005 manda ordenar por tempo crescente e desempatar por momento de conclusão mais antigo, sem posições compartilhadas. Isso é exatamente `ORDER BY tempo ASC, momento_conclusao ASC`, e a colocação é a posição da linha no resultado. Guardar a posição em coluna significa ter que reescrever todas as linhas do dia a cada nova resolução — não faça isso.
- **Guarde a ofensiva máxima no `jogador`, mas trate a ofensiva atual com cuidado** — veja a Fase 8, que explica o problema.

**Critério de pronto**: você roda o `schema.sql` num banco vazio e ele cria tudo sem erro; e você consegue apontar, para cada tabela, qual BR a justifica.

**Armadilhas**:
- Achar que restrição de banco substitui validação no PHP, ou vice-versa. Faça as duas: `UNIQUE` no CPF (BR-001) protege contra corrida entre dois cadastros simultâneos; a validação em PHP é o que produz a mensagem de erro amigável que o caso de uso [`cadastrar_conta_de_jogador.md`](use_cases/jogador/cadastrar_conta_de_jogador.md) exige.
- Guardar senha em texto puro, ou com `md5`/`sha1`. Use `password_hash()` e `password_verify()` do PHP — são uma linha cada.
- Esquecer o campo de elegibilidade em `resolucao`. BR-009 é explícito: **sem ele, o registro é inválido**, porque é ele que separa prática de classificação oficial.

---

### Fase 3 — Esqueleto da aplicação PHP

**Objetivo**: criar a "forma" que todas as páginas seguintes vão repetir. Investir aqui economiza retrabalho em todas as fases seguintes.

**Aprender/pesquisar antes**:
- `require` / `include` em PHP e a diferença entre eles.
- `$_POST`, `$_GET`, `$_SESSION`, `session_start()`, `header('Location: ...')`.
- PDO: `new PDO(...)`, `prepare()`, `execute()`, `fetch()`, `fetchAll()`, e `PDO::ERRMODE_EXCEPTION`.
- `htmlspecialchars()` — e por que toda variável impressa em HTML precisa passar por ela.
- Conceito de **CSRF** e o padrão de token em formulário.
- Bootstrap: grid, `navbar`, `card`, `table`, `alert`, `form-control` (você já se vira, é só relembrar as classes).

**Tarefas**:
1. `config/` com as credenciais do banco (fora de `public/`, e no `.gitignore`).
2. `src/db.php`: função que devolve uma conexão PDO configurada com exceções ligadas e charset `utf8mb4`.
3. `src/layout.php`: cabeçalho e rodapé comuns, com Bootstrap. Deixe no header um espaço reservado para os indicadores de ofensiva — eles serão preenchidos na Fase 8 ([`BR-011`](business_rules/BR-011.md)).
4. `src/sessao.php`: funções `usuario_logado()`, `exigir_login_jogador()`, `exigir_login_admin()`. Toda página protegida começa com uma dessas chamadas — é assim que você cumpre BR-003 ("sem entrada concluída, o desafio não abre").
5. `src/flash.php`: guardar/ler mensagens de sucesso e erro na sessão (o "flash message" do padrão POST → Redirect → GET).
6. `src/validacao.php`: funções de validação reaproveitáveis (e-mail, CPF, tamanho de nome de usuário, tamanho de senha). **Elas serão usadas em três lugares diferentes** — cadastro do jogador, edição pelo próprio jogador e edição pelo admin — porque BR-001, BR-012 e BR-015 exigem exatamente as mesmas validações nos três casos. Escreva uma vez só.
7. Uma página de teste que usa tudo isso junto.

**Critério de pronto**: existe uma página com layout Bootstrap, que lê algo do banco, que exige login para abrir e que exibe uma mensagem flash depois de um POST.

**Armadilhas**:
- **Não crie um "path de validação mais frouxo" para o administrador.** BR-015 diz explicitamente que as alterações administrativas respeitam as mesmas validações do jogador. Se você escrever a validação duas vezes, elas vão divergir — é questão de tempo.
- Imprimir dados do banco sem `htmlspecialchars()`. Nome de usuário é campo livre; alguém vai colocar `<script>` ali.
- Copiar e colar o bloco de conexão ao banco em cada arquivo. Centralize já.

---

### Fase 4 — Conta e acesso

**Objetivo**: um jogador consegue se cadastrar, verificar o e-mail, entrar resolvendo o captcha do dia e editar seus dados. O administrador consegue entrar (sem captcha).

**Histórias**: `HU-JOG-01`, `HU-JOG-02`, `HU-JOG-04`, `HU-ADM-00` (busque os IDs em [`historias_de_usuario.md`](historias_de_usuario.md)).
**Regras**: [`BR-001`](business_rules/BR-001.md), [`BR-002`](business_rules/BR-002.md), [`BR-012`](business_rules/BR-012.md).
**Casos de uso**: [`use_cases/jogador/cadastrar_conta_de_jogador.md`](use_cases/jogador/cadastrar_conta_de_jogador.md), [`use_cases/fazer_login.md`](use_cases/fazer_login.md), [`use_cases/jogador/editar_dados_da_conta_de_jogador.md`](use_cases/jogador/editar_dados_da_conta_de_jogador.md).

**Aprender/pesquisar antes**:
- `password_hash()`, `password_verify()`, `session_regenerate_id()`.
- Envio de e-mail em PHP: a função `mail()` e suas limitações, e a biblioteca **PHPMailer** com **SMTP** (é uma biblioteca PHP — está dentro da sua stack; não confunda com framework).
- `random_bytes()` / `bin2hex()` para gerar tokens.
- Biblioteca **GD** do PHP (`imagecreate`, `imagestring`, `imagepng`) — para desenhar um captcha próprio.
- Validação de CPF: o algoritmo dos dígitos verificadores.

**Tarefas**:
1. **Cadastro** ([`cadastrar_conta_de_jogador.md`](use_cases/jogador/cadastrar_conta_de_jogador.md)): formulário com e-mail, CPF, nome de usuário, senha. As quatro validações de BR-001 precisam valer **simultaneamente** — o cadastro só acontece se as quatro passarem. Reexiba o formulário preenchido com a mensagem do erro (o caso de uso manda voltar ao passo 2, não recomeçar do zero).
2. **Verificação de e-mail**: o fluxo **não está especificado** (ponto em aberto #6 de `historias_de_usuario.md`). Decisão sugerida e suficiente: gerar um token aleatório, gravá-lo, enviar um link com o token, e marcar `email_verificado = 1` quando o link for aberto. Enquanto não verificado, a conta não faz login. Anote essa decisão como sua.
3. **Login compartilhado** ([`fazer_login.md`](use_cases/fazer_login.md)): **um** fluxo para os dois perfis. A diferença é que o ramo do jogador exige captcha e o do administrador não.
4. **Captcha diário** ([`BR-002`](business_rules/BR-002.md)): este é o ponto que mais gente entende errado. O captcha é exigido **no primeiro login de cada dia**, todo dia, e não uma vez na vida. A implementação prática é: ao tentar login como jogador, consultar se já existe registro em `acesso_diario` para (jogador, hoje-em-Brasília); se existir, dispensar o captcha; se não existir, exigir, e ao acertar gravar o registro. A documentação não define qual captcha usar — desenhar um você mesmo com GD (uma sequência de caracteres embaralhada, guardada na sessão) é simples, funciona offline e evita depender de serviço externo.
5. **Edição de conta** ([`editar_dados_da_conta_de_jogador.md`](use_cases/jogador/editar_dados_da_conta_de_jogador.md)): reutilizando as mesmas funções de validação da Fase 3, conforme [`BR-012`](business_rules/BR-012.md).
6. Logout.

**Critério de pronto**: percorra os critérios de aceitação de `HU-JOG-01`, `HU-JOG-02`, `HU-JOG-04` e `HU-ADM-00` um a um, incluindo os casos negativos (CPF repetido, senha de 7 caracteres, nome de 51 caracteres, e-mail não verificado, captcha errado). Os **Exemplos** de BR-001 são literalmente a sua lista de testes.

**Armadilhas**:
- Tratar o captcha como "uma vez por conta". Releia BR-002: é **por dia de uso**.
- Testar a virada do dia. Para testar sem esperar a meia-noite, altere manualmente a data do registro em `acesso_diario` para ontem e tente entrar de novo.
- Aplicar o captcha ao administrador. BR-002 é exclusiva do jogador — está dito explicitamente em `CLAUDE.md`, em `fazer_login.md` e em `HU-ADM-00`.
- Envio de e-mail no ambiente local costuma **não funcionar** e isso é normal. Enquanto estiver local, imprima o link de verificação na tela ou grave num arquivo de log em vez de enviar. Resolva o envio de verdade na Fase 9/10.

---

### Fase 5 — Temas do teste

**Objetivo**: existir um catálogo de temas válidos (5 categorias × 5 valores) e o jogador poder escolher o seu. Isso vem **antes** do jogo porque o desafio não pode ser exibido sem um tema ([`BR-013`](business_rules/BR-013.md), e a pré-condição 2 de [`jogar_teste_de_einstein.md`](use_cases/jogador/jogar_teste_de_einstein.md)).

**Histórias**: `HU-JOG-03`, e um adiantamento parcial de `HU-ADM-06`.
**Regras**: [`BR-013`](business_rules/BR-013.md), [`BR-019`](business_rules/BR-019.md), [`BR-016`](business_rules/BR-016.md).
**Casos de uso**: [`use_cases/jogador/alterar_tema_de_teste_de_einstein.md`](use_cases/jogador/alterar_tema_de_teste_de_einstein.md).

**Tarefas**:
1. Popular o banco com **dois ou três temas de exemplo** via script SQL (`sql/seed_temas.sql`). Você precisa de dados para desenvolver a Fase 6; o CRUD administrativo de temas só chega na Fase 11.
2. Escrever a **função de validação de estrutura de tema**: exatamente 5 categorias, exatamente 5 valores por categoria, nem mais nem menos ([`BR-019`](business_rules/BR-019.md)). Essa função será reaproveitada tal e qual pelo painel admin na Fase 11.
3. **Seletor de tema na página de configurações** ([`alterar_tema_de_teste_de_einstein.md`](use_cases/jogador/alterar_tema_de_teste_de_einstein.md)): formulário simples, salva a preferência na conta.
4. Escrever a função que **carrega o tema salvo do jogador** — ela será chamada em toda tela que exibe um desafio, inclusive nos desafios antigos (BR-013 vale também para o histórico).
5. Implementar a função "**tema padrão do sistema**" = o tema **mais antigo** ainda existente no catálogo ([`BR-016`](business_rules/BR-016.md)). Ela é necessária como fallback para todo jogador sem preferência definida, e será reusada na Fase 11 quando um tema for excluído.

**Critério de pronto**: você troca o tema nas configurações e a mudança aparece; o sistema nunca fica sem um tema para exibir.

**Armadilhas**:
- Modelar tema como um campo de texto livre ou JSON solto. A estrutura 5×5 é **invariante** (BR-019) e você vai precisar consultá-la categoria por categoria para montar a grade do jogo. Tabelas relacionais são a escolha certa aqui.
- Deixar o jogador sem tema nenhum. Todo caminho que exibe um desafio precisa ter um tema — recém-cadastrado, defina o tema padrão.

---

### Fase 6 — Desafio diário e resolução

**Objetivo**: o coração do produto. Existe um desafio por dia, o jogador abre, resolve, finaliza e a resolução é registrada.

**Histórias**: `HU-SIS-03`, `HU-JOG-05`, `HU-JOG-06`, `HU-SIS-05`.
**Regras**: [`BR-020`](business_rules/BR-020.md), [`BR-003`](business_rules/BR-003.md), [`BR-009`](business_rules/BR-009.md), [`BR-013`](business_rules/BR-013.md), [`BR-019`](business_rules/BR-019.md).
**Casos de uso**: [`use_cases/jogador/jogar_teste_de_einstein.md`](use_cases/jogador/jogar_teste_de_einstein.md).

**Aprender/pesquisar antes**:
- JS puro: `document.querySelector`, `addEventListener`, `setInterval`, manipulação de `classList`, `<input type="hidden">` preenchido por JS.
- PHP: arrays multidimensionais, `shuffle()`, `json_encode()`/`json_decode()`, `mt_srand()` (semente determinística), `DateTime` e `DateTimeZone`.
- Conceito de "zebra puzzle" / "Einstein's riddle" e como se resolve por eliminação numa grade.

**Tarefas**:

1. **Gerador do desafio** ([`BR-020`](business_rules/BR-020.md)). Este é o pedaço tecnicamente mais difícil do projeto inteiro, e o algoritmo **não está especificado na documentação** (ponto em aberto #5). Caminho sugerido, em ordem de dificuldade crescente:
   - a) sortear a **solução**: para 5 categorias × 5 valores, a solução é simplesmente a atribuição de cada valor a uma das 5 posições — na prática, 5 permutações (uma por categoria);
   - b) derivar um conjunto de **pistas verdadeiras** a partir dessa solução (do tipo "X está na mesma posição que Y", "X está imediatamente à esquerda de Y", "X não é Z");
   - c) verificar se as pistas levam a **solução única** — escreva um resolvedor por força bruta e vá **removendo** pistas enquanto a solução continuar única;
   - d) guardar solução e pistas no `desafio_diario`.
   
   Comece por uma versão simples e funcional (b + d, com muitas pistas, sem minimizar), e só depois refine. Um jogo com pistas demais é jogável; um jogo sem gerador não é.

2. **Separação crucial entre desafio e tema**: a lógica do desafio é abstrata (5 categorias × 5 valores × posições). O tema apenas **dá nomes** a essas categorias e valores (BR-013 e BR-019 são claras: o tema muda nomes e textos, **nunca** a estrutura). A leitura recomendada — e coerente com BR-020 + BR-013 — é: **gerar um único desafio por dia, de forma abstrata, e renderizá-lo com o tema de cada jogador**. Assim todos jogam o mesmo enigma e cada um o vê com a roupagem que escolheu. *(Esta é uma interpretação de projeto: a documentação não afirma explicitamente que o desafio é o mesmo para todos — anote a decisão.)*

3. **Geração sob demanda, agora; agendada, na Fase 10**: por enquanto, ao abrir a página do jogo, se não existir desafio para o dia, gere na hora. O agendamento às 00:00 (BR-020) entra na Fase 10, quando você tiver cron.

4. **Página do jogo** ([`jogar_teste_de_einstein.md`](use_cases/jogador/jogar_teste_de_einstein.md)): o ***Painel do Teste*** é uma grade HTML montada em PHP a partir do tema salvo. A interação de marcar/desmarcar células é JS puro no navegador. Ao clicar em ***Botão Finalizar Teste***, o estado da grade vai num POST comum.

5. **Cronômetro**: grave o momento de início **no servidor** (na sessão ou numa linha de "tentativa em andamento") quando o teste é aberto. O JS só mostra os segundos passando. No POST de finalização, o tempo é `agora - inicio_gravado_no_servidor`. **Nunca** aceite o tempo vindo do cliente.

6. **Registro da resolução** ([`BR-009`](business_rules/BR-009.md)): grave os **seis** campos juntos — jogador, dia do desafio, momento da conclusão, tempo de resolução, tema utilizado e flag de elegibilidade. A elegibilidade é calculada na Fase 7; por enquanto, deixe a coluna preenchida com um valor provisório e volte aqui.

7. **Acesso imediato** ([`BR-003`](business_rules/BR-003.md)): jogador com entrada concluída abre o desafio do dia direto da página inicial, sem passos extras. Jogador sem entrada concluída é bloqueado.

8. **Dia futuro nunca abre** (fluxo alternativo 1 do caso de uso, e [`BR-004`](business_rules/BR-004.md)): valide **no servidor**, não só escondendo o botão.

**Critério de pronto**: você abre o desafio do dia, resolve, finaliza, e existe uma linha completa na tabela de resoluções com o tempo calculado pelo servidor.

**Armadilhas**:
- **Confiar no tempo enviado pelo cliente.** Vale repetir: é a falha mais óbvia de burlar em um jogo cujo ranking é por tempo (BR-005).
- Gerar um desafio diferente por jogador por acidente (ex.: gerando dentro do loop de renderização). Um desafio por dia, chave `UNIQUE` na coluna do dia.
- Calcular "hoje" com `date('Y-m-d')` sem fuso definido. Use `DateTime` com `DateTimeZone('America/Sao_Paulo')`, sempre, e escreva uma função `dia_de_referencia()` usada por **todo** o sistema.
- Tentar fazer o gerador perfeito antes de ter o jogo funcionando. Feio e funcional primeiro.

---

### Fase 7 — Elegibilidade, leaderboard e dias anteriores

**Objetivo**: as regras que fazem o jogo ser um jogo competitivo justo. É a fase com mais regras de negócio densas — vá devagar.

**Histórias**: `HU-JOG-07`, `HU-JOG-08`, `HU-JOG-09`, `HU-JOG-10`, `HU-JOG-11`, `HU-SIS-04`.
**Regras**: [`BR-004`](business_rules/BR-004.md), [`BR-005`](business_rules/BR-005.md), [`BR-006`](business_rules/BR-006.md), [`BR-007`](business_rules/BR-007.md), [`BR-008`](business_rules/BR-008.md).
**Casos de uso**: [`use_cases/jogador/consultar_leaderboard_diario.md`](use_cases/jogador/consultar_leaderboard_diario.md), [`use_cases/jogador/navegar_historico_de_jogos_anteriores.md`](use_cases/jogador/navegar_historico_de_jogos_anteriores.md).

**Tarefas**:

1. **A função de elegibilidade.** Escreva uma única função que, ao finalizar uma resolução, decide se ela é elegível. Ela responde "sim" **apenas** quando as três condições valem juntas:
   - o dia do desafio é **hoje** (senão, [`BR-008`](business_rules/BR-008.md): resolução tardia nunca entra no leaderboard do dia original);
   - o jogador **ainda não tem** resolução elegível registrada para esse dia ([`BR-006`](business_rules/BR-006.md));
   - a resolução é válida (o teste foi de fato resolvido corretamente).
   
   Se for elegível, cria o registro em `leaderboard`. Se não for, grava a resolução no histórico com a flag em falso e **não toca no leaderboard** ([`BR-007`](business_rules/BR-007.md)).

2. **Página de leaderboard** ([`consultar_leaderboard_diario.md`](use_cases/jogador/consultar_leaderboard_diario.md)): filtro de dia (um `<form method="get">` com um `<input type="date">` resolve), tabela ordenada por `tempo ASC, momento_conclusao ASC` ([`BR-005`](business_rules/BR-005.md)), colocação numerada sequencialmente **sem empates** (o desempate por momento de conclusão garante posição única para cada jogador). Dia sem resoluções elegíveis exibe a mensagem de "não há classificação".

3. **Navegação pelo histórico** ([`navegar_historico_de_jogos_anteriores.md`](use_cases/jogador/navegar_historico_de_jogos_anteriores.md)): listar os dias com desafio existente, permitir abrir qualquer dia **anterior** disponível, bloquear dias futuros e informar indisponibilidade de dias que não existem ([`BR-004`](business_rules/BR-004.md)). Abrir um desafio antigo cai no mesmo fluxo de resolução da Fase 6, com o tema salvo do jogador.

4. **Rejogo** ([`BR-007`](business_rules/BR-007.md)): não há limite de tentativas. Nova resolução gera nova linha no histórico, com elegibilidade falsa, e o leaderboard não muda.

**Critério de pronto**: reproduza manualmente os **Exemplos** de BR-005, BR-006, BR-007 e BR-008 — inclusive os exemplos rotulados como "cenário inválido", que descrevem o que **não** pode acontecer. Se algum "cenário inválido" acontece no seu sistema, ele está errado.

**Armadilhas**:
- Confundir "primeira resolução **do dia**" com "melhor resolução do dia". BR-006 é **primeira**, não melhor. Um jogador que melhora o tempo na segunda tentativa continua com o tempo da primeira.
- Confundir "dia do desafio" com "dia da conclusão". As duas datas coincidem no caso normal e divergem justamente no caso que BR-008 quer proteger. Compare sempre as duas.
- Fazer a checagem de elegibilidade só no PHP. O `UNIQUE (dia, jogador)` na tabela `leaderboard` é a rede de segurança contra dois POSTs simultâneos.
- Guardar a colocação numérica no banco. Calcule na consulta.

---

### Fase 8 — Ofensiva (streak)

**Objetivo**: calcular e exibir ofensiva atual e maior ofensiva.

**Histórias**: `HU-SIS-01`, `HU-JOG-12`.
**Regras**: [`BR-010`](business_rules/BR-010.md), [`BR-011`](business_rules/BR-011.md).
**Casos de uso**: nenhum. BR-010 é regra de sistema; BR-011 é uma regra de exibição que a documentação reconhece como **sem caso de uso dedicado** (ponto em aberto #1 de [`historias_de_usuario.md`](historias_de_usuario.md)).

**Tarefas**:
1. **Cálculo da ofensiva atual** ([`BR-010`](business_rules/BR-010.md)): dias **consecutivos** em que o jogador resolveu o desafio do respectivo dia. Faltou um dia, zera.
2. **Maior ofensiva**: atualizada apenas quando a atual a supera. Ela **nunca diminui**.
3. **Header** ([`BR-011`](business_rules/BR-011.md)): a página inicial exibe os **dois** valores, sempre juntos. Exibir apenas um é explicitamente inválido segundo os exemplos da BR.

**Decisão importante — como calcular a ofensiva atual**: existe uma armadilha sutil aqui. Se você só recalcular a ofensiva **quando o jogador resolve um desafio**, um jogador que ficou três dias sem entrar continuará vendo "ofensiva: 5" no header, porque nada disparou o recálculo. Duas saídas:

- **(recomendada)** calcular a ofensiva atual **na hora de exibir**, a partir do histórico de resoluções: conte para trás a partir de hoje (ou de ontem, se ainda não resolveu hoje) enquanto houver dias consecutivos resolvidos. É sempre correto, dispensa job noturno, e o volume de dados de um projeto de estudo torna o custo irrelevante. A **maior ofensiva** continua guardada em coluna no `jogador` e é atualizada quando superada.
- **(alternativa)** manter a ofensiva atual em coluna e rodar uma rotina diária que zera a de quem perdeu o dia. Mais eficiente, mas cria mais um jeito de o dado ficar errado.

**Critério de pronto**: crie manualmente, direto no banco, resoluções em dias consecutivos e com buracos, e confira os quatro **Exemplos** de BR-010.

**Armadilhas**:
- Contar resoluções em vez de dias. Cinco resoluções no mesmo dia é ofensiva 1, não 5. Conte **dias distintos**.
- Contar como parte da ofensiva a resolução tardia de um desafio antigo. Cuidado: BR-010 fala em "dias consecutivos em que o jogador resolveu o desafio **do respectivo dia**". Decida e documente sua interpretação — a leitura mais coerente com BR-008 é que resolver hoje um desafio de anteontem não conserta a ofensiva quebrada de anteontem.
- Deixar a maior ofensiva diminuir. Ela só sobe (BR-010).

---

### Fase 9 — Primeiro deploy real

**Objetivo**: colocar no ar o que já funciona localmente. A partir daqui você desenvolve com um alvo real.

**Aprender/pesquisar antes**: tudo da Fase 1, agora numa máquina remota, mais:
- `ssh`, chaves SSH (`ssh-keygen`), `scp` ou `rsync`.
- Firewall: `ufw` (Debian/Ubuntu) ou `firewalld` (Fedora/RHEL).
- DNS: registro `A`, apontar um domínio para um IP, propagação.
- **Certbot** / **Let's Encrypt** para HTTPS gratuito.
- `timedatectl set-timezone America/Sao_Paulo`.

**Escolha de hospedagem** — a decisão não está na documentação, e há um trade-off honesto:

| Opção | A favor | Contra |
|---|---|---|
| **VPS** (máquina Linux sua) | Controle total; cron livre; fuso configurável; você **aprende Linux de verdade** — que é meio ponto deste projeto de portfólio | Você é o administrador do sistema: firewall, atualizações, backup, tudo é seu |
| **Hospedagem compartilhada** (painel tipo cPanel) | PHP e MySQL prontos; HTTPS em um clique; quase nada de Linux | Cron pode ser limitado ou inexistente; fuso do servidor pode não ser negociável; pouco a aprender |

**Recomendação**: VPS, justamente porque você não sabe Linux — este projeto é a oportunidade. Mas **verifique antes de contratar qualquer coisa** se há suporte a **cron** e se o **fuso é ajustável**: as rotinas de BR-014 e BR-020 dependem disso, e descobrir a limitação na Fase 10 seria caro.

**Tarefas**:
1. Contratar o servidor; acessar por SSH com **chave**, não com senha. Desabilitar login de `root` por senha.
2. Firewall liberando apenas 22 (SSH), 80 (HTTP) e 443 (HTTPS).
3. Instalar Apache, PHP (com as extensões da Fase 1), MySQL/MariaDB. Rodar `mysql_secure_installation`.
4. Definir o fuso do servidor como `America/Sao_Paulo` e conferir o do PHP e o do MySQL.
5. Enviar o código (`git clone` do repositório no servidor é o caminho mais limpo; `rsync` também serve). **O arquivo de credenciais não vem do Git** — crie-o na mão no servidor.
6. VirtualHost com `DocumentRoot` apontando para `public/`, e **não** para a raiz do projeto. Isso é o que mantém `config/`, `src/` e `cron/` inacessíveis pelo navegador.
7. Ajustar dono e permissões: arquivos `644`, diretórios `755`, dono do usuário do Apache (`www-data` no Debian/Ubuntu, `apache` no Fedora/RHEL) apenas onde a aplicação precisar escrever.
8. Importar o `schema.sql` e os temas de exemplo.
9. Apontar o domínio e emitir o certificado HTTPS com Certbot. Forçar redirecionamento de HTTP para HTTPS.
10. Em produção: `display_errors = Off` e `log_errors = On`. Erro na tela do usuário é vazamento de informação.
11. Configurar o envio de e-mail de verdade (Fase 4, tarefa 2) — provavelmente via **SMTP** de um provedor, com **PHPMailer**.

**Critério de pronto**: o site abre no domínio, com cadeado, e você consegue se cadastrar, entrar e jogar pela internet.

**Armadilhas**:
- Apontar o `DocumentRoot` para a raiz do projeto. Aí `https://seusite/config/db.php` vira um problema sério.
- Subir o arquivo de credenciais para o Git. Confira o `.gitignore` **antes** do primeiro `git push`.
- Deixar `display_errors` ligado em produção — as mensagens de erro do PHP mostram caminhos de arquivo e trechos de SQL.
- Achar que `mail()` do PHP vai funcionar. E-mail enviado direto de um IP de VPS cai em spam ou é bloqueado. Pesquise **SPF**, **DKIM**, **DNS reverso** — ou, muito mais simples, use o SMTP de um provedor de e-mail.
- Esquecer de habilitar os serviços no boot (`systemctl enable`). O servidor reinicia e o site não volta.

---

### Fase 10 — Automações agendadas (cron)

**Objetivo**: o sistema passa a funcionar sozinho, como BR-020 e BR-014 exigem.

**Histórias**: `HU-SIS-02`, `HU-SIS-03` (agora de verdade).
**Regras**: [`BR-020`](business_rules/BR-020.md), [`BR-014`](business_rules/BR-014.md), [`BR-010`](business_rules/BR-010.md).

**Aprender/pesquisar antes**:
- `crontab -e`, a sintaxe dos cinco campos, `crontab -l`.
- PHP em linha de comando (**PHP CLI**): rodar `php /caminho/script.php` no terminal.
- Redirecionamento de saída para arquivo de log (`>>` e `2>&1`).

**Tarefas**:
1. `cron/gerar_desafio.php` — agendado para **00:00** ([`BR-020`](business_rules/BR-020.md)): gera o desafio do novo dia de referência, se ainda não existir. Mantenha também a geração sob demanda da Fase 6 como rede de segurança: se o cron falhar, ninguém fica sem jogo.
2. `cron/alerta_ofensiva.php` — agendado para **18:00** ([`BR-014`](business_rules/BR-014.md)): envia e-mail **apenas** para quem tem ofensiva ativa **e** ainda não resolveu o desafio de hoje. Quem já resolveu não recebe; quem não tem ofensiva não recebe.
3. Cada script deve gravar num log próprio o que fez (quantos e-mails, qual dia gerado). Sem log, um cron que falha silenciosamente é indetectável.
4. Torne os scripts **idempotentes**: rodar duas vezes no mesmo dia não pode gerar dois desafios nem dois e-mails. Grave uma marca de "já executei hoje".

**Critério de pronto**: você espera a virada de um dia real e, na manhã seguinte, existe o desafio novo sem você ter feito nada; e às 18:00 o log do alerta mostra a decisão tomada para cada jogador.

**Armadilhas**:
- **Fuso do cron.** O cron usa o fuso **do sistema**. Se o servidor estiver em UTC, seu "18:00" dispara às 15:00 de Brasília. Confira com `timedatectl` e com `date` no próprio servidor.
- O PHP da linha de comando pode usar um `php.ini` **diferente** do PHP do Apache — inclusive com outro fuso. Verifique com `php -i | grep -i timezone`.
- Caminhos relativos dentro de scripts de cron. O cron não roda a partir da pasta do projeto; use sempre caminhos absolutos ou `__DIR__`.
- Deixar os scripts dentro de `public/`. Eles ficam em `cron/`, fora do alcance do navegador — senão qualquer pessoa dispara o envio de e-mails acessando uma URL.
- Mandar e-mail de teste para jogadores reais enquanto ajusta. Use um modo de simulação que só escreve no log.

---

### Fase 11 — Painel administrativo

**Objetivo**: as ferramentas do administrador. Vem por último entre as funcionalidades porque, segundo BR-017, BR-018 e BR-020, o sistema já cria desafio, leaderboard e histórico sozinho — o CRUD administrativo desses três é **mecanismo de correção**, não caminho primário. A exceção é a gestão de temas, que é o único fluxo administrativo genuinamente do dia a dia.

**Histórias**: `HU-ADM-01` a `HU-ADM-16`.
**Regras**: [`BR-015`](business_rules/BR-015.md), [`BR-016`](business_rules/BR-016.md), [`BR-017`](business_rules/BR-017.md), [`BR-018`](business_rules/BR-018.md).
**Casos de uso**: os 16 arquivos em [`use_cases/admin/`](use_cases/admin/), agrupados por recurso.

**Ordem sugerida dentro da fase** (da maior para a menor utilidade real):

1. **Temas** ([`use_cases/admin/temas_de_testes/`](use_cases/admin/temas_de_testes/)) — o único fluxo admin que não é corretivo. Criar, visualizar, editar, apagar. A validação 5×5 já existe desde a Fase 5. **O ponto delicado é o apagar**: [`apagar_tema.md`](use_cases/admin/temas_de_testes/apagar_tema.md), passo 6, e [`BR-016`](business_rules/BR-016.md) exigem que todo jogador que tinha aquele tema salvo passe a usar o **tema padrão** (o mais antigo restante). Isso é um `UPDATE` em massa nas contas afetadas, e nenhuma conta pode ficar apontando para um tema que não existe mais.
2. **Contas de jogadores** ([`use_cases/admin/conta_de_jogador/`](use_cases/admin/conta_de_jogador/)) — reaproveite **as mesmas** funções de validação da Fase 3/4. [`BR-015`](business_rules/BR-015.md) é explícito: as validações são as mesmas do jogador. Nada de caminho mais frouxo para o admin.
3. **Histórico de testes** ([`use_cases/admin/historico_de_testes/`](use_cases/admin/historico_de_testes/)) — ferramenta de reparo. Os seis campos de [`BR-009`](business_rules/BR-009.md) são obrigatórios também aqui, e a coerência entre dia do teste e dados associados precisa ser validada ([`BR-018`](business_rules/BR-018.md)).
4. **Leaderboard** ([`use_cases/admin/leaderboards/`](use_cases/admin/leaderboards/)) — ferramenta de reparo. Ao salvar, o registro precisa continuar respeitando ordenação por menor tempo e a regra da primeira resolução válida ([`BR-017`](business_rules/BR-017.md) + BR-005 + BR-006). Como validar um registro isolado contra os demais do dia **não está especificado** (ponto em aberto #3): a suposição registrada em `historias_de_usuario.md` é que o sistema recalcula as posições do dia ao salvar — e, como você calcula a colocação na consulta (Fase 2), isso acontece naturalmente.

**Tarefas transversais**:
- Todas as telas em `public/admin/`, protegidas por `exigir_login_admin()`.
- Todo "apagar" exige **confirmação explícita** — está em todos os casos de uso de exclusão.
- Todo "não encontrado" precisa informar indisponibilidade em vez de quebrar (é fluxo alternativo em todos os `visualizar_*`).
- Reaproveite um mesmo par de arquivos "lista + formulário" por recurso; os quatro CRUDs são estruturalmente idênticos.

**Critério de pronto**: os critérios de aceitação de `HU-ADM-01` a `HU-ADM-16`, com atenção especial ao cenário de exclusão de tema usado por vários jogadores (exemplo 5 de [`BR-016`](business_rules/BR-016.md)).

**Armadilhas**:
- Construir o painel como se fosse o jeito normal de criar leaderboard e histórico. Ele é o extintor de incêndio, não a torneira. Deixe isso visível na própria interface (um aviso do tipo "estes registros são gerados automaticamente; edite apenas para corrigir inconsistências").
- Duplicar as regras de validação. Se você reescrever a validação de CPF na área admin, ela vai divergir da do cadastro.
- Excluir um tema e deixar jogadores órfãos. Esse é o erro que BR-016 antecipa explicitamente.

---

### Fase 12 — Endurecimento, backup e operação

**Objetivo**: o site fica de pé sozinho, e um erro seu não apaga o projeto.

**Aprender/pesquisar antes**: `mysqldump`, `tar`, `cron` (já visto), `logrotate`, atualizações de sistema (`apt upgrade` / `dnf upgrade`).

**Tarefas**:
1. **Backup automático do banco**: `mysqldump` diário via cron, guardando os últimos N dias. **Copie os backups para fora do servidor** — backup que mora na mesma máquina não protege contra a máquina morrer.
2. **Teste a restauração pelo menos uma vez.** Backup nunca testado não é backup.
3. Revisar segurança: senhas com `password_hash`, todo SQL com prepared statements, toda saída com `htmlspecialchars()`, token CSRF nos formulários que alteram dados, `session_regenerate_id()` no login, cookie de sessão com as flags `HttpOnly` e `Secure`.
4. Conferir que `config/`, `src/` e `cron/` **não** são acessíveis pelo navegador (teste na marra: tente abrir as URLs).
5. Rotina de atualização de pacotes do servidor.
6. Uma página de erro amigável para 404 e 500.

**Critério de pronto**: você consegue destruir o banco de propósito e restaurá-lo a partir do backup.

---

## 7. Conselhos gerais de hospedagem (Linux + Apache)

Consolidado para consulta rápida — você não sabe nada disso hoje, e é normal voltar aqui várias vezes.

### Onde o código fica

- O padrão do Apache é servir a partir de `/var/www/html`. O melhor arranjo para este projeto é colocar o projeto inteiro em algo como `/var/www/geniooo` e apontar o `DocumentRoot` do VirtualHost para `/var/www/geniooo/public`.
- **Tudo que estiver fora do `DocumentRoot` é invisível para o navegador.** É por isso que `config/` (credenciais), `src/` (lógica) e `cron/` (rotinas) ficam fora dele. Essa única decisão elimina uma classe inteira de vulnerabilidades.

### Permissões de arquivo

- Diretórios `755`, arquivos `644`. Isso significa: o dono pode alterar, os outros só podem ler.
- O Apache roda como um usuário próprio: `www-data` (Debian/Ubuntu) ou `apache` (Fedora/RHEL). Descubra qual é o da sua distribuição.
- A aplicação só precisa de **permissão de escrita** nas pastas onde realmente escreve (logs, uploads se houver). O resto do código o Apache só precisa **ler**.
- **`777` nunca resolve nada de forma correta.** Se aparecer erro 403, o problema é dono/grupo do arquivo ou uma diretiva do Apache — não a ausência de permissão para o mundo inteiro.
- O arquivo de credenciais do banco merece permissão mais restritiva ainda (`640` ou `600`, com dono adequado).

### VirtualHost

- Debian/Ubuntu: arquivos em `/etc/apache2/sites-available/`, habilitados com `a2ensite`, e o Apache recarregado com `systemctl reload apache2`.
- Fedora/RHEL: arquivos em `/etc/httpd/conf.d/`.
- Diretivas que importam: `ServerName`, `DocumentRoot`, o bloco `<Directory>` correspondente, e `AllowOverride` (necessário se você quiser usar `.htaccess`).
- Módulo `rewrite` (`a2enmod rewrite`) só é necessário se você for fazer URLs amigáveis — **não é requisito**; `jogo.php?dia=2026-08-04` funciona perfeitamente.

### HTTPS

- **Certbot** com **Let's Encrypt**: certificado gratuito, renovação automática, e ele mesmo ajusta o VirtualHost.
- Force o redirecionamento de HTTP para HTTPS. Login e senha trafegando em texto puro não é aceitável nem em projeto de estudo.
- Depois do HTTPS ativo, marque o cookie de sessão como `Secure`.

### Cron para as rotinas automáticas

As três rotinas do sistema:

| Horário | Script | Regra |
|---|---|---|
| 00:00 | geração do desafio do dia | [`BR-020`](business_rules/BR-020.md) |
| 18:00 | alerta de risco de ofensiva | [`BR-014`](business_rules/BR-014.md) |
| (opcional, noturno) | consolidação de ofensiva | [`BR-010`](business_rules/BR-010.md) — desnecessário se você calcular na exibição, conforme a Fase 8 |

- Edite com `crontab -e`; a sintaxe são cinco campos (minuto, hora, dia do mês, mês, dia da semana) seguidos do comando.
- **Confirme o fuso do servidor** (`timedatectl`). Todos esses horários são de Brasília por exigência das BRs.
- Use caminho absoluto para o binário do PHP e para o script, e redirecione a saída para um arquivo de log — cron silencioso é cron que você não sabe que quebrou.
- O PHP CLI pode ter `php.ini` distinto do PHP do Apache. Confira o fuso dos dois.

### Banco de dados

- Rode `mysql_secure_installation` logo depois de instalar.
- Crie um usuário de banco **exclusivo da aplicação**, com permissão apenas no banco `geniooo`. Nunca conecte como `root`.
- Não exponha a porta do banco na internet — a aplicação e o banco estão na mesma máquina, então o banco só precisa escutar em `localhost`.

### Backup

- `mysqldump` diário via cron.
- Guarde várias gerações (últimos 7 dias, por exemplo), não só a última — se a corrupção passar despercebida por dois dias, o backup de ontem já veio corrompido.
- Copie para fora do servidor.
- Restaure ao menos uma vez, para valer.

### Logs — o hábito mais importante

Antes de perguntar "por que não funciona", olhe:

- log de erro do Apache (`/var/log/apache2/error.log` no Debian/Ubuntu, `/var/log/httpd/error_log` no Fedora/RHEL);
- log de acesso, para confirmar se a requisição sequer chegou;
- log de erro do PHP (conforme configurado no `php.ini`);
- os logs dos seus próprios scripts de cron.

`tail -f` acompanhando o log enquanto você recarrega a página no navegador vai resolver a maioria dos seus problemas mais rápido do que qualquer busca.

### Acesso ao servidor

- Entre por SSH com **chave**, não com senha; desabilite o login de `root` por senha.
- Firewall liberando apenas 22, 80 e 443.
- Mantenha o sistema atualizado.

---

## 8. Matriz de rastreabilidade — fase × histórias × regras

| Fase | Histórias | Regras de negócio |
|---|---|---|
| 0 — Preparação | — | — |
| 1 — Linux e Apache | — | — (mas o fuso configurado aqui sustenta BR-002, BR-010, BR-014, BR-020) |
| 2 — Banco de dados | (todas, indiretamente) | BR-001, BR-005, BR-006, BR-009, BR-016, BR-019, BR-020 |
| 3 — Esqueleto PHP | — | BR-001, BR-012, BR-015 (funções de validação compartilhadas) |
| 4 — Conta e acesso | HU-JOG-01, HU-JOG-02, HU-JOG-04, HU-ADM-00 | BR-001, BR-002, BR-012 |
| 5 — Temas | HU-JOG-03, (parte de HU-ADM-06) | BR-013, BR-016, BR-019 |
| 6 — Desafio e resolução | HU-SIS-03, HU-JOG-05, HU-JOG-06, HU-SIS-05 | BR-003, BR-009, BR-013, BR-019, BR-020 |
| 7 — Elegibilidade e leaderboard | HU-JOG-07, HU-JOG-08, HU-JOG-09, HU-JOG-10, HU-JOG-11, HU-SIS-04 | BR-004, BR-005, BR-006, BR-007, BR-008 |
| 8 — Ofensiva | HU-SIS-01, HU-JOG-12 | BR-010, BR-011 |
| 9 — Deploy | — | — |
| 10 — Cron | HU-SIS-02, HU-SIS-03 | BR-010, BR-014, BR-020 |
| 11 — Painel admin | HU-ADM-01 a HU-ADM-16 | BR-015, BR-016, BR-017, BR-018 |
| 12 — Operação | — | — |

Ao final, as **34 histórias** de [`historias_de_usuario.md`](historias_de_usuario.md) e as **20 regras** de [`business_rules/`](business_rules/) estão cobertas.

## 9. Erros que mais custam caro neste projeto — resumo

1. **Confiar no tempo enviado pelo navegador.** O tempo do leaderboard (BR-005) precisa ser calculado no servidor. É a falha mais fácil de explorar e a que estraga o produto inteiro.
2. **Fuso horário inconsistente.** Sistema, PHP do Apache, PHP CLI, MySQL e cron precisam **todos** estar em `America/Sao_Paulo`. Uma divergência quebra captcha diário, leaderboard, ofensiva e alerta das 18:00 de uma vez só.
3. **Confundir "primeira" com "melhor" resolução.** BR-006 é a **primeira**. Rejogar nunca melhora a posição (BR-007).
4. **Confundir "dia do desafio" com "dia da conclusão".** É essa distinção que faz BR-008 funcionar.
5. **`chmod 777` e `DocumentRoot` na raiz do projeto.** As duas formas mais rápidas de deixar credenciais expostas.
6. **Duplicar as regras de validação** entre jogador e administrador. BR-015 exige que sejam idênticas.
7. **Cron sem log.** Uma rotina automática que falha em silêncio é pior do que não existir.
8. **Adicionar funcionalidades fora do escopo da seção 3.** Cada uma delas é tempo que não entra no que a documentação realmente pede.
