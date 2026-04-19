# SDG — Software Design Guide
## Website da Escola com Sistema de Presenças RFID

Aluno: Marko Nikolaienko

---

## Índice

1. Introdução
2. Contexto e Objetivos do Projeto
3. Requisitos Funcionais e Não Funcionais
   - 3.1 Gestão de Utilizadores
   - 3.2 Requisitos Funcionais RFID
   - 3.3 Requisitos Não Funcionais
4. Visão Arquitetural Geral
5. Design de Dados
6. Design da Interface (UI/UX)
7. Diagramas
8. Testes
9. Segurança
10. Implementação
11. Deploy e Ambiente
12. Logging e Monitorização
13. Manutenção e Evolução
14. Conclusão

---

## 1. Introdução

O presente documento descreve o Software Design Guide (SDG) do projeto "Website da escola com sistema de presenças RFID". Este projeto foi desenvolvido no âmbito da PAP do curso profissional de Programador de Informática.

O objetivo principal do projeto é desenvolver um website escolar que permita melhorar o acesso à informação para alunos e professores, bem como simplificar a utilização das plataformas digitais usadas na escola. O sistema reúne num único ambiente diferentes funcionalidades — gestão de presenças, avaliações, sumários e comunicação — com ligações a serviços externos como SIGE, Inovar e Google Classroom.

Uma das principais características do sistema é a integração de um mecanismo de identificação por RFID, que reconhece alunos e professores através do cartão escolar. A leitura é realizada por um dispositivo Arduino R4 WiFi equipado com um leitor RFID RC522, que comunica com o servidor via rede Wi-Fi.

Quando um cartão é aproximado do leitor, o sistema envia o UID para uma API desenvolvida em PHP, que valida o utilizador na base de dados e atualiza automaticamente o estado de presença. O sistema distingue entrada de saída com base no estado anterior — cada leitura alterna o estado do utilizador.

O website é desenvolvido com HTML, CSS e JavaScript no frontend e PHP 8.3 no backend, com MySQL 9.1 como sistema de gestão de base de dados. O sistema está a ser desenvolvido em ambiente WAMP como protótipo funcional completo, pronto para migração para servidor de produção.

## 2. Contexto e Objetivos do Projeto

O projeto surge da necessidade de melhorar o acesso à informação escolar. Muitas plataformas utilizadas nas escolas apresentam informação dispersa e de difícil navegação. Durante a experiência como aluno, foi possível observar que o acesso a conteúdos como horários, plataformas externas e informações escolares nem sempre é intuitivo, especialmente para novos utilizadores.

O projeto tem como objetivo principal desenvolver um sistema centralizado que facilite o acesso à informação e integre um mecanismo de controlo de presenças por RFID.

**Objetivos principais:**

- Melhorar a acessibilidade à informação escolar
- Centralizar plataformas e serviços escolares
- Automatizar o registo de presença através de cartões RFID
- Gerir avaliações, sumários e agenda num único sistema
- Demonstrar integração entre hardware (Arduino) e software (PHP + MySQL)

## 3. Requisitos Funcionais e Não Funcionais

### 3.1 Gestão de Utilizadores

O sistema suporta três tipos de utilizadores com permissões distintas:

- Aluno
- Professor
- Administrador

Cada utilizador autentica-se com login e password (hash bcrypt). O acesso às funcionalidades é controlado por role verificado em sessão PHP.

#### 3.1.1 Funcionalidades do Aluno

O aluno pode:

- Consultar o perfil pessoal (nome, turma, número, idade)
- Ver o estado atual de presença
- Visualizar todas as notas por matéria, com médias e gráfico de progresso
- Registar e gerir tarefas na agenda pessoal (adicionar, concluir, eliminar)
- Consultar os testes marcados para a sua turma (próximos e passados)
- Ver o histórico de presenças recente com estatísticas
- Aceder a ligações úteis da escola (SIGE, Inovar, Google Classroom)

#### 3.1.2 Funcionalidades do Professor

O professor pode:

- Consultar o perfil pessoal (nome, cargo, gabinete, matéria, turma)
- Ver o estado atual de presença dos alunos da sua turma, atualizado em tempo real via polling AJAX a cada 5 segundos
- Registar avaliações para os alunos da sua turma (matéria, tipo de avaliação, nota de 0 a 20, data, observação)
- Escrever e consultar os sumários de aulas (diário de turma), incluindo justificação de faltas
- Marcar testes para a sua turma
- Gerir a agenda pessoal (tarefas e lembretes)

