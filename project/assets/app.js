let index = 0;

const carousel = document.getElementById("carousel");
const slides = document.querySelectorAll(".slide");
const totalSlides = slides.length;

const btnPrev = document.querySelector(".nav-btn.prev");
const btnNext = document.querySelector(".nav-btn.next");
const container = document.querySelector(".carousel-container");

function showSlide() {
  if (!carousel) return;
  carousel.style.transform = `translateX(-${index * 100}%)`;
}

function nextSlide() {
  if (totalSlides === 0) return;
  index = (index + 1) % totalSlides;
  showSlide();
}

function prevSlide() {
  if (totalSlides === 0) return;
  index = (index - 1 + totalSlides) % totalSlides;
  showSlide();
}

if (btnNext) btnNext.addEventListener("click", nextSlide);
if (btnPrev) btnPrev.addEventListener("click", prevSlide);

// авто-прокрутка + пауза при наведении
let timer = setInterval(nextSlide, 4000);

if (container) {
  container.addEventListener("mouseenter", () => clearInterval(timer));
  container.addEventListener("mouseleave", () => {
    timer = setInterval(nextSlide, 4000);
  });

 
}



// старт
showSlide();

document.getElementById("loginForm").addEventListener("submit", async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    try {
        const response = await fetch("/PAP/api/auth.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        const statusBox = document.getElementById("loginStatus");

        if (data.ok) {
            statusBox.textContent = "Вы вошли как: " + data.login + " (" + data.role + ")";
            statusBox.style.color = "green";

            document.getElementById("loginForm").style.display = "none";
            document.getElementById("logoutBox").style.display = "block";
        } else {
            statusBox.textContent = data.error || "Login failed";
            statusBox.style.color = "red";
        }
    } catch (err) {
        document.getElementById("loginStatus").textContent = "Ошибка запроса";
        document.getElementById("loginStatus").style.color = "red";
    }
});