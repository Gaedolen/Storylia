document.addEventListener("DOMContentLoaded", function() {
    const track = document.querySelector(".carousel-track");
    const prevBtn = document.querySelector(".carousel-btn.prev");
    const nextBtn = document.querySelector(".carousel-btn.next");

    if (!track || !prevBtn || !nextBtn) return;

    const cards = Array.from(track.children);
    const gap = 20; // correspond au gap CSS
    let index = 0;

    function getCardWidth() {
        const card = cards[0];
        return card.offsetWidth + gap;
    }

    function getCardsPerView() {
        const trackWidth = track.parentElement.offsetWidth;
        const cardWidth = getCardWidth();
        return Math.floor(trackWidth / cardWidth) || 1;
    }

    function updateSlider() {
        const cardWidth = getCardWidth();
        const cardsPerView = getCardsPerView();
        const maxIndex = Math.max(0, cards.length - cardsPerView);
        if (index > maxIndex) index = maxIndex;
        track.style.transform = `translateX(-${index * cardWidth}px)`;
    }

    nextBtn.addEventListener("click", () => {
        const cardsPerView = getCardsPerView();
        const maxIndex = cards.length - cardsPerView;
        if (index < maxIndex) {
            index++;
            updateSlider();
        }
    });

    prevBtn.addEventListener("click", () => {
        if (index > 0) {
            index--;
            updateSlider();
        }
    });

    window.addEventListener("resize", updateSlider);

    updateSlider(); // initial
});
