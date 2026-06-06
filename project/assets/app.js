document.addEventListener("DOMContentLoaded", function () {
  // ── CSRF ──────────────────────────────────────────────────
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || "";
  const CSRF_HEADERS = { "X-CSRF-Token": CSRF };

  // ── HELPERS ───────────────────────────────────────────────
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => (
      { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]
    ));
  }

  function splitTurma(turma) {
    const t = (turma || "").toString().trim();
    if (t.length >= 2) return { num: t.slice(0, -1), letra: t.slice(-1).toUpperCase() };
    return { num: "", letra: "" };
  }

  async function fetchUsers() {
    const res = await fetch("/PAP/api/admin_update_card.php?action=list");
    const data = await res.json();
    if (!data.success) throw new Error("Erro ao carregar utilizadores.");
    return data.users || [];
  }

  function renderUserList(list, resultsBox, onSelect) {
    resultsBox.innerHTML = "";

    if (!list.length) {
      resultsBox.innerHTML = "<p>Nenhum resultado encontrado.</p>";
      return;
    }

    list.forEach((user) => {
      const item = document.createElement("div");
      item.className = "update-result-item";

      const role = user.tipo === "professor" ? "Professor" : "Aluno";
      const dotClass = user.tipo === "professor" ? "dot-professor" : "dot-aluno";

      item.innerHTML = `
        <div class="update-left">
          <div class="update-dot ${dotClass}"></div>
          <div>
            <div class="update-name">${escapeHtml(user.nome || "")}</div>
            <div class="update-role">${escapeHtml(role)}</div>
          </div>
        </div>
      `;

      item.addEventListener("click", () => onSelect(user));
      resultsBox.appendChild(item);
    });
  }

  // ── MODALS ────────────────────────────────────────────────
  const loginModal = document.getElementById("loginModal");
  const registerModal = document.getElementById("registerModal");
  const problemModal = document.getElementById("problemModal");

  const openLogin = document.getElementById("openLogin");
  const openRegister = document.getElementById("openRegister");
  const openProblem = document.getElementById("openProblem");

  const loginForm = document.getElementById("loginForm");
  const registerForm = document.getElementById("registerForm");
  const problemForm = document.getElementById("problemForm");

  if (openLogin && loginModal) {
    openLogin.addEventListener("click", () => loginModal.classList.remove("hidden"));
  }

  if (openRegister && registerModal) {
    openRegister.addEventListener("click", () => registerModal.classList.remove("hidden"));
  }

  if (openProblem && problemModal) {
    openProblem.addEventListener("click", (e) => {
      e.preventDefault();
      if (loginModal) loginModal.classList.add("hidden");
      problemModal.classList.remove("hidden");
    });
  }

  document.querySelectorAll(".close-modal").forEach((btn) => {
    btn.addEventListener("click", function () {
      const modal = document.getElementById(this.dataset.close);
      if (modal) modal.classList.add("hidden");
    });
  });

  [loginModal, registerModal, problemModal].forEach((modal) => {
    if (!modal) return;
    modal.addEventListener("click", (e) => {
      if (e.target === modal) modal.classList.add("hidden");
    });
  });

  if (loginForm) {
    loginForm.addEventListener("submit", async function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      const statusBox = document.getElementById("loginFormStatus");

      try {
        const res = await fetch("/PAP/api/auth.php", {
          method: "POST",
          headers: CSRF_HEADERS,
          body: formData,
        });
        const data = await res.json();

        if (data.ok) {
          if (loginModal) loginModal.classList.add("hidden");
          location.reload();
        } else if (statusBox) {
          statusBox.textContent = data.error || "Login failed";
          statusBox.style.color = "red";
        }
      } catch (err) {
        if (statusBox) {
          statusBox.textContent = "Erro de rede.";
          statusBox.style.color = "red";
        }
        console.error(err);
      }
    });
  }

  if (registerForm) {
    registerForm.addEventListener("submit", async function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      const statusBox = document.getElementById("registerStatus");

      try {
        const res = await fetch("/PAP/api/register.php", {
          method: "POST",
          headers: CSRF_HEADERS,
          body: formData,
        });
        const data = await res.json();

        if (data.ok) {
          if (registerModal) registerModal.classList.add("hidden");
          location.reload();
        } else if (statusBox) {
          statusBox.textContent = data.error || "Erro no registo";
          statusBox.style.color = "red";
        }
      } catch (err) {
        if (statusBox) {
          statusBox.textContent = "Erro de rede.";
          statusBox.style.color = "red";
        }
        console.error(err);
      }
    });
  }

  if (problemForm) {
    problemForm.addEventListener("submit", async function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      const statusBox = document.getElementById("problemStatus");

      if (statusBox) {
        statusBox.textContent = "A enviar...";
        statusBox.style.color = "#6b7280";
      }

      try {
        const res = await fetch("/PAP/api/access_problem.php", {
          method: "POST",
          headers: CSRF_HEADERS,
          body: formData,
        });
        const data = await res.json();

        if (data.ok) {
          problemForm.reset();
          if (statusBox) {
            statusBox.textContent = "Mensagem enviada. Obrigado!";
            statusBox.style.color = "green";
          }
          setTimeout(() => {
            if (problemModal) problemModal.classList.add("hidden");
            if (statusBox) statusBox.textContent = "";
          }, 1500);
        } else if (statusBox) {
          statusBox.textContent = data.error || "Erro ao enviar.";
          statusBox.style.color = "red";
        }
      } catch (err) {
        if (statusBox) {
          statusBox.textContent = "Erro de rede.";
          statusBox.style.color = "red";
        }
        console.error(err);
      }
    });
  }

  // ── RFID SCAN ────────────────────────────────────────────
  let scanInterval = null;

  function stopScan(btn, statusEl, msg, color) {
    clearInterval(scanInterval);
    scanInterval = null;
    btn.dataset.scanning = "0";
    btn.textContent = "Ler cartão";
    if (statusEl) {
      statusEl.textContent = msg;
      statusEl.style.color = color;
    }
  }

  function cancelScan(btn, statusEl) {
    fetch("/PAP/api/scan_uid.php?action=cancel").catch(() => {});
    stopScan(btn, statusEl, "Scan cancelado.", "#6b7280");
  }

  function startScan(uidInput, btn, statusEl) {
    if (btn.dataset.scanning === "1") {
      cancelScan(btn, statusEl);
      return;
    }

    fetch("/PAP/api/scan_uid.php?action=start")
      .then((r) => r.json())
      .then((data) => {
        if (!data.ok) return;

        btn.dataset.scanning = "1";
        btn.textContent = "Cancelar";

        if (statusEl) {
          statusEl.textContent = "Aguardando cartão...";
          statusEl.style.color = "#6b7280";
        }

        scanInterval = setInterval(async () => {
          try {
            const res = await fetch("/PAP/api/scan_uid.php?action=poll");
            const poll = await res.json();

            if (poll.state === "ready") {
              uidInput.value = poll.uid;
              stopScan(btn, statusEl, "UID lido: " + poll.uid, "#15803d");
            } else if (poll.state === "timeout") {
              stopScan(btn, statusEl, "Tempo expirado.", "#b91c1c");
            }
          } catch {
            stopScan(btn, statusEl, "Erro de ligação.", "#b91c1c");
          }
        }, 1500);
      })
      .catch(() => {
        if (statusEl) {
          statusEl.textContent = "Erro ao iniciar scan.";
          statusEl.style.color = "#b91c1c";
        }
      });
  }

  const addScanBtn = document.getElementById("addScanBtn");
  const addScanStatus = document.getElementById("addScanStatus");
  const addUid = document.getElementById("addUid");

  if (addScanBtn && addUid) {
    addScanBtn.addEventListener("click", () => startScan(addUid, addScanBtn, addScanStatus));
  }

  const addCardForm = document.getElementById("addCardForm");
  const addCardStatus = document.getElementById("addCardStatus");

  if (addCardForm) {
    // ── Toggle Aluno-only / Professor-only fields by selected role ──
    function syncAddCardRole() {
      const role = addCardForm.querySelector('input[name="role"]:checked')?.value || "Aluno";
      const isProf = role === "Professor";
      addCardForm.querySelectorAll("[data-aluno-only]").forEach(el => el.style.display = isProf ? "none" : "");
      addCardForm.querySelectorAll("[data-professor-only]").forEach(el => el.style.display = isProf ? "" : "none");
    }
    addCardForm.querySelectorAll('input[name="role"]').forEach(r => r.addEventListener("change", syncAddCardRole));
    syncAddCardRole();

    addCardForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      if (addCardStatus) {
        addCardStatus.textContent = "A guardar...";
        addCardStatus.style.color = "#6b7280";
      }

      const fd = new FormData(addCardForm);

      try {
        const res = await fetch("/PAP/api/admin_add_card.php", {
          method: "POST",
          headers: CSRF_HEADERS,
          body: fd,
        });
        const data = await res.json();

        if (data.ok) {
          if (addCardStatus) {
            addCardStatus.textContent = "Utilizador criado.";
            addCardStatus.style.color = "#10b981";
          }
          addCardForm.reset();
        } else if (addCardStatus) {
          addCardStatus.textContent = data.error || "Erro ao criar.";
          addCardStatus.style.color = "#ef4444";
        }
      } catch {
        if (addCardStatus) {
          addCardStatus.textContent = "Erro de rede.";
          addCardStatus.style.color = "#ef4444";
        }
      }
    });
  }

  const updateScanBtn = document.getElementById("updateScanBtn");
  const updateScanStatus = document.getElementById("updateScanStatus");

  // ── ADMIN TABS ───────────────────────────────────────────
  document.querySelectorAll(".admin-tab").forEach((tab) => {
    tab.addEventListener("click", () => {
      document.querySelectorAll(".admin-tab").forEach((t) => t.classList.remove("active"));
      document.querySelectorAll(".admin-tab-content").forEach((c) => c.classList.remove("active"));

      tab.classList.add("active");
      const content = document.getElementById("tab-" + tab.dataset.tab);
      if (content) content.classList.add("active");

      if (tab.dataset.tab === "logs") loadLogs();
      if (tab.dataset.tab === "charts") initChart();
      if (tab.dataset.tab === "alunos") loadAlunos();
      if (tab.dataset.tab === "professores") loadProfessores();
      if (tab.dataset.tab === "noticias") loadNoticias();
      if (tab.dataset.tab === "suporte") loadSuporte();
    });
  });

  // ── SUB-TABS (Alunos: List / Tests) ──────────────────────
  document.querySelectorAll(".subtab").forEach((sub) => {
    sub.addEventListener("click", () => {
      const group = sub.parentElement;
      group.querySelectorAll(".subtab").forEach((s) => s.classList.remove("active"));
      document.querySelectorAll(".subtab-content").forEach((c) => c.classList.remove("active"));

      sub.classList.add("active");
      const content = document.getElementById("subtab-" + sub.dataset.subtab);
      if (content) content.classList.add("active");

      if (sub.dataset.subtab === "alunos-tests") loadTestes();
    });
  });

  // ── CONFIRM MODAL ────────────────────────────────────────
  const modal = document.getElementById("confirmModal");
  const modalTitle = document.getElementById("confirmTitle");
  const modalText = document.getElementById("confirmText");
  const modalOk = document.getElementById("confirmOk");
  const modalCancel = document.getElementById("confirmCancel");
  let modalCallback = null;

  function showConfirm(title, text, onOk) {
    if (!modal) return;
    modalTitle.textContent = title;
    modalText.textContent = text;
    modalCallback = onOk;
    modal.style.display = "flex";
  }

  if (modalCancel) {
    modalCancel.addEventListener("click", () => {
      modal.style.display = "none";
      modalCallback = null;
    });
  }

  if (modalOk) {
    modalOk.addEventListener("click", () => {
      modal.style.display = "none";
      if (modalCallback) modalCallback();
      modalCallback = null;
    });
  }

  // ── ALUNOS list + dossier ────────────────────────────────
  const alunosList = document.getElementById("alunos-list");
  const alunosSearch = document.getElementById("alunosSearch");
  const alunosFilterNum = document.getElementById("alunosFilterNum");
  const alunosFilterLetra = document.getElementById("alunosFilterLetra");
  const dossier = document.getElementById("alunoDossier");
  const dossierClose = document.getElementById("dossierClose");

  let allAlunos = [];
  let currentAlunoLogin = null;

  async function loadAlunos() {
    if (!alunosList) return;
    alunosList.innerHTML = '<p class="logs-empty">A carregar...</p>';

    try {
      const res = await fetch("/PAP/api/admin_alunos.php?action=list");
      const data = await res.json();

      if (!data.ok) throw new Error();

      allAlunos = data.alunos || [];
      renderAlunosList();
    } catch (err) {
      console.error("Erro alunos:", err);
      alunosList.innerHTML = '<p class="logs-empty">Erro ao carregar.</p>';
    }
  }

  function renderAlunosList() {
    const q = (alunosSearch?.value || "").toLowerCase().trim();
    const turmaNum = (alunosFilterNum?.value || "").trim();
    const turmaLetra = (alunosFilterLetra?.value || "").trim().toUpperCase();

    const filtered = allAlunos.filter((a) => {
      const nome = (a.nome || "").toLowerCase();
      const login = (a.login || "").toLowerCase();
      const turma = (a.turma || "").toString().trim().toUpperCase();

      const matchesSearch = !q || nome.includes(q) || login.includes(q);
      const matchesNum = !turmaNum || turma.startsWith(turmaNum);
      const matchesLetra = !turmaLetra || turma.endsWith(turmaLetra);

      return matchesSearch && matchesNum && matchesLetra;
    });

    if (!filtered.length) {
      alunosList.innerHTML = '<p class="logs-empty">Nenhum aluno encontrado.</p>';
      return;
    }

    alunosList.innerHTML = filtered.map((a) => `
      <div class="aluno-row" data-login="${a.login}">
        <span class="scan-dot ${Number(a.presenca) === 1 ? "present" : "absent"}"></span>
        <div class="scan-info">
          <div class="scan-name">${escapeHtml(a.nome || "—")}</div>
          <div class="scan-meta">Turma ${escapeHtml(a.turma || "")} • ${escapeHtml(a.login || "")}</div>
        </div>
        ${Number(a.blocked) === 1 ? '<span class="badge-blocked">Bloqueado</span>' : ""}
      </div>
    `).join("");

    alunosList.querySelectorAll(".aluno-row").forEach((row) => {
      row.addEventListener("click", () => openDossier(row.dataset.login));
    });
  }

  if (alunosSearch) alunosSearch.addEventListener("input", renderAlunosList);
  if (alunosFilterNum) alunosFilterNum.addEventListener("change", renderAlunosList);
  if (alunosFilterLetra) alunosFilterLetra.addEventListener("change", renderAlunosList);

  async function openDossier(login) {
    try {
      const res = await fetch("/PAP/api/admin_alunos.php?action=get&login=" + encodeURIComponent(login));
      const data = await res.json();

      if (!data.ok) throw new Error();

      const a = data.aluno;
      currentAlunoLogin = a.login;

      document.getElementById("dossierNome").textContent = a.nome || "—";
      document.getElementById("dossierLogin").textContent = a.login || "—";
      document.getElementById("dossierIdade").textContent = a.data_nascimento || "—";
      document.getElementById("dossierTurma").textContent = a.turma || "—";
      document.getElementById("dossierUid").textContent = a.uid || "—";
      document.getElementById("dossierPresenca").textContent = Number(a.presenca) === 1 ? "Presente" : "Falta";
      document.getElementById("dossierBlocked").textContent = Number(a.blocked) === 1 ? "Bloqueado" : "Ativo";

      const btnBlockCard = document.getElementById("btnBlockCard");
      if (btnBlockCard) {
        btnBlockCard.textContent = Number(a.blocked) === 1 ? "Desbloquear cartão" : "Bloquear cartão";
      }

      dossier.style.display = "flex";
      dossier.scrollIntoView({ behavior: "smooth", block: "start" });
    } catch (err) {
      console.error("Erro ao carregar aluno:", err);
      alert("Erro ao carregar aluno.");
    }
  }

  if (dossierClose) {
    dossierClose.addEventListener("click", () => {
      dossier.style.display = "none";
      currentAlunoLogin = null;
    });
  }

  const btnBlock = document.getElementById("btnBlockCard");
  const btnDelete = document.getElementById("btnDeleteAluno");

  if (btnBlock) {
    btnBlock.addEventListener("click", async () => {
      if (!currentAlunoLogin) return;

      const fd = new FormData();
      fd.append("login", currentAlunoLogin);

      try {
        const res = await fetch("/PAP/api/admin_alunos.php?action=toggle_block", {
          method: "POST",
          headers: CSRF_HEADERS,
          body: fd,
        });
        const data = await res.json();

        if (!data.ok) throw new Error();

        await loadAlunos();
        openDossier(currentAlunoLogin);
      } catch {
        alert("Erro.");
      }
    });
  }

  if (btnDelete) {
    btnDelete.addEventListener("click", () => {
      if (!currentAlunoLogin) return;

      showConfirm(
        "Eliminar aluno",
        `Tens a certeza? Todos os dados do aluno "${currentAlunoLogin}" serão apagados.`,
        async () => {
          const fd = new FormData();
          fd.append("login", currentAlunoLogin);

          try {
            const res = await fetch("/PAP/api/admin_alunos.php?action=delete", {
              method: "POST",
              headers: CSRF_HEADERS,
              body: fd,
            });
            const data = await res.json();

            if (!data.ok) throw new Error();

            dossier.style.display = "none";
            currentAlunoLogin = null;
            await loadAlunos();
          } catch {
            alert("Erro ao eliminar.");
          }
        }
      );
    });
  }

  // ── PROFESSORES list + dossier ───────────────────────────
  const professoresList = document.getElementById("professores-list");
  const professoresSearch = document.getElementById("professoresSearch");
  const professorDossier = document.getElementById("professorDossier");
  const professorClose = document.getElementById("professorClose");

  let allProfessores = [];
  let currentProfessorLogin = null;

  async function loadProfessores() {
    if (!professoresList) return;

    professoresList.innerHTML = '<p class="logs-empty">A carregar...</p>';

    try {
      const res = await fetch("/PAP/api/admin_professores.php?action=list");
      const data = await res.json();

      if (!data.ok) throw new Error();

      allProfessores = data.professores || [];
      renderProfessoresList();
    } catch (err) {
      console.error("Erro professores:", err);
      professoresList.innerHTML = '<p class="logs-empty">Erro ao carregar.</p>';
    }
  }

  function renderProfessoresList() {
    const q = (professoresSearch?.value || "").toLowerCase().trim();

    const filtered = allProfessores.filter((p) => {
      const nome = (p.nome || "").toLowerCase();
      const login = (p.login || "").toLowerCase();
      return !q || nome.includes(q) || login.includes(q);
    });

    if (!filtered.length) {
      professoresList.innerHTML = '<p class="logs-empty">Nenhum professor encontrado.</p>';
      return;
    }

    professoresList.innerHTML = filtered.map((p) => `
      <div class="aluno-row" data-login="${p.login}">
        <span class="scan-dot present"></span>
        <div class="scan-info">
          <div class="scan-name">${escapeHtml(p.nome || "—")}</div>
          <div class="scan-meta">${escapeHtml(p.materia || "—")} • ${escapeHtml(p.login || "")}</div>
        </div>
      </div>
    `).join("");

    professoresList.querySelectorAll(".aluno-row").forEach((row) => {
      row.addEventListener("click", () => openProfessorDossier(row.dataset.login));
    });
  }

  async function openProfessorDossier(login) {
    try {
      const res = await fetch("/PAP/api/admin_professores.php?action=get&login=" + encodeURIComponent(login));
      const data = await res.json();

      if (!data.ok) throw new Error();

      const p = data.professor;
      currentProfessorLogin = p.login;

      document.getElementById("professorNome").textContent = p.nome || "—";
      document.getElementById("professorLogin").textContent = p.login || "—";
      document.getElementById("professorTurma").textContent = p.turma || "—";
      document.getElementById("professorGabinete").textContent = p.gabinete || "—";
      document.getElementById("professorCargo").textContent = p.cargo || "—";
      document.getElementById("professorMateria").textContent = p.materia || "—";
      document.getElementById("professorHorario").textContent = p.horario || "—";

      if (professorDossier) {
        const btnBlockProfessor = document.getElementById("btnBlockProfessor");
        if (btnBlockProfessor) {
          btnBlockProfessor.textContent = Number(p.blocked) === 1 ? "Desbloquear conta" : "Bloquear conta";
        }
        professorDossier.style.display = "flex";
        professorDossier.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    } catch (err) {
      console.error("Erro professor dossier:", err);
      alert("Erro ao carregar professor.");
    }
  }

  if (professoresSearch) {
    professoresSearch.addEventListener("input", renderProfessoresList);
  }

 if (professorClose) {
  professorClose.addEventListener("click", () => {
    if (professorDossier) professorDossier.style.display = "none";
    currentProfessorLogin = null;
  });
}

const btnBlockProfessor = document.getElementById("btnBlockProfessor");
const btnDeleteProfessor = document.getElementById("btnDeleteProfessor");

if (btnBlockProfessor) {
  btnBlockProfessor.addEventListener("click", async () => {
    if (!currentProfessorLogin) return;

    const fd = new FormData();
    fd.append("login", currentProfessorLogin);

    try {
      const res = await fetch("/PAP/api/admin_professores.php?action=toggle_block", {
        method: "POST",
        headers: CSRF_HEADERS,
        body: fd
      });

      const data = await res.json();
      if (!data.ok) throw new Error();

      await loadProfessores();
      openProfessorDossier(currentProfessorLogin);
    } catch (err) {
      console.error(err);
      alert("Erro ao bloquear professor.");
    }
  });
}

if (btnDeleteProfessor) {
  btnDeleteProfessor.addEventListener("click", () => {
    if (!currentProfessorLogin) return;

    showConfirm(
      "Eliminar professor",
      `Tens a certeza? O professor "${currentProfessorLogin}" será eliminado.`,
      async () => {
        const fd = new FormData();
        fd.append("login", currentProfessorLogin);

        try {
          const res = await fetch("/PAP/api/admin_professores.php?action=delete", {
            method: "POST",
            headers: CSRF_HEADERS,
            body: fd
          });

          const data = await res.json();
          if (!data.ok) throw new Error();

          if (professorDossier) professorDossier.style.display = "none";
          currentProfessorLogin = null;
          await loadProfessores();
        } catch (err) {
          console.error(err);
          alert("Erro ao eliminar professor.");
        }
      }
    );
  });
}

  // ── TESTES list + delete ─────────────────────────────────
  const testesList = document.getElementById("testes-list");
  const testesFilterNum = document.getElementById("testesFilterNum");
  const testesFilterLet = document.getElementById("testesFilterLetra");

  async function loadTestes() {
    if (!testesList) return;
    testesList.innerHTML = '<p class="logs-empty">A carregar...</p>';

    const params = new URLSearchParams({ action: "list" });
    if (testesFilterNum?.value) params.append("turma_num", testesFilterNum.value);
    if (testesFilterLet?.value) params.append("turma_letra", testesFilterLet.value);

    try {
      const res = await fetch("/PAP/api/admin_testes.php?" + params.toString());
      const data = await res.json();
      if (!data.ok) throw new Error();
      renderTestes(data.testes);
    } catch {
      testesList.innerHTML = '<p class="logs-empty">Erro ao carregar.</p>';
    }
  }

  function renderTestes(testes) {
    if (testes.length === 0) {
      testesList.innerHTML = '<p class="logs-empty">Nenhum teste encontrado.</p>';
      return;
    }

    testesList.innerHTML = testes.map((t) => `
      <div class="teste-row">
        <div class="teste-info">
          <div class="teste-title">${escapeHtml(t.titulo)} <span class="teste-turma">${t.turma_num}${escapeHtml(t.turma_letra)}</span></div>
          <div class="teste-meta">
            ${escapeHtml(t.materia || "—")} • ${escapeHtml(t.data_teste)}
            ${t.professor_nome ? " • " + escapeHtml(t.professor_nome) : ""}
          </div>
          ${t.descricao ? `<div class="teste-desc">${escapeHtml(t.descricao)}</div>` : ""}
        </div>
        <button class="danger-btn teste-del" data-id="${t.id}" data-title="${escapeHtml(t.titulo)}">Eliminar</button>
      </div>
    `).join("");

    testesList.querySelectorAll(".teste-del").forEach((btn) => {
      btn.addEventListener("click", () => {
        const id = btn.dataset.id;
        const title = btn.dataset.title;

        showConfirm(
          "Eliminar teste",
          `Eliminar o teste "${title}"? Esta ação é irreversível.`,
          async () => {
            const fd = new FormData();
            fd.append("id", id);

            try {
              const res = await fetch("/PAP/api/admin_testes.php?action=delete", {
                method: "POST",
                headers: CSRF_HEADERS,
                body: fd,
              });
              const data = await res.json();
              if (!data.ok) throw new Error();
              await loadTestes();
            } catch {
              alert("Erro ao eliminar.");
            }
          }
        );
      });
    });
  }

  if (testesFilterNum) testesFilterNum.addEventListener("change", loadTestes);
  if (testesFilterLet) testesFilterLet.addEventListener("change", loadTestes);

  // ── CHARTS block (presenças com navegação semanal) ────────
  let chartInstance = null;
  let weekOffset    = 0;

  const weekPrev  = document.getElementById("weekPrev");
  const weekNext  = document.getElementById("weekNext");
  const weekRange = document.getElementById("weekRange");

  function renderChart(labels, values) {
    const canvas = document.getElementById("chartPresencas");
    if (!canvas || !window.Chart) return;

    if (chartInstance) {
      chartInstance.data.labels = labels;
      chartInstance.data.datasets[0].data = values;
      chartInstance.update();
      return;
    }

    chartInstance = new Chart(canvas, {
      type: "bar",
      data: {
        labels,
        datasets: [{
          label: "Presentes",
          data: values,
          backgroundColor: "#3b82f6",
          borderRadius: 6,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
      },
    });
  }

  async function loadWeek() {
    if (weekRange) weekRange.textContent = "A carregar...";
    try {
      const res  = await fetch("/PAP/api/admin_charts.php?week_offset=" + weekOffset);
      const data = await res.json();
      if (!data.ok) throw new Error();

      renderChart(data.labels, data.values);
      if (weekRange) weekRange.textContent = data.range;
      if (weekPrev) weekPrev.disabled = !data.can_prev;
      if (weekNext) weekNext.disabled = !data.can_next;
    } catch {
      if (weekRange) weekRange.textContent = "Erro";
    }
  }

  if (weekPrev) {
    weekPrev.addEventListener("click", () => {
      weekOffset -= 1;
      loadWeek();
    });
  }
  if (weekNext) {
    weekNext.addEventListener("click", () => {
      weekOffset += 1;
      if (weekOffset > 0) weekOffset = 0;
      loadWeek();
    });
  }

  function initChart() {
    if (!document.getElementById("chartPresencas")) return;
    if (chartInstance) return;
    loadWeek();
  }

  initChart();

  // ── LOGS block ────────────────────────────────────────────
  const logsList = document.getElementById("logs-list");
  const logsSearch = document.getElementById("logsSearch");
  const logsLevel = document.getElementById("logsLevel");
  const logsRefresh = document.getElementById("logsRefresh");

  let allLogs = [];

  async function loadLogs() {
    if (!logsList) return;
    logsList.innerHTML = '<p class="logs-empty">A carregar logs...</p>';

    try {
      const res = await fetch("/PAP/api/admin_logs.php");
      const data = await res.json();
      if (!data.success) throw new Error();
      allLogs = data.entries;
      renderLogs();
    } catch {
      logsList.innerHTML = '<p class="logs-empty">Erro ao carregar logs.</p>';
    }
  }

  function renderLogs() {
    if (!logsList) return;

    const q = (logsSearch?.value || "").toLowerCase().trim();
    const level = logsLevel?.value || "";

    const filtered = allLogs.filter((e) => {
      if (level && e.level !== level) return false;
      if (q) {
        const text = `${e.msg || ""} ${e.uri || ""} ${e.ip || ""} ${JSON.stringify(e.ctx || "")}`.toLowerCase();
        if (!text.includes(q)) return false;
      }
      return true;
    });

    if (!filtered.length) {
      logsList.innerHTML = '<p class="logs-empty">Nenhum resultado encontrado.</p>';
      return;
    }

    logsList.innerHTML = "";
    filtered.forEach((e) => {
      const item = document.createElement("div");
      item.className = "log-entry";
      const ctx = e.ctx ? JSON.stringify(e.ctx) : "";

      item.innerHTML = `
        <span class="log-time">${escapeHtml(e.time || "")}</span>
        <span class="log-level ${escapeHtml(e.level || "")}">${escapeHtml(e.level || "")}</span>
        <div class="log-body">
          <span class="log-msg">${escapeHtml(e.msg || "")}</span>
          <span class="log-meta">${escapeHtml(e.ip || "")}${e.uri ? " &middot; " + escapeHtml(e.uri) : ""}</span>
          ${ctx ? `<span class="log-ctx">${escapeHtml(ctx)}</span>` : ""}
        </div>
      `;
      logsList.appendChild(item);
    });
  }

  if (logsSearch) logsSearch.addEventListener("input", renderLogs);
  if (logsLevel) logsLevel.addEventListener("change", renderLogs);
  if (logsRefresh) logsRefresh.addEventListener("click", loadLogs);

  // ── SUPORTE (access problems) ─────────────────────────────
  const suporteList = document.getElementById("suporteList");
  const suporteRefresh = document.getElementById("suporteRefresh");
  const suporteDossier = document.getElementById("suporteDossier");

  let suporteItems = [];
  let suporteCurrent = null;

  async function loadSuporte() {
    if (!suporteList) return;
    suporteList.innerHTML = '<p class="logs-empty">A carregar...</p>';

    try {
      const res = await fetch("/PAP/api/admin_access_problems.php?action=list");
      const data = await res.json();
      if (!data.ok) throw new Error();
      suporteItems = data.items || [];
      renderSuporte();
    } catch {
      suporteList.innerHTML = '<p class="logs-empty">Erro ao carregar.</p>';
    }
  }

  function renderSuporte() {
    if (!suporteList) return;

    if (!suporteItems.length) {
      suporteList.innerHTML = '<p class="logs-empty">Sem mensagens.</p>';
      if (suporteDossier) suporteDossier.style.display = "none";
      return;
    }

    suporteList.innerHTML = suporteItems.map((it) => {
      const isRead = Number(it.lido) === 1;
      return `
        <div class="suporte-row ${isRead ? "is-read" : ""}" data-id="${it.id}">
          <span class="scan-dot ${isRead ? "present" : "absent"}"></span>
          <div class="scan-info">
            <div class="scan-name">${escapeHtml(it.email || "(sem email)")}</div>
            <div class="scan-meta">${escapeHtml(it.criado_em || "")}</div>
          </div>
          ${isRead ? "" : '<span class="badge-new">NOVO</span>'}
        </div>
      `;
    }).join("");

    suporteList.querySelectorAll(".suporte-row").forEach((row) => {
      row.addEventListener("click", () => openSuporteDossier(Number(row.dataset.id)));
    });
  }

  function openSuporteDossier(id) {
    const it = suporteItems.find((x) => Number(x.id) === id);
    if (!it || !suporteDossier) return;

    suporteCurrent = it;

    document.querySelectorAll("#suporteList .suporte-row").forEach((row) => {
      row.classList.toggle("active", Number(row.dataset.id) === id);
    });

    document.getElementById("suporteDossierData").textContent = it.criado_em || "—";
    document.getElementById("suporteDossierEmail").textContent = it.email || "(sem email)";
    document.getElementById("suporteDossierEstado").textContent = Number(it.lido) === 1 ? "Lido" : "Novo";
    document.getElementById("suporteDossierMensagem").textContent = it.mensagem || "";

    const btnRead = document.getElementById("suporteBtnRead");
    if (btnRead) {
      btnRead.textContent = Number(it.lido) === 1 ? "Marcar não lido" : "Marcar lido";
    }

    suporteDossier.style.display = "block";
    suporteDossier.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  const suporteDossierClose = document.getElementById("suporteDossierClose");
  if (suporteDossierClose) {
    suporteDossierClose.addEventListener("click", () => {
      if (suporteDossier) suporteDossier.style.display = "none";
      suporteCurrent = null;
    });
  }

  async function suporteAction(action) {
    if (!suporteCurrent) return;

    const fd = new FormData();
    fd.set("id", suporteCurrent.id);

    try {
      const res = await fetch("/PAP/api/admin_access_problems.php?action=" + action, {
        method: "POST",
        headers: CSRF_HEADERS,
        body: fd,
      });
      const data = await res.json();

      if (data.ok) {
        if (action === "delete") {
          if (suporteDossier) suporteDossier.style.display = "none";
          suporteCurrent = null;
        }
        await loadSuporte();
      }
    } catch {}
  }

  const suporteBtnRead = document.getElementById("suporteBtnRead");
  const suporteBtnDelete = document.getElementById("suporteBtnDelete");

  if (suporteBtnRead) {
    suporteBtnRead.addEventListener("click", () => {
      if (!suporteCurrent) return;
      suporteAction(Number(suporteCurrent.lido) === 1 ? "mark_unread" : "mark_read");
    });
  }

  if (suporteBtnDelete) {
    suporteBtnDelete.addEventListener("click", () => {
      if (!suporteCurrent) return;
      if (!confirm("Eliminar esta mensagem?")) return;
      suporteAction("delete");
    });
  }

  if (suporteRefresh) suporteRefresh.addEventListener("click", loadSuporte);

  // ── ATUALIZAR block ───────────────────────────────────────
  const updateSearch = document.getElementById("update-search");
  const updateResults = document.getElementById("update-results");
  const updateForm = document.getElementById("update-form");

  if (updateSearch && updateResults && updateForm) {
    const updateId = document.getElementById("update-id");
    const updateType = document.getElementById("update-type");
    const updateNome = document.getElementById("update-nome");
    const updateLogin = document.getElementById("update-login");
    const updateIdade = document.getElementById("update-idade");
    const updateTurmaNum = document.getElementById("updateTurmaNum");
    const updateTurmaLetra = document.getElementById("updateTurmaLetra");
    const updateNumero = document.getElementById("updateNumeroTurma");
    const updateUid = document.getElementById("updateUid");
    const updatePassword = document.getElementById("update-password");
    const updateStatus = document.getElementById("updateCardStatus");

    let updateUsers = [];

    async function initUpdate() {
      try {
        updateUsers = await fetchUsers();
        renderUserList(updateUsers, updateResults, fillUpdateForm);
      } catch {
        updateResults.innerHTML = "<p>Erro de ligação ao servidor.</p>";
      }
    }

    function fillUpdateForm(user) {
      if (updateId) updateId.value = user.id ?? "";
      if (updateType) updateType.value = user.tipo ?? "";
      if (updateNome) updateNome.value = user.nome ?? "";
      if (updateLogin) updateLogin.value = user.login ?? "";
      if (updateIdade) updateIdade.value = user.idade ?? "";
      if (updateUid) updateUid.value = user.uid ?? "";
      if (updatePassword) updatePassword.value = "";

      const { num, letra } = splitTurma(user.turma);
      if (updateTurmaNum) updateTurmaNum.value = num;
      if (updateTurmaLetra) updateTurmaLetra.value = letra;

      updateForm.style.display = "block";

      const isProfessor = user.tipo === "professor";
      const idadeRow = updateIdade?.closest(".form-row");
      if (idadeRow) idadeRow.style.display = isProfessor ? "none" : "";
    }

    updateSearch.addEventListener("input", () => {
      const q = updateSearch.value.toLowerCase().trim();
      renderUserList(
        updateUsers.filter((u) => (u.nome || "").toLowerCase().includes(q)),
        updateResults,
        fillUpdateForm
      );
    });

    updateForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      const tNum = updateTurmaNum?.value || "";
      const tLetra = (updateTurmaLetra?.value || "").toUpperCase();
      const turma = `${tNum}${tLetra}`;

      const payload = {
        id: updateId?.value ?? "",
        tipo: (updateType?.value || "").toLowerCase(),
        nome: updateNome?.value ?? "",
        login: updateLogin?.value ?? "",
        idade: updateIdade?.value ?? "",
        turma,
        turma_num: tNum,
        turma_letra: tLetra,
        uid: updateUid?.value ?? "",
        password: updatePassword?.value ?? "",
      };

      try {
        const res = await fetch("/PAP/api/admin_update_card.php?action=update", {
          method: "POST",
          headers: { "Content-Type": "application/json", "X-CSRF-Token": CSRF },
          body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (data.success) {
          if (updateStatus) {
            updateStatus.textContent = "Dados atualizados com sucesso.";
            updateStatus.style.color = "green";
          }
          updateUsers = await fetchUsers();
          renderUserList(updateUsers, updateResults, fillUpdateForm);
        } else if (updateStatus) {
          updateStatus.textContent = data.message || "Erro ao atualizar.";
          updateStatus.style.color = "red";
        }
      } catch {
        if (updateStatus) {
          updateStatus.textContent = "Erro de ligação ao servidor.";
          updateStatus.style.color = "red";
        }
      }
    });

    initUpdate();

    if (updateScanBtn && updateUid) {
      updateScanBtn.addEventListener("click", () => startScan(updateUid, updateScanBtn, updateScanStatus));
    }
  }

  // ── ENCONTRAR block (read-only) ───────────────────────────
  const findSearch = document.getElementById("find-search");
  const findResults = document.getElementById("find-results");
  const findForm = document.getElementById("find-form");

  if (findSearch && findResults && findForm) {
    const findNome = document.getElementById("find-nome");
    const findLogin = document.getElementById("find-login");
    const findIdade = document.getElementById("find-idade");
    const findTurmaNum = document.getElementById("findTurmaNum");
    const findTurmaLetra = document.getElementById("findTurmaLetra");
    const findUid = document.getElementById("findUid");

    let findUsers = [];

    async function initFind() {
      try {
        findUsers = await fetchUsers();
        renderUserList(findUsers, findResults, fillFindForm);
      } catch {
        findResults.innerHTML = "<p>Erro de ligação ao servidor.</p>";
      }
    }

    function fillFindForm(user) {
      if (findNome) findNome.value = user.nome ?? "";
      if (findLogin) findLogin.value = user.login ?? "";
      if (findIdade) findIdade.value = user.idade ?? "";
      if (findUid) findUid.value = user.uid ?? "";

      const { num, letra } = splitTurma(user.turma);
      if (findTurmaNum) findTurmaNum.value = num;
      if (findTurmaLetra) findTurmaLetra.value = letra;

      findForm.style.display = "block";

      const isProfessor = user.tipo === "professor";
      const idadeRow = findIdade?.closest(".form-row");
      if (idadeRow) idadeRow.style.display = isProfessor ? "none" : "";
    }

    findSearch.addEventListener("input", () => {
      const q = findSearch.value.toLowerCase().trim();
      renderUserList(
        findUsers.filter((u) => (u.nome || "").toLowerCase().includes(q)),
        findResults,
        fillFindForm
      );
    });

    initFind();
  }

  // ── NOTÍCIAS (admin) ─────────────────────────────────────
  const noticiasList = document.getElementById("noticiasList");
  const noticiaForm = document.getElementById("noticiaForm");
  const noticiaId = document.getElementById("noticiaId");
  const noticiaTitulo = document.getElementById("noticiaTitulo");
  const noticiaCorpo = document.getElementById("noticiaCorpo");
  const noticiaImagem = document.getElementById("noticiaImagem");
  const noticiaAtivo = document.getElementById("noticiaAtivo");
  const noticiaSubmit = document.getElementById("noticiaSubmit");
  const noticiaCancel = document.getElementById("noticiaCancel");
  const noticiaStatus = document.getElementById("noticiaStatus");

  function resetNoticiaForm() {
    if (!noticiaForm) return;
    noticiaForm.reset();
    if (noticiaId) noticiaId.value = "";
    if (noticiaAtivo) noticiaAtivo.checked = true;
    if (noticiaSubmit) noticiaSubmit.textContent = "Criar notícia";
    if (noticiaCancel) noticiaCancel.style.display = "none";
    if (noticiaStatus) noticiaStatus.textContent = "";
  }

  async function loadNoticias() {
    if (!noticiasList) return;
    noticiasList.innerHTML = '<p class="logs-empty">A carregar...</p>';

    try {
      const res = await fetch("/PAP/api/noticias.php?action=list");
      const data = await res.json();
      if (!data.ok) throw new Error();

      if (data.noticias.length === 0) {
        noticiasList.innerHTML = '<p class="logs-empty">Ainda não há notícias.</p>';
        return;
      }

      noticiasList.innerHTML = data.noticias.map((n) => `
        <div class="noticia-admin-item ${n.ativo ? "" : "inactive"}" data-id="${n.id}">
          ${n.imagem ? `<img class="noticia-thumb" src="${escapeHtml(n.imagem)}" alt="">` : '<div class="noticia-thumb noticia-thumb-empty"></div>'}
          <div class="noticia-admin-body">
            <div class="noticia-admin-title">${escapeHtml(n.titulo)}</div>
            <div class="noticia-admin-corpo">${escapeHtml(n.corpo)}</div>
          </div>
          <div class="noticia-admin-actions">
            <button type="button" class="noticia-edit">Editar</button>
            <button type="button" class="noticia-toggle">${n.ativo ? "Ocultar" : "Ativar"}</button>
            <button type="button" class="danger-btn noticia-del">Eliminar</button>
          </div>
        </div>
      `).join("");
    } catch {
      noticiasList.innerHTML = '<p class="logs-empty">Erro ao carregar.</p>';
    }
  }

  if (noticiaForm) {
    noticiaForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      if (noticiaStatus) {
        noticiaStatus.textContent = "A guardar...";
        noticiaStatus.style.color = "#6b7280";
      }

      const fd = new FormData(noticiaForm);
      const editing = noticiaId && noticiaId.value !== "";
      fd.append("action", editing ? "update" : "create");
      if (!fd.has("ativo")) fd.append("ativo", "0");

      try {
        const res = await fetch("/PAP/api/noticias.php", {
          method: "POST",
          headers: CSRF_HEADERS,
          body: fd,
        });
        const data = await res.json();

        if (data.ok) {
          if (noticiaStatus) {
            noticiaStatus.textContent = editing ? "Atualizada." : "Criada.";
            noticiaStatus.style.color = "#10b981";
          }
          resetNoticiaForm();
          loadNoticias();
        } else if (noticiaStatus) {
          noticiaStatus.textContent = "Erro: " + (data.error || "desconhecido");
          noticiaStatus.style.color = "#ef4444";
        }
      } catch {
        if (noticiaStatus) {
          noticiaStatus.textContent = "Erro de rede.";
          noticiaStatus.style.color = "#ef4444";
        }
      }
    });
  }

  if (noticiaCancel) noticiaCancel.addEventListener("click", resetNoticiaForm);

  if (noticiasList) {
    noticiasList.addEventListener("click", async (e) => {
      const item = e.target.closest(".noticia-admin-item");
      if (!item) return;

      const id = item.dataset.id;

      if (e.target.matches(".noticia-edit")) {
        try {
          const res = await fetch("/PAP/api/noticias.php?action=get&id=" + encodeURIComponent(id));
          const data = await res.json();
          if (!data.ok) throw new Error();

          const n = data.noticia;
          if (noticiaId) noticiaId.value = n.id;
          if (noticiaTitulo) noticiaTitulo.value = n.titulo || "";
          if (noticiaCorpo) noticiaCorpo.value = n.corpo || "";
          if (noticiaImagem) noticiaImagem.value = n.imagem || "";
          if (noticiaAtivo) noticiaAtivo.checked = Number(n.ativo) === 1;
          if (noticiaSubmit) noticiaSubmit.textContent = "Guardar alterações";
          if (noticiaCancel) noticiaCancel.style.display = "";
          noticiaForm.scrollIntoView({ behavior: "smooth", block: "start" });
        } catch {
          alert("Erro ao carregar notícia.");
        }
        return;
      }

      if (e.target.matches(".noticia-toggle")) {
        const fd = new FormData();
        fd.append("action", "toggle");
        fd.append("id", id);

        const res = await fetch("/PAP/api/noticias.php", {
          method: "POST",
          headers: CSRF_HEADERS,
          body: fd,
        });
        const data = await res.json();
        if (data.ok) loadNoticias();
        return;
      }

      if (e.target.matches(".noticia-del")) {
        showConfirm(
          "Eliminar notícia",
          "Tens a certeza? Esta ação é irreversível.",
          async () => {
            const fd = new FormData();
            fd.append("action", "delete");
            fd.append("id", id);

            const res = await fetch("/PAP/api/noticias.php", {
              method: "POST",
              headers: CSRF_HEADERS,
              body: fd,
            });
            const data = await res.json();
            if (data.ok) loadNoticias();
          }
        );
      }
    });
  }

  // ── CAROUSEL ─────────────────────────────────────────────
  const carousel = document.getElementById("carousel");
  const prevBtn = document.querySelector(".carousel-container .prev");
  const nextBtn = document.querySelector(".carousel-container .next");

  if (carousel && prevBtn && nextBtn) {
    const slides = carousel.querySelectorAll(".slide");
    let currentIndex = 0;
    let autoSlide = null;

    function updateCarousel() {
      carousel.style.transform = `translateX(-${currentIndex * 100}%)`;
    }

    function goToNext() {
      currentIndex = (currentIndex + 1) % slides.length;
      updateCarousel();
    }

    function goToPrev() {
      currentIndex = (currentIndex - 1 + slides.length) % slides.length;
      updateCarousel();
    }

    function startAutoSlide() {
      stopAutoSlide();
      autoSlide = setInterval(goToNext, 5000);
    }

    function stopAutoSlide() {
      if (autoSlide) {
        clearInterval(autoSlide);
        autoSlide = null;
      }
    }

    nextBtn.addEventListener("click", () => {
      goToNext();
      startAutoSlide();
    });

    prevBtn.addEventListener("click", () => {
      goToPrev();
      startAutoSlide();
    });

    carousel.addEventListener("mouseenter", stopAutoSlide);
    carousel.addEventListener("mouseleave", startAutoSlide);

    updateCarousel();
    startAutoSlide();
  }

  // ── ALUNO PAGE: notas + sumarios + agenda ────────────────
  if (document.body.classList.contains("page-aluno")) {
    const notasBox     = document.getElementById("notasAluno");
    const notasChartEl = document.getElementById("notasChart");
    const notasWrap    = document.getElementById("notasChartWrap");

    async function loadNotasAluno() {
      if (!notasBox) return;
      notasBox.innerHTML = '<p class="empty-state">A carregar...</p>';
      try {
        const res  = await fetch("/PAP/api/notas.php?action=list");
        const data = await res.json();
        if (!data.ok) throw new Error();

        const notas = data.notas || [];
        if (notas.length === 0) {
          notasBox.innerHTML = '<p class="empty-state">Ainda não há notas.</p>';
          return;
        }

        notasBox.innerHTML = notas.map(n => `
          <div class="nota-row ${Number(n.valor) < 10 ? 'nota-bad' : Number(n.valor) < 14 ? 'nota-mid' : 'nota-good'}">
            <div class="nota-left">
              <div class="nota-materia">${escapeHtml(n.materia || "—")}</div>
              <div class="nota-meta">${escapeHtml(n.tipo || "")} • ${escapeHtml(n.data || "")} ${n.professor_nome ? "• " + escapeHtml(n.professor_nome) : ""}</div>
              ${n.observacao ? `<div class="nota-obs">${escapeHtml(n.observacao)}</div>` : ""}
            </div>
            <div class="nota-valor">${escapeHtml(String(n.valor))}</div>
          </div>
        `).join("");

        // Chart
        if (notasChartEl && notasWrap && typeof Chart !== "undefined") {
          notasWrap.style.display = "block";
          const labels = notas.map(n => `${n.materia || ""} ${n.data || ""}`);
          const values = notas.map(n => Number(n.valor));
          new Chart(notasChartEl, {
            type: "line",
            data: {
              labels,
              datasets: [{
                label: "Notas",
                data: values,
                borderColor: "#3b82f6",
                backgroundColor: "rgba(59,130,246,0.15)",
                tension: 0.3,
              }],
            },
            options: {
              responsive: true,
              scales: { y: { min: 0, max: 20 } },
            },
          });
        }
      } catch {
        notasBox.innerHTML = '<p class="empty-state">Erro ao carregar.</p>';
      }
    }

    const sumariosListAluno = document.getElementById("sumariosList");
    async function loadSumariosAluno() {
      if (!sumariosListAluno) return;
      sumariosListAluno.innerHTML = '<p class="empty-state">A carregar...</p>';
      try {
        const res  = await fetch("/PAP/api/sumarios.php?action=list");
        const data = await res.json();
        if (!data.ok) throw new Error();
        const sums = data.sumarios || [];
        if (sums.length === 0) {
          sumariosListAluno.innerHTML = '<p class="empty-state">Ainda não há sumários.</p>';
          return;
        }
        sumariosListAluno.innerHTML = sums.map(s => `
          <div class="sumario-card">
            <div class="sumario-head">${escapeHtml(s.data || "")} • ${escapeHtml(s.materia || "")}</div>
            <div class="sumario-body">${escapeHtml(s.descricao || "")}</div>
          </div>
        `).join("");
      } catch {
        sumariosListAluno.innerHTML = '<p class="empty-state">Erro ao carregar.</p>';
      }
    }

    const agendaListAluno = document.getElementById("agendaList");
    const agendaFormAluno = document.getElementById("agendaForm");
    const agStatus        = document.getElementById("agStatus");

    async function loadAgendaAluno() {
      if (!agendaListAluno) return;
      agendaListAluno.innerHTML = '<p class="empty-state">A carregar...</p>';
      try {
        const res  = await fetch("/PAP/api/agenda.php?action=list");
        const data = await res.json();
        if (!data.ok) throw new Error();
        const tarefas = data.tarefas || [];
        if (tarefas.length === 0) {
          agendaListAluno.innerHTML = '<p class="empty-state">Sem tarefas.</p>';
          return;
        }
        agendaListAluno.innerHTML = tarefas.map(t => `
          <div class="agenda-row ${Number(t.concluido) === 1 ? "done" : ""}">
            <label class="agenda-check">
              <input type="checkbox" data-id="${t.id}" ${Number(t.concluido) === 1 ? "checked" : ""}>
              <span>${escapeHtml(t.titulo || "")}</span>
            </label>
            ${t.data ? `<span class="agenda-date">${escapeHtml(t.data)}</span>` : ""}
            <button class="agenda-del" data-id="${t.id}" type="button">×</button>
          </div>
        `).join("");

        agendaListAluno.querySelectorAll('input[type="checkbox"]').forEach(cb => {
          cb.addEventListener("change", async () => {
            const fd = new FormData();
            fd.append("id", cb.dataset.id);
            fd.append("concluido", cb.checked ? "1" : "0");
            await fetch("/PAP/api/agenda.php?action=toggle", { method: "POST", headers: CSRF_HEADERS, body: fd });
            loadAgendaAluno();
          });
        });

        agendaListAluno.querySelectorAll(".agenda-del").forEach(btn => {
          btn.addEventListener("click", async () => {
            const fd = new FormData();
            fd.append("id", btn.dataset.id);
            await fetch("/PAP/api/agenda.php?action=delete", { method: "POST", headers: CSRF_HEADERS, body: fd });
            loadAgendaAluno();
          });
        });
      } catch {
        agendaListAluno.innerHTML = '<p class="empty-state">Erro ao carregar.</p>';
      }
    }

    if (agendaFormAluno) {
      agendaFormAluno.addEventListener("submit", async e => {
        e.preventDefault();
        const fd = new FormData(agendaFormAluno);
        if (agStatus) { agStatus.textContent = "A guardar..."; agStatus.style.color = "#6b7280"; }
        try {
          const res  = await fetch("/PAP/api/agenda.php?action=create", { method: "POST", headers: CSRF_HEADERS, body: fd });
          const data = await res.json();
          if (data.ok) {
            agendaFormAluno.reset();
            if (agStatus) { agStatus.textContent = "Adicionado."; agStatus.style.color = "#10b981"; }
            loadAgendaAluno();
          } else if (agStatus) {
            agStatus.textContent = data.error || "Erro.";
            agStatus.style.color = "#ef4444";
          }
        } catch {
          if (agStatus) { agStatus.textContent = "Erro de rede."; agStatus.style.color = "#ef4444"; }
        }
      });
    }

    loadNotasAluno();
    loadSumariosAluno();
    loadAgendaAluno();
  }

  // ── PROFESSOR PAGE: tabs + notas + sumarios + agenda ─────
  if (document.body.classList.contains("page-professor")) {
    // Tab switcher (reuses .admin-tab / .admin-tab-content from CSS)
    document.querySelectorAll(".admin-tab").forEach(tab => {
      tab.addEventListener("click", () => {
        document.querySelectorAll(".admin-tab").forEach(t => t.classList.remove("active"));
        document.querySelectorAll(".admin-tab-content").forEach(c => c.classList.remove("active"));
        tab.classList.add("active");
        const content = document.getElementById("tab-" + tab.dataset.tab);
        if (content) content.classList.add("active");
      });
    });

    const notaForm     = document.getElementById("notaForm");
    const notaAlunoSel = document.getElementById("notaAluno");
    const notaStatus   = document.getElementById("notaStatus");
    const notasList    = document.getElementById("notasList");

    async function loadAlunosDaTurma() {
      if (!notaAlunoSel) return;
      try {
        const res  = await fetch("/PAP/api/notas.php?action=alunos_da_turma");
        const data = await res.json();
        if (!data.ok) throw new Error();
        const alunos = data.alunos || [];
        notaAlunoSel.innerHTML = '<option value="">Selecionar aluno...</option>' +
          alunos.map(a => `<option value="${escapeHtml(a.login)}">${escapeHtml(a.nome)}</option>`).join("");
      } catch {
        notaAlunoSel.innerHTML = '<option value="">Erro ao carregar</option>';
      }
    }

    async function loadNotasProf() {
      if (!notasList) return;
      notasList.innerHTML = '<p class="empty-state">A carregar...</p>';
      try {
        const res  = await fetch("/PAP/api/notas.php?action=list");
        const data = await res.json();
        if (!data.ok) throw new Error();
        const notas = data.notas || [];
        if (notas.length === 0) {
          notasList.innerHTML = '<p class="empty-state">Ainda não lançou notas.</p>';
          return;
        }
        notasList.innerHTML = notas.map(n => `
          <div class="nota-row ${Number(n.valor) < 10 ? 'nota-bad' : Number(n.valor) < 14 ? 'nota-mid' : 'nota-good'}">
            <div class="nota-left">
              <div class="nota-materia">${escapeHtml(n.aluno_nome || n.login_aluno || "—")} • ${escapeHtml(n.materia || "")}</div>
              <div class="nota-meta">${escapeHtml(n.tipo || "")} • ${escapeHtml(n.data || "")}${n.aluno_turma ? " • Turma " + escapeHtml(n.aluno_turma) : ""}</div>
              ${n.observacao ? `<div class="nota-obs">${escapeHtml(n.observacao)}</div>` : ""}
            </div>
            <div class="nota-valor">${escapeHtml(String(n.valor))}</div>
          </div>
        `).join("");
      } catch {
        notasList.innerHTML = '<p class="empty-state">Erro ao carregar.</p>';
      }
    }

    if (notaForm) {
      notaForm.addEventListener("submit", async e => {
        e.preventDefault();
        const fd = new FormData(notaForm);
        if (notaStatus) { notaStatus.textContent = "A guardar..."; notaStatus.style.color = "#6b7280"; }
        try {
          const res  = await fetch("/PAP/api/notas.php?action=create", { method: "POST", headers: CSRF_HEADERS, body: fd });
          const data = await res.json();
          if (data.ok) {
            notaForm.reset();
            if (notaStatus) { notaStatus.textContent = "Nota lançada."; notaStatus.style.color = "#10b981"; }
            loadNotasProf();
          } else if (notaStatus) {
            notaStatus.textContent = data.error || "Erro.";
            notaStatus.style.color = "#ef4444";
          }
        } catch {
          if (notaStatus) { notaStatus.textContent = "Erro de rede."; notaStatus.style.color = "#ef4444"; }
        }
      });
    }

    const sumarioForm    = document.getElementById("sumarioForm");
    const sumStatus      = document.getElementById("sumStatus");
    const sumariosListEl = document.getElementById("sumariosList");

    async function loadSumariosProf() {
      if (!sumariosListEl) return;
      sumariosListEl.innerHTML = '<p class="empty-state">A carregar...</p>';
      try {
        const res  = await fetch("/PAP/api/sumarios.php?action=list");
        const data = await res.json();
        if (!data.ok) throw new Error();
        const sums = data.sumarios || [];
        if (sums.length === 0) {
          sumariosListEl.innerHTML = '<p class="empty-state">Ainda não há sumários.</p>';
          return;
        }
        sumariosListEl.innerHTML = sums.map(s => `
          <div class="sumario-card">
            <div class="sumario-head">${escapeHtml(s.data || "")} • Turma ${escapeHtml(String(s.turma_num || "") + (s.turma_letra || ""))}</div>
            <div class="sumario-body">${escapeHtml(s.descricao || "")}</div>
          </div>
        `).join("");
      } catch {
        sumariosListEl.innerHTML = '<p class="empty-state">Erro ao carregar.</p>';
      }
    }

    if (sumarioForm) {
      sumarioForm.addEventListener("submit", async e => {
        e.preventDefault();
        const fd = new FormData(sumarioForm);
        if (sumStatus) { sumStatus.textContent = "A guardar..."; sumStatus.style.color = "#6b7280"; }
        try {
          const res  = await fetch("/PAP/api/sumarios.php?action=create", { method: "POST", headers: CSRF_HEADERS, body: fd });
          const data = await res.json();
          if (data.ok) {
            sumarioForm.reset();
            if (sumStatus) { sumStatus.textContent = "Sumário criado."; sumStatus.style.color = "#10b981"; }
            loadSumariosProf();
          } else if (sumStatus) {
            sumStatus.textContent = data.error || "Erro.";
            sumStatus.style.color = "#ef4444";
          }
        } catch {
          if (sumStatus) { sumStatus.textContent = "Erro de rede."; sumStatus.style.color = "#ef4444"; }
        }
      });
    }

    const agendaFormProf = document.getElementById("agendaForm");
    const agStatusProf   = document.getElementById("agStatus");
    const agendaListProf = document.getElementById("agendaList");

    async function loadAgendaProf() {
      if (!agendaListProf) return;
      agendaListProf.innerHTML = '<p class="empty-state">A carregar...</p>';
      try {
        const res  = await fetch("/PAP/api/agenda.php?action=list");
        const data = await res.json();
        if (!data.ok) throw new Error();
        const tarefas = data.tarefas || [];
        if (tarefas.length === 0) {
          agendaListProf.innerHTML = '<p class="empty-state">Sem tarefas.</p>';
          return;
        }
        agendaListProf.innerHTML = tarefas.map(t => `
          <div class="agenda-row ${Number(t.concluido) === 1 ? "done" : ""}">
            <label class="agenda-check">
              <input type="checkbox" data-id="${t.id}" ${Number(t.concluido) === 1 ? "checked" : ""}>
              <span>${escapeHtml(t.titulo || "")}</span>
            </label>
            ${t.data ? `<span class="agenda-date">${escapeHtml(t.data)}</span>` : ""}
            <button class="agenda-del" data-id="${t.id}" type="button">×</button>
          </div>
        `).join("");

        agendaListProf.querySelectorAll('input[type="checkbox"]').forEach(cb => {
          cb.addEventListener("change", async () => {
            const fd = new FormData();
            fd.append("id", cb.dataset.id);
            fd.append("concluido", cb.checked ? "1" : "0");
            await fetch("/PAP/api/agenda.php?action=toggle", { method: "POST", headers: CSRF_HEADERS, body: fd });
            loadAgendaProf();
          });
        });

        agendaListProf.querySelectorAll(".agenda-del").forEach(btn => {
          btn.addEventListener("click", async () => {
            const fd = new FormData();
            fd.append("id", btn.dataset.id);
            await fetch("/PAP/api/agenda.php?action=delete", { method: "POST", headers: CSRF_HEADERS, body: fd });
            loadAgendaProf();
          });
        });
      } catch {
        agendaListProf.innerHTML = '<p class="empty-state">Erro ao carregar.</p>';
      }
    }

    if (agendaFormProf) {
      agendaFormProf.addEventListener("submit", async e => {
        e.preventDefault();
        const fd = new FormData(agendaFormProf);
        if (agStatusProf) { agStatusProf.textContent = "A guardar..."; agStatusProf.style.color = "#6b7280"; }
        try {
          const res  = await fetch("/PAP/api/agenda.php?action=create", { method: "POST", headers: CSRF_HEADERS, body: fd });
          const data = await res.json();
          if (data.ok) {
            agendaFormProf.reset();
            if (agStatusProf) { agStatusProf.textContent = "Adicionado."; agStatusProf.style.color = "#10b981"; }
            loadAgendaProf();
          } else if (agStatusProf) {
            agStatusProf.textContent = data.error || "Erro.";
            agStatusProf.style.color = "#ef4444";
          }
        } catch {
          if (agStatusProf) { agStatusProf.textContent = "Erro de rede."; agStatusProf.style.color = "#ef4444"; }
        }
      });
    }

    loadAlunosDaTurma();
    loadNotasProf();
    loadSumariosProf();
    loadAgendaProf();
  }
});