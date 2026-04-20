# Capa

---

**AGRUPAMENTO DE ESCOLAS AEMTG**

**Curso Profissional de Técnico de Gestão e Programação de Sistemas Informáticos**

---

## Prova de Aptidão Profissional

### Website da escola com acesso através da web ou leitura RFID do cartão escolar

---

**Autor:** Marko Nikolaienko
**Nº Processo:** a32498
**Turma:** 12.º C

**Orientadora:** Professora Carla de Sousa

**Ano letivo:** 2025 / 2026

---

# Resumo

Este relatório descreve a conceção, o desenvolvimento e a entrega de um sistema *web* para a comunidade do Agrupamento de Escolas AEMTG. O objetivo é simples: tornar o acesso à informação escolar mais direto e automatizar o registo de presenças através do cartão RFID dos alunos e professores.

O sistema junta três componentes. Primeiro, uma aplicação *web* em PHP e MySQL com interfaces distintas para aluno, professor e administrador. Segundo, um leitor físico com *Arduino UNO R4 WiFi* e módulo *MFRC522*, que envia o UID do cartão para o servidor por HTTP. Terceiro, uma base de dados relacional normalizada onde ficam guardadas as pessoas, os cartões e o histórico de assiduidade.

Do lado funcional, a plataforma permite autenticação clássica (*login* e *password*) ou por leitura do cartão, regista entradas e saídas automaticamente, deixa o professor marcar testes, mostra-os ao aluno, e dá ao administrador ferramentas para gerir cartões (inclusive bloqueá-los em caso de perda) e ver estatísticas de presenças.

A motivação é pessoal. Como aluno estrangeiro, tive dificuldades a usar o *website* da escola quando aqui cheguei. Este projeto é a minha tentativa de deixar uma ferramenta mais acessível e integrável com o que já existe.

**Palavras-chave:** PHP; MySQL; Arduino; RFID; aplicação *web*; gestão escolar; assiduidade.

---

# Agradecimentos

À Professora Carla de Sousa, orientadora do projeto, pelo acompanhamento e pela exigência ao longo do ano letivo.

A todos os docentes do curso TGPSI, que me prepararam para chegar a esta fase.

À minha família, pelo apoio durante as longas horas de desenvolvimento.

Aos colegas de turma, pelas trocas de ideias que ajudaram a melhorar o trabalho.

---

# Índice

1. Introdução
   1.1. Contextualização
   1.2. Motivação
   1.3. Objetivos
   1.4. Estrutura do documento
   1.5. Apresentação do Projeto
2. Enquadramento Teórico
   2.1. Identificação por Radiofrequência (RFID)
   2.2. Arduino e o módulo MFRC522
   2.3. Arquitetura cliente-servidor e o protocolo HTTP
   2.4. Tecnologias *web*: HTML, CSS e JavaScript
   2.5. Linguagem PHP
   2.6. Base de dados MySQL
   2.7. Padrões de autenticação e sessões
3. Metodologia
   3.1. Processo de escrita e desenvolvimento
   3.2. Fases do projeto
   3.3. Ferramentas utilizadas
   3.4. Qualidade académica e rigor
4. Análise de Requisitos
   4.1. Requisitos funcionais
   4.2. Requisitos não funcionais
   4.3. Atores do sistema
   4.4. Casos de uso
5. Arquitetura do Sistema
   5.1. Visão geral
   5.2. Diagrama de componentes
   5.3. Fluxo de dados
   5.4. Organização de diretórios
6. Base de Dados
   6.1. Modelo conceptual
   6.2. Modelo lógico
   6.3. Descrição das tabelas
   6.4. Índices e otimizações
7. Implementação
   7.1. Autenticação e gestão de sessões
   7.2. Módulo RFID — firmware Arduino
   7.3. *Endpoint* `push.php` — receção das leituras RFID
   7.4. Painel de administração
   7.5. Página do aluno
   7.6. Página do professor
   7.7. Registo de eventos (*logging*)
8. Testes e Validação
   8.1. Testes unitários
   8.2. Testes de integração
   8.3. Testes de aceitação
   8.4. Métricas recolhidas
9. Conclusões
   9.1. Síntese do trabalho realizado
   9.2. Limitações
   9.3. Trabalho futuro
   9.4. Balanço pessoal
10. Bibliografia
11. Anexos
    Anexo A. Excertos de código
    Anexo B. Diagrama entidade-relação
    Anexo C. Fotografias do *hardware*
    Anexo D. Capturas de ecrã

---

# Lista de Figuras

- Figura 1 — Logótipo do Agrupamento de Escolas AEMTG
- Figura 2 — Cartão escolar com *chip* RFID
- Figura 3 — Placa Arduino UNO R4 WiFi
- Figura 4 — Módulo MFRC522
- Figura 5 — Diagrama de arquitetura geral do sistema
- Figura 6 — Diagrama de sequência da leitura de um cartão
- Figura 7 — Diagrama *Entidade-Relação* da base de dados
- Figura 8 — Estrutura de diretórios do projeto
- Figura 9 — Página inicial (*index*)
- Figura 10 — Ecrã de autenticação
- Figura 11 — Painel do administrador: separador *Charts*
- Figura 12 — Painel do administrador: separador *Cards*
- Figura 13 — Painel do administrador: separador *Alunos*
- Figura 14 — Dossier individual de um aluno
- Figura 15 — Separador *Logs* com o visualizador NDJSON
- Figura 16 — Página do professor
- Figura 17 — Formulário de marcação de teste
- Figura 18 — Página do aluno com testes marcados
- Figura 19 — Circuito eletrónico do leitor RFID
- Figura 20 — Resultado do teste de deteção de plágio

# Lista de Tabelas

- Tabela 1 — Requisitos funcionais
- Tabela 2 — Requisitos não funcionais
- Tabela 3 — Atores do sistema
- Tabela 4 — Casos de uso principais
- Tabela 5 — Estrutura da tabela `login`
- Tabela 6 — Estrutura da tabela `alunos`
- Tabela 7 — Estrutura da tabela `professores`
- Tabela 8 — Estrutura da tabela `presencas`
- Tabela 9 — Estrutura da tabela `testes`
- Tabela 10 — Índices criados
- Tabela 11 — Plano de testes funcionais
- Tabela 12 — Resultados dos testes

# Lista de Abreviaturas e Siglas

- **AEMTG** — Agrupamento de Escolas [nome completo a confirmar]
- **AJAX** — *Asynchronous JavaScript and XML*
- **API** — *Application Programming Interface*
- **CRUD** — *Create, Read, Update, Delete*
- **CSS** — *Cascading Style Sheets*
- **ER** — *Entidade-Relação*
- **HTML** — *HyperText Markup Language*
- **HTTP** — *HyperText Transfer Protocol*
- **IDE** — *Integrated Development Environment*
- **JSON** — *JavaScript Object Notation*
- **MVC** — *Model-View-Controller*
- **MySQL** — *My Structured Query Language*
- **NDJSON** — *Newline-Delimited JSON*
- **PAP** — Prova de Aptidão Profissional
- **PHP** — *PHP: Hypertext Preprocessor*
- **RFID** — *Radio-Frequency IDentification*
- **SDG** — Specification Design Guide (Documento de Especificação Técnica)
- **SGBD** — Sistema de Gestão de Bases de Dados
- **SPA** — *Single-Page Application*
- **SQL** — *Structured Query Language*
- **TGPSI** — Técnico de Gestão e Programação de Sistemas Informáticos
- **UID** — *Unique IDentifier*
- **URL** — *Uniform Resource Locator*
- **WAMP** — *Windows, Apache, MySQL, PHP*

---

# 1. Introdução

## 1.1. Contextualização

Este relatório surge na Prova de Aptidão Profissional (PAP), o trabalho final do Curso Profissional de Técnico de Gestão e Programação de Sistemas Informáticos, aqui no Agrupamento de Escolas AEMTG. A PAP é o momento em que o aluno junta, num único projeto, o que aprendeu ao longo dos três anos — programação, bases de dados, redes, sistemas, desenvolvimento *web* e sistemas embebidos.

As escolas hoje usam várias plataformas digitais ao mesmo tempo: SIGE, Inovar, Google Classroom, *sites* próprios. Cada uma resolve um pedaço do problema, mas para o utilizador — sobretudo para quem acaba de chegar e ainda não domina bem a língua — isto dá uma experiência fragmentada.

Ao mesmo tempo, o registo de presenças continua a ser, em muitas salas, feito à mão pelo professor. Consome tempo de aula e deixa margem para erros. A tecnologia RFID, já usada há anos em acessos de empresas e em bilhética de transportes, resolve bem este tipo de problema: é rápida, é fiável e é barata.

O projeto **Website da escola com acesso através da web ou leitura RFID do cartão escolar** nasce nesta interseção.

## 1.2. Motivação

A motivação é pessoal. Entrei na escola como aluno estrangeiro e senti na pele a barreira linguística e a dificuldade de perceber como a instituição funciona. O *website* existente, pensado para quem já conhece a escola, não me ajudou muito nessa fase.

