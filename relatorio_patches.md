# Relatório_PAP.docx — patches para aplicar em Word

Abre `Relatorio_PAP.docx` em Word e usa **Ctrl+H** (Localizar/Substituir) para cada bloco. Os textos abaixo estão em português europeu para manter coerência com o relatório.

---

## 1. Dubblicado na secção 3.3 (Ferramentas)

**LOCALIZAR** (aparece duas vezes seguidas):
```
Visual Studio Code
Editor de código principal (PHP, JavaScript, HTML, CSS)
Visual Studio Code 
Editor de código principal (PHP, JavaScript, HTML, CSS).
```

**SUBSTITUIR POR** (só uma vez):
```
Visual Studio Code
Editor de código principal (PHP, JavaScript, HTML, CSS).
```

---

## 2. Secção 7.1 — ficheiros errados

**LOCALIZAR:**
```
Ficheiros: project/public/login.php, config/session.php, config/db.php, api/login.php.
```

**SUBSTITUIR POR:**
```
Ficheiros: project/public/index.php (modal de login), config/session.php, config/db.php, api/auth.php.
```

**LOCALIZAR:**
```
O utilizador submete o formulário de login em login.php, enviando login e password por método POST. O script api/login.php recebe os dados
```

**SUBSTITUIR POR:**
```
O utilizador submete o formulário de login na modal presente em index.php, enviando login e password por método POST. O script api/auth.php recebe os dados
```

---

## 3. Secção 7.4.1 — endpoint inexistente

**LOCALIZAR:**
```
O endpoint de dados é api/admin_stats.php, que devolve um único JSON com os três blocos agregados, minimizando o número de pedidos HTTP.
```

**SUBSTITUIR POR:**
```
Os dados estatísticos são calculados diretamente em admin.php através de consultas agregadas à base de dados (COUNT, GROUP BY por data) no momento do carregamento da página, evitando pedidos HTTP adicionais.
```

---

## 4. Secção 7.6 — endpoint errado

**LOCALIZAR:**
```
api/prof_add_teste.php
```

**SUBSTITUIR POR:**
```
api/create_teste.php
```

---

## 5. Secção 6.3 — tabela `login` corrigida

**LOCALIZAR** toda a linha da tabela com `Login ... VARCHAR(50) ... PRIMARY KEY`. A tabela actual já está quase correta, mas acrescenta a linha final sobre `blocked` (se já não existir):

**ADICIONAR no final da tabela 5 (`login`), como nova linha:**

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| `blocked` | TINYINT(1) | NOT NULL, DEFAULT 0 | Indicador de bloqueio do cartão (1 = cartão bloqueado pelo administrador). |

**Logo abaixo da tabela 5, ADICIONAR este parágrafo:**

```
Nota sobre nomenclatura: no SDG original, este campo é designado por `ativo` (com semântica inversa: 1 = cartão ativo). Durante a implementação optou-se pela denominação `blocked` (1 = cartão bloqueado), por refletir de forma mais direta a natureza restritiva da ação administrativa — "bloquear um cartão" é o verbo que o administrador usa na interface. A lógica final é equivalente, mas o nome do campo acompanha a ação do utilizador e melhora a legibilidade do código.
```

---

## 6. Secção 2.5 (PHP) — acrescentar CSRF e hardening

No final da secção 2.5 (antes de passar para 2.6), **ADICIONAR** este parágrafo novo:

```
No plano da segurança aplicacional, a presente versão do sistema implementa um conjunto adicional de proteções contra ataques web comuns. A proteção contra Cross-Site Request Forgery (CSRF) é garantida por um token aleatório de 64 caracteres hexadecimais, gerado com `random_bytes(32)` e armazenado na sessão do utilizador. Este token é incluído em todos os formulários POST, quer como campo oculto `_csrf`, quer como cabeçalho HTTP `X-CSRF-Token` nos pedidos `fetch()` assíncronos, sendo verificado no servidor através da função `hash_equals()` — comparação em tempo constante, resistente a timing attacks. As cookies de sessão são configuradas com as flags `httponly=true` e `samesite=Strict`, impedindo o acesso por JavaScript e o envio em contextos cross-site. Adicionalmente, a chave de API partilhada com o Arduino foi externalizada para o ficheiro `config/secrets.php`, excluído do controlo de versões através de `.gitignore`, evitando a exposição acidental de credenciais em caso de partilha do repositório.
```

---

## 7. Secção 2.7 (Autenticação) — session_regenerate_id

No final da secção 2.7, **ADICIONAR** este parágrafo:

