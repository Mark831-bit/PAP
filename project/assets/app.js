document.addEventListener("DOMContentLoaded", function () {

  // ── CSRF ──────────────────────────────────────────────────
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const CSRF_HEADERS = { "X-CSRF-Token": CSRF };

  // ── MODALS ────────────────────────────────────────────────
  const loginModal    = document.getElementById("loginModal");
  const registerModal = document.getElementById("registerModal");
  const problemModal  = document.getElementById("problemModal");
  const openLogin     = document.getElementById("openLogin");
  const openRegister  = document.getElementById("openRegister");
  const openProblem   = document.getElementById("openProblem");
  const loginForm     = document.getElementById("loginForm");
  const registerForm  = document.getElementById("registerForm");
  const problemForm   = document.getElementById("problemForm");

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
        const res  = await fetch("/PAP/api/auth.php", { method: "POST", headers: CSRF_HEADERS, body: formData });
        const data = await res.json();
        if (data.ok) {
          if (loginModal) loginModal.classList.add("hidden");
          location.reload();
        } else if (statusBox) {
          statusBox.textContent = data.error || "Login failed";
          statusBox.style.color = "red";
        }
      } catch (err) {
        if (statusBox) { statusBox.textContent = "Erro de rede."; statusBox.style.color = "red"; }
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
        const res  = await fetch("/PAP/api/register.php", { method: "POST", headers: CSRF_HEADERS, body: formData });
        const data = await res.json();
        if (data.ok) {
          if (registerModal) registerModal.classList.add("hidden");
          location.reload();
        } else if (statusBox) {
          statusBox.textContent = data.error || "Erro no registo";
          statusBox.style.color = "red";
        }
      } catch (err) {
        if (statusBox) { statusBox.textContent = "Erro de rede."; statusBox.style.color = "red"; }
        console.error(err);
      }
    });
  }

  if (problemForm) {
    problemForm.addEventListener("submit", async function (e) {
      e.preventDefault();
      const formData  = new FormData(this);
      const statusBox = document.getElementById("problemStatus");
      if (statusBox) { statusBox.textContent = "A enviar..."; statusBox.style.color = "#6b7280"; }
      try {
        const res  = await fetch("/PAP/api/access_problem.php", { method: "POST", headers: CSRF_HEADERS, body: formData });
        const data = await res.json();
        if (data.ok) {
          problemForm.reset();
          if (statusBox) { statusBox.textContent = "Mensagem enviada. Obrigado!"; statusBox.style.color = "green"; }
          setTimeout(() => {
            if (problemModal) problemModal.classList.add("hidden");
            if (statusBox) statusBox.textContent = "";
          }, 1500);
        } else if (statusBox) {
          statusBox.textContent = data.error || "Erro ao enviar.";
          statusBox.style.color = "red";
        }
      } catch (err) {
        if (statusBox) { statusBox.textContent = "Erro de rede."; statusBox.style.color = "red"; }
        console.error(err);
      }
    });
  }

  // ── SHARED: helpers для списков пользователей ─────────────
  async function fetchUsers() {
    const res  = await fetch("/PAP/api/admin_update_card.php?action=list");
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
      const item     = document.createElement("div");
      item.className = "update-result-item";
      const role     = user.tipo === "professor" ? "Professor" : "Aluno";
      const dotClass = user.tipo === "professor" ? "dot-professor" : "dot-aluno";
      item.innerHTML = `
        <div class="update-left">
          <div class="update-dot ${dotClass}"></div>
          <div>
            <div class="update-name">${escapeHtml(user.nome || "")}</div>
            <div class="update-role">${escapeHtml(role)}</div>
          </div>
        </div>`;
      item.addEventListener("click", () => onSelect(user));
      resultsBox.appendChild(item);
    });
  }

  function splitTurma(turma) {
    const t = (turma || "").toString().trim();
    if (t.length >= 2) return { num: t.slice(0, -1), letra: t.slice(-1).toUpperCase() };
    return { num: "", letra: "" };
  }

  // ── RFID SCAN ────────────────────────────────────────────
  let scanInterval = null;

  function startScan(uidInput, btn, statusEl) {
    // Если уже идёт скан — отменяем
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
            const res  = await fetch("/PAP/api/scan_uid.php?action=poll");
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
        if (statusEl) { statusEl.textContent = "Erro ao iniciar scan."; statusEl.style.color = "#b91c1c"; }
      });
  }

  function stopScan(btn, statusEl, msg, color) {
    clearInterval(scanInterval);
    scanInterval = null;
    btn.dataset.scanning = "0";
    btn.textContent = "Ler cartão";
    if (statusEl) { statusEl.textContent = msg; statusEl.style.color = color; }
  }

  function cancelScan(btn, statusEl) {
    fetch("/PAP/api/scan_uid.php?action=cancel").catch(() => {});
    stopScan(btn, statusEl, "Scan cancelado.", "#6b7280");
  }

  const addScanBtn    = document.getElementById("addScanBtn");
  const addScanStatus = document.getElementById("addScanStatus");
  const addUid        = document.getElementById("addUid");
  if (addScanBtn && addUid) {
    addScanBtn.addEventListener("click", () => startScan(addUid, addScanBtn, addScanStatus));
  }

  const addCardForm   = document.getElementById("addCardForm");
  const addCardStatus = document.getElementById("addCardStatus");
  if (addCardForm) {
    addCardForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (addCardStatus) { addCardStatus.textContent = "A guardar..."; addCardStatus.style.color = "#6b7280"; }

      const fd = new FormData(addCardForm);
      try {
        const res  = await fetch("/PAP/api/admin_add_card.php", { method: "POST", headers: CSRF_HEADERS, body: fd });
        const data = await res.json();
        if (data.ok) {
          if (addCardStatus) { addCardStatus.textContent = "Utilizador criado."; addCardStatus.style.color = "#10b981"; }
          addCardForm.reset();
        } else if (addCardStatus) {
          addCardStatus.textContent = data.error || "Erro ao criar.";
          addCardStatus.style.color = "#ef4444";
        }
      } catch {
        if (addCardStatus) { addCardStatus.textContent = "Erro de rede."; addCardStatus.style.color = "#ef4444"; }
      }
    });
  }

  const updateScanBtn    = document.getElementById("updateScanBtn");
  const updateScanStatus = document.getElementById("updateScanStatus");
  // updateUid wired up after updateForm block — see below

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
  const modal         = document.getElementById("confirmModal");
  const modalTitle    = document.getElementById("confirmTitle");
  const modalText     = document.getElementById("confirmText");
  const modalOk       = document.getElementById("confirmOk");
  const modalCancel   = document.getElementById("confirmCancel");
  let   modalCallback = null;

  function showConfirm(title, text, onOk) {
    if (!modal) return;
    modalTitle.textContent = title;
    modalText.textContent  = text;
    modalCallback = onOk;
    modal.style.display = "flex";
  }
  if (modalCancel) modalCancel.addEventListener("click", () => { modal.style.display = "none"; modalCallback = null; });
  if (modalOk)     modalOk.addEventListener("click", () => {
    modal.style.display = "none";
    if (modalCallback) modalCallback();
    modalCallback = null;
  });

  // ── ALUNOS list + dossier ────────────────────────────────
  const alunosList   = document.getElementById("alunos-list");
  const alunosSearch = document.getElementById("alunosSearch");
  const dossier      = document.getElementById("alunoDossier");
  const dossierClose = document.getElementById("dossierClose");
  let allAlunos = [];
  let currentAlunoLogin = null;

  async function loadAlunos() {
    if (!alunosList) return;
    alunosList.innerHTML = '<p class="logs-empty">A carregar...</p>';
    try {
      const res  = await fetch("/PAP/api/admin_alunos.php?action=list");
      const data = await res.json();
      if (!data.ok) throw new Error();
      allAlunos = data.alunos;
      renderAlunosList();
    } catch (err) {
      alunosList.innerHTML = '<p class="logs-empty">Erro ao carregar.</p>';
    }
  }

  function renderAlunosList() {
    const q = (alunosSearch?.value || "").toLowerCase().trim();
    const filtered = allAlunos.filter(a => !q || (a.nome || "").toLowerCase().includes(q) || (a.login || "").toLowerCase().includes(q));
    if (filtered.length === 0) {
      alunosList.innerHTML = '<p class="logs-empty">Nenhum aluno encontrado.</p>';
      return;
    }
    alunosList.innerHTML = filtered.map(a => `
      <div class="aluno-row" data-login="${a.login}">
        <span class="scan-dot ${Number(a.presenca) === 1 ? 'present' : 'absent'}"></span>
        <div class="scan-info">
          <div class="scan-name">${escapeHtml(a.nome || '—')}</div>
          <div class="scan-meta">Turma ${escapeHtml(a.turma || '')} • Nº ${escapeHtml(String(a.numero_turma || ''))} • ${escapeHtml(a.login || '')}</div>
        </div>
        ${Number(a.blocked) === 1 ? '<span class="badge-blocked">Bloqueado</span>' : ''}
      </div>
    `).join("");

    alunosList.querySelectorAll(".aluno-row").forEach(row => {
      row.addEventListener("click", () => openDossier(row.dataset.login));
    });
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
  }

  if (alunosSearch) alunosSearch.addEventListener("input", renderAlunosList);

  async function openDossier(login) {
    try {
      const res  = await fetch("/PAP/api/admin_alunos.php?action=get&login=" + encodeURIComponent(login));
      const data = await res.json();
      if (!data.ok) throw new Error();
      const a = data.aluno;
      currentAlunoLogin = a.login;

      document.getElementById("dossierNome").textContent     = a.nome || '—';
      document.getElementById("dossierLogin").textContent    = a.login || '—';
      document.getElementById("dossierIdade").textContent    = a.idade || '—';
      document.getElementById("dossierTurma").textContent    = a.turma || '—';
      document.getElementById("dossierNumero").textContent   = a.numero_turma || '—';
      document.getElementById("dossierUid").textContent      = a.uid || '—';
      document.getElementById("dossierPresenca").textContent = Number(a.presenca) === 1 ? 'Presente' : 'Falta';
      document.getElementById("dossierBlocked").textContent  = Number(a.blocked) === 1 ? 'Bloqueado' : 'Ativo';

      document.getElementById("btnBlockCard").textContent = Number(a.blocked) === 1 ? 'Desbloquear cartão' : 'Bloquear cartão';

      dossier.style.display = "flex";
      dossier.scrollIntoView({ behavior: "smooth", block: "start" });
    } catch (err) {
      alert("Erro ao carregar aluno.");
    }
  }

  if (dossierClose) dossierClose.addEventListener("click", () => {
    dossier.style.display = "none";
    currentAlunoLogin = null;
  });

  const btnBlock  = document.getElementById("btnBlockCard");
  const btnDelete = document.getElementById("btnDeleteAluno");

  if (btnBlock) btnBlock.addEventListener("click", async () => {
    if (!currentAlunoLogin) return;
    const fd = new FormData();
    fd.append("login", currentAlunoLogin);
    try {
      const res  = await fetch("/PAP/api/admin_alunos.php?action=toggle_block", { method: "POST", headers: CSRF_HEADERS, body: fd });
      const data = await res.json();
      if (!data.ok) throw new Error();
      await loadAlunos();
      openDossier(currentAlunoLogin);
    } catch { alert("Erro."); }
  });

  if (btnDelete) btnDelete.addEventListener("click", () => {
    if (!currentAlunoLogin) return;
    showConfirm(
      "Eliminar aluno",
      `Tens a certeza? Todos os dados do aluno "${currentAlunoLogin}" serão apagados (incluindo histórico de presenças). Esta ação é irreversível.`,
      async () => {
        const fd = new FormData();
        fd.append("login", currentAlunoLogin);
        try {
          const res  = await fetch("/PAP/api/admin_alunos.php?action=delete", { method: "POST", headers: CSRF_HEADERS, body: fd });
          const data = await res.json();
          if (!data.ok) throw new Error();
          dossier.style.display = "none";
          currentAlunoLogin = null;
          await loadAlunos();
        } catch { alert("Erro ao eliminar."); }
      }
    );
  });

  // ── TESTES list + delete ─────────────────────────────────
  const testesList       = document.getElementById("testes-list");
  const testesFilterNum  = document.getElementById("testesFilterNum");
  const testesFilterLet  = document.getElementById("testesFilterLetra");

  async function loadTestes() {
    if (!testesList) return;
    testesList.innerHTML = '<p class="logs-empty">A carregar...</p>';
    const params = new URLSearchParams({ action: "list" });
    if (testesFilterNum?.value) params.append("turma_num", testesFilterNum.value);
    if (testesFilterLet?.value) params.append("turma_letra", testesFilterLet.value);
    try {
      const res  = await fetch("/PAP/api/admin_testes.php?" + params.toString());
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
    testesList.innerHTML = testes.map(t => `
      <div class="teste-row">
        <div class="teste-info">
          <div class="teste-title">${escapeHtml(t.titulo)} <span class="teste-turma">${t.turma_num}${escapeHtml(t.turma_letra)}</span></div>
          <div class="teste-meta">
            ${escapeHtml(t.materia || '—')} • ${escapeHtml(t.data_teste)}
            ${t.professor_nome ? ' • ' + escapeHtml(t.professor_nome) : ''}
          </div>
          ${t.descricao ? `<div class="teste-desc">${escapeHtml(t.descricao)}</div>` : ''}
        </div>
        <button class="danger-btn teste-del" data-id="${t.id}" data-title="${escapeHtml(t.titulo)}">Eliminar</button>
      </div>
    `).join("");

    testesList.querySelectorAll(".teste-del").forEach(btn => {
      btn.addEventListener("click", () => {
        const id    = btn.dataset.id;
        const title = btn.dataset.title;
        showConfirm(
          "Eliminar teste",
          `Eliminar o teste "${title}"? Esta ação é irreversível.`,
          async () => {
            const fd = new FormData();
            fd.append("id", id);
            try {
              const res  = await fetch("/PAP/api/admin_testes.php?action=delete", { method: "POST", headers: CSRF_HEADERS, body: fd });
              const data = await res.json();
              if (!data.ok) throw new Error();
              await loadTestes();
            } catch { alert("Erro ao eliminar."); }
          }
        );
      });
    });
  }

  if (testesFilterNum) testesFilterNum.addEventListener("change", loadTestes);
  if (testesFilterLet) testesFilterLet.addEventListener("change", loadTestes);

  // ── CHARTS block ──────────────────────────────────────────
  let chartInstance = null;

  function initChart() {
    const canvas = document.getElementById("chartPresencas");
    if (!canvas || !window.Chart || !window.__chartData) return;
    if (chartInstance) return;

    chartInstance = new Chart(canvas, {
      type: "bar",
      data: {
        labels: window.__chartData.labels,
        datasets: [{
          label: "Presentes",
          data: window.__chartData.values,
          backgroundColor: "#3b82f6",
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });
  }

  initChart();

  // ── LOGS block ────────────────────────────────────────────
  const logsList    = document.getElementById("logs-list");
  const logsSearch  = document.getElementById("logsSearch");
  const logsLevel   = document.getElementById("logsLevel");
  const logsRefresh = document.getElementById("logsRefresh");

  let allLogs = [];

  async function loadLogs() {
    if (!logsList) return;
    logsList.innerHTML = '<p class="logs-empty">A carregar logs...</p>';
    try {
      const res  = await fetch("/PAP/api/admin_logs.php");
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
    const q     = (logsSearch?.value || "").toLowerCase().trim();
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
        </div>`;
      logsList.appendChild(item);
    });
  }

  if (logsSearch) logsSearch.addEventListener("input", renderLogs);
  if (logsLevel)  logsLevel.addEventListener("change", renderLogs);
  if (logsRefresh) logsRefresh.addEventListener("click", loadLogs);

  // ── SUPORTE (access problems) ─────────────────────────────
  const suporteList    = document.getElementById("suporteList");
  const suporteRefresh = document.getElementById("suporteRefresh");
  const suporteDossier = document.getElementById("suporteDossier");
  let suporteItems    = [];
  let suporteCurrent  = null;

  async function loadSuporte() {
    if (!suporteList) return;
    suporteList.innerHTML = '<p class="logs-empty">A carregar...</p>';
    try {
      const res  = await fetch("/PAP/api/admin_access_problems.php?action=list");
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
      const novo = Number(it.lido) === 1 ? "" : '<span class="badge-blocked">NOVO</span>';
      return `
        <div class="aluno-row" data-id="${it.id}" style="${Number(it.lido) === 1 ? "opacity:0.55;" : ""}">
          <span class="scan-dot ${Number(it.lido) === 1 ? "present" : "absent"}"></span>
          <div class="scan-info">
            <div class="scan-name">${escapeHtml(it.email || "(sem email)")}</div>
            <div class="scan-meta">${escapeHtml(it.criado_em || "")}</div>
          </div>
          ${novo}
        </div>`;
    }).join("");

    suporteList.querySelectorAll(".aluno-row").forEach(row => {
      row.addEventListener("click", () => openSuporteDossier(Number(row.dataset.id)));
    });
  }

  function openSuporteDossier(id) {
    const it = suporteItems.find(x => Number(x.id) === id);
    if (!it || !suporteDossier) return;
    suporteCurrent = it;

    document.getElementById("suporteDossierData").textContent    = it.criado_em || "—";
    document.getElementById("suporteDossierEmail").textContent   = it.email || "(sem email)";
    document.getElementById("suporteDossierEstado").textContent  = Number(it.lido) === 1 ? "Lido" : "Novo";
    document.getElementById("suporteDossierMensagem").textContent = it.mensagem || "";

    const btnRead = document.getElementById("suporteBtnRead");
    if (btnRead) btnRead.textContent = Number(it.lido) === 1 ? "Marcar não lido" : "Marcar lido";

    suporteDossier.style.display = "flex";
    suporteDossier.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  const suporteDossierClose = document.getElementById("suporteDossierClose");
  if (suporteDossierClose) suporteDossierClose.addEventListener("click", () => {
    if (suporteDossier) suporteDossier.style.display = "none";
    suporteCurrent = null;
  });

  async function suporteAction(action) {
    if (!suporteCurrent) return;
    const fd = new FormData();
    fd.set("id", suporteCurrent.id);
    try {
      const res  = await fetch("/PAP/api/admin_access_problems.php?action=" + action, {
        method: "POST", headers: CSRF_HEADERS, body: fd
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

  const suporteBtnRead   = document.getElementById("suporteBtnRead");
  const suporteBtnDelete = document.getElementById("suporteBtnDelete");
  if (suporteBtnRead) suporteBtnRead.addEventListener("click", () => {
    if (!suporteCurrent) return;
    suporteAction(Number(suporteCurrent.lido) === 1 ? "mark_unread" : "mark_read");
  });
  if (suporteBtnDelete) suporteBtnDelete.addEventListener("click", () => {
    if (!suporteCurrent) return;
    if (!confirm("Eliminar esta mensagem?")) return;
    suporteAction("delete");
  });

  if (suporteRefresh) suporteRefresh.addEventListener("click", loadSuporte);

  // ── ATUALIZAR block ───────────────────────────────────────
  const updateSearch  = document.getElementById("update-search");
  const updateResults = document.getElementById("update-results");
  const updateForm    = document.getElementById("update-form");

  if (updateSearch && updateResults && updateForm) {
    const updateId         = document.getElementById("update-id");
    const updateType       = document.getElementById("update-type");
    const updateNome       = document.getElementById("update-nome");
    const updateLogin      = document.getElementById("update-login");
    const updateIdade      = document.getElementById("update-idade");
    const updateTurmaNum   = document.getElementById("updateTurmaNum");
    const updateTurmaLetra = document.getElementById("updateTurmaLetra");
    const updateNumero     = document.getElementById("updateNumeroTurma");
    const updateUid        = document.getElementById("updateUid");
    const updatePassword   = document.getElementById("update-password");
    const updateStatus     = document.getElementById("updateCardStatus");

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
      if (updateId)       updateId.value       = user.id            ?? "";
      if (updateType)     updateType.value     = user.tipo          ?? "";
      if (updateNome)     updateNome.value     = user.nome          ?? "";
      if (updateLogin)    updateLogin.value    = user.login         ?? "";
      if (updateIdade)    updateIdade.value    = user.idade         ?? "";
      if (updateNumero)   updateNumero.value   = user.numero_turma  ?? "";
      if (updateUid)      updateUid.value      = user.uid           ?? "";
      if (updatePassword) updatePassword.value = "";

      const { num, letra } = splitTurma(user.turma);
      if (updateTurmaNum)   updateTurmaNum.value   = num;
      if (updateTurmaLetra) updateTurmaLetra.value = letra;

      updateForm.style.display = "block";

      const isProfessor = user.tipo === "professor";
      const idadeRow    = updateIdade?.closest(".form-row");
      const numeroRow   = updateNumero?.closest(".form-row");
      if (idadeRow)  idadeRow.style.display  = isProfessor ? "none" : "";
      if (numeroRow) numeroRow.style.display = isProfessor ? "none" : "";
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
      const tNum    = updateTurmaNum?.value   || "";
      const tLetra  = (updateTurmaLetra?.value || "").toUpperCase();
      const turma   = `${tNum}${tLetra}`;
      const payload = {
        id:           updateId?.value       ?? "",
        tipo:         (updateType?.value    || "").toLowerCase(),
        nome:         updateNome?.value     ?? "",
        login:        updateLogin?.value    ?? "",
        idade:        updateIdade?.value    ?? "",
        turma,
        turma_num:    tNum,
        turma_letra:  tLetra,
        numero_turma: updateNumero?.value   ?? "",
        uid:          updateUid?.value      ?? "",
        password:     updatePassword?.value ?? "",
      };
      try {
        const res  = await fetch("/PAP/api/admin_update_card.php?action=update", {
          method:  "POST",
          headers: { "Content-Type": "application/json", "X-CSRF-Token": CSRF },
          body:    JSON.stringify(payload),
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
  const findSearch  = document.getElementById("find-search");
  const findResults = document.getElementById("find-results");
  const findForm    = document.getElementById("find-form");

  if (findSearch && findResults && findForm) {
    const findNome       = document.getElementById("find-nome");
    const findLogin      = document.getElementById("find-login");
    const findIdade      = document.getElementById("find-idade");
    const findTurmaNum   = document.getElementById("findTurmaNum");
    const findTurmaLetra = document.getElementById("findTurmaLetra");
    const findNumero     = document.getElementById("findNumeroTurma");
    const findUid        = document.getElementById("findUid");

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
      if (findNome)   findNome.value   = user.nome          ?? "";
      if (findLogin)  findLogin.value  = user.login         ?? "";
      if (findIdade)  findIdade.value  = user.idade         ?? "";
      if (findNumero) findNumero.value = user.numero_turma  ?? "";
      if (findUid)    findUid.value    = user.uid           ?? "";

      const { num, letra } = splitTurma(user.turma);
      if (findTurmaNum)   findTurmaNum.value   = num;
      if (findTurmaLetra) findTurmaLetra.value = letra;

      findForm.style.display = "block";

      const isProfessor = user.tipo === "professor";
      const idadeRow    = findIdade?.closest(".form-row");
      const numeroRow   = findNumero?.closest(".form-row");
      if (idadeRow)  idadeRow.style.display  = isProfessor ? "none" : "";
      if (numeroRow) numeroRow.style.display = isProfessor ? "none" : "";
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
  const noticiasList    = document.getElementById("noticiasList");
  const noticiaForm     = document.getElementById("noticiaForm");
  const noticiaId       = document.getElementById("noticiaId");
  const noticiaTitulo   = document.getElementById("noticiaTitulo");
  const noticiaCorpo    = document.getElementById("noticiaCorpo");
  const noticiaImagem   = document.getElementById("noticiaImagem");
  const noticiaAtivo    = document.getElementById("noticiaAtivo");
  const noticiaSubmit   = document.getElementById("noticiaSubmit");
  const noticiaCancel   = document.getElementById("noticiaCancel");
  const noticiaStatus   = document.getElementById("noticiaStatus");

  function resetNoticiaForm() {
    if (!noticiaForm) return;
    noticiaForm.reset();
    if (noticiaId)     noticiaId.value = "";
    if (noticiaAtivo)  noticiaAtivo.checked = true;
    if (noticiaSubmit) noticiaSubmit.textContent = "Criar notícia";
    if (noticiaCancel) noticiaCancel.style.display = "none";
    if (noticiaStatus) noticiaStatus.textContent = "";
  }

  async function loadNoticias() {
    if (!noticiasList) return;
    noticiasList.innerHTML = '<p class="logs-empty">A carregar...</p>';
    try {
      const res  = await fetch("/PAP/api/noticias.php?action=list");
      const data = await res.json();
      if (!data.ok) throw new Error();

      if (data.noticias.length === 0) {
        noticiasList.innerHTML = '<p class="logs-empty">Ainda não há notícias.</p>';
        return;
      }

      noticiasList.innerHTML = data.noticias.map(n => `
        <div class="noticia-admin-item ${n.ativo ? '' : 'inactive'}" data-id="${n.id}">
          ${n.imagem ? `<img class="noticia-thumb" src="${escapeHtml(n.imagem)}" alt="">` : '<div class="noticia-thumb noticia-thumb-empty"></div>'}
          <div class="noticia-admin-body">
            <div class="noticia-admin-title">${escapeHtml(n.titulo)}</div>
            <div class="noticia-admin-corpo">${escapeHtml(n.corpo)}</div>
          </div>
          <div class="noticia-admin-actions">
            <button type="button" class="noticia-edit">Editar</button>
            <button type="button" class="noticia-toggle">${n.ativo ? 'Ocultar' : 'Ativar'}</button>
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
      if (noticiaStatus) { noticiaStatus.textContent = "A guardar..."; noticiaStatus.style.color = "#6b7280"; }

      const fd = new FormData(noticiaForm);
      const editing = noticiaId && noticiaId.value !== "";
      fd.append("action", editing ? "update" : "create");
      if (!fd.has("ativo")) fd.append("ativo", "0");

      try {
        const res  = await fetch("/PAP/api/noticias.php", { method: "POST", headers: CSRF_HEADERS, body: fd });
        const data = await res.json();
        if (data.ok) {
          if (noticiaStatus) { noticiaStatus.textContent = editing ? "Atualizada." : "Criada."; noticiaStatus.style.color = "#10b981"; }
          resetNoticiaForm();
          loadNoticias();
        } else if (noticiaStatus) {
          noticiaStatus.textContent = "Erro: " + (data.error || "desconhecido");
          noticiaStatus.style.color = "#ef4444";
        }
      } catch {
        if (noticiaStatus) { noticiaStatus.textContent = "Erro de rede."; noticiaStatus.style.color = "#ef4444"; }
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
          const res  = await fetch("/PAP/api/noticias.php?action=get&id=" + encodeURIComponent(id));
          const data = await res.json();
          if (!data.ok) throw new Error();
          const n = data.noticia;
          if (noticiaId)     noticiaId.value     = n.id;
          if (noticiaTitulo) noticiaTitulo.value = n.titulo || "";
          if (noticiaCorpo)  noticiaCorpo.value  = n.corpo || "";
          if (noticiaImagem) noticiaImagem.value = n.imagem || "";
          if (noticiaAtivo)  noticiaAtivo.checked = Number(n.ativo) === 1;
          if (noticiaSubmit) noticiaSubmit.textContent = "Guardar alterações";
          if (noticiaCancel) noticiaCancel.style.display = "";
          noticiaForm.scrollIntoView({ behavior: "smooth", block: "start" });
        } catch { alert("Erro ao carregar notícia."); }
        return;
      }

      if (e.target.matches(".noticia-toggle")) {
        const fd = new FormData();
        fd.append("action", "toggle");
        fd.append("id", id);
        const res  = await fetch("/PAP/api/noticias.php", { method: "POST", headers: CSRF_HEADERS, body: fd });
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
            const res  = await fetch("/PAP/api/noticias.php", { method: "POST", headers: CSRF_HEADERS, body: fd });
            const data = await res.json();
            if (data.ok) loadNoticias();
          }
        );
      }
    });
  }

  // ── NOTAS (Professor lança + Aluno vê) ───────────────────
  (function initNotasProfessor() {
    const listEl    = document.getElementById("notasList");
    const formEl    = document.getElementById("notaForm");
    const alunoSel  = document.getElementById("notaAluno");
    const statusEl  = document.getElementById("notaStatus");
    if (!formEl || !alunoSel) return;

    function badgeClass(v) {
      if (v >= 14) return 'nota-good';
      if (v >= 10) return 'nota-mid';
      return 'nota-bad';
    }

    function fmtDate(iso) {
      if (!iso) return '';
      const [y, m, d] = iso.split('-');
      return d + '/' + m + '/' + y;
    }

    async function loadAlunos() {
      try {
        const res  = await fetch("/PAP/api/notas.php?action=alunos_da_turma");
        const data = await res.json();
        if (!data.ok) throw new Error();
        if (!data.alunos.length) {
          alunoSel.innerHTML = '<option value="">Sem alunos na turma</option>';
          return;
        }
        alunoSel.innerHTML = '<option value="">Escolher aluno</option>' +
          data.alunos.map(a => `<option value="${escapeHtml(a.login)}">Nº ${escapeHtml(String(a.numero || ''))} — ${escapeHtml(a.nome)}</option>`).join('');
      } catch {
        alunoSel.innerHTML = '<option value="">Erro ao carregar</option>';
      }
    }

    async function loadNotas() {
      if (!listEl) return;
      try {
        const res  = await fetch("/PAP/api/notas.php?action=list");
        const data = await res.json();
        if (!data.ok) throw new Error();

        if (!data.notas.length) {
          listEl.innerHTML = '<p class="empty-state">Ainda não lançaste notas.</p>';
          return;
        }

        listEl.innerHTML = data.notas.map(n => `
          <div class="nota-row" data-id="${n.id}">
            <div class="nota-info">
              <div class="nota-head">
                <span class="nota-badge ${badgeClass(n.valor)}">${n.valor.toFixed(1)}</span>
                <strong>${escapeHtml(n.aluno_nome || n.login_aluno)}</strong>
                <span class="nota-meta-small">Nº ${escapeHtml(String(n.aluno_numero || ''))} • ${escapeHtml(n.aluno_turma || '')}</span>
              </div>
              <div class="nota-meta">
                ${escapeHtml(n.tipo)} • ${escapeHtml(n.materia)} • ${fmtDate(n.data)}
              </div>
              ${n.observacao ? `<div class="nota-obs">${escapeHtml(n.observacao)}</div>` : ''}
            </div>
            <button type="button" class="danger-btn nota-del">Eliminar</button>
          </div>
        `).join('');
      } catch {
        listEl.innerHTML = '<p class="empty-state">Erro ao carregar.</p>';
      }
    }

    formEl.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (statusEl) { statusEl.textContent = "A guardar..."; statusEl.style.color = "#6b7280"; }

      const fd = new FormData(formEl);
      fd.append("action", "create");

      try {
        const res  = await fetch("/PAP/api/notas.php", { method: "POST", headers: CSRF_HEADERS, body: fd });
        const data = await res.json();
        if (data.ok) {
          formEl.reset();
          if (statusEl) { statusEl.textContent = "Nota lançada."; statusEl.style.color = "#10b981"; }
          loadNotas();
        } else if (statusEl) {
          statusEl.textContent = "Erro: " + (data.error || "desconhecido");
          statusEl.style.color = "#ef4444";
        }
      } catch {
        if (statusEl) { statusEl.textContent = "Erro de rede."; statusEl.style.color = "#ef4444"; }
      }
    });

    if (listEl) listEl.addEventListener("click", async (e) => {
      if (!e.target.matches('.nota-del')) return;
      const row = e.target.closest('.nota-row');
      const id  = row.dataset.id;
      if (!confirm("Eliminar esta nota?")) return;

      const fd = new FormData();
      fd.append("action", "delete");
      fd.append("id", id);

      const res  = await fetch("/PAP/api/notas.php", { method: "POST", headers: CSRF_HEADERS, body: fd });
      const data = await res.json();
      if (data.ok) row.remove();
    });

    loadAlunos();
    loadNotas();
  })();

  (function initNotasAluno() {
    const wrap     = document.getElementById("notasAluno");
    const chartEl  = document.getElementById("notasChart");
    const chartBox = document.getElementById("notasChartWrap");
    if (!wrap) return;

    function badgeClass(v) {
      if (v >= 14) return 'nota-good';
      if (v >= 10) return 'nota-mid';
      return 'nota-bad';
    }

    function fmtDate(iso) {
      if (!iso) return '';
      const [y, m, d] = iso.split('-');
      return d + '/' + m + '/' + y;
    }

    function mediaGroup(notas) {
      if (!notas.length) return 0;
      const s = notas.reduce((acc, n) => acc + n.valor, 0);
      return s / notas.length;
    }

    async function load() {
      try {
        const res  = await fetch("/PAP/api/notas.php?action=list");
        const data = await res.json();
        if (!data.ok) throw new Error();

        if (!data.notas.length) {
          wrap.innerHTML = '<p class="empty-state">Ainda não tens notas.</p>';
          return;
        }

        const groups = {};
        data.notas.forEach(n => {
          if (!groups[n.materia]) groups[n.materia] = [];
          groups[n.materia].push(n);
        });

        wrap.innerHTML = Object.keys(groups).sort().map(materia => {
          const notas = groups[materia];
          const media = mediaGroup(notas);
          return `
            <div class="materia-card">
              <div class="materia-head">
                <span class="materia-nome">${escapeHtml(materia)}</span>
                <span class="materia-media ${badgeClass(media)}">Média ${media.toFixed(1)}</span>
              </div>
              <div class="materia-notas">
                ${notas.map(n => `
                  <div class="nota-mini">
                    <span class="nota-badge ${badgeClass(n.valor)}">${n.valor.toFixed(1)}</span>
                    <div class="nota-mini-body">
                      <div class="nota-mini-head">${escapeHtml(n.tipo)} <span class="nota-mini-date">${fmtDate(n.data)}</span></div>
                      ${n.professor_nome ? `<div class="nota-mini-prof">${escapeHtml(n.professor_nome)}</div>` : ''}
                      ${n.observacao ? `<div class="nota-mini-obs">${escapeHtml(n.observacao)}</div>` : ''}
                    </div>
                  </div>
                `).join('')}
              </div>
            </div>
          `;
        }).join('');

        if (chartEl && window.Chart) {
          const sorted = data.notas.slice().sort((a, b) => (a.data || '').localeCompare(b.data || ''));
          const byMat  = {};
          sorted.forEach(n => {
            if (!byMat[n.materia]) byMat[n.materia] = [];
            byMat[n.materia].push({ x: n.data, y: n.valor });
          });

          const palette = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
          const datasets = Object.keys(byMat).map((mat, i) => ({
            label: mat,
            data: byMat[mat].map(p => p.y),
            labels: byMat[mat].map(p => p.x),
            borderColor: palette[i % palette.length],
            backgroundColor: palette[i % palette.length] + '33',
            tension: 0.2,
            fill: false,
          }));

          const allLabels = sorted.map(n => fmtDate(n.data));

          if (chartBox) chartBox.style.display = '';
          new Chart(chartEl, {
            type: 'line',
            data: {
              labels: allLabels,
              datasets: Object.keys(byMat).map((mat, i) => ({
                label: mat,
                data: sorted.map(n => n.materia === mat ? n.valor : null),
                borderColor: palette[i % palette.length],
                backgroundColor: palette[i % palette.length] + '33',
                spanGaps: true,
                tension: 0.2,
              })),
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: { y: { min: 0, max: 20, ticks: { stepSize: 2 } } },
              plugins: { legend: { position: 'bottom' } },
            },
          });
        }
      } catch {
        wrap.innerHTML = '<p class="empty-state">Erro ao carregar.</p>';
      }
    }

    load();
  })();

  // ── AGENDA (Aluno + Professor) ───────────────────────────
  (function initAgenda() {
    const listEl = document.getElementById("agendaList");
    if (!listEl) return;

    const formEl   = document.getElementById("agendaForm");
    const statusEl = document.getElementById("agStatus");

    function fmtDate(iso) {
      if (!iso) return '';
      const [y, m, d] = iso.split('-');
      return d + '/' + m + '/' + y;
    }

    async function load() {
      try {
        const res  = await fetch("/PAP/api/agenda.php?action=list");
        const data = await res.json();
        if (!data.ok) { listEl.innerHTML = '<p class="empty-state">Erro ao carregar.</p>'; return; }

        if (data.tarefas.length === 0) {
          listEl.innerHTML = '<p class="empty-state">Ainda não há tarefas.</p>';
          return;
        }

        listEl.innerHTML = data.tarefas.map(t => `
          <div class="agenda-item ${t.concluido ? 'done' : ''}" data-id="${t.id}">
            <label class="agenda-check">
              <input type="checkbox" ${t.concluido ? 'checked' : ''}>
              <span class="agenda-text">
                <span class="agenda-title">${escapeHtml(t.titulo)}</span>
                ${t.data ? `<span class="agenda-date">${fmtDate(t.data)}</span>` : ''}
              </span>
            </label>
            <button class="agenda-delete" type="button" title="Eliminar">×</button>
          </div>
        `).join('');
      } catch (e) {
        listEl.innerHTML = '<p class="empty-state">Erro de rede.</p>';
      }
    }

    if (formEl) formEl.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (statusEl) { statusEl.textContent = "A guardar..."; statusEl.style.color = "#6b7280"; }

      const fd = new FormData(formEl);
      fd.append("action", "create");

      try {
        const res  = await fetch("/PAP/api/agenda.php", { method: "POST", headers: CSRF_HEADERS, body: fd });
        const data = await res.json();
        if (data.ok) {
          formEl.reset();
          if (statusEl) { statusEl.textContent = "Adicionada."; statusEl.style.color = "#10b981"; }
          load();
        } else if (statusEl) {
          statusEl.textContent = "Erro: " + (data.error || "desconhecido");
          statusEl.style.color = "#ef4444";
        }
      } catch {
        if (statusEl) { statusEl.textContent = "Erro de rede."; statusEl.style.color = "#ef4444"; }
      }
    });

    listEl.addEventListener("change", async (e) => {
      if (!e.target.matches('.agenda-check input[type="checkbox"]')) return;
      const item = e.target.closest('.agenda-item');
      const id   = item.dataset.id;

      const fd = new FormData();
      fd.append("action", "toggle");
      fd.append("id", id);

      const res  = await fetch("/PAP/api/agenda.php", { method: "POST", headers: CSRF_HEADERS, body: fd });
      const data = await res.json();
      if (data.ok) item.classList.toggle('done', data.concluido === 1);
      else e.target.checked = !e.target.checked;
    });

    listEl.addEventListener("click", async (e) => {
      if (!e.target.matches('.agenda-delete')) return;
      const item = e.target.closest('.agenda-item');
      const id   = item.dataset.id;
      if (!confirm("Eliminar esta tarefa?")) return;

      const fd = new FormData();
      fd.append("action", "delete");
      fd.append("id", id);

      const res  = await fetch("/PAP/api/agenda.php", { method: "POST", headers: CSRF_HEADERS, body: fd });
      const data = await res.json();
      if (data.ok) {
        item.remove();
        if (!listEl.querySelector('.agenda-item')) {
          listEl.innerHTML = '<p class="empty-state">Ainda não há tarefas.</p>';
        }
      }
    });

    load();
  })();

  // ── SUMÁRIOS (Aluno list + Professor create/list) ────────
  (function initSumarios() {
    const listEl = document.getElementById("sumariosList");
    if (!listEl) return;

    const formEl   = document.getElementById("sumarioForm");
    const statusEl = document.getElementById("sumStatus");

    function fmtDate(iso) {
      if (!iso) return '';
      const [y, m, d] = iso.split('-');
      return d + '/' + m + '/' + y;
    }

    async function load() {
      try {
        const res  = await fetch("/PAP/api/sumarios.php?action=list");
        const data = await res.json();
        if (!data.ok) { listEl.innerHTML = '<p class="empty-state">Erro ao carregar.</p>'; return; }

        if (data.sumarios.length === 0) {
          listEl.innerHTML = '<p class="empty-state">Ainda não há sumários.</p>';
          return;
        }

        const canDelete = !!formEl;

        listEl.innerHTML = data.sumarios.map(s => `
          <div class="sumario-item" data-id="${s.id}">
            <div class="sumario-head">
              <span class="sumario-date">${fmtDate(s.data)}</span>
              <span class="sumario-turma">${s.turma_num}${escapeHtml(s.turma_letra)}</span>
              <span class="sumario-materia">${escapeHtml(s.materia || '—')}</span>
              ${s.professor_nome ? `<span class="sumario-prof">${escapeHtml(s.professor_nome)}</span>` : ''}
              ${canDelete ? `<button class="sumario-delete" type="button" title="Eliminar">×</button>` : ''}
            </div>
            <div class="sumario-body">${escapeHtml(s.descricao)}</div>
          </div>
        `).join('');
      } catch {
        listEl.innerHTML = '<p class="empty-state">Erro de rede.</p>';
      }
    }

    if (formEl) formEl.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (statusEl) { statusEl.textContent = "A guardar..."; statusEl.style.color = "#6b7280"; }

      const fd = new FormData(formEl);
      fd.append("action", "create");

      try {
        const res  = await fetch("/PAP/api/sumarios.php", { method: "POST", headers: CSRF_HEADERS, body: fd });
        const data = await res.json();
        if (data.ok) {
          formEl.reset();
          if (statusEl) { statusEl.textContent = "Sumário criado."; statusEl.style.color = "#10b981"; }
          load();
        } else if (statusEl) {
          statusEl.textContent = "Erro: " + (data.error || "desconhecido");
          statusEl.style.color = "#ef4444";
        }
      } catch {
        if (statusEl) { statusEl.textContent = "Erro de rede."; statusEl.style.color = "#ef4444"; }
      }
    });

    listEl.addEventListener("click", async (e) => {
      if (!e.target.matches('.sumario-delete')) return;
      const item = e.target.closest('.sumario-item');
      const id   = item.dataset.id;
      if (!confirm("Eliminar este sumário?")) return;

      const fd = new FormData();
      fd.append("action", "delete");
      fd.append("id", id);

      const res  = await fetch("/PAP/api/sumarios.php", { method: "POST", headers: CSRF_HEADERS, body: fd });
      const data = await res.json();
      if (data.ok) {
        item.remove();
        if (!listEl.querySelector('.sumario-item')) {
          listEl.innerHTML = '<p class="empty-state">Ainda não há sumários.</p>';
        }
      }
    });

    load();
  })();

});

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

  