---
name: PAP merge plan
description: User is finishing old PAP, then will merge PAP + PAP_NEW into one project
type: project
---

User is actively developing the old PAP (`c:\wamp64\www\PAP`) as their working base.
PAP_NEW (`c:\wamp64\www\PAP_NEW`) is the fully refactored version built in previous sessions.

**Why:** PAP needs to be finished first; then the two will be merged.

**How to apply:** When asked for help, work primarily in `c:\wamp64\www\PAP`.
When the merge phase starts — audit both projects side by side before touching anything.

**Merge checklist (for when the time comes):**
- Compare DB schemas (PAP vs PAP_NEW schema_full.sql)
- Check all pages: Aluno.php → student.php, Professor.php → prof.php
- Verify CSS/JS versions are consistent
- Test auth flow, RFID API, all roles
- Run seed_data.sql on clean DB