#### 3.1.3 Funcionalidades do Administrador

O administrador pode:

- Adicionar, editar e remover alunos
- Adicionar, editar e remover professores
- Associar, alterar e bloquear cartões RFID
- Gerir notícias publicadas na página principal
- Eliminar testes marcados (moderação)
- Visualizar logs do sistema com destaque por nível de severidade
- Aceder ao dashboard com estatísticas e gráfico de tendência de presenças

### 3.2 Requisitos Funcionais RFID

#### 3.2.1 Terminal de Presença (entrada principal)

Quando um cartão RFID é aproximado do leitor:

- O Arduino R4 WiFi lê o UID do cartão via MFRC522
- O UID é enviado via Wi-Fi para o endpoint `api/push.php`
- O backend procura o UID na tabela `login` (campo único)
- Se o cartão estiver bloqueado, devolve HTTP 403
- Se o UID não existir, devolve HTTP 404
- Se o utilizador for válido, alterna o estado de presença (entrada/saída)
- O evento é registado na tabela `presencas` com data, hora e device_id
- O backend devolve JSON com `estado: "entrada"` ou `estado: "saida"`
- O Arduino sinaliza o resultado através dos LEDs (verde/vermelho)

#### 3.2.2 Atualização do estado de presença

O estado de presença funciona com lógica de alternância:

- `0` — utilizador ausente (fora)
- `1` — utilizador presente (dentro)

Cada leitura do cartão inverte o estado atual, representando entrada e saída ao longo do dia. Cada evento é guardado com data e hora em `presencas`, permitindo histórico completo e estatísticas de assiduidade.

### 3.3 Requisitos Não Funcionais

#### 3.3.1 Desempenho

O sistema deve responder à leitura do cartão RFID em menos de 2 segundos. O polling AJAX de presenças atualiza a interface a cada 5 segundos sem recarregar a página.

#### 3.3.2 Usabilidade

A interface é simples, responsiva e adaptada a vários tamanhos de ecrã. A navegação é organizada por perfil de utilizador; cada tipo de utilizador vê apenas as funcionalidades relevantes para si.

#### 3.3.3 Segurança

O sistema implementa várias camadas de proteção:

- **Autenticação:** passwords armazenadas como hash bcrypt com `password_hash()` e verificadas com `password_verify()`
- **Regeneração de sessão:** `session_regenerate_id(true)` após login bem-sucedido, prevenindo session fixation
- **Autorização:** role do utilizador verificado no início de cada página; professor não pode aceder a dados de turmas que não sejam a sua
- **CSRF:** todos os formulários POST incluem um token de 64 caracteres gerado com `random_bytes(32)`, verificado com `hash_equals()` (resistente a timing attacks). O token circula via campo oculto `_csrf`, header `X-CSRF-Token` ou campo `_csrf` no corpo JSON
- **SQL Injection:** utilização sistemática de prepared statements (MySQLi e PDO) em todas as consultas envolvendo input do utilizador
- **XSS:** todos os dados apresentados ao utilizador passam por `htmlspecialchars()`
- **Sessões:** configuradas com `httponly=true` e `samesite=Strict`
- **Segredos:** API key do Arduino armazenada em `config/secrets.php`, excluído do controlo de versões

#### 3.3.4 Fiabilidade

O sistema regista logs JSON de todas as operações relevantes em ficheiros diários. O Arduino tenta reconectar-se automaticamente em caso de perda de Wi-Fi, sem necessitar de reinicialização.

#### 3.3.5 Portabilidade

O sistema foi desenvolvido em ambiente WAMP local e pode ser migrado para qualquer servidor com Apache/Nginx + PHP 8.x + MySQL 8+. Toda a configuração de ligação à base de dados está centralizada em `config/db.php`.

## 4. Visão Arquitetural Geral

O sistema é composto por quatro componentes principais:

- **Hardware de captura** — Arduino UNO R4 WiFi + módulo MFRC522 + 2 LEDs (verde/vermelho)
- **Servidor web** — Apache + PHP 8.3, expõe endpoints API em `api/` e páginas em `project/public/`
- **Base de dados** — MySQL 9.1 (motor InnoDB, charset utf8mb4)
- **Cliente web** — browser dos utilizadores (HTML + CSS + vanilla JavaScript)

