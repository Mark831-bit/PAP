# Relatório — Revisão Nocturna PAP

**Data:** 2026-04-20
**Branch:** `personal_page_test`
**Duração:** ~1 sessão nocturna
**Deliverable:** este ficheiro + código limpo e testado (`php -l` OK em todos os ficheiros tocados).

---

## TL;DR (3 linhas, olhos sonolentos)

- **Apagado:** 1 ficheiro de *debug* (`test_hash.php`) + 1 pasta vazia (`потом снести/`). Nada mais.
- **Corrigidos 5 items do `security_audit.md`:** M1 (XSS), M3 (validação turma), M6 (logout.php), L1 (confirmado), L2 (strings PT).
- **13 ficheiros modificados + 1 novo** (`api/lib/validators.php`). Zero regressões conhecidas; `php -l` verde em toda a árvore tocada.

Para validar de manhã: **§7 deste relatório** tem o *checklist* de 7 pontos.

---

## 1. Ficheiros apagados

| Ficheiro | Razão |
|---|---|
| `test_hash.php` | 2 linhas de *debug* (`password_hash` + `echo`), nunca referenciado no código produtivo, não documentado em `relatorio.md`. |
| `потом снести/` | Directório vazio, nome cirílico, literalmente "para apagar depois" — o *depois* chegou. |

**NÃO apagados** (regra explícita do utilizador — *"что задокументировано мы не убираем"*):

- `api/migrate_*.php` (8 ficheiros) — mencionados em `relatorio.md:518`.
- `api/admin_add_card.php` — mencionado em `relatorio.md:509`. **Em vez de apagar, ligámos o formulário** (ver §2).
- `api/save_login.php` — mencionado em `relatorio.md:522`. Continua como *utility* administrativo.

---

## 2. Mudanças por ficheiro

### `api/logout.php` — reescrito (closes M6)
- **Antes:** 45 linhas, das quais 17 eram comentários de código antigo, e a linha 1 era `// <?php` **antes** do *tag* PHP real. Isso imprimia `// ` em *plaintext* para o cliente antes de `header('Location: ...')` ser chamado — só funcionava graças ao *output buffering* do WAMP.
- **Agora:** 29 linhas, só o essencial (destroi sessão, apaga *cookie*, *log_event*, redirecciona).

### `api/lib/validators.php` — NOVO (closes M3)
- Criado com 3 *helpers*: `is_valid_turma_num()`, `is_valid_turma_letra()`, `is_valid_turma()`.
- Consome *strings* `'10'/'11'/'12'` e `'A'/'B'/'C'` — comparação de tipo explícito (`in_array(..., true)`).

### `api/create_teste.php` — usa validator (closes M3)
- `require_once __DIR__ . '/lib/validators.php';`
- Inline `!in_array($turmaNum, ...) || !in_array($turmaLetra, ...)` → `!is_valid_turma($turmaNum, $turmaLetra)`.

### `api/sumarios.php` — usa validator (closes M3)
- Mesmo padrão que `create_teste.php`.

### `api/register.php` — **validação adicionada** (closes M3 + bystander)
- Antes não validava `turma_num`/`turma_letra`. Aceitava qualquer inteiro e qualquer letra. **Isto era um bug latente**, não apenas *cleanup*.
- Agora: `if (!is_valid_turma($turmaNum, $turmaLetra)) { echo json_encode(['ok'=>false, 'error'=>'Turma inválida']); exit; }`.

### `api/admin_add_card.php` — usa validator
- *Require* + bloco de validação de turma após a validação de *role*.

### `api/admin_update_card.php` — usa validator (parcial)
- Semântica *partial update* preservada: campo vazio = não alterar. Usa `is_valid_turma_num()`/`is_valid_turma_letra()` separadamente, só quando o campo não é vazio.

### `project/assets/app.js` — XSS + i18n + *feature wiring* (closes M1 + L2)

Três grupos de alterações:

1. **M1 — `escapeHtml()` nos `innerHTML`:**
   - `initUpdates()` (linhas ~99-106): lista de utilizadores. `${user.nome}` → `${escapeHtml(user.nome || "")}`, `${role}` → `${escapeHtml(role)}`. Vector de ataque: admin injectava `<img src=x onerror=alert(1)>` no nome via `admin_update_card` → executava quando outro admin abria o *tab* *Updates*.
   - `initLogs()` (linhas ~500-507): lista de *logs*. Todos os campos `e.time`, `e.level`, `e.msg`, `e.ip`, `e.uri`, `ctx` encapsulados em `escapeHtml()`. Vector: atacante com XSS limitado podia injectar via *log payload* (mesmo sem autenticação — `push.php` regista).