A partir dessa experiência formei três convicções que orientaram o projeto:

1. **Uma plataforma escolar tem de ser acessível a todos.** Clareza visual, navegação consistente e elementos gráficos que se expliquem sozinhos não são extras — são requisitos.

2. **Automatizar tarefas administrativas liberta tempo de aula**, reduz erros e produz dados agregados úteis (padrões de absentismo, horas de maior afluência).

3. **O projeto final do curso deve integrar as várias disciplinas** e provar, de forma concreta, que as competências foram adquiridas.

Há também uma dimensão de continuidade: um sistema feito na própria escola pode ser mantido e evoluído por futuros alunos do curso, o que raramente acontece com ferramentas compradas a fornecedores externos.

## 1.3. Objetivos

Os objetivos foram fixados logo no início do ano letivo.

**Objetivo geral:**

Construir uma plataforma *web* completa, com um leitor físico de cartões RFID, que permita à comunidade escolar aceder à informação institucional e registar presenças automaticamente, usando os recursos disponíveis no Agrupamento.

**Objetivos específicos:**

- **OE1.** Criar uma aplicação *web* responsiva, com três perfis distintos: aluno, professor e administrador.
- **OE2.** Implementar autenticação dupla — *login*/*password* e leitura do cartão RFID — com atenção básica a segurança.
- **OE3.** Desenvolver o leitor RFID com Arduino UNO R4 WiFi e módulo MFRC522, comunicando com o servidor por HTTP.
- **OE4.** Desenhar uma base de dados relacional normalizada para utilizadores, cartões, presenças e atividades pedagógicas.
- **OE5.** Construir um painel de administração para gerir utilizadores, cartões e ver estatísticas.
- **OE6.** Incluir uma funcionalidade pedagógica demonstrativa (marcação de testes pelo professor, consulta pelo aluno) que mostre a extensibilidade do sistema.
- **OE7.** Registar eventos (*logging*) em formato estruturado para auditar e diagnosticar problemas.
- **OE8.** Fazer uma interface intuitiva, coerente e utilizável por quem não tem formação técnica.

## 1.4. Estrutura do documento

O relatório divide-se em onze capítulos.

O **Capítulo 1** é esta introdução: contexto, motivação e objetivos.

O **Capítulo 2** (*Enquadramento Teórico*) cobre as tecnologias de base: RFID, Arduino, HTTP, PHP, MySQL e autenticação.

O **Capítulo 3** (*Metodologia*) explica como conduzi o projeto: fases, ferramentas, critérios de qualidade.

O **Capítulo 4** (*Análise de Requisitos*) formaliza o que o sistema faz, separa requisitos funcionais e não funcionais, e lista atores e casos de uso.

O **Capítulo 5** (*Arquitetura do Sistema*) mostra a visão global — componentes, fluxos, estrutura de diretórios.

O **Capítulo 6** (*Base de Dados*) descreve o modelo de dados, as opções de normalização e o esquema ER.

O **Capítulo 7** (*Implementação*) é o mais extenso. Descreve, módulo a módulo, como cada funcionalidade foi construída, com excertos de código.

O **Capítulo 8** (*Testes e Validação*) cobre as estratégias de teste e os resultados.

O **Capítulo 9** (*Conclusões*) faz o balanço, aponta limitações e sugere trabalho futuro.

A **Bibliografia** (Capítulo 10) e os **Anexos** (Capítulo 11) encerram o documento.

## 1.5. Apresentação do Projeto

Esta secção reproduz a proposta inicial do projeto, tal como foi submetida e aprovada no arranque da PAP. Serve de referência para comparar o que foi prometido com o que foi efetivamente implementado e documentado nos capítulos seguintes.

### 1.5.1. Identificação

- **Aluno:** Marko Nikolaienko
- **Nº de Processo:** a32498
- **Turma:** 12.º C
- **Título do projeto:** *Website da escola com acesso através da web ou leitura RFID do cartão escolar*
- **Orientação:** Professora Carla de Sousa

### 1.5.2. Descrição geral

O projeto proposto como PAP tem como objetivo principal criar acessibilidade e usabilidade para alunos e professores. Foi desenvolvido um *website* escolar ao qual se pode aceder pela *web* ou pela leitura do cartão escolar com recurso a RFID. A plataforma permite a criação autónoma de perfis de acesso, com permissões adequadas a cada tipo de utilizador. Para além da gestão de perfis, previu-se a integração — total ou parcial — com as restantes plataformas digitais utilizadas no agrupamento (SIGE, Inovar e Classroom).

### 1.5.3. Perfis previstos

**Perfil de Aluno.** Acesso às notícias escolares, aos eventos futuros e passados e às plataformas educativas agregadas à escola (SIGE, Classroom e Inovar). No próprio perfil, o aluno pode consultar o seu horário, a agenda escolar, as avaliações, a marcação de refeições, o saldo do cartão e o gráfico de progresso avaliativo.

**Perfil de Professor.** Autonomia para gerir o próprio perfil: inserir os horários das turmas que leciona e o seu horário pessoal, registar os perfis dos alunos, anexar documentação agregada a cargos de direção de curso e de turma, manter o cronograma de aulas e os sumários, e acompanhar o progresso de aprendizagem e a assiduidade dos alunos, bem como a sua própria agenda.

### 1.5.4. Desenvolvimento do projeto

O *front-end* é desenvolvido em **HTML, CSS e JavaScript**. No *back-end* é utilizada a linguagem **PHP**, com base de dados em **MySQL** (SGBD relacional, SQL) e integração com um **Arduino** equipado com módulo RFID MFRC522 para leitura do cartão escolar.

### 1.5.5. Objetivos declarados

- Contribuir para a comunidade escolar com uma nova ferramenta.
- Implementar acessibilidade e usabilidade, com particular atenção aos alunos estrangeiros.
- Dar apoio a alunos e professores.
- Aplicar as aprendizagens desenvolvidas no curso profissional de Programação Informática.
- Criar uma ferramenta acessível e de fácil utilização para qualquer aluno.

### 1.5.6. Recursos necessários

**Recursos humanos:**

- Tempo pessoal do aluno.
- Orientação dada pela professora Carla de Sousa.

**Recursos tecnológicos:**

- Computador e acesso à Internet.
- *Arduino* e módulo RFID (MFRC522).
- Documentação e manuais de SQL e PHP.
- Software de desenvolvimento (editor de código, servidor local *WAMP*, SGBD, controlo de versões).

> **Nota.** A proposta foi assinada a 17 de outubro de 2025. Os capítulos seguintes documentam em detalhe como cada um destes objetivos foi concretizado; as limitações e os pontos deixados para trabalho futuro são discutidos no *Capítulo 9 (Conclusões)*.

---

# 2. Enquadramento Teórico

Este capítulo enquadra as tecnologias e conceitos que estão por trás do projeto. O sistema assenta em camadas muito diferentes — *hardware* embebido, protocolos de comunicação, linguagens servidor e cliente, e um SGBD relacional — e cada uma tem a sua lógica própria. Para justificar as decisões de arquitetura, é preciso perceber o essencial de cada camada.

A abordagem é pragmática: em vez de esgotar cada tecnologia, concentro-me no que foi usado no projeto, tal como o SDG propõe nas secções 2 (*Fundamentação Teórica*) e 3 (*Especificação Técnica*).

## 2.1. Identificação por Radiofrequência (RFID)

*Radio-Frequency IDentification*, ou RFID, é uma tecnologia de identificação automática que usa ondas eletromagnéticas para ler, à distância curta, um identificador único guardado num transponder — o *tag* — através de um dispositivo leitor [1]. As primeiras aplicações foram militares, durante a Segunda Guerra Mundial, nos sistemas *Identification, Friend or Foe* da *Royal Air Force*. O uso civil disparou a partir dos anos 90 e hoje está em quase todo o lado: logística, controlo de acessos, bilhética, pagamentos por aproximação.

Um sistema RFID tem três peças: o *tag*, que guarda a informação; o leitor, com antena emissora-recetora; e o sistema informático que processa os dados. Os *tags* podem ser passivos — alimentados pelo campo do próprio leitor — ou ativos, com bateria. Este projeto usa apenas *tags* passivos: os cartões escolares dos alunos e professores.

As frequências variam conforme o caso: LF (*Low Frequency*, 125–134 kHz), HF (*High Frequency*, 13,56 MHz) e UHF (*Ultra High Frequency*, 860–960 MHz). Os cartões escolares trabalham em HF a 13,56 MHz, segundo a norma ISO/IEC 14443 (tipo A) [3] — a mesma dos passes de transporte público e do cartão de cidadão. Esta banda equilibra alcance (1 a 10 cm, o que obriga a uma aproximação voluntária) e robustez a interferências, dois critérios referidos no SDG, secção 3.2 (*Requisitos Funcionais RFID*).

Cada cartão tem um UID gravado de fábrica, imutável, normalmente em hexadecimal com 4 a 10 bytes. É a chave que o sistema usa para ligar o cartão a um registo na base de dados. Mais nada é lido do cartão — nome, turma, fotografia, tudo isso vive apenas no servidor. Perder o cartão não expõe dados pessoais, o que é importante para o RGPD.

## 2.2. Arduino e o módulo MFRC522

Arduino, criado em 2005 em Ivrea (Itália), é uma plataforma de *hardware* aberto que democratizou a prototipagem eletrónica [7][10]. As placas usam microcontroladores de vários fabricantes e vêm com um IDE próprio, que esconde boa parte da complexidade de programar microcontroladores em baixo nível. Em duas décadas, a comunidade produziu bibliotecas para praticamente qualquer sensor ou módulo.

Escolhi a placa **Arduino UNO R4 WiFi**, lançada em 2023 como sucessora da UNO R3 clássica. As razões:

- **Microcontrolador de 32 bits**: o Renesas RA4M1 a 48 MHz, contra o ATmega328P a 16 MHz da R3. Muito mais capacidade de processamento e memória.
- **Wi-Fi nativo**: o módulo ESP32-S3 já está integrado. Não preciso de *shield* adicional — menos cabos, menos custo.
- **Mais memória**: 32 KB de SRAM e 256 KB de flash. Dá folga para certificados TLS e *payloads* HTTP maiores.
- **Alimentação USB-C**, mais prática que a Mini-B da R3.

Para a leitura dos cartões escolhi o módulo **MFRC522** da NXP Semiconductors [2], um dos leitores RFID mais usados em prototipagem. Trabalha nos 13,56 MHz, suporta ISO/IEC 14443 A e comunica com o microcontrolador por SPI. Escolhi-o pela compatibilidade com os cartões da escola, por ser fácil de arranjar em Portugal, por custar cerca de 3€ e por ter bibliotecas maduras — em especial a `MFRC522` do Miguel Balboa, bem documentada e muito referenciada.

O circuito segue o que está previsto no SDG (secção 3.2.1, *Terminal de Presença*). As ligações entre o MFRC522 e a UNO R4 são: *SDA/SS* no pino 10, *SCK* no 13, *MOSI* no 11, *MISO* no 12, *RST* no 9, *GND* numa das massas da placa e *3.3V* na saída de 3,3 V. Atenção a este último: o MFRC522 não aguenta os 5 V da linha principal. Ligar mal o pino de alimentação queima o módulo instantaneamente.

O circuito tem ainda **dois LEDs** para dar *feedback* visual imediato ao utilizador. O verde, no pino 7 com resistência de 220 Ω, acende durante dois segundos quando a leitura corre bem (servidor responde `HTTP 200` com `data.ok = true`). O vermelho, no pino 6 com resistência igual, acende em três situações: UID não registado (resposta 404), cartão bloqueado pelo administrador (resposta 401) ou falha de rede. Esta sinalização dupla, alinhada com o SDG secção 3.2.1, permite ao utilizador saber imediatamente o que aconteceu, sem precisar de ir ver o ecrã. Isso torna o leitor autónomo em situações reais, como na entrada do edifício numa hora de ponta.

A leitura é orquestrada pelo *firmware* em C/C++ escrito no IDE Arduino. A cada iteração do *loop*, o microcontrolador pergunta ao MFRC522 se há algum cartão no campo. Se sim, extrai o UID, converte-o para *string* hexadecimal e envia-o ao servidor num HTTP POST — como descrito em 2.3.

## 2.3. Arquitetura cliente-servidor e o protocolo HTTP

O modelo **cliente-servidor** manda em sistemas distribuídos desde os anos 80. A ideia é simples: o **cliente** pede, o **servidor** recebe, processa e responde. A separação tem consequências profundas em organização do código, escalabilidade e evolução independente da apresentação e da lógica de negócio.

No projeto há **dois tipos de cliente** a falar com o mesmo servidor: os *browsers* dos utilizadores humanos e o leitor Arduino, que funciona como cliente HTTP automatizado e faz POST sem intervenção humana. Esta diferença é importante, porque obriga o servidor a expor APIs genéricas o suficiente para ambos — daí a opção pelo formato JSON nos *endpoints* de API.

O **HTTP**, descrito por Tim Berners-Lee em 1991 e hoje padronizado nos RFC 7230–7235 [8], é o meio de comunicação. A versão mais recente, HTTP/2, traz multiplexagem e compressão de cabeçalhos. Mas aqui o tráfego é local e modesto, pelo que usei HTTP/1.1, plenamente suportado pelo Apache do WAMP.

A comunicação HTTP estrutura-se em **métodos** — `GET` para consultar, `POST` para criar ou enviar, `PUT` para atualizar, `DELETE` para remover. No projeto os *endpoints* usam `GET` (leituras) e `POST` (escritas), numa abordagem *RESTful* pragmática e não estritamente ortodoxa, mas suficiente para o que preciso.

Os **códigos de estado** são a outra peça essencial: `200 OK` para sucesso, `400 Bad Request` para erros do cliente, `401 Unauthorized` para falha de autenticação, `403 Forbidden` para falta de permissões, `404 Not Found` quando o recurso não existe, `500 Internal Server Error` para erros do servidor. O sistema devolve códigos apropriados em cada caso, o que permite ao leitor Arduino reagir de forma diferente consoante a resposta — como já referi sobre o LED vermelho.

## 2.4. Tecnologias *web*: HTML, CSS e JavaScript

As interfaces *web* modernas assentam em três peças com papéis distintos: **HTML** para a estrutura, **CSS** para a apresentação, **JavaScript** para o comportamento.

O **HTML5**, estabilizado em 2014 pelo W3C, trouxe elementos semânticos (`<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<footer>`) que exprimem diretamente a natureza de cada parte do documento. Isto ajuda a acessibilidade, ajuda o SEO e ajuda a manutenção. Todas as páginas do projeto seguem esta estrutura: `<header class="topbar">` para a barra, `<main class="page-content">` para o corpo e `<section>` para os blocos funcionais.

O **CSS3** introduziu o *Flexbox* e o *Grid*, que substituíram as velhas técnicas baseadas em *float*. Uso *Flexbox* para barras de navegação, linhas de formulários e listas de cartões, e *Grid* para as estatísticas, que adaptam o número de colunas à largura do ecrã. Os gráficos usam *Chart.js* [9]. A paleta — azul `#3b82f6` para ativo, verde `#10b981` para confirmação, vermelho `#ef4444` para alerta, cinzentos para neutros — foi escolhida com contraste WCAG 2.1 AA, em linha com o SDG secção 3.3.2.

O **JavaScript**, criado em 1995 por Brendan Eich na Netscape, evoluiu muito. O ES6+ trouxe *arrow functions*, *template literals*, desestruturação, módulos, `async/await`, `fetch` API. Uso tudo isto. O código fica compacto e expressivo, sem precisar de bibliotecas pesadas como jQuery, que hoje é dispensável.

Não usei *frameworks* modernos (React, Vue, Angular) de propósito — é coerente com o SDG secção 4 (*Visão Arquitetural Geral*). Pela dimensão do projeto, a curva de aprendizagem e o peso dessas bibliotecas não compensavam. *Vanilla JavaScript* cumpre, e mantém o código acessível a quem vier a continuar o trabalho.

## 2.5. Linguagem PHP

O **PHP** (*PHP: Hypertext Preprocessor*) é uma linguagem interpretada, orientada ao *web* do lado servidor, criada em 1994 por Rasmus Lerdorf [4]. Cerca de 76% dos *sites* na Web usam PHP no *backend*, incluindo a Wikipédia, o Facebook (sob a forma modificada HHVM) e o ecossistema WordPress, que sozinho corre em mais de 40% dos *sites* mundiais.

Usei **PHP 8.3**, saído em novembro de 2023. Traz tipagem estrita opcional, tipos de retorno nativos, *readonly classes*, *enums* e *attributes* para metadados. Apesar disto, optei por um estilo sobretudo procedural — próximo do que é ensinado nas aulas — para manter o código legível para quem entrar no repositório sem formação avançada em engenharia de *software*.

Para falar com a base de dados, o projeto usa duas interfaces: **MySQLi** (procedural) nos *scripts* onde vale mais a clareza, e **PDO** quando preciso de transações explícitas ou de abstração do motor. As duas usam sistematicamente *prepared statements*, que cortam a possibilidade de **injeção de SQL** — uma das vulnerabilidades mais críticas segundo a OWASP [6]. Este cuidado está em todas as consultas e cumpre o SDG secção 3.3.3.

## 2.6. Base de dados MySQL

O **MySQL** [5] é um SGBD relacional de código aberto, criado pela sueca MySQL AB e hoje propriedade da Oracle. Muitas vezes apresentado como a *“base de dados mais popular do mundo”* — afirmação comercial, mas que traduz uma quota real robusta no segmento *web*. Implementa o modelo relacional de Edgar F. Codd (1970) e usa SQL, padronizado pela ANSI em 1986.

O projeto usa o motor **InnoDB**, que suporta **transações ACID**, chaves estrangeiras e bloqueio ao nível da linha. Isto é decisivo, por exemplo, na eliminação de um aluno: é preciso apagar o registo em três tabelas (`login`, `alunos`, `presencas`) de forma atómica. Encapsulei a operação numa transação — se um dos `DELETE` falha, tudo é revertido (`ROLLBACK`) e a base fica consistente.

As tabelas seguem normalização até à 3FN, com redução de redundâncias. Há uma exceção consciente na tabela `presencas`, descrita no SDG secção 5.4: **desnormalizei** o nome do utilizador. Em vez de fazer *join* com `alunos` ou `professores` sempre que mostro um histórico, o nome fica replicado na linha da presença. Simplifica consultas e acelera leituras. Em troca, quando o nome muda tenho de atualizar em dois sítios — mas no contexto escolar os nomes quase nunca mudam.

Criei também **índices** estratégicos: um sobre `data` em `presencas` (para agregar o gráfico diário) e outro sobre `login` (para o histórico individual). Em 6.4 detalho cada índice. A diferença de desempenho notou-se assim que a tabela passou a ter um volume realista.

## 2.7. Padrões de autenticação e sessões

Por fim, autenticação e sessões. O PHP tem, desde a primeira versão, um mecanismo de sessões baseado num identificador único num *cookie* (`PHPSESSID`) que aponta para variáveis guardadas em ficheiros do lado servidor. É simples, mas serve quase todos os cenários *web* desde que acompanhado de algumas práticas: regenerar o identificador periodicamente (para evitar *session fixation*), expirar por inatividade (fixei em 30 minutos) e transmitir só por canal cifrado em produção (*flag* `Secure` nos *cookies*).

As palavras-passe passam pela função `password_hash()` do PHP, que usa bcrypt por omissão com custo configurável (10 por omissão, ou seja, 2¹⁰ iterações). Está em linha com as recomendações OWASP [6]. A verificação faz-se com `password_verify()`, que reconhece o algoritmo e compara em tempo constante, resistente a ataques de canal lateral. Nenhuma palavra-passe é guardada em texto — garantia básica contra fugas de dados.

A autenticação por cartão RFID (detalhada no Capítulo 7) complementa a clássica mas não a substitui. O cartão identifica para registo de presença; o acesso aos perfis e áreas restritas continua a exigir credenciais tradicionais. Este ponto foi muito discutido na fase de conceção e ficou no SDG secção 3.3.3, com a designação de *autenticação dupla*.

---

# 3. Metodologia

O projeto é multidisciplinar: *software* *web*, *hardware* embebido e modelação de base de dados ao mesmo tempo. Avançar em paralelo em tudo não era viável, nem fazia sentido terminar uma componente antes de começar a seguinte. Adotei uma abordagem **iterativa e incremental**, inspirada em metodologias ágeis e adequada a um projeto académico conduzido por uma só pessoa.

## 3.1. Processo de escrita e desenvolvimento

Segui quatro princípios, em linha com as boas práticas de escrita de uma PAP:

1. **Planeamento** — no início do ano letivo fixei um cronograma realista, com as fases distribuídas mês a mês.
2. **Revisão contínua** — reli e melhorei código e texto à medida que tomava decisões novas.
3. ***Feedback*** — entreguei versões preliminares à orientadora, Professora Carla de Sousa, para análise.
4. **Revisão final** — verifiquei gramática, formatação e regulamentos internos antes da entrega.

## 3.2. Fases do projeto

Organizei o trabalho em cinco fases, parcialmente sobrepostas:

1. **Levantamento e pesquisa** (setembro–outubro 2025): estudo de RFID [1][2][3], análise de sistemas parecidos, leitura da documentação oficial de Arduino [10] e PHP [4].
2. **Modelação e prototipagem** (outubro–novembro 2025): modelo de dados, diagrama ER, primeiro protótipo do leitor em *breadboard*.
3. **Implementação da aplicação *web*** (novembro 2025–fevereiro 2026): construção incremental das páginas, *endpoints* PHP e integração com a base de dados.
4. **Integração hardware/software** (fevereiro–março 2026): ligação efetiva do Arduino ao servidor, adição dos LEDs, afinação de tempos de resposta.
5. **Testes, validação e redação** (março–abril 2026): testes funcionais, integração, aceitação; escrita do relatório.

## 3.3. Ferramentas utilizadas

| Ferramenta | Finalidade |
|------------|-----------|
| **Visual Studio Code** | Editor principal (PHP, JavaScript, HTML, CSS). |
| **Arduino IDE** | Desenvolvimento e *upload* do *firmware* para a UNO R4 WiFi. |
| **WAMP 64** | Servidor local com Apache, PHP 8.3 e MySQL. |
| **phpMyAdmin** | Administração da base de dados durante o desenvolvimento. |
| **Git** | Controlo de versões e *rollback* seguro de alterações experimentais. |
| **MySQL Workbench** | Modelação visual do diagrama ER. |
| **Postman** | Teste isolado dos *endpoints* HTTP antes da integração com o Arduino. |

## 3.4. Qualidade académica e rigor

Tentei manter, ao longo da escrita, os pilares habituais da qualidade académica. **Originalidade**: a combinação Arduino + RFID + painel com sub-separadores e bloqueio de cartão é um contributo próprio, mesmo assente em peças conhecidas. **Clareza e objetividade**: frases curtas, eliminação de repetições. **Coerência lógica**: cada capítulo prepara o seguinte. **Rigor científico**: fundamentação na bibliografia [1]–[10]. **Prevenção de plágio**: citação explícita de todas as fontes consultadas.

---

# 4. Análise de Requisitos

A análise de requisitos é das fases mais críticas de qualquer projeto. É aqui que se identificam e hierarquizam funcionalidades, restrições e qualidades. Este capítulo apresenta os requisitos do projeto, em articulação com o SDG, que foi a referência ao longo de todo o desenvolvimento.

Levantei requisitos de duas formas: em sessões informais com a orientadora e a partir da minha experiência como utilizador das ferramentas do Agrupamento. Não apliquei uma metodologia ágil formal (Scrum ou Kanban) por causa da dimensão pessoal do projeto, mas aproveitei a ideia de iteração curta: cada módulo era implementado, testado e ajustado antes de passar ao seguinte.

## 4.1. Requisitos funcionais

Os requisitos funcionais descrevem o que o sistema deve fazer. Estão organizados por módulo e numerados sequencialmente, na convenção usada no SDG capítulo 3.

| Código | Descrição | Prioridade |
|--------|-----------|-----------|
| RF-01 | Autenticação por *login* e *password*. | Alta |
| RF-02 | Autenticação/identificação por leitura do cartão RFID. | Alta |
| RF-03 | Redirecionamento de cada utilizador para a página do seu papel (aluno, professor, administrador). | Alta |
| RF-04 | O Arduino envia o UID lido para o servidor por HTTP POST. | Alta |
| RF-05 | O servidor regista cada leitura válida em `presencas`, alternando entre *entrada* e *saída*. | Alta |
| RF-06 | O Arduino dá *feedback* visual por dois LEDs (verde = sucesso, vermelho = erro). | Média |
| RF-07 | O administrador pode adicionar alunos e professores. | Alta |
| RF-08 | O administrador pode atualizar dados pessoais e académicos. | Alta |
| RF-09 | O administrador pode consultar, em leitura, os dados completos de qualquer utilizador. | Média |
| RF-10 | O administrador pode bloquear um cartão em caso de perda, sem apagar o utilizador. | Alta |
| RF-11 | O administrador pode eliminar definitivamente um utilizador, com confirmação. | Média |
| RF-12 | Painel com estatísticas agregadas, incluindo gráfico de 7 dias. | Média |
| RF-13 | Lista dos últimos registos com utilizador, data, hora e tipo. | Média |
| RF-14 | Consulta dos *logs* do sistema, com filtros por nível e pesquisa. | Baixa |
| RF-15 | O professor pode marcar testes (título, descrição, data, turma-alvo). | Alta |
| RF-16 | O professor pode consultar os seus alunos, filtrando por turma e presença. | Média |
| RF-17 | O aluno pode consultar os testes da sua turma, separados em *próximos* e *passados*. | Alta |
| RF-18 | Registo de eventos em NDJSON (autenticações, leituras RFID, operações administrativas, falhas). | Baixa |
| RF-19 | O formulário de adicionar/atualizar utilizador permite ler o UID diretamente do leitor (*scan on demand*). | Média |
| RF-20 | Gestão centralizada de notícias, ligada à tabela `noticias` prevista no SDG secção 5.5. | Baixa |

A maior parte dos requisitos de prioridade *Alta* e *Média* está implementada. Os de prioridade *Baixa* estão operacionais, à espera de afinações finais — detalhados nos capítulos seguintes.

## 4.2. Requisitos não funcionais

Descrevem propriedades transversais — desempenho, segurança, usabilidade, portabilidade — independentes das funcionalidades.

| Código | Categoria | Descrição |
|--------|-----------|-----------|
| RNF-01 | Desempenho | Resposta a uma leitura RFID < 500 ms em condições normais. |
| RNF-02 | Desempenho | Carregamento do painel de estatísticas (7 dias + últimos 10 *scans*) < 1 s. |
| RNF-03 | Segurança | Todas as consultas SQL com dados do utilizador usam *prepared statements*. |
| RNF-04 | Segurança | *Hash* bcrypt (custo mínimo 10) para as palavras-passe. |
| RNF-05 | Segurança | Sessão expira por 30 minutos de inatividade. |
| RNF-06 | Segurança | O *endpoint* da Arduino exige uma chave de API partilhada. |
| RNF-07 | Usabilidade | Interface integralmente em português, sem *software* adicional. |
| RNF-08 | Usabilidade | Responsiva: secretária, portátil e *tablet*. |
| RNF-09 | Acessibilidade | Contraste WCAG 2.1 AA e indicadores duais (cor + ícone/texto). |
| RNF-10 | Portabilidade | Corre num servidor WAMP local, sem serviços *cloud*. |
| RNF-11 | Manutibilidade | Estrutura de diretórios clara: *backend*, *frontend*, recursos, configuração. |
| RNF-12 | Manutibilidade | Eventos relevantes registados em *logs* estruturados. |

## 4.3. Atores do sistema

Identifiquei quatro atores:

| Ator | Descrição | Meio de acesso |
|------|-----------|----------------|
| **Aluno** | Utilizador mais numeroso. Consulta testes e dados pessoais. Regista presença por cartão. | Browser *web* + cartão RFID |
| **Professor** | Consulta a lista dos seus alunos, marca testes, regista presença. | Browser *web* + cartão RFID |
| **Administrador** | Privilégios completos: utilizadores, cartões, testes, *logs*, estatísticas. | Browser *web* |
| **Leitor RFID (Arduino)** | Ator não humano: comunica as leituras ao servidor. | HTTP POST (chave de API) |

Incluí o leitor Arduino como ator formal. Não é prática corrente na UML clássica, mas a sua interação com o servidor é relevante e merece estar explícita. É coerente com a descrição do SDG secção 3.1 (*Gestão de Utilizadores*).

## 4.4. Casos de uso

Destaco sete casos de uso que, na minha opinião, resumem bem a lógica da plataforma. Os fluxos detalhados estão no Anexo C.

| UC | Nome | Ator primário | Resultado esperado |
|----|------|--------------|--------------------|
| UC-01 | Registar entrada/saída no edifício | Aluno / Professor | Presença registada, *feedback* via LED, estado `Presença` atualizado. |
| UC-02 | Autenticar-se na plataforma | Qualquer utilizador | Sessão iniciada, redirecionamento para a sua página. |
| UC-03 | Marcar um teste | Professor | Teste persistido, visível aos alunos da turma. |
| UC-04 | Consultar testes marcados | Aluno | Lista separada em *próximos* e *passados*. |
| UC-05 | Adicionar novo utilizador | Administrador | Registo em `login` e `alunos`/`professores`, cartão opcional. |
| UC-06 | Bloquear cartão | Administrador | Cartão marcado como bloqueado, leituras seguintes rejeitadas. |
| UC-07 | Consultar estatísticas | Administrador | Painel com *stat-cards*, gráfico 7 dias, últimos 10 *scans*. |

Os fluxos principais destes sete casos estão todos cobertos pela implementação descrita no Capítulo 7. Cada um foi validado na fase de testes com um cenário documentado no Capítulo 8.

---

# 5. Arquitetura do Sistema

Passados os requisitos, é preciso mostrar como o sistema foi organizado para os cumprir. Este capítulo descreve a arquitetura — componentes e padrões de comunicação — e a estrutura de diretórios. Os detalhes de implementação ficam para o Capítulo 7.

## 5.1. Visão geral

A arquitetura é uma variante pragmática do clássico modelo de **três camadas** — apresentação, lógica e persistência —, com uma quarta peça: o **hardware de captura** (leitor Arduino + cartões), que alimenta o sistema com eventos externos. Está estabilizada no SDG capítulo 5. Procura o equilíbrio entre simplicidade (adequada a uma PAP) e separação mínima de responsabilidades.

Na prática, os componentes mapeiam-se assim:

- A **camada de apresentação** são as páginas PHP renderizadas em HTML e o JavaScript no *browser*. É aqui que mora a interação com o utilizador humano.
- A **camada de lógica** são os *scripts* PHP em `api/`, que aplicam regras de negócio, validam entradas e orquestram a persistência.
- A **camada de persistência** é o MySQL, com o esquema do Capítulo 6.
- O **hardware de captura** é a UNO R4 WiFi com o MFRC522, descrito no Capítulo 2.

A separação é respeitada com algum rigor, sem a formalidade do MVC estrito — demasiado pesado para a dimensão do projeto. O SDG, secção 4, apresenta esta mesma visão em camadas.

## 5.2. Diagrama de componentes

A Figura 5 (ver Anexos) mostra o diagrama de componentes. Em resumo, há:

1. **Cliente humano** — o *browser*, que fala com o servidor por HTTP/HTTPS.
2. **Cliente RFID** — a Arduino, que fala com o servidor por HTTP, num fluxo unidirecional (POST com UID, resposta JSON).
3. **Servidor Apache + PHP** — aloja a aplicação e expõe os *endpoints*.
4. **Base de dados MySQL** — acedida pelo PHP via MySQLi e PDO.
5. **Sistema de ficheiros** — guarda os *logs* NDJSON e o ficheiro temporário `scan_session.json`, que coordena a funcionalidade *Ler cartão* entre o painel e a Arduino.

O PHP fala com o MySQL pelo *socket* local, com credenciais em `config/db.php`. Em produção, este ficheiro deveria ficar fora do diretório público e nunca ser versionado em *git*.

## 5.3. Fluxo de dados

Dois fluxos-tipo cobrem a essência do funcionamento.

**Fluxo 1 — Leitura de um cartão RFID.** O utilizador aproxima o cartão. O MFRC522 deteta-o e devolve o UID à Arduino. A Arduino constrói um HTTP POST com o UID e a chave de API, e envia-o para `api/push.php`. O servidor valida a chave, normaliza o UID (remove espaços, maiúsculas), procura em `login` uma correspondência com `blocked = 0`. Se encontrar, identifica o utilizador, decide entre entrada e saída (olhando para a última entrada do dia em `presencas`), insere a nova linha, atualiza `Presença` em `alunos` ou `professores`, regista no *log* NDJSON e devolve JSON com `ok: true`. A Arduino recebe, pisca o LED verde dois segundos, fica pronta para a próxima leitura.

**Fluxo 2 — Consulta de testes pelo aluno.** O aluno abre `project/public/Aluno.php`. O PHP verifica a sessão. Se for válida e o papel for *Aluno*, consulta `alunos` para obter nome e turma, depois `testes` filtrando por `turma_num` e `turma_letra`, separa em *próximos* (data ≥ hoje) e *passados*, e renderiza a página HTML com as duas listas. O cliente recebe a página já pronta, sem pedidos assíncronos adicionais.

Os dois exemplos ilustram, respetivamente, uma interação *machine-to-machine* (hardware → servidor) e uma *human-to-machine* síncrona (utilizador → servidor) — os dois paradigmas suportados.

## 5.4. Organização de diretórios

A estrutura segue uma decomposição funcional, em linha com o SDG capítulo 5.4, com pequenas adaptações.

```
PAP/
├── api/                      # Endpoints do servidor (PHP)
│   ├── admin_add_card.php    # Adicionar utilizador
│   ├── admin_alunos.php      # Listar/obter/bloquear/eliminar aluno
│   ├── admin_logs.php        # Leitura de logs NDJSON
│   ├── admin_testes.php      # Listar/eliminar teste
│   ├── admin_update_card.php # Atualizar utilizador
│   ├── auth.php              # Autenticação login/password
│   ├── create_teste.php      # Criar teste (pelo professor)
│   ├── lib/                  # Utilitários partilhados (logger)
│   ├── logout.php            # Termina sessão
│   ├── migrate_*.php         # Scripts de migração da BD
│   ├── profile.php           # Dados do perfil do utilizador
│   ├── push.php              # Entrada de dados da Arduino
│   ├── register.php          # Registo público
│   ├── save_login.php        # Criação de conta
│   └── scan_uid.php          # Coordenação de leitura RFID on-demand
├── config/                   # Configurações do sistema
│   ├── db.php                # Credenciais e ligação MySQL
│   └── session.php           # Inicialização e timeout de sessão
├── logs/                     # Ficheiros de log NDJSON
├── project/
│   ├── assets/               # CSS, JS, imagens
│   │   ├── app.js
│   │   ├── style.css
│   │   └── aemtg.jpg
│   └── public/               # Páginas acessíveis no browser
│       ├── Aluno.php
│       ├── Professor.php
│       ├── admin.php
│       └── index.php
├── relatorio.md              # Presente relatório (fonte Markdown)
└── arduino/                  # Firmware do leitor (sketch .ino)
```

Esta organização permite localizar qualquer ficheiro pelo seu papel lógico. A convenção de prefixar *endpoints* administrativos com `admin_` foi particularmente útil: filtra visualmente e facilita aplicar verificações de autorização partilhadas.

---

# 6. Base de Dados

A base de dados é o núcleo persistente do sistema. É onde vivem as entidades — utilizadores, cartões, presenças, testes — e contra a qual as várias camadas da aplicação leem e escrevem. Erros aqui propagam-se a todo o sistema, e são caros de corrigir depois de a base estar povoada.

## 6.1. Modelo conceptual

O modelo conceptual identifica as entidades principais, as relações entre elas e as propriedades mais significativas. São cinco:

- **Utilizador (Login)** — o par de credenciais usado para autenticação, com o UID do cartão e o papel (`Aluno`, `Professor` ou `admin`). Funciona como âncora de identidade: qualquer referência a um utilizador noutra tabela é feita pelo *login*.
- **Aluno** — especialização do utilizador, com idade, turma, número na turma, estado de presença corrente.
- **Professor** — especialização do utilizador, com cargo, gabinete, matéria lecionada, turma principal.
- **Presença** — evento de leitura do cartão, com data, hora, tipo (entrada ou saída) e referência ao utilizador. É uma entidade temporal, acumulativa.
- **Teste** — compromisso pedagógico marcado pelo professor, visível aos alunos da turma-alvo.

As relações principais: um utilizador é aluno ou professor (especialização exclusiva); cada aluno e cada professor gera zero ou mais presenças; cada professor marca zero ou mais testes; cada teste destina-se a uma turma, identificada pelo par (número, letra).

## 6.2. Modelo lógico

A tradução para SQL envolveu estas decisões:

- **Chave primária artificial** (`id` auto-incremental) em todas as tabelas transacionais (`presencas`, `testes`). Em `alunos`, `professores` e `login` mantive a convenção existente: `ID` em `alunos` e `professores`, e `Login` (texto) como chave primária natural em `login`.
- **Referência por *login*** — a coluna `login` (texto) é a chave estrangeira lógica entre tabelas. Não impus *foreign keys* físicas ao nível do SGBD, para ter flexibilidade na fase de desenvolvimento; serão adicionadas numa fase de consolidação.
- **Desnormalização de `nome`** em `presencas`, explicada no Capítulo 2 e formalizada em 6.3.
- **Duplicação de `turma`** em `alunos` e `professores`: mantenho `Turma` (texto, ex. `"12C"`) e junto o par normalizado `turma_num` + `turma_letra`. Esta redundância, introduzida por migração a meio do projeto (`api/migrate_turma_split.php`), permitiu transitar para o novo modelo sem parar a aplicação. `Turma` será descontinuada quando todas as páginas estiverem migradas.

## 6.3. Descrição das tabelas

Segue a estrutura detalhada das tabelas da base `pap`, no estado atual depois das migrações do Capítulo 7.

**Tabela 5 — `login`**

| Coluna | Tipo | Restrições | Descrição |
|--------|------|-----------|-----------|
| `Login` | VARCHAR(50) | PRIMARY KEY | Identificador único (e.g. `a12345`, `b12345`). |
| `Password` | VARCHAR(255) | NOT NULL | *Hash* bcrypt da palavra-passe. |
| `UID` | VARCHAR(32) | NULL | UID do cartão RFID associado. |
| `Role` | ENUM('Aluno', 'Professor', 'admin') | NOT NULL | Papel no sistema. |
| `blocked` | TINYINT(1) | NOT NULL, DEFAULT 0 | 1 = cartão inativo. |

**Tabela 6 — `alunos`**

| Coluna | Tipo | Restrições | Descrição |
|--------|------|-----------|-----------|
| `ID` | INT | PRIMARY KEY, AUTO_INCREMENT | Chave primária. |
| `Nome` | VARCHAR(100) | NOT NULL | Nome completo. |
| `Idade` | INT | NULL | Idade em anos. |
| `Turma` | VARCHAR(10) | NULL | Turma no formato legado (e.g. `10A`). |
| `turma_num` | TINYINT | NULL | Ano (10, 11 ou 12). |
| `turma_letra` | CHAR(1) | NULL | Letra (A, B ou C). |
| `Número em turma` | INT | NULL | Posição na turma. |
| `Presença` | TINYINT(1) | NOT NULL, DEFAULT 0 | 1 = dentro do edifício. |
| `login` | VARCHAR(50) | NOT NULL, UNIQUE | Referência para `login.Login`. |

**Tabela 7 — `professores`**

| Coluna | Tipo | Restrições | Descrição |
|--------|------|-----------|-----------|
| `ID` | INT | PRIMARY KEY, AUTO_INCREMENT | Chave primária. |
| `Nome` | VARCHAR(100) | NOT NULL | Nome completo. |
| `Cargo (posição)` | VARCHAR(100) | NULL | Cargo ou posição. |
| `Gabinete` | VARCHAR(30) | NULL | Gabinete. |
| `Presença` | TINYINT(1) | NOT NULL, DEFAULT 0 | Estado corrente. |
| `Horario` | VARCHAR(100) | NULL | Ficheiro ou URL com o horário. |
| `Matéria ensinada` | VARCHAR(100) | NULL | Disciplina. |
| `turma` | VARCHAR(10) | NULL | Turma principal (legado). |
| `turma_num` | TINYINT | NULL | Ano. |
| `turma_letra` | CHAR(1) | NULL | Letra. |
| `login` | VARCHAR(50) | NOT NULL, UNIQUE | Referência para `login.Login`. |

**Tabela 8 — `presencas`**

| Coluna | Tipo | Restrições | Descrição |
|--------|------|-----------|-----------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Chave primária. |
| `login` | VARCHAR(50) | NOT NULL | Referência para `login.Login`. |
| `nome` | VARCHAR(100) | NOT NULL | Nome do utilizador (desnormalizado). |
| `person_type` | ENUM('Aluno', 'Professor') | NOT NULL | Classe do utilizador. |
| `uid` | VARCHAR(32) | NOT NULL | UID lido no momento. |
| `data` | DATE | NOT NULL | Data da leitura. |
| `hora` | TIME | NOT NULL | Hora da leitura. |
| `presenca` | TINYINT(1) | NOT NULL | 1 = entrada; 0 = saída. |

**Tabela 9 — `testes`**

| Coluna | Tipo | Restrições | Descrição |
|--------|------|-----------|-----------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Chave primária. |
| `titulo` | VARCHAR(200) | NOT NULL | Título do teste. |
| `descricao` | TEXT | NULL | Descrição livre (tópicos, material). |
| `data_teste` | DATE | NOT NULL | Data prevista. |
| `turma_num` | TINYINT | NOT NULL | Ano da turma-alvo. |
| `turma_letra` | CHAR(1) | NOT NULL | Letra da turma-alvo. |
| `professor_login` | VARCHAR(50) | NOT NULL | *Login* do professor que marcou. |
| `materia` | VARCHAR(100) | NULL | Matéria (preenchida automaticamente). |
| `criado_em` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Carimbo temporal da criação. |

Separei `data` e `hora` em dois campos em `presencas`, em vez de um `DATETIME`, pela simplicidade da agregação diária: `SELECT data, COUNT(*) FROM presencas GROUP BY data` é muito mais direto do que a versão com `DATE(datetime)`. É uma decisão pequena mas com impacto percetível no desempenho do gráfico quando a tabela cresce ao longo do ano.

## 6.4. Índices e otimizações

Para além das chaves primárias e únicas, defini estes índices. Escolhi-os a olhar para as consultas que a aplicação realmente faz:

| Tabela | Índice | Colunas | Justificação |
|--------|--------|---------|--------------|
| `presencas` | `idx_data` | `data` | Agregação diária para o gráfico. |
| `presencas` | `idx_login` | `login` | Consulta eficiente do histórico individual. |
| `testes` | `idx_turma` | `turma_num`, `turma_letra` | Consulta por turma na página do aluno. |
| `testes` | `idx_data` | `data_teste` | Ordenação cronológica em listagens. |
| `login` | (implícito) | `Login` (PK) | Consulta por *login* — o caso mais comum. |

A regra é *“indexar colunas que aparecem em `WHERE`, `JOIN` ou `ORDER BY`”*. Há sempre a tensão clássica entre velocidade de leitura (ajudada pelos índices) e velocidade de escrita (prejudicada por eles), mas aqui as leituras são muito mais frequentes — pelo que ser agressivo com índices compensa. Coerente com a filosofia do SDG secção 5 (*Design de Dados*).

Uma nota adicional: todas as tabelas usam `utf8mb4`, o único *charset* que garante suporte completo a emojis, caracteres matemáticos e nomes portugueses com diacríticos (*Matéria*, *Presença*, *Número*). O antigo `utf8` do MySQL, limitado a 3 bytes por carácter, é insuficiente e considerado obsoleto.

---

# 7. Implementação

Este capítulo descreve, módulo a módulo, como o sistema foi implementado. Organizei a descrição pelas fronteiras naturais do código — autenticação, *firmware* Arduino, *endpoint* de leitura, painel de administração, página do aluno, página do professor, gestão de testes e *logging* — em vez de uma ordem estritamente cronológica. Fica mais legível e acompanha a estrutura de diretórios do Capítulo 5.

Em linha com o SDG secção 10 (*Implementação*), cada módulo traz: descrição funcional, ficheiros envolvidos, pontos técnicos não óbvios e, quando útil, excertos de código. Os excertos estão deliberadamente encurtados para destacar o essencial; as versões integrais estão nos anexos do Capítulo 11 e no repositório local.

## 7.1. Autenticação e gestão de sessões

A autenticação é o ponto de entrada de todos os fluxos *web*. Tem de estar correta: uma falha aqui compromete tudo o resto. Segui o SDG (secção 3.3.3 e secção 9, *Segurança*) e as boas práticas correntes da comunidade PHP.

**Ficheiros:** `project/public/login.php`, `config/session.php`, `config/db.php`, `api/login.php`.

O fluxo é este. O utilizador submete o formulário em [login.php](project/public/login.php), enviando `login` e `password` por `POST`. O *script* [api/login.php](api/login.php) recebe os dados, procura o registo em `login` e compara o *hash* com `password_verify()`. Se for válido, grava três variáveis na sessão: `$_SESSION['login']`, `$_SESSION['role']` e `$_SESSION['uid']`. Depois redireciona para a página do papel: `/project/public/Aluno.php`, `/project/public/Professor.php` ou `/project/public/admin.php`.

Todas as páginas protegidas começam por incluir `config/session.php` e verificar, logo no topo, se a sessão existe e se o papel é o esperado. O padrão é uniforme, o que facilita auditar:

```php
if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'Aluno') {
    header("Location: /PAP/project/public/index.php");
    exit;
}
```

Uso `password_hash()` com `PASSWORD_DEFAULT` (hoje bcrypt, com migração transparente para algoritmos futuros). Cumpre as recomendações OWASP em matéria de armazenamento de palavras-passe. Em nenhum momento houve palavras-passe em texto, nem durante o desenvolvimento — as contas de teste foram criadas com *hash* desde o primeiro dia.

## 7.2. Módulo RFID — firmware Arduino

O *firmware* da UNO R4 WiFi é o componente físico do sistema. A teoria está em 2.2; na prática são cerca de 180 linhas num único `.ino`, com a estrutura convencional: `setup()` para inicializar o *hardware* e `loop()` para repetir o ciclo de leitura.

**Pinos utilizados:**

| Pino | Função |
|------|--------|
| 10 | SDA (SS) do MFRC522 |
| 11–13 | MOSI / MISO / SCK (SPI) |
| 9 | RST do MFRC522 |
| 7 | LED verde (leitura OK) |
| 6 | LED vermelho (leitura falhou ou cartão bloqueado) |

Os dois LEDs (pinos 6 e 7, cada um em série com 220 Ω para proteção) foram uma melhoria notável de usabilidade. Antes disso, o utilizador não tinha retorno imediato e tinha de ir à aplicação *web* ver o que aconteceu. A semântica é intuitiva: **verde** quando a leitura tem sucesso *e* o servidor responde `HTTP 200` com `data.ok = true`; **vermelho** em todos os outros casos — cartão desconhecido, bloqueado, falha de rede, *timeout* ou resposta inválida.

O núcleo do ciclo de leitura:

```cpp
if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    String uid = uidToHex(mfrc522.uid.uidByte, mfrc522.uid.size);
    bool ok = sendUidToServer(uid);
    digitalWrite(ok ? PIN_LED_VERDE : PIN_LED_VERMELHO, HIGH);
    delay(800);
    digitalWrite(PIN_LED_VERDE, LOW);
    digitalWrite(PIN_LED_VERMELHO, LOW);
    mfrc522.PICC_HaltA();
}
```

A função `sendUidToServer()` faz um `HTTP POST` para [api/push.php](api/push.php), com corpo JSON contendo o UID. O *timeout* está em 3 segundos: suficiente para absorver flutuações pequenas, curto o bastante para não bloquear o dispositivo.

Adicionei ainda uma rotina de reconexão automática ao Wi-Fi, acionada quando a ligação cai. É simples mas mostra atenção às situações-limite típicas de um dispositivo embebido: quebras momentâneas, reinício do *router*, etc.

## 7.3. *Endpoint* `push.php` — receção das leituras RFID

O *endpoint* [api/push.php](api/push.php) é o ponto de contacto entre o Arduino e o sistema. A sua responsabilidade é estreita de propósito: receber o UID, validar, registar e responder. Quanto mais focado, mais simples é garantir correção e desempenho.

O fluxo:

1. Ler o corpo JSON do pedido e extrair `uid`.
2. Procurar em `login` um registo com `UID = ?` e `blocked = 0`.
3. Se não encontrar, responder `{ "ok": false, "reason": "unknown_or_blocked" }`.
4. Se encontrar, inserir em `presencas` com data e hora atuais e o tipo de movimento (`entrada` ou `saida`), calculado a partir do último movimento registado.
5. Responder `{ "ok": true, "nome": "...", "tipo": "entrada|saida" }`.

A verificação de `blocked = 0`, adicionada em conjunto com a funcionalidade de bloqueio no painel, garante que cartões marcados como bloqueados — tipicamente perdidos ou roubados — ficam sem efeito imediatamente, sem serem apagados. A distinção entre "bloqueado" e "apagado" é importante: se o cartão for recuperado, basta reativá-lo.

A alternância entre `entrada` e `saida` consulta o último registo do dia em `presencas` para aquele *login*: se o último foi `entrada`, o novo é `saida`, e vice-versa. Na primeira leitura do dia, assume `entrada`.

## 7.4. Painel de administração

O painel em [admin.php](project/public/admin.php) é o componente mais extenso da aplicação. Junta, num único documento HTML, cinco separadores — **Charts**, **Cards**, **Alunos**, **Professores** e **Logs** — navegáveis por JavaScript sem recarregar a página.

A estrutura do ficheiro é declarativa: cada separador é uma `<section>` com `id` correspondente, e a troca faz-se pela classe `.active`. Todo o comportamento dinâmico (listas, modais, submissão de formulários) vive em [app.js](project/assets/app.js).

### 7.4.1. Separador *Charts*

O separador *Charts* é o primeiro na ordem visual, porque resume a "saúde" do sistema. Tem três blocos empilhados:

1. **Quatro *stat-cards***: alunos totais, alunos presentes hoje, professores totais e professores presentes hoje.
2. **Gráfico de barras de 7 dias**, feito com *Chart.js* 4.4.0, com o número de presenças em cada um dos últimos sete dias.
3. **Últimas 10 leituras**, lista cronológica inversa, com nome, turma (ou "professor") e hora.

O *endpoint* é [api/admin_stats.php](api/admin_stats.php), que devolve um único JSON com os três blocos — para minimizar pedidos HTTP.

### 7.4.2. Separador *Cards*

Concentra as funções de cartões físicos: adicionar utilizador (com geração de *login* e *password*), associar um UID a um utilizador existente, editar dados. A associação usa [api/scan_uid.php](api/scan_uid.php), que põe o servidor em modo de escuta temporária para o próximo UID que a Arduino enviar.

### 7.4.3. Separador *Alunos*

Está dividido em dois sub-separadores — **Lista** e **Testes** — introduzidos na fase mais recente do desenvolvimento para organizar melhor a informação.

Em **Lista**, vêem-se todos os alunos em formato tabular compacto, com turma, número e estado de bloqueio. Ao clicar numa linha, abre um *dossier* lateral com a ficha completa — nome, *login*, idade, turma, número, UID, última presença, estado de bloqueio — e dois botões: **Bloquear cartão** (alterna `blocked`) e **Eliminar aluno** (modal de confirmação obrigatório, pela irreversibilidade).

Como a eliminação mexe em três tabelas, é feita dentro de uma transação explícita:

```php
$conn->begin_transaction();
try {
    $s1 = $conn->prepare("DELETE FROM presencas WHERE login = ?");
    $s1->bind_param("s", $login); $s1->execute(); $s1->close();
    $s2 = $conn->prepare("DELETE FROM alunos WHERE ID = ?");
    $s2->bind_param("i", $id);    $s2->execute(); $s2->close();
    $s3 = $conn->prepare("DELETE FROM login WHERE Login = ?");
    $s3->bind_param("s", $login); $s3->execute(); $s3->close();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}
```

Em **Testes**, a lista completa de testes marcados, com dois *dropdowns* de filtragem (ano e letra) no topo. Cada linha mostra título, turma, data, matéria e professor; há um botão de eliminar protegido por modal, para remover marcações erradas.

### 7.4.4. Separador *Professores*

Análogo ao de alunos, sem o bloco de testes (quem marca testes são os professores; a gestão por turma é feita lá). Mantém o mesmo padrão: *dossier*, bloqueio, eliminação com modal.

### 7.4.5. Separador *Logs*

Mostra os eventos do sistema — leituras RFID, erros, sessões — vindos de [api/admin_logs.php](api/admin_logs.php). Os registos estão em ficheiros NDJSON diários (`logs/app-YYYY-MM-DD.log`), formato que combina legibilidade humana com facilidade de processamento.

## 7.5. Página do aluno

[Aluno.php](project/public/Aluno.php) é minimalista por opção. Mostra o essencial: nome, turma, próximos testes, testes passados.

A consulta usa a forma normalizada da turma (`turma_num` e `turma_letra`), evitando manipulações de *string* em SQL:

```php
$stmt = $conn->prepare("
    SELECT t.titulo, t.descricao, t.data_teste, t.materia,
           p.Nome AS professor_nome
    FROM testes t
    LEFT JOIN professores p ON p.login = t.professor_login
    WHERE t.turma_num = ? AND t.turma_letra = ?
    ORDER BY t.data_teste ASC
");
```

Os testes são separados em dois grupos — futuros e passados — comparando `data_teste` com a data atual. Cada grupo fica numa secção distinta; os passados ordenam-se do mais recente para o mais antigo, como é habitual em contextos académicos.

## 7.6. Página do professor

[Professor.php](project/public/Professor.php) espelha em parte a do aluno mas traz um componente interativo essencial: o formulário **Marcar teste**. No topo da página, pede título, data, descrição (opcional) e turma-alvo (dois *dropdowns*: ano 10–12, letra A–C). A matéria vem automaticamente do perfil do professor, evitando erros de introdução.

A submissão é por `fetch()` para [api/prof_add_teste.php](api/prof_add_teste.php), que insere o novo registo em `testes` associado ao `professor_login` da sessão ativa. Em baixo, a lista dos testes marcados pelo próprio professor, com eliminação individual — mas só dos seus próprios, nunca dos de colegas.

## 7.7. Registo de eventos (*logging*)

O *logging* usa ficheiros de texto em NDJSON, um JSON por linha. Cada entrada tem *timestamp* ISO-8601, nível (`info`, `warn`, `error`), evento (`rfid_read`, `login_ok`, `login_fail`, etc.) e um objeto de contexto.

Exemplo:

```json
{"ts":"2026-04-17T09:12:44+01:00","level":"info","event":"rfid_read","ctx":{"uid":"A2B4F10C","login":"a32498","match":true}}
```

A rotação diária é trivial: o nome do ficheiro inclui a data (`app-2026-04-17.log`), pelo que a cada dia nasce um ficheiro novo, sem precisar de mecanismo externo. A simplicidade é intencional — para este sistema, Monolog ou ELK seriam excessivos.

---

# 8. Testes e Validação

A validação foi feita em três níveis, segundo o SDG secção 8: **testes unitários** para lógica isolada, **testes de integração** para fluxos completos e **testes de aceitação** num ambiente o mais próximo possível do real.

## 8.1. Testes unitários

Cobri as funções de maior risco: a conversão de UID para hexadecimal no *firmware*, a validação do formato de UID em `push.php`, a alternância `entrada`/`saida`, e a separação da turma em (`turma_num`, `turma_letra`). Os resultados foram bons no geral; houve uma única correção, relativa ao tratamento de letras minúsculas na turma (resolvido com `strtoupper()`).

## 8.2. Testes de integração

Simulei leituras RFID com cartões de teste, verificando que: (i) a Arduino acende o LED certo, (ii) o servidor regista o movimento correto, (iii) o painel mostra o registo na lista das últimas leituras em poucos segundos, (iv) o bloqueio de um cartão pelo painel impede leituras seguintes.

Também testei fluxos completos: criação de aluno → associação de cartão → leitura → visualização no *dashboard*; marcação de teste por professor → consulta pelo aluno da turma; eliminação de aluno → verificação de que as presenças associadas são removidas em cascata.

## 8.3. Testes de aceitação

Fiz sessões de demonstração com utilizadores-alvo (dois colegas de turma e um professor) para avaliar a usabilidade do painel e da página do aluno. O *feedback* foi sobretudo positivo, com destaque para a clareza dos *stat-cards* e a rapidez de resposta. A sugestão mais relevante foi incluir um filtro por turma na lista de alunos — já prevista como melhoria futura (ver 9.3).

## 8.4. Métricas recolhidas

Nos testes na rede local da sala de aula medi:

| Métrica | Valor observado |
|---------|-----------------|
| Latência média RFID → resposta HTTP | ≈ 180 ms |
| Tempo de carregamento do *dashboard* admin | ≈ 450 ms |
| Tempo médio de consulta do gráfico 7 dias | ≈ 90 ms |
| Taxa de leituras RFID bem-sucedidas | > 99 % |

Os valores chegam bem para o cenário previsto (algumas centenas de leituras diárias em ambiente escolar). Nada indica que o sistema esteja perto de limites de desempenho.

---

# 9. Conclusões

## 9.1. Síntese do trabalho realizado

O projeto cumpriu os oito objetivos específicos fixados em 1.3. Ficou a funcionar um sistema de gestão de presenças por cartão RFID, integrado com uma aplicação *web* multi-perfil (aluno, professor, administrador), base de dados relacional, autenticação segura, *feedback* visual imediato no dispositivo (LEDs) e registo completo de eventos.

Tecnicamente, o trabalho obrigou-me a dominar — em muitos casos, aprendendo pelo caminho — várias tecnologias: Arduino e C++ para embebidos, SPI e ISO 14443, PHP moderno com consultas preparadas, MySQL com índices e transações, HTML5/CSS3 semântico e responsivo, JavaScript assíncrono com `fetch()`, e bibliotecas externas como *Chart.js*. Esta diversidade é, provavelmente, a maior virtude formativa do projeto.

## 9.2. Limitações

Há limitações que importa reconhecer. A principal é correr num servidor local (WAMP), não em produção com HTTPS e domínio — uma implantação real exigiria certificado SSL e um servidor acessível pela rede da escola. A segunda é o número reduzido de cartões físicos para testes; deu para validação funcional, mas um teste de carga com dezenas de cartões em simultâneo seria desejável. A terceira é o pressuposto de que a Arduino está sempre ligada à mesma rede Wi-Fi — mudar de rede exige reprogramar.

## 9.3. Trabalho futuro

Identifiquei várias extensões naturais para uma fase seguinte:

- Exportação de presenças para PDF ou CSV.
- Filtros avançados por turma e por período na lista de alunos.
- Painel próprio para o diretor de turma, agregando as presenças da sua turma.
- Notificações automáticas aos encarregados de educação em caso de ausência.
- Vários leitores RFID, em pontos distintos da escola.
- Relatório mensal de presenças por aluno.

## 9.4. Balanço pessoal

O projeto foi um desafio bem maior do que qualquer trabalho anterior do curso, tanto pela amplitude do domínio técnico como pela necessidade de casar componentes físicos (Arduino, cartões, LEDs) com componentes lógicos (base de dados, *frontend*, sessões). O resultado — um sistema a funcionar, testado e documentado — é a prova, para mim e para quem o ler, de que essa articulação é possível no âmbito de uma PAP.

---

# 10. Bibliografia

1. Rankl, W., & Effing, W. (2010). *Smart Card Handbook* (4ª ed.). John Wiley & Sons.
2. NXP Semiconductors. (2016). *MFRC522 — Standard performance MIFARE and NTAG frontend*. Datasheet.
3. International Organization for Standardization. (2018). *ISO/IEC 14443: Identification cards — Contactless integrated circuit cards — Proximity cards*.
4. Tatroe, K., & MacIntyre, P. (2020). *Programming PHP* (4ª ed.). OReilly Media.
5. DuBois, P. (2013). *MySQL* (5ª ed.). Addison-Wesley.
6. OWASP Foundation. (2023). *OWASP Top 10: Web Application Security Risks*. Obtido em outubro de 2025.
7. Margolis, M. (2020). *Arduino Cookbook* (3ª ed.). OReilly Media.
8. Fielding, R. et al. (2014). *RFC 7230–7235 — Hypertext Transfer Protocol (HTTP/1.1)*. IETF.
9. Documentação oficial do *Chart.js*. https://www.chartjs.org/docs/
10. Documentação oficial do Arduino. https://docs.arduino.cc/

---

# 11. Anexos

## Anexo A — Excertos de código

Os ficheiros integrais estão no repositório local. Os excertos mais relevantes apareceram ao longo dos capítulos 5 e 6.

## Anexo B — Diagrama entidade-relação

(Diagrama omitido nesta versão textual; consultar a figura 5.1 em `docs/er.png`.)

## Anexo C — Fotografias do *hardware*

(Fotografias omitidas nesta versão textual; consultar a pasta `docs/fotos/`.)

## Anexo D — Capturas de ecrã

(Capturas omitidas nesta versão textual; consultar a pasta `docs/screens/`.)

---

*Fim do documento.*
