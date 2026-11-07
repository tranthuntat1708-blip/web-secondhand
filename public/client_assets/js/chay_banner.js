document.addEventListener("DOMContentLoaded", () => {
    const track = document.getElementById("banner-track");
    if (!track) return;

    const slides = track.children;
    const btnPrev = document.getElementById("btn-prev");
    const btnNext = document.getElementById("btn-next");

    let currentIndex = 0;
    const totalSlides = slides.length;
    let slideInterval;

    if (totalSlides <= 1) {
        if (btnPrev) btnPrev.style.display = "none";
        if (btnNext) btnNext.style.display = "none";
        return;
    }

    function updateSlide() {
        track.style.transform = `translateX(-${currentIndex * 100}%)`;
    }

    function nextSlide() {
        currentIndex = (currentIndex + 1) % totalSlides;
        updateSlide();
    }

    function prevSlide() {
        currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
        updateSlide();
    }

    function startAutoPlay() {
        clearInterval(slideInterval);
        slideInterval = setInterval(nextSlide, 5000);
    }

    if (btnNext) {
        btnNext.addEventListener("click", () => {
            nextSlide();
            startAutoPlay();
        });
    }

    if (btnPrev) {
        btnPrev.addEventListener("click", () => {
            prevSlide();
            startAutoPlay();
        });
    }

    startAutoPlay();
});
