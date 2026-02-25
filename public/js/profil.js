document.addEventListener('DOMContentLoaded', () => {
    const carouselElement = document.getElementById('coupsDeCoeurCarousel');
    if (!carouselElement) return;

    // Initialisation du carousel
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

    // Flèches
    const prevBtn = carouselElement.querySelector('.carousel-control-prev');
    const nextBtn = carouselElement.querySelector('.carousel-control-next');
    [prevBtn, nextBtn].forEach(btn => {
        if (btn) btn.addEventListener('click', stopAutoSlide);
    });

    // Hover pour pause / reprise auto-slide
    carouselElement.addEventListener('mouseenter', stopAutoSlide);
    carouselElement.addEventListener('mouseleave', resetAutoSlide);

    // Timer initial
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
    const totalPagesField = document.getElementById("totalPagesField");

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

            if (totalPagesInput && totalPagesField) {
                totalPagesField.value = parseInt(totalPagesInput.value, 10) || 0;
            }

            updateForm.submit();
        }
    });

    // Initialisation
    updateProgressVisual(pagesReadInput.value);
    adjustCircleSize();
});

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('report-user-modal');
    const reportForm = document.getElementById('report-user-form');
    const messageInput = document.getElementById('report-user-message');
    const charCount = document.getElementById('report-user-char-count');
    const closeBtn = document.getElementById('close-report-user');

    // --- Ouvrir la modal ---
    document.querySelectorAll('.btn-report-user').forEach(button => {
        button.addEventListener('click', () => {
            const url = button.dataset.url;
            if (!url) return;

            reportForm.dataset.url = url; // stocker l'URL
            messageInput.value = '';
            charCount.textContent = '0 / 500';
            modal.style.display = 'block';
        });
    });

    // --- Fermer la modal ---
    closeBtn.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.style.display = 'none';
    });

    // --- Compteur de caractères ---
    messageInput.addEventListener('input', () => {
        charCount.textContent = `${messageInput.value.length} / 500`;
    });

    // --- Soumission du formulaire ---
    reportForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const reason = document.getElementById('report-user-reason').value;
        const message = messageInput.value.trim();
        const csrfToken = reportForm.querySelector('input[name="_token"]').value;
        const url = reportForm.dataset.url;

        if (!reason || !message) {
            alert("Veuillez remplir le motif et le message.");
            return;
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ reason, message })
            });

            const data = await response.json();

            if (data.success) {
                modal.style.display = 'none';
                reportForm.reset();
                charCount.textContent = '0 / 500';

                // --- Remplacer le bouton par "Signalé ✅" ---
                const btn = document.querySelector(`.btn-report-user[data-url="${url}"]`);
                if (btn) {
                    btn.outerHTML = `
                        <span class="report-badge in-progress">
                            <svg class="check-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M20 6L9 17l-5-5" />
                            </svg>
                            Signalé
                        </span>
                    `;
                }

                // Facultatif : prévenir l'utilisateur
                console.log(data.message || "Utilisateur signalé.");

            } else {
                alert(data.message || "Une erreur est survenue.");
            }
        } catch (err) {
            console.error(err);
            alert("Erreur lors de l'envoi du signalement.");
        }
    });
});