O sistema usa o padrão PRG (Post-Redirect-Get) em toda a aplicação web para evitar submissões duplicadas de formulários.

## 5. Design de Dados

A base de dados `pap` contém 7 tabelas principais. Todas usam o motor InnoDB com charset `utf8mb4_unicode_ci`.

### 5.1 Tabela `login`

Armazena as credenciais e o cartão RFID de todos os utilizadores.

| Campo | Tipo | Descrição |
|---|---|---|
| Login | VARCHAR(50) | Chave primária — identificador único |
| Password | VARCHAR(255) | Hash bcrypt |
| UID | VARCHAR(32) NULL | UID do cartão RFID |
| Role | ENUM('Aluno','Professor','admin') | Papel do utilizador |
| blocked | TINYINT(1) | 1 = cartão bloqueado, 0 = ativo |

### 5.2 Tabela `alunos`

| Campo | Tipo | Descrição |
|---|---|---|
| ID | INT AUTO_INC | Chave primária |
| Nome | VARCHAR(100) | Nome completo |
| Idade | INT NULL | Idade |

| turma_num | TINYINT NULL | Ano da turma (10, 11 ou 12) |
| turma_letra | CHAR(1) NULL | Letra da turma (A, B, C) |
| Número em turma | INT NULL | Número do aluno na turma |
| Presença | TINYINT(1) | Estado atual (0=ausente/1=presente) |
| login | VARCHAR(50) UNIQUE | Referência para tabela `login` |

### 5.3 Tabela `professores`

| Campo | Tipo | Descrição |
|---|---|---|
| ID | INT AUTO_INC | Chave primária |
| Nome | VARCHAR(100) | Nome completo |
| Cargo (posição) | VARCHAR(100) | Cargo do professor |
| Gabinete | VARCHAR(30) | Número do gabinete |
| Presença | TINYINT(1) | Estado atual de presença |
| Matéria ensinada | VARCHAR(100) | Disciplina lecionada |
| turma | VARCHAR(10) | Turma atribuída (legado) |
| turma_num | TINYINT | Ano da turma |
| turma_letra | CHAR(1) | Letra da turma |
| login | VARCHAR(50) UNIQUE | Referência para tabela `login` |

### 5.4 Tabela `presencas`

Histórico completo de todas as entradas e saídas registadas por RFID.

| Campo | Tipo | Descrição |
|---|---|---|
| id | INT AUTO_INC | Chave primária |
| login | VARCHAR(50) | Utilizador que registou presença |
| nome | VARCHAR(100) | Nome denormalizado (snapshot no momento da leitura) |
| person_type | ENUM('Aluno','Professor') | Classe do utilizador |
| uid | VARCHAR(32) | UID lido no momento |
| data | DATE | Data do evento |
| hora | TIME | Hora do evento |
| presenca | TINYINT(1) | 1 = entrada, 0 = saída |

### 5.5 Tabela `testes`

Marcações de testes / avaliações feitas pelo professor e visíveis aos alunos da turma-alvo.

| Campo | Tipo | Descrição |
|---|---|---|
| id | INT AUTO_INC | Chave primária |
| titulo | VARCHAR(200) | Título do teste |
| descricao | TEXT NULL | Descrição livre |
| data_teste | DATE | Data prevista |
| turma_num | TINYINT | Ano da turma-alvo |
| turma_letra | CHAR(1) | Letra da turma-alvo |
| professor_login | VARCHAR(50) | Login do professor |
| materia | VARCHAR(100) | Matéria (preenchida automaticamente) |
| criado_em | DATETIME | Timestamp de criação |

### 5.6 Tabela `notas`

Avaliações dos alunos na escala 0–20 (sistema português).

| Campo | Tipo | Descrição |
|---|---|---|
| id | INT AUTO_INC | Chave primária |
| login_aluno | VARCHAR(50) | Aluno avaliado |
| materia | VARCHAR(100) | Disciplina |
| tipo | VARCHAR(50) | Teste / Mini-teste / Projeto / Oral |
| valor | DECIMAL(4,1) | Nota de 0.0 a 20.0 |
| data | DATE | Data da avaliação |
| professor_login | VARCHAR(50) | Professor que lançou |
| observacao | VARCHAR(200) | Observação opcional |

