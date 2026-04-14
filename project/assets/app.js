document.addEventListener("DOMContentLoaded", function () {
  const loginModal = document.getElementById("loginModal");
  const registerModal = document.getElementById("registerModal");

  const openLogin = document.getElementById("openLogin");
  const openRegister = document.getElementById("openRegister");

  const loginForm = document.getElementById("loginForm");
  const registerForm = document.getElementById("registerForm");

  if (openLogin && loginModal) {
    openLogin.addEventListener("click", () => {
      loginModal.classList.remove("hidden");
    });
  }

  if (openRegister && registerModal) {
    openRegister.addEventListener("click", () => {
      registerModal.classList.remove("hidden");
    });
  }

  document.querySelectorAll(".close-modal").forEach(btn => {
    btn.addEventListener("click", function () {
      const modalId = this.dataset.close;
      const modal = document.getElementById(modalId);
      if (modal) modal.classList.add("hidden");
    });
  });

  [loginModal, registerModal].forEach(modal => {
    if (!modal) return;
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        modal.classList.add("hidden");
      }
    });
  });

  if (loginForm) {
  loginForm.addEventListener("submit", async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const statusBox = document.getElementById("loginFormStatus");

    try {
      const response = await fetch("/PAP/api/auth.php", {
        method: "POST",
        body: formData
      });

      const data = await response.json();

      if (data.ok) {
        if (loginModal) loginModal.classList.add("hidden");
        location.reload();
      } else {
        statusBox.textContent = data.error || "Login failed";
        statusBox.style.color = "red";
      }
    } catch (err) {
      statusBox.textContent = "Ошибка запроса";
      statusBox.style.color = "red";
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
      const response = await fetch("/PAP/api/register.php", {
        method: "POST",
        body: formData
      });

      const data = await response.json();

      if (data.ok) {
        if (registerModal) registerModal.classList.add("hidden");
        location.reload();
      } else {
        statusBox.textContent = data.error || "Erro no registo";
        statusBox.style.color = "red";
      }
    } catch (err) {
      statusBox.textContent = "Ошибка запроса";
      statusBox.style.color = "red";
      console.error(err);
    }
  });
  }
});

document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('update-search');
  const resultsBox = document.getElementById('update-results');
  const form = document.getElementById('update-form');

  if (!searchInput || !resultsBox || !form) return;

  const idInput = document.getElementById('update-id');
  const typeInput = document.getElementById('update-type');
  const nomeInput = document.getElementById('update-nome');
  const loginInput = document.getElementById('update-login');
  const idadeInput = document.getElementById('update-idade');
  const turmaNumInput = document.getElementById('updateTurmaNum');
  const turmaLetraInput = document.getElementById('updateTurmaLetra');
  const numeroInput = document.getElementById('updateNumeroTurma');
  const uidInput = document.getElementById('updateUid');
  const passwordInput = document.getElementById('update-password');
  const statusBox = document.getElementById('updateCardStatus');

  let users = [];

  async function loadUsers() {
    try {
      const response = await fetch('/PAP/api/admin_update_card.php?action=list');
      const data = await response.json();

      if (data.success) {
        users = data.users || [];
        renderResults(users);
      } else {
        resultsBox.innerHTML = '<p>Erro ao carregar utilizadores.</p>';
      }
    } catch (error) {
      console.error('Erro ao carregar utilizadores:', error);
      resultsBox.innerHTML = '<p>Erro de ligação ao servidor.</p>';
    }
  }

  function renderResults(list) {
  resultsBox.innerHTML = '';

  if (!list.length) {
    resultsBox.innerHTML = '<p>Nenhum resultado encontrado.</p>';
    return;
  }

  list.forEach(user => {
    const item = document.createElement('div');
    item.className = 'update-result-item';

    const role = user.tipo === 'professor' ? 'Professor' : 'Aluno';
    const dotClass = user.tipo === 'professor' ? 'dot-professor' : 'dot-aluno';

    item.innerHTML = `
      <div class="update-left">
        <div class="update-dot ${dotClass}"></div>
        <div>
          <div class="update-name">${user.nome}</div>
          <div class="update-role">${role}</div>
        </div>
      </div>
    `;

    item.addEventListener('click', () => fillForm(user));

    resultsBox.appendChild(item);
  });
}         

  function fillForm(user) {
    idInput.value = user.id || '';
    typeInput.value = user.tipo || '';
    nomeInput.value = user.nome || '';
    loginInput.value = user.login || '';
    idadeInput.value = user.idade || '';
    numeroInput.value = user.numero_turma || '';
    uidInput.value = user.uid || '';
    passwordInput.value = '';

    const turma = (user.turma || '').toString().trim();
    if (turma.length >= 2) {
      turmaNumInput.value = turma.slice(0, -1);
      turmaLetraInput.value = turma.slice(-1).toUpperCase();
    } else {
      turmaNumInput.value = '';
      turmaLetraInput.value = '';
    }

    form.style.display = 'block';

    const idadeRow = document.getElementById('update-idade')?.closest('.form-row');
    const numeroRow = document.getElementById('updateNumeroTurma')?.closest('.form-row');

    if (user.tipo === 'professor') {
      if (idadeRow) idadeRow.style.display = 'none';
      if (numeroRow) numeroRow.style.display = 'none';
    } else {
      if (idadeRow) idadeRow.style.display = '';
      if (numeroRow) numeroRow.style.display = '';
    }
  }

  searchInput.addEventListener('input', () => {
    const value = searchInput.value.toLowerCase().trim();

    const filtered = users.filter(user =>
      (user.nome || '').toLowerCase().includes(value)
    );

    renderResults(filtered);
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const turma = `${turmaNumInput.value || ''}${turmaLetraInput.value || ''}`;

    const payload = {
      id: idInput.value,
      tipo: (typeInput.value || '').toLowerCase(),
      nome: nomeInput.value,
      login: loginInput.value,
      idade: idadeInput.value,
      turma: turma,
      numero_turma: numeroInput.value,
      uid: uidInput.value,
      password: passwordInput.value
    };

    try {
      const response = await fetch('/PAP/api/admin_update_card.php?action=update', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json();

      if (data.success) {
        statusBox.textContent = 'Dados atualizados com sucesso.';
        statusBox.style.color = 'green';
        loadUsers();
      } else {
        statusBox.textContent = data.message || 'Erro ao atualizar.';
        statusBox.style.color = 'red';
      }
    } catch (error) {
      console.error('Erro ao atualizar:', error);
      statusBox.textContent = 'Erro de ligação ao servidor.';
      statusBox.style.color = 'red';
    }
  });

  loadUsers();
});