```
Como medida complementar contra ataques de session fixation, a função `session_regenerate_id(true)` é invocada imediatamente após a verificação bem-sucedida da palavra-passe, substituindo o identificador de sessão por um novo e invalidando qualquer valor que possa ter sido previamente fixado por um atacante. Esta técnica, amplamente recomendada pela OWASP, é particularmente relevante quando o utilizador chega à aplicação por meio de uma ligação externa suscetível de conter um `PHPSESSID` pré-definido.
```

---

## 8. Secção 7.3 (push.php) — códigos HTTP detalhados

**LOCALIZAR:**
```
3. Se não encontrado, responder { "ok": false, "reason": "unknown_or_blocked" }.
```

**SUBSTITUIR POR:**
```
3. Se a chave de API for inválida, responder HTTP 401. Se o UID não existir, responder HTTP 404. Se o cartão estiver bloqueado, responder HTTP 403.
4. Se o utilizador for válido e o cartão estiver ativo, prosseguir para o registo da leitura.
```

(ajusta a numeração dos pontos seguintes: 4→5, 5→6)

---

## 9. Secção 2.2 — LEDs (honestidade vs SDG)

No final da subsecção sobre LEDs (secção 2.2, antes de passar para 2.3), **ADICIONAR**:

```
Importa esclarecer que o SDG inicial previa a utilização de um LED RGB com padrões de piscar distintos para cada código de resposta HTTP (entrada: um flash verde; saída: dois flashes verdes; cartão bloqueado: cinco flashes vermelhos rápidos; UID não encontrado: três flashes vermelhos; etc.). Durante a implementação optou-se pela versão simplificada aqui descrita — dois LEDs binários (verde/vermelho) acesos durante 800 ms — por três razões: (i) a disponibilidade imediata dos componentes em stock, (ii) a redução do firmware necessário no Arduino, e (iii) a clareza do sinal para o utilizador final, que na prática só precisa de distinguir "sucesso" de "erro" no momento da entrada. Esta simplificação é documentada honestamente como desvio consciente relativamente à especificação inicial.
```

---

## 10. Tabela de RF (secção 4.1) — atualizar RF-20 e acrescentar novos

**LOCALIZAR a linha RF-20** e **SUBSTITUIR** a sua descrição por:

```
RF-20 | O administrador deve poder gerir notícias escolares (criar, editar, ocultar), visíveis na página principal através de um carrossel dinâmico. | Média
```

**ADICIONAR as novas linhas no final da tabela:**

```
RF-21 | O professor deve poder lançar avaliações para os alunos da sua turma, indicando matéria, tipo, nota (0–20), data e observação. | Alta
RF-22 | O aluno deve poder consultar as suas avaliações por matéria, com cálculo automático da média e gráfico de progresso. | Alta
RF-23 | O professor deve poder registar sumários de aula por turma, incluindo justificação de faltas no texto descritivo. | Média
RF-24 | O aluno e o professor devem poder gerir uma agenda pessoal de tarefas (adicionar, marcar como concluída, eliminar). | Média
RF-25 | A página do professor deve atualizar o estado de presença dos alunos em tempo real, através de polling AJAX a cada 5 segundos. | Média
```

---

## 11. Anexos (secção 11) — reestruturar

**SUBSTITUIR** toda a secção "11. Anexos" pelo seguinte:

```
11. Anexos

Anexo A — Excertos de código
  A.1. Firmware Arduino (PAP_RFID.ino) — ciclo principal e envio HTTP
  A.2. api/push.php — validação da API key, whitelist de tabela e inserção em presencas
  A.3. api/admin_alunos.php — transação de eliminação de aluno (3 tabelas)
  A.4. config/session.php — funções csrf_token() / csrf_check() e cookie hardening
  A.5. Script SQL — CREATE TABLE para as 7 tabelas principais

Anexo B — Diagrama entidade-relação (ver Figura 7)

Anexo C — Casos de uso detalhados
  Para cada UC listado na tabela 4.4 (UC-01 a UC-07), apresenta-se o fluxo principal, fluxos alternativos e condições de erro.

Anexo D — Fotografias do hardware
  Fotografias do Arduino UNO R4 WiFi com o módulo MFRC522 e os dois LEDs montados em breadboard.

Anexo E — Capturas de ecrã
  Sequência cronológica das principais interfaces: página inicial (com e sem login), modal de autenticação, página do aluno, página do professor (com formulário de marcação de teste), painel de administração nos cinco separadores (Charts, Cards, Alunos, Professores, Logs), dossier individual de aluno, modal de confirmação de eliminação.
```

(depois, em Word, preenche cada anexo com o material real — código, diagrama ER, fluxos UC, fotos e screenshots)

---

## 12. Secção 4.4 — ligação ao Anexo C

