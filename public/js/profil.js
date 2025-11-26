document.addEventListener('DOMContentLoaded', () => {
    const carouselElement = document.getElementById('coupsDeCoeurCarousel');
    const carousel = new bootstrap.Carousel(carouselElement, {
        interval: 3000,
        ride: 'carousel',
        pause: false
    });

    let autoTimeout;

    const resetAutoSlide = () => {
        clearTimeout(autoTimeout);
        autoTimeout = setTimeout(() => {
            carousel.cycle();
        }, 15000); // 15 secondes après dernière interaction
    };

    const stopAutoSlide = () => {
        carousel.pause();
        resetAutoSlide();
    };

    // Dès qu'on clique sur une flèche
    const prevBtn = carouselElement.querySelector('.carousel-control-prev');
    const nextBtn = carouselElement.querySelector('.carousel-control-next');

    if (carouselElement) {
        const carousel = new bootstrap.Carousel(carouselElement, {
            interval: 3000,
            ride: 'carousel',
            pause: false
        });

        let autoTimeout;

        const resetAutoSlide = () => {
            clearTimeout(autoTimeout);
            autoTimeout = setTimeout(() => {
                carousel.cycle();
            }, 15000);
        };

        const stopAutoSlide = () => {
            carousel.pause();
            resetAutoSlide();
        };

        const prevBtn = carouselElement.querySelector('.carousel-control-prev');
        const nextBtn = carouselElement.querySelector('.carousel-control-next');

        [prevBtn, nextBtn].forEach(btn => {
            if (btn) btn.addEventListener('click', stopAutoSlide);
        });

        carouselElement.addEventListener('mouseenter', stopAutoSlide);
        carouselElement.addEventListener('mouseleave', resetAutoSlide);

        resetAutoSlide();
    }

    // Redémarre auto après 15s d'inactivité
    carouselElement.addEventListener('mouseenter', stopAutoSlide);
    carouselElement.addEventListener('mouseleave', resetAutoSlide);

    // Lancer le timer initial
    resetAutoSlide();

    // Cercle de progression lecture en cours
    const pagesReadInput = document.getElementById("pagesReadInput");
    const totalPagesInput = document.getElementById("totalPagesInput");
    const progressCircle = document.getElementById("progressCircle");
    const percentageDisplay = document.getElementById("percentageDisplay");
    const updateBtn = document.getElementById("updateProgressBtn");
    const updateForm = document.getElementById("updateProgressForm");
    const pagesReadField = document.getElementById("pagesReadField");
    const progressContainer = document.getElementById("progressContainer");

    if (!progressCircle || !pagesReadInput || !percentageDisplay || !updateBtn) return;

    const radius = progressCircle.r.baseVal.value;
    const circumference = 2 * Math.PI * radius;
    progressCircle.style.strokeDasharray = circumference;

    function setProgress(percent) {
        const offset = circumference - (percent / 100) * circumference;
        progressCircle.style.strokeDashoffset = offset;
    }

    function computePercent(value) {
        const pages = parseInt(value, 10) || 0;
        const total = totalPagesInput
            ? parseInt(totalPagesInput.value, 10) || 0
            : parseInt(progressCircle.dataset.totalPages || "0", 10);
        if (total <= 0) return { percent: 0, pages, total };
        const percent = Math.min(100, (pages / total) * 100);
        return { percent, pages, total };
    }

    function updateProgressVisual(value) {
        const { percent } = computePercent(value);
        setProgress(percent);
        percentageDisplay.textContent = `${Math.round(percent)}%`;
    }

    function adjustCircleSize() {
        const total = totalPagesInput
            ? parseInt(totalPagesInput.value,10) || 0
            : parseInt(progressCircle.dataset.totalPages || "0", 10);
        if (!progressContainer) return;
        if (total >= 1000) progressContainer.classList.add("large");
        else progressContainer.classList.remove("large");
    }

    pagesReadInput.addEventListener("input", () => {
        updateProgressVisual(pagesReadInput.value);
        adjustCircleSize();
    });

    updateBtn.addEventListener("click", (e) => {
        e.preventDefault();
        updateProgressVisual(pagesReadInput.value);
        if (updateForm && pagesReadField) {
            pagesReadField.value = parseInt(pagesReadInput.value, 10) || 0;
            updateForm.submit();
        }
    });

    // Initialisation
    updateProgressVisual(pagesReadInput.value);
    adjustCircleSize();
});
