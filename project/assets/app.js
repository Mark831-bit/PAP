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