**LOCALIZAR:**
```
A descrição detalhada — com fluxos principais, fluxos alternativos e condições de erro — encontra-se no Anexo C do presente relatório.
```

Está correta depois da reestruturação acima (Anexo C passa a ser "Casos de uso detalhados"). Não alterar.

---

## 13. Secção 9.3 — trabalho futuro

Na subsecção 9.3, **SUBSTITUIR** a lista atual por esta versão expandida:

```
Várias extensões foram identificadas como naturais para uma fase seguinte:

- Gestão de refeições escolares (marcação de almoços pelos alunos e painel de consulta agregada no administrador), fora do âmbito do presente projeto por envolver processos administrativos da escola.
- Gestão de horário semanal pela interface web, com uma nova tabela `horario` e formulários para o professor editar as aulas da sua turma. Esta funcionalidade foi considerada estrategicamente relevante mas exigiria tempo de desenvolvimento adicional, pelo que se inscreve como primeira prioridade numa eventual continuação do projeto.
- Perfil individual do aluno acessível ao professor, com histórico agregado de presenças, avaliações e justificações.
- Exportação de presenças e avaliações em PDF e CSV para arquivo escolar.
- Integração com notificações automáticas aos encarregados de educação em caso de ausência não justificada.
- Possibilidade de mais do que um leitor RFID em pontos distintos da escola.
- Painel dedicado ao diretor de turma, com visão agregada da sua turma.
- Aplicação móvel nativa (iOS e Android).
```

---

## 14. Secção 9.2 — limitações (menção a CSRF adicionado)

**Depois do parágrafo actual sobre limitações**, ADICIONAR:

```
É justo mencionar que, numa fase avançada do desenvolvimento, o sistema foi submetido a uma revisão de segurança interna que identificou a ausência inicial de proteções contra CSRF em vários endpoints administrativos, bem como a presença de uma chave de API literalmente incluída no código-fonte do `push.php`. Ambas as situações foram prontamente corrigidas — com a introdução de tokens CSRF em todos os formulários POST e a externalização da chave para `config/secrets.php` — mas evidenciam a importância de submeter projetos desta natureza a auditorias contínuas, prática que um ambiente escolar pode legitimamente simular antes da entrega final.
```

---

## 15. Índice — Anexos A–D desatualizado

No **Índice geral** (início do documento, a seguir a "11. Anexos"), **LOCALIZAR**:

```
11. Anexos
    Anexo A. Excertos de código
    Anexo B. Diagrama entidade-relação
    Anexo C. Fotografias do hardware
    Anexo D. Capturas de ecrã
```

**SUBSTITUIR POR** (coerente com o patch nº 9 acima):

```
11. Anexos
    Anexo A. Excertos de código
    Anexo B. Diagrama entidade-relação
    Anexo C. Casos de uso detalhados
    Anexo D. Fotografias do hardware
    Anexo E. Capturas de ecrã
```

---

## 16. Lista de Tabelas — Tabela 11 e 12 não existem no texto

Na **Lista de Tabelas**, aparecem duas entradas que não correspondem a tabelas reais no corpo do relatório:

```
- Tabela 11 — Plano de testes funcionais
- Tabela 12 — Resultados dos testes
```

Há duas opções. **Escolher uma:**

### Opção A (mais simples) — remover da lista

Apagar ambas as linhas. A Lista de Tabelas fica com 10 entradas (Tabela 1 a Tabela 10).

### Opção B (mais completa) — criar as tabelas em §8

**B.1** Na secção **8.2 (Testes de integração)**, antes do parágrafo "Foram simuladas leituras RFID...", **ADICIONAR** a Tabela 11:

**Tabela 11 — Plano de testes funcionais**

| ID | Cenário | Pré-condição | Resultado esperado |
|---|---|---|---|
| T-01 | Leitura RFID de cartão válido e ativo | Aluno registado, cartão ativo | LED verde acende 800 ms; registo inserido em `presencas` |
| T-02 | Leitura RFID de cartão bloqueado | Admin bloqueou o cartão | LED vermelho acende; HTTP 403; sem registo em `presencas` |
| T-03 | Leitura RFID de UID não registado | UID inexistente em `login` | LED vermelho; HTTP 404 |
| T-04 | Login com credenciais válidas | Aluno/Professor/Admin existente | Redirecionamento para página da sua role; sessão iniciada |
| T-05 | Login com palavra-passe errada | Login existe, password incorreta | Permanece em index; mensagem de erro |
| T-06 | Submissão de POST sem token CSRF | Qualquer endpoint protegido | HTTP 403 `{error:"csrf_invalid"}` |
| T-07 | Admin elimina aluno | Admin autenticado, aluno existe | Aluno removido de 3 tabelas em transação; logs registam a ação |
| T-08 | Professor marca teste | Professor autenticado | Linha inserida em `testes`; alunos da turma veem o teste |

