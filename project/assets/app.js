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
      const turma   = `${updateTurmaNum?.value || ""}${updateTurmaLetra?.value || ""}`;
      const payload = {
        id:           updateId?.value       ?? "",
        tipo:         (updateType?.value    || "").toLowerCase(),
        nome:         updateNome?.value     ?? "",
        login:        updateLogin?.value    ?? "",
        idade:        updateIdade?.value    ?? "",
        turma,
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