### 5.7 Tabela `sumarios`

Diário de aulas — registo do conteúdo lecionado por cada professor, com possibilidade de justificar faltas no texto.

| Campo | Tipo | Descrição |
|---|---|---|
| id | INT AUTO_INC | Chave primária |
| professor_login | VARCHAR(50) | Professor que registou |
| turma_num | TINYINT | Ano da turma |
| turma_letra | CHAR(1) | Letra da turma |
| data | DATE | Data da aula |
| materia | VARCHAR(100) | Disciplina |
| descricao | TEXT | Conteúdo lecionado + justificações de faltas |
| criado_em | DATETIME | Timestamp de criação |

### 5.8 Tabela `agenda`

Agenda pessoal de tarefas e lembretes, partilhada entre alunos e professores.

| Campo | Tipo | Descrição |
|---|---|---|
| id | INT AUTO_INC | Chave primária |
| login | VARCHAR(50) | Utilizador dono da tarefa |
| titulo | VARCHAR(200) | Descrição da tarefa |
| data | DATE NULL | Data prevista (opcional) |
| concluido | TINYINT(1) | 0 = pendente, 1 = concluída |
| criado_em | DATETIME | Timestamp de criação |

### 5.9 Tabela `noticias`

Notícias geridas pelo administrador, apresentadas na página principal.

| Campo | Tipo | Descrição |
|---|---|---|
| id | INT AUTO_INC | Chave primária |
| titulo | VARCHAR(200) | Título da notícia |
| corpo | TEXT | Conteúdo da notícia |
| imagem | VARCHAR(300) NULL | Nome do ficheiro em `assets/` |
| criado_em | DATETIME | Timestamp de criação |
| ativo | TINYINT(1) | 1 = visível, 0 = oculta |

## 6. Design da Interface (UI/UX)

### 6.1 Estrutura Geral

A interface é composta por um cabeçalho fixo com logótipo, navegação e botão de sessão, e uma área de conteúdo principal. O design é responsivo e adaptado a ecrãs de secretária e telemóvel.

As cores principais são azul escuro (`#0b1b3a`) e branco, com acentos verdes (`#16a34a`) para presença/sucesso e vermelhos (`#dc2626`) para ausência/erro.

### 6.2 Página Principal

A página principal é o ponto de entrada do sistema. Apresenta:

- Saudação temporal ("Bom dia / Boa tarde / Boa noite")
- Carrossel de notícias da escola (geridas pelo administrador)
- Ligações para plataformas externas (SIGE, Inovar, Google Classroom)
- Botão de login no cabeçalho

### 6.3 Área do Aluno

Organizada em secções verticais:

- **Perfil** — nome, turma, número, badge de presença
- **Avaliações** — cartões de médias por matéria + gráfico de barras + tabela completa de notas com badges coloridos (verde ≥14, amarelo ≥10, vermelho <10)
- **Agenda** — checklist com adição, toggle de conclusão e eliminação
- **Testes marcados** — próximos e passados, separados em duas listas
- **Assiduidade** — gráfico doughnut com percentagem de presença
- **Ligações úteis** — SIGE, Inovar, Google Classroom

### 6.4 Área do Professor

- **Perfil** — dados profissionais
- **Presenças em tempo real** — tabela com AJAX polling a cada 5 segundos
- **Avaliações** — formulário de lançamento + tabela de notas com eliminar
- **Sumários** — formulário + lista cronológica (inclui justificação de faltas no texto)
- **Testes** — formulário de marcação
- **Agenda** — checklist pessoal

### 6.5 Área de Administração

Painel com separadores:

- **Charts** — cartões de estatísticas + gráfico de tendência 7d + últimas 10 leituras
- **Cards** — associação de UID e toggle de bloqueio de cartão
- **Alunos** — CRUD com sub-separadores (Lista, Testes)
- **Professores** — CRUD
- **Notícias** — gestão do conteúdo da página principal
- **Logs** — visualizador de logs com destaque por nível

## 7. Diagramas

### 7.1 Diagrama de Casos de Uso

**Aluno:**
- Autenticar no sistema
- Consultar perfil
- Ver avaliações e médias
- Gerir agenda pessoal
- Consultar testes marcados
- Ver histórico de presenças

