document.addEventListener("DOMContentLoaded", function () {

  // ── MODALS ────────────────────────────────────────────────
  const loginModal    = document.getElementById("loginModal");
  const registerModal = document.getElementById("registerModal");
  const openLogin     = document.getElementById("openLogin");
  const openRegister  = document.getElementById("openRegister");
  const loginForm     = document.getElementById("loginForm");
  const registerForm  = document.getElementById("registerForm");

  if (openLogin && loginModal) {
    openLogin.addEventListener("click", () => loginModal.classList.remove("hidden"));
  }
  if (openRegister && registerModal) {
    openRegister.addEventListener("click", () => registerModal.classList.remove("hidden"));
  }

  document.querySelectorAll(".close-modal").forEach((btn) => {
    btn.addEventListener("click", function () {
      const modal = document.getElementById(this.dataset.close);
      if (modal) modal.classList.add("hidden");
    });
  });

  [loginModal, registerModal].forEach((modal) => {
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
        const res  = await fetch("/PAP/api/auth.php", { method: "POST", body: formData });
        const data = await res.json();
        if (data.ok) {
          if (loginModal) loginModal.classList.add("hidden");
          location.reload();
        } else if (statusBox) {
          statusBox.textContent = data.error || "Login failed";
          statusBox.style.color = "red";
        }
      } catch (err) {
        if (statusBox) { statusBox.textContent = "Ошибка запроса"; statusBox.style.color = "red"; }
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
        const res  = await fetch("/PAP/api/register.php", { method: "POST", body: formData });
        const data = await res.json();
        if (data.ok) {
          if (registerModal) registerModal.classList.add("hidden");
          location.reload();
        } else if (statusBox) {
          statusBox.textContent = data.error || "Erro no registo";
          statusBox.style.color = "red";
        }
      } catch (err) {
        if (statusBox) { statusBox.textContent = "Ошибка запроса"; statusBox.style.color = "red"; }
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
            <div class="update-name">${user.nome || ""}</div>
            <div class="update-role">${role}</div>
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
      const res  = await fetch("/PAP/api/admin_alunos.php?action=toggle_block", { method: "POST", body: fd });
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
          const res  = await fetch("/PAP/api/admin_alunos.php?action=delete", { method: "POST", body: fd });
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
              const res  = await fetch("/PAP/api/admin_testes.php?action=delete", { method: "POST", body: fd });
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
        <span class="log-time">${e.time || ""}</span>
        <span class="log-level ${e.level || ""}">${e.level || ""}</span>
        <div class="log-body">
          <span class="log-msg">${e.msg || ""}</span>
          <span class="log-meta">${e.ip || ""}${e.uri ? " · " + e.uri : ""}</span>
          ${ctx ? `<span class="log-ctx">${ctx}</span>` : ""}
        </div>`;
      logsList.appendChild(item);
    });
  }

  if (logsSearch) logsSearch.addEventListener("input", renderLogs);
  if (logsLevel)  logsLevel.addEventListener("change", renderLogs);
  if (logsRefresh) logsRefresh.addEventListener("click", loadLogs);

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
          headers: { "Content-Type": "application/json" },
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

});
