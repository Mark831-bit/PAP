---
name: PAP security hardening (2026-04-18)
description: CSRF + secrets externalization + session hardening added to PAP — pattern new endpoints must follow
type: project
originSessionId: 9431c481-a4fe-4ff6-bb8d-6e6abd8c8934
---
В ночь с 2026-04-17 на 2026-04-18 в PAP добавлена security-обвязка, портированная из PAP_NEW. Полный отчёт аудита — в `c:/wamp64/www/PAP/security_audit.md`.

**Why:** пользователь попросил «защиту из PAP_NEW» + аудит. Закрыты 4 critical (orphan admin_update_card без auth, хардкод API_KEY, утечка `$e->getMessage()`), 4 high (CSRF везде, session fixation).

**How to apply** — при добавлении новых endpoints/форм:

1. **Новый POST endpoint в `api/*.php`** — обязательно:
   - `require_once __DIR__ . '/../config/session.php';`
   - Проверка роли (если admin-only или role-specific) — 403 на несоответствие
   - `csrf_check();` после проверки роли
   - В `catch (Throwable $e)` — НЕ отдавать `$e->getMessage()` клиенту, логировать через `log_event("ERROR", ...)` и возвращать generic `'Erro interno do servidor.'`

2. **Новая HTML-форма с JS fetch** — читать токен через:
   ```js
   const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
   fetch(url, { method: "POST", headers: { "X-CSRF-Token": CSRF }, body: fd });
   ```
   Мета-тег `<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">` уже есть в `index.php`, `admin.php`, `Professor.php` — на новой странице нужно добавить.

3. **Новый секрет (API key, 3rd-party token)** — класть в `c:/wamp64/www/PAP/config/secrets.php` (в `.gitignore`), читать через `require_once`. Сравнивать через `hash_equals()`, не `===`.

4. **`csrf_check()` принимает токен из трёх источников**: `$_POST['_csrf']`, header `X-CSRF-Token`, JSON body `_csrf`. Работает и для FormData, и для JSON POST.

5. **Endpoints, где csrf_check() ещё НЕТ** (вне скоупа ночной работы): `admin_alunos.php`, `admin_testes.php`, `scan_uid.php`. Если будем править — добавить csrf_check() в их POST-ветки. Фронтенд уже шлёт X-CSRF-Token в эти эндпоинты, так что добавление csrf_check() не ломает UI.

6. **`session_regenerate_id(true)`** вызывается в `auth.php` после успешного login и в `register.php` после регистрации. При смене роли/привилегий в новых потоках — тоже вызывать.