**Professor:**
- Autenticar no sistema
- Ver presenças em tempo real
- Lançar avaliações
- Escrever sumários de aulas (com justificação de faltas)
- Marcar testes
- Gerir agenda pessoal

**Administrador:**
- Autenticar no sistema
- CRUD de alunos e professores
- Gerir cartões RFID (bloquear/desbloquear)
- Publicar e gerir notícias
- Visualizar logs do sistema

**Arduino (ator externo):**
- Ler UID de cartão RFID
- Enviar pedido de presença (`push.php`)
- Receber e interpretar resposta JSON
- Sinalizar resultado via LEDs (verde / vermelho)

### 7.2 Diagrama de Sequência — Fluxo de Presença RFID

(diagrama em anexo)

### 7.3 Diagrama de Deployment

(diagrama em anexo)

## 8. Testes

### 8.1 Teste do Sistema RFID

Foram realizados testes de leitura de diferentes cartões RFID para verificar a correta captura do UID pelo módulo RC522 e o seu envio ao servidor. Os testes confirmaram que o sistema identifica corretamente os cartões e comunica com o backend sem erros.

Foram testados os seguintes cenários:

- Cartão registado e ativo → entrada registada, LED verde
- Segunda leitura do mesmo cartão → saída registada, LED verde
- Cartão bloqueado pelo administrador → HTTP 403, LED vermelho
- Cartão não registado na base de dados → HTTP 404, LED vermelho
- Sem ligação Wi-Fi → tentativa de reconexão automática, LED vermelho

### 8.2 Teste da Comunicação Arduino–Servidor

Foi testada a comunicação HTTP entre o Arduino e o servidor PHP. Foram verificados o envio correto dos campos `uid`, `device_id` e `key`, bem como a receção e interpretação da resposta JSON pelo Arduino sem recurso a bibliotecas externas de parsing.

### 8.3 Teste da Base de Dados

Foram testados os seguintes cenários:

- Associação correta entre UID e utilizador
- Alternância do campo `Presença` em cada leitura
- Inserção de registos em `presencas` com data e hora corretas
- Transação atómica na eliminação de aluno (3 tabelas em simultâneo)

### 8.4 Teste da Interface Web

Foram testadas todas as páginas do sistema para cada perfil de utilizador:

- Fluxo de autenticação: login correto, password errada, logout
- Página do aluno: visualização de notas, agenda, testes, histórico
- Página do professor: lançamento de notas, sumários, marcação de testes
- Painel de administração: CRUD de alunos e professores, gestão de cartões, notícias
- Segurança: verificação de que um professor não consegue aceder a dados de alunos de outras turmas
- Proteção CSRF: submissão de formulários sem token resulta em rejeição HTTP 403

## 9. Segurança

### 9.1 Autenticação

As passwords dos utilizadores são armazenadas exclusivamente como hash bcrypt, gerado com `password_hash()` em PHP. A verificação no login é feita com `password_verify()`, que é resistente a timing attacks. Após login bem-sucedido é chamada `session_regenerate_id(true)` para prevenir ataques de session fixation.

Nunca são guardadas passwords em texto simples na base de dados.

### 9.2 Proteção CSRF

Todos os formulários POST da aplicação são protegidos por um token de 64 caracteres hexadecimais, gerado com `random_bytes(32)` e armazenado na sessão. O servidor verifica o token com `hash_equals()` antes de processar qualquer pedido POST. Pedidos sem token válido são rejeitados com HTTP 403.

O token circula por três vias aceites pelo servidor:
- Campo oculto `_csrf` em formulários HTML
- Header `X-CSRF-Token` em pedidos `fetch()` com `FormData`
- Campo `_csrf` no corpo JSON em pedidos com `Content-Type: application/json`

A função `csrf_check()` em `config/session.php` verifica as três fontes.

### 9.3 Prevenção de SQL Injection

Toda a aplicação usa prepared statements com parâmetros vinculados (MySQLi procedural para os módulos CRUD e PDO para as operações com transações). Nunca é concatenado input do utilizador diretamente em queries SQL.

### 9.4 Prevenção de XSS

Todos os valores apresentados ao utilizador no HTML passam pela função `htmlspecialchars()`.

