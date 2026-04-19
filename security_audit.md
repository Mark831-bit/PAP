# Relatório de Auditoria de Segurança — PAP

**Data:** 2026-04-18
**Âmbito:** `c:/wamp64/www/PAP` — aplicação *web* PHP/MySQL + leitor Arduino RFID
**Metodologia:** revisão manual de todos os *endpoints* `api/`, páginas `project/public/` e ficheiros de configuração, por classes de vulnerabilidade OWASP.

Foram identificadas **14 vulnerabilidades**: 4 Critical, 4 High, 6 Medium/Low. As de prioridade Critical foram corrigidas em 2026-04-18 (ver commit de segurança e respectivos ficheiros).

---

## CRITICAL

### C1 — `api/admin_update_card.php` sem verificação de sessão nem de role
**Ficheiro:** `api/admin_update_card.php:1-6`
**Problema:** O *endpoint* serve dois *actions* (`list` e `update`) e não inclui `config/session.php` nem verifica `$_SESSION['role'] === 'admin'`. Qualquer utilizador — incluindo um não autenticado — pode invocar `?action=list` para obter todos os utilizadores (incluindo os seus `UID` RFID), ou `POST ?action=update` para alterar nome, turma, UID e palavra-passe de qualquer utilizador.
**Correcção:** Incluir `session.php` e rejeitar com `403` se a sessão não existir ou o *role* não for `admin`. *(Corrigido 2026-04-18.)*

### C2 — `API_KEY` do Arduino em plaintext no repositório
**Ficheiro:** `api/push.php:10` — `$API_KEY = "pds_arduino_2026";`
**Problema:** Quem aceder ao repositório Git (ou ao código-fonte do servidor) obtém a chave e pode submeter leituras RFID falsas, registando presenças impostores.
**Correcção:** Mover para `config/secrets.php` fora do versionamento; adicionar ao `.gitignore`. Usar `hash_equals()` na comparação (tempo constante, resistente a *timing attacks*). *(Corrigido 2026-04-18.)*

### C3 — Mensagens de exceção devolvidas ao cliente
**Ficheiros:**
- `api/register.php:98` — `'error' => $e->getMessage()`
- `api/save_login.php:102` — `'error' => $e->getMessage()`
- `api/admin_add_card.php:122` — `'error' => $e->getMessage()`

**Problema:** Exceções `PDOException` e `mysqli` incluem nomes de tabelas, colunas, *SQL state*, *stack trace* parcial. Qualquer erro (colisão de *unique key*, tipo errado) expõe estrutura interna da BD ao utilizador — informação útil para um atacante em fase de reconhecimento.
**Correcção:** Capturar a excepção, registar em `log_event("ERROR", ...)` e devolver mensagem genérica (`"Erro interno do servidor."`). *(Corrigido 2026-04-18.)*

### C4 — Nome de tabela interpolado dinamicamente
**Ficheiro:** `api/push.php:148-149`
```php
$table = ($role === 'Aluno') ? 'alunos' : 'professores';
$qUpd = $mysqli->prepare("UPDATE `$table` SET `Presença` = ? WHERE login = ?");
```
**Problema:** O valor depende de `$role`, que vem da BD, mas a tabela é interpolada directamente na *string* da *query*. Se uma migração futura introduzir um novo *role* ou se `$role` vier corrompido, pode gerar SQL inválido ou exploitável. Não é uma injecção acessível *hoje*, mas é uma fragilidade estrutural.
**Correcção:** *Whitelist* explícito: `if (!in_array($table, ['alunos','professores'], true)) { exit('invalid role'); }`. *(Corrigido 2026-04-18.)*

---

## HIGH

