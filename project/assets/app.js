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

document.addEventListener("DOMContentLoaded", function () {
  const addCardForm = document.getElementById("addCardForm");
  if (!addCardForm) return;

  addCardForm.addEventListener("submit", async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const statusBox = document.getElementById("addCardStatus");

    try {
      const response = await fetch("/PAP/api/admin_add_card.php", {
        method: "POST",
        body: formData
      });

      const data = await response.json();

      if (data.ok) {
        statusBox.textContent = data.message || "Guardado com sucesso";
        statusBox.style.color = "green";
        this.reset();
      } else {
        statusBox.textContent = data.error || "Erro ao guardar";
        statusBox.style.color = "red";
      }
    } catch (err) {
      statusBox.textContent = "Ошибка запроса";
      statusBox.style.color = "red";
      console.error(err);
    }
  });
});