### 9.5 Controlo de Acesso

Cada página verifica o role do utilizador em sessão logo no início. O professor está restrito aos alunos da sua própria turma — esta verificação é feita tanto no GET (ao carregar a página) como no POST (ao submeter formulários). O administrador tem acesso total.

### 9.6 Segurança das Sessões

As sessões PHP são configuradas com:

- `httponly = true` — o cookie de sessão não é acessível a JavaScript
- `samesite = Strict` — previne envio do cookie em pedidos cross-site
- `session_regenerate_id(true)` no login — previne session fixation
- `session_destroy()` no logout — elimina todos os dados de sessão
- Timeout de inatividade de 30 minutos

### 9.7 Proteção da API RFID

O endpoint `api/push.php` está protegido por uma API key partilhada entre o Arduino e o servidor. A chave é armazenada em `config/secrets.php` (excluído do Git) e comparada em tempo constante através de `hash_equals()`. Pedidos sem a chave correta são rejeitados com HTTP 401. Os campos sensíveis (como a API key) são automaticamente mascarados nos logs pelo módulo `logger.php`.

### 9.8 Estrutura dos Logs

O sistema regista eventos em ficheiros JSON diários (`logs/app-YYYY-MM-DD.log`), localizados fora do diretório `public/` e portanto inacessíveis via browser.

Cada entrada inclui:

- `time` — data e hora do evento
- `level` — INFO / WARN / ERROR / ADMIN_ACTION
- `ip` — endereço IP do pedido
- `uri` — endpoint chamado
- `msg` — mensagem descritiva
- `ctx` — contexto adicional (uid, login, resultado, etc.)

## 10. Implementação

### 10.1 Endpoint de Presença (api/push.php)

O endpoint executa a seguinte lógica:

1. Recebe `uid`, `key` e `device_id` via HTTP POST
2. Verifica a API key com `hash_equals()` — rejeita com HTTP 401 se inválida
3. Normaliza o UID (maiúsculas, sem espaços)
4. Valida o nome da tabela-alvo contra uma whitelist (`alunos`, `professores`)
5. Procura o UID na tabela `login`
6. Verifica se o cartão está bloqueado — rejeita com HTTP 403 se estiver
7. Conforme o Role, acede a `alunos` ou `professores`
8. Inverte o campo `Presença` (toggle)
9. Insere registo em `presencas` com data, hora e device_id
10. Devolve JSON com `{ok: true, estado: "entrada"|"saida", nome, turma}`

### 10.2 Endpoint AJAX de Presenças (api/presence.php)

Endpoint GET que recebe o parâmetro `turma` e devolve o estado atual de presença de todos os alunos dessa turma. Usado pelo frontend para atualização automática a cada 5 segundos sem recarregar a página. Só acessível a utilizadores com role Professor ou admin.

### 10.3 Padrão PRG (Post-Redirect-Get)

As páginas de autenticação usam o padrão PRG: os formulários POST processam a ação e fazem imediatamente `header('Location: ...')`. Isto impede que o utilizador resubmeta acidentalmente um formulário ao recarregar a página.

### 10.4 Firmware Arduino

O firmware lê o UID do cartão RFID e envia-o ao servidor. Distingue os seguintes resultados através de dois LEDs (verde e vermelho):

| Situação | Sinal LED |
|---|---|
| Entrada / Saída registada (HTTP 200) | LED verde aceso ~800 ms |
| Cartão bloqueado (HTTP 403) | LED vermelho aceso ~800 ms |
| UID não encontrado (HTTP 404) | LED vermelho aceso ~800 ms |
| Erro de API key (HTTP 401) | LED vermelho aceso ~800 ms |
| Sem ligação Wi-Fi | LED vermelho aceso ~800 ms |

O dispositivo usa o MAC address como `device_id`, permitindo identificar qual terminal registou cada presença.

## 11. Deploy e Ambiente

O sistema foi desenvolvido e testado em ambiente local utilizando WAMP (Windows + Apache + MySQL + PHP), que inclui servidor Apache, base de dados MySQL 9.1 e PHP 8.3.

**Para instalar o sistema:**