**B.2** Substituir o bloco "Métricas" de **8.4** pela **Tabela 12**:

**LOCALIZAR:**
```
Em testes efetuados na rede local da sala de aula, mediu-se:

 Métrica 
 Valor observado 
Latência média RFID → resposta HTTP 
 ≈ 180 ms 
 Tempo de carregamento do dashboard admin 
 ≈ 450 ms 
 Tempo médio de consulta do gráfico 7 dias 
 ≈ 90 ms 
 Taxa de leituras RFID bem-sucedidas 
 > 99 %
```

**SUBSTITUIR POR:**

```
Em testes efetuados na rede local da sala de aula, obtiveram-se os valores apresentados na Tabela 12.
```

**Tabela 12 — Resultados dos testes**

| Métrica | Valor observado |
|---|---|
| Latência média RFID → resposta HTTP | ≈ 180 ms |
| Tempo de carregamento do dashboard admin | ≈ 450 ms |
| Tempo médio de consulta do gráfico 7 dias | ≈ 90 ms |
| Taxa de leituras RFID bem-sucedidas | > 99 % |

---

## 17. Acrescentar secção 1.5 — Apresentação do Projeto

**Motivação.** A professora orientadora pediu que fosse incluída, no Capítulo 1, uma secção que reproduza a proposta formal do projeto (ficheiro de arranque assinado em 17 de outubro de 2025 — `Marko.docx`). Serve como referência da proposta inicial aprovada, para poder ser comparada com o que foi efetivamente entregue nos capítulos seguintes.

**Onde inserir.** Imediatamente depois da secção **1.4. Estrutura do documento** e antes do Capítulo 2 (*Enquadramento Teórico*).

**Também atualizar o Índice:** acrescentar a linha `1.5. Apresentação do Projeto` por baixo de `1.4. Estrutura do documento`.

**Conteúdo da nova secção (já redigido em [relatorio_v2.md](relatorio_v2.md) §1.5):**

- **1.5.1. Identificação** — aluno, nº processo, turma, título do projeto, orientação.
- **1.5.2. Descrição geral** — objetivo principal (acessibilidade e usabilidade), *website* com acesso *web* + RFID, criação autónoma de perfis, integração prevista com SIGE / Inovar / Classroom.
- **1.5.3. Perfis previstos** — Aluno (notícias, eventos, plataformas, horário, agenda, avaliações, cartão, gráfico de progresso) e Professor (horários, sumários, gestão de perfis de aluno, documentação, assiduidade, agenda).
- **1.5.4. Desenvolvimento do projeto** — *front-end* (HTML/CSS/JS), *back-end* (PHP + MySQL), *hardware* (Arduino + MFRC522).
- **1.5.5. Objetivos declarados** — lista de 5 pontos tal como na proposta assinada.
- **1.5.6. Recursos necessários** — recursos humanos e tecnológicos.
- Nota final — data da assinatura (17/10/2025) e remissão para o *Capítulo 9* onde se discutem limitações e o que ficou por concretizar.

**Como aplicar em Word:**

1. Coloca o cursor no final da §1.4 (*Estrutura do documento*).
2. Insere quebra de página ou parágrafo e digita `1.5. Apresentação do Projeto` com o estilo **Heading 2**.
3. Copia os parágrafos da §1.5 de `relatorio_v2.md` para o Word.
4. Usa **Heading 3** para `1.5.1.` … `1.5.6.`.
5. Atualiza o **Índice** (*Referências → Atualizar Tabela → Atualizar tabela inteira*).
6. Atualiza também a **Lista de Figuras / Tabelas** se for preciso (nesta secção não há figuras nem tabelas novas).

---

## Nota sobre hiperligação para o SDG

Em todas as ocorrências da palavra **"SDG"** no documento (há ~21), seleciona a palavra e aplica **Insert → Link → Existing File → `SDG_corrigido (2).docx`**. Alternativa mais prática: cria um **Bookmark** único chamado "SDG" no início do documento e faz todas as ligações apontarem para esse bookmark — assim, se renomeares o ficheiro, basta atualizar o bookmark uma vez.

---

## Resumo do que NÃO precisa ser alterado

- Índice geral (capítulos 1–11) — continua correto
- Lista de Abreviaturas — já inclui SDG
- Secções 1, 3, 4.2, 4.3, 5, 6 (texto), 8 (Testes) — sem alterações
- Bibliografia — inalterada
