---
name: PAP Project Overview
description: PAP is a Portuguese school attendance management system using Arduino RFID + PHP + MySQL
type: project
originSessionId: 52cf77dc-d6b3-45f6-b9a0-409b15b19b54
---
PAP (Projeto de Aptidão Profissional) — school project for AEMTG (Portuguese school).

**System architecture:**
- Arduino UNO R4 WiFi + MFRC522 RFID reader → sends card UID via HTTP POST to PHP API
- PHP backend on WAMP (localhost), MySQL database `pap`
- Frontend: HTML/CSS/JS school website with role-based pages (student, teacher, admin)

**DB tables:** `alunos` (students), `professores` (teachers), both with `UID`, `Presença`, `Nome`, `Turma`/`Cargo` fields

**Roles:** student → student.php, teacher → prof.php, admin → admin.php

**Why:** Final school project (PAP) for Portuguese vocational/tech school

**How to apply:** This is a school project, likely solo dev. Prioritize simplicity and working features over enterprise patterns.