1. Copiar a pasta do projeto para `C:\wamp64\www\PAP\`
2. Executar `db/schema_full.sql` no phpMyAdmin para criar as tabelas
3. Executar `db/seed_data.sql` para carregar dados de teste
4. Criar o ficheiro `config/secrets.php` com a API key do Arduino (fora do controlo de versões)
5. Aceder em `http://localhost/PAP/project/public/index.php`

**Para o Arduino:**

1. Abrir `arduino/PAP_RFID/PAP_RFID.ino` no Arduino IDE 2.x
2. Configurar SSID, password Wi-Fi e IP do servidor
3. Selecionar placa Arduino UNO R4 WiFi
4. Instalar bibliotecas: MFRC522, WiFiS3, ArduinoHttpClient
5. Carregar o sketch

Em contexto de produção, o sistema pode ser migrado para um servidor Apache/Nginx com PHP 8.x e MySQL 8+ sem alterações no código.

## 12. Logging e Monitorização

O sistema possui logging em duas camadas independentes.

### 12.1 Logging no Arduino

O Arduino apresenta informação em tempo real no Serial Monitor:

- Deteção de cartão e UID lido
- Estado da ligação Wi-Fi
- Código HTTP e resposta do servidor
- Resultado interpretado ("ENTRADA registada", "SAIDA registada", etc.)

### 12.2 Logging no Backend PHP

O módulo `api/lib/logger.php` escreve entradas JSON em ficheiros diários em `logs/app-YYYY-MM-DD.log`. Os ficheiros de log ficam fora do diretório público, não sendo acessíveis via browser.

São registados: pedidos RFID recebidos, correspondências de utilizador, tentativas de autenticação, ações administrativas (`ADMIN_ACTION`), erros e avisos. Campos sensíveis como a API key e a password são automaticamente mascarados.

O administrador pode consultar os logs diretamente no painel de administração, com destaque visual por nível de severidade.

## 13. Manutenção e Evolução

O sistema foi desenvolvido com uma arquitectura modular que facilita a manutenção e a adição de novas funcionalidades. O código está organizado de forma clara, com separação entre configuração, lógica de negócio e apresentação.

**Possíveis melhorias futuras (fora do âmbito da PAP):**

- **Gestão de refeições escolares** — marcação de almoços pelos alunos para os próximos dias úteis, com painel de consulta agregada no administrador. Funcionalidade fora do âmbito do presente projeto por envolver processos administrativos da escola que não competem ao autor.
- **Gestão de horário pela interface web** — permitir que professores adicionem e removam aulas diretamente pela aplicação, e que alunos consultem o horário semanal da sua turma. Implicaria uma tabela dedicada `horario` e interfaces específicas.
- **Perfil individual do aluno para o professor** — vista detalhada com histórico de assiduidade, avaliações e notas do aluno, acessível ao professor responsável.
- **Exportação PDF de relatórios de presenças** por turma ou por aluno.
- **Notificações** — envio de alertas aos encarregados de educação por e-mail ou SMS em caso de ausência não justificada.
- **Aplicação móvel** — interface nativa para iOS e Android.
- **Integração com plataformas externas** — sincronização automática com SIGE ou Inovar.
- **Múltiplos terminais RFID** — suporte a vários dispositivos Arduino em diferentes entradas do edifício.
- **Painel de encarregado de educação** — acesso restrito para consulta da presença e avaliações do educando.

## 14. Conclusão

O projeto desenvolvido resultou num sistema funcional completo que integra um website escolar com tecnologia RFID. A solução cobre o núcleo central de gestão de uma turma: controlo de presenças automático, lançamento de avaliações, diário de aulas (sumários), marcação de testes, agenda pessoal, gestão de notícias e visualização de estatísticas de assiduidade.

A integração entre hardware e software foi concretizada com sucesso: o dispositivo Arduino R4 WiFi lê cartões RFID e comunica com o servidor PHP em tempo real, distinguindo visualmente diferentes situações através de dois LEDs.

Do ponto de vista técnico, o projeto aplica boas práticas de segurança (bcrypt, CSRF, prepared statements, controlo de acesso por role, regeneração de sessão, externalização de segredos) e padrões de desenvolvimento web (PRG, separação de responsabilidades, logging estruturado).

O sistema está pronto para uso em ambiente escolar real e apresenta uma base sólida para evolução futura, nomeadamente nas áreas de gestão de refeições, horário interativo e perfil individual do aluno — descritas no capítulo 13.