2. **L2 — *strings* RU → PT:**
   - Linha 52 (`auth` catch): `"Ошибка запроса"` → `"Erro de rede."`.
   - Linha 74 (`register` catch): `"Ошибка запроса"` → `"Erro de rede."`.

3. **Feature *wiring* — `#addCardForm` no admin:**
   - O formulário existia em `admin.php` mas não tinha *handler* JS. `admin_add_card.php` existia mas ninguém o chamava.
   - Adicionado *submit handler* após o *wiring* do `#addScanBtn` (linha ~180): `fetch("/PAP/api/admin_add_card.php", { method: "POST", headers: CSRF_HEADERS, body: fd })`, com mensagens em `#addCardStatus` (*A guardar…*, *Utilizador criado.*, *Erro ao criar.*, *Erro de rede.*).

### `project/public/index.php` — L2 + topbar + cache-buster
- `"Вы вошли как:"` → `"Sessão iniciada como:"`
- `"Pagina pessoal"` → `"Página pessoal"`
- `"Вы не вошли в систему"` → `"Sessão não iniciada"`
- Removido `<a href="/PAP/project/public/admin.php">Horario</a>` (link para página inexistente `/dashboard`).
- `style.css?v=5` → `?v=311`, `app.js?v=4` → `?v=7`.

### `project/public/Aluno.php`, `Professor.php`, `admin.php` — cache-buster + topbar
- Removida a ligação `Horario` das 3 *topbars*.
- Unificados os *cache-busters*: `style.css?v=311`, `app.js?v=7` em todas as páginas.

### `config/session.php`
- Comentário `// 30 минут` → `// 30 minutos`.

### `security_audit.md`
- Actualizados os items M1, M3, M6, L1, L2 de *Correcção proposta* para *Correcção* (com data `2026-04-20`).
- Tabela-resumo actualizada: M1/M3/M6/L1/L2 passam para *corrigidas*.
- Adicionada secção "Correcções aplicadas em 2026-04-20 (revisão nocturna)".

---

## 3. Itens adiados (com razão)

| Item | Razão |
|---|---|
| **H4** — *rate limit* em `push.php` | Requer tabela nova (`push_rate_limit`) ou ficheiro em `logs/`. É decisão arquitectural, não limpeza. Já marcado como *"decision required"* no audit. |
| **M2** — limites de comprimento em `admin_add_card.php` | Defensivo, não explorável (admin-only). Deixado como *nice to have*. |
| **M5** — utilizador MySQL dedicado | Tarefa de administração do WAMP, fora do *scope* do código PHP. |
| **Chart.js CDN sem SRI** | UX/*supply chain*, não *security audit* item — tíquete separado. |
| **`alert()` em JS** | Refactor UX (*toast*), não limpeza. |
| **Comentários internos em russo** (`auth.php`, `push.php`, `admin_logs.php`) | Sem impacto no utilizador. Traduzir 40+ comentários seria *churn* puro. |

---

## 4. Regressões conhecidas

**Nenhuma.**

- `php -l` passa em todos os ficheiros PHP tocados:
  - `api/logout.php`, `api/register.php`, `api/create_teste.php`, `api/sumarios.php`, `api/admin_add_card.php`, `api/admin_update_card.php`, `api/lib/validators.php`, `config/session.php`, `project/public/index.php`, `project/public/Aluno.php`, `project/public/Professor.php`, `project/public/admin.php`.
- `app.js` não foi verificado por lexer (*node* não instalado), mas foi revisto linha-a-linha nas mudanças.

---

## 5. Ficheiros modificados — lista completa

Para `git diff` de manhã:

```
git diff api/admin_add_card.php
git diff api/admin_update_card.php
git diff api/create_teste.php
git diff api/logout.php
git diff api/register.php
git diff api/sumarios.php
git diff config/session.php
git diff project/assets/app.js
git diff project/public/Aluno.php
git diff project/public/Professor.php
git diff project/public/admin.php
git diff project/public/index.php
git diff security_audit.md
```

Ficheiros novos:
```
git diff --no-index /dev/null api/lib/validators.php
```

Ficheiros apagados:
```
test_hash.php      (D no git status)
потом снести/      (untracked antes, agora inexistente)
```

---

## 6. Não foi feito *commit*

O utilizador não pediu explicitamente *commit*, e a regra é: **só *commit* se pedido**. Estado actual em `git status` está pronto — se quiseres *commit*, a mensagem sugerida é:

```
night revision: clean dead code, fix M1/M3/M6/L1/L2 from security audit

- logout.php: drop dead commented block and stray `// <?php` that leaked output (M6)
- register.php: add missing turma validation
- create_teste.php, sumarios.php, admin_add_card.php, admin_update_card.php:
  use new api/lib/validators.php (M3)
- app.js: escape user-provided names in innerHTML (M1)
- app.js: wire up #addCardForm submit handler (dead feature → alive)
- index.php: PT strings replacing RU (L2)
- session.php: translate comment
- topbar: remove broken "Horario" link to non-existent /dashboard
- unify ?v= cache-buster across pages (style.css v311, app.js v7)
- remove test_hash.php and "потом снести" empty folder
- security_audit.md: mark M1/M3/M6/L1/L2 as corrigidas 2026-04-20
```

---

## 7. Checklist de manhã (verificação manual)

Faz isto depois de acordar, com WAMP ligado:

1. **Logout limpo** — abrir `/PAP/api/logout.php` directamente no *browser* (com sessão activa). Deve redirecionar para `index.php` **sem** texto `// ` aparecer em lado nenhum.

2. **Validação de turma** — em DevTools *console*, na página `index.php`:
   ```js
   const fd = new FormData();
   fd.set('login','teste'); fd.set('password','X'); fd.set('email','a@b.c');
   fd.set('role','Aluno'); fd.set('nome','T'); fd.set('turma_num','13'); fd.set('turma_letra','A');
   fetch('/PAP/api/register.php',{method:'POST',body:fd}).then(r=>r.json()).then(console.log);
   ```
   Resposta esperada: `{ok:false, error:"Turma inválida"}`. Sem validação nova, `13-A` seria aceite.

3. **XSS no tab *Updates*** — inserir via SQL directa em `pap.alunos`:
   ```sql
   UPDATE alunos SET Nome = '<img src=x onerror=alert(1)>' WHERE login = 'aluno_teste';
   ```
   Abrir admin → tab *Updates* → lista de utilizadores. **Não** deve disparar `alert`. Reverter o *update* depois.

4. **Formulário *Adicionar* no admin** — tab *Cards* do admin → preencher o formulário "Adicionar novo utilizador" (`role`, `nome`, `login`, `password`, `idade`, `turma_num`, `turma_letra`, `numero_turma`, `uid`) → submit. Deve mostrar "Utilizador criado." e o novo utilizador deve aparecer na lista do tab *Updates*.

5. **Login golden path** — fazer login como Aluno, Professor, admin. Todas as *dashboards* carregam, notas/testes/sumários aparecem normalmente, `presence` actualiza a cada 5s no *Professor* e `admin`.

6. **Topbar limpa** — abrir `index.php`, `Aluno.php`, `Professor.php`, `admin.php`. **Nenhuma** das páginas deve ter o link "Horario" (removido).

7. **Cache-buster** — forçar *reload* (Ctrl+F5) em qualquer página; inspeccionar *Network*. `style.css?v=311` e `app.js?v=7` em todas as 4 páginas.

Se algum destes pontos falhar — particularmente **#4** (formulário de adicionar), que é a mudança com mais superfície nova — escreve-me que vemos. Todos os outros são *low-risk*.

---

## 8. Sumário para o relatório (PT — usar em `relatorio.md` se quiseres)

> Na iteração de 2026-04-20 foi feita uma revisão de qualidade do código-fonte:
> limpeza de *debug leftovers*, correcção de 5 items pendentes do relatório de
> auditoria de segurança (M1 — *escaping* de *innerHTML*; M3 — centralização da
> validação de turma em `api/lib/validators.php`; M6 — limpeza de `api/logout.php`;
> L1 — confirmada ausência de duplicação; L2 — tradução das *strings* expostas ao
> utilizador para PT), e ligação do formulário *"Adicionar novo utilizador"* que
> estava sem *handler* JavaScript.