### H1 — Ausência de CSRF em todos os *endpoints* POST
**Ficheiros afectados:** `api/auth.php`, `api/register.php`, `api/save_login.php`, `api/admin_add_card.php`, `api/admin_update_card.php`, `api/create_teste.php`, `api/admin_alunos.php` (`toggle_block`, `delete`), `api/admin_testes.php` (`delete`).
**Problema:** Um administrador autenticado que carregue num *link* malicioso (ou abra uma página externa comprometida) pode, sem o saber, disparar um POST para `admin_update_card.php?action=update` com *payload* do atacante — eliminação de alunos, mudança de palavras-passe, atribuição de novos UIDs.
**Correcção:** Portar o sistema de *tokens* CSRF do projecto `PAP_NEW`:
- `csrf_token()`, `csrf_field()`, `csrf_check()` em `config/session.php`;
- `<?= csrf_field() ?>` nos formulários HTML;
- `<meta name="csrf-token" content="<?= csrf_token() ?>">` nas páginas com `fetch()`;
- Cabeçalho `X-CSRF-Token` nos *fetch* JSON;
- `csrf_check()` no início de cada *endpoint* POST.

*(Corrigido 2026-04-18.)*

### H2 — Session fixation possível em `api/auth.php`
**Ficheiro:** `api/auth.php:37-39`
**Problema:** Após `password_verify()` bem-sucedido, a sessão recebe as variáveis `$_SESSION['login']/['role']/['user_id']` sem regenerar o `session_id`. Um atacante que consiga definir o *cookie* `PHPSESSID` do utilizador *antes* do *login* (*e.g.* via XSS num subdomínio, ou em quiosque público) obtém uma sessão autenticada depois do *login* legítimo da vítima.
**Correcção:** `session_regenerate_id(true);` imediatamente após o `password_verify()` positivo. *(Corrigido 2026-04-18.)*

### H3 — Cookie de sessão sem *flags* de endurecimento
**Ficheiro:** `config/session.php:2` — `session_start()` isolado, sem `session_set_cookie_params()`.
**Problema:** O *cookie* `PHPSESSID` é emitido sem `HttpOnly` e sem `SameSite=Strict`. Sem `HttpOnly`, *scripts* podem ler o *cookie* (agravamento em caso de XSS). Sem `SameSite`, o *browser* envia o *cookie* em pedidos *cross-site* (agravamento de CSRF).
**Correcção:** Configurar antes de `session_start()`:
```php
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/',
    'secure' => false,   // true em produção (HTTPS)
    'httponly' => true,
    'samesite' => 'Strict',
]);
```
*(Corrigido 2026-04-18.)*

### H4 — *Endpoint* `push.php` sem *rate limit* nem *IP whitelist*
**Ficheiro:** `api/push.php`
**Problema:** Mesmo corrigindo C2, a chave API é estática e partilhada. Um atacante com a chave (ou por força bruta se a rede local for comprometida) pode fabricar milhares de leituras por segundo. Não há *rate limit* por IP nem verificação de que a origem é o Arduino.
**Correcção proposta (não aplicada):** Adicionar *rate limit* — por IP ou por UID — com uma tabela `push_rate_limit` (IP, contagem, janela) ou um ficheiro em `logs/`. Opcionalmente, *IP whitelist* se a Arduino tiver IP fixo na rede da escola. **Não corrigido** — adiado para discussão (tabelas novas).

---

## MEDIUM

### M1 — XSS potencial via dados de teste
**Ficheiros:** `project/public/Aluno.php:104-113`, `project/public/Professor.php` (lista de alunos), separador *Testes* em `admin.php`.
**Problema:** O código usa `htmlspecialchars()` correctamente na exibição. O risco está na *API* de listagem (`admin_testes.php`): se retornar JSON directo para o cliente e o cliente inserir via `innerHTML` sem escapar, qualquer título malicioso (`<img src=x onerror=...>`) é executado. Verificar todos os pontos de inserção no `app.js`.
**Correcção proposta:** Usar `textContent` em vez de `innerHTML` em todos os loops que inserem dados vindos da BD. Alternativamente, escapar no lado servidor antes de serializar.

### M2 — Validação incompleta no `admin_add_card.php`
**Ficheiro:** `api/admin_add_card.php:28-42`
**Problema:** Campos obrigatórios validados, mas não há limites de comprimento. Um `nome` de 50.000 caracteres pode ser inserido e partir o *frontend*. `turma_num` validado só implicitamente via `(int)` — aceita qualquer inteiro.
**Correcção proposta:** Adicionar verificações: `if (strlen($nome) > 100) exit('nome too long');` e `if (!in_array($turmaNum, ['10','11','12'], true)) exit('invalid turma');`.

### M3 — Validação de `turma` inconsistente em `create_teste.php`
**Ficheiro:** `api/create_teste.php:27-31`
**Problema:** `$turmaNum` validado por `in_array`, mas comparação é feita com *strings* `'10','11','12'` enquanto em outros ficheiros comparamos com inteiros. Risco de aceitar `'10 '` (com espaço — já é coberto por `trim`, mas cada novo *endpoint* reinventa a roda).
**Correcção proposta:** Centralizar em `api/lib/validators.php` uma função `is_valid_turma($num, $letra)`.

### M4 — Ausência de *logging* de acções administrativas
**Ficheiros:** `api/admin_add_card.php`, `api/admin_update_card.php`, `api/admin_alunos.php`.
**Problema:** O administrador altera passwords, UIDs e apaga utilizadores, mas não há registo auditável de *quem* fez *o quê* *quando*. Sem isso, um incidente de abuso interno não deixa rasto.
**Correcção proposta:** Em cada alteração, chamar `log_event("ADMIN_ACTION", "descrição", ["admin"=>$_SESSION['login'], "target"=>$login, "fields"=>[...]])`. **Parcialmente corrigido 2026-04-18** (add/update de cartão).

### M5 — `config/db.php` com credenciais hard-coded (root, sem password)
**Ficheiro:** `config/db.php:4-5`, `config/db.php:12`
**Problema:** `user='root'`, `pass=''`. É o default do WAMP, mas se alguma vez a aplicação for exposta, um atacante tem acesso de *root* à BD.
**Correcção proposta:** Criar utilizador MySQL dedicado (`pap_app`) com *password* forte e apenas as permissões necessárias (`SELECT, INSERT, UPDATE, DELETE` em `pap.*`). Nunca `DROP`, `GRANT`, `FILE`. **Não corrigido** — tarefa de administração do WAMP, fora do *scope* do código PHP.

### M6 — Código comentado em `api/logout.php`
**Ficheiro:** `api/logout.php:1-17` (aprox.)
**Problema:** Código antigo deixado comentado reduz legibilidade e aumenta carga de manutenção. Não é *security* em si, mas confundiu-me durante a auditoria.
**Correcção proposta:** Remover linhas comentadas.

---

## LOW

### L1 — `header('Content-Type: application/json')` duplicado em `register.php`
**Ficheiro:** `api/register.php:5-9`
**Problema:** Cabeçalho definido duas vezes. Não tem impacto de segurança, mas evidencia que o código não passou por revisão.
**Correcção proposta:** Remover a duplicação.

### L2 — Mensagens de erro mistas em PT/RU/EN
**Problema:** O relatório fala em localização para PT+RU, mas o código tem mensagens em russo (`"Ошибка запроса"`), português (`"Erro..."`) e inglês (`"Login failed"`). Isto é mais UX do que *security*, mas dificulta o logging consistente.
**Correcção proposta:** Centralizar mensagens numa constante ou num ficheiro `lang/*.json` (está previsto para a fase de i18n).

---

## Resumo

| Severidade | Total | Corrigidas |
|------------|-------|------------|
| Critical   | 4     | 4          |
| High       | 4     | 3          |
| Medium     | 6     | 1 (parcial)|
| Low        | 2     | 0          |

Correcções aplicadas em 2026-04-18: C1, C2, C3, C4, H1, H2, H3, e *logging* parcial de acções administrativas (M4). Adiadas: H4 (*rate limit*), M1 (XSS em `innerHTML`), M2, M3, M5 (MySQL user), M6, L1, L2.

Próximos passos sugeridos ao administrador do sistema:
1. Criar utilizador MySQL dedicado (M5).
2. Revisar todos os `innerHTML` em `app.js` (M1).
3. Implementar *rate limit* no `push.php` (H4) — requer decisão sobre estrutura de dados (tabela nova ou ficheiro).
4. Limpar código comentado em `logout.php` (M6).
