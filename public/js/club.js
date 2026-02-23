document.addEventListener('DOMContentLoaded', () => {

    // Modal de vote
    (() => {
        const voteBtn = document.getElementById('vote-btn');
        const voteModal = document.getElementById('voteModal');
        if (!voteBtn || !voteModal) return;

        const closeBtn = voteModal.querySelector('.close-modal');

        voteBtn.addEventListener('click', () => voteModal.style.display = 'flex');
        if (closeBtn) closeBtn.addEventListener('click', () => voteModal.style.display = 'none');
        window.addEventListener('click', (e) => { if (e.target === voteModal) voteModal.style.display = 'none'; });
        window.addEventListener('keydown', (e) => { if (e.key === 'Escape') voteModal.style.display = 'none'; });
    })();

    // Modal laisser un avis
    const reviewForm = document.getElementById('reviewForm');
    const textarea = document.getElementById('comment');
    const charCount = document.getElementById('charCount');

    if (reviewForm && textarea && charCount) {

        textarea.addEventListener('input', () => {
            charCount.textContent = textarea.value.length;
        });

        const reviewUrl = "{{ path('club_review_add') }}";

        reviewForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const monthId = reviewForm.dataset.month;
            if (!monthId) return console.error("data-month manquant");

            const data = {
                comment: textarea.value,
                readingMonthId: monthId,
                rating: 5
            };

            try {
                const response = await fetch(reviewUrl, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                if (response.ok && result.success) {
                    location.reload();
                } else {
                    console.error("Erreur:", result.error || response.statusText);
                }
            } catch (err) {
                console.error("Erreur réseau :", err);
            }
        });
    }
    
    // Modal signalement club
    const reportForm = document.getElementById('reportClubForm');
    const modalEl = document.getElementById('reportClubModal');
    const openBtn = document.getElementById('openReportClubBtn');

    if (reportForm && modalEl) {

        // Quand le modal est complètement fermé → rendre le focus au bouton
        modalEl.addEventListener('hidden.bs.modal', () => {
            if (openBtn) {
                openBtn.focus();
            }
        });

        reportForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const reason = reportForm.reason.value.trim();
            const message = document.getElementById('message').value.trim();
            const csrfToken = reportForm.querySelector('input[name="_token"]').value;

            if (!reason || !message) {
                alert("Tous les champs sont requis.");
                return;
            }

            try {
                const response = await fetch(reportForm.action, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ reason, message, _token: csrfToken })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    alert(result.message || "Signalement envoyé !");
                    reportForm.reset();

                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    if (openBtn) {
                        openBtn.textContent = "Signalement envoyé ✓";
                        openBtn.classList.add("disabled");
                        openBtn.style.pointerEvents = "none";
                        openBtn.style.opacity = "0.6";
                        openBtn.removeAttribute("data-bs-toggle");
                        openBtn.removeAttribute("data-bs-target");
                        openBtn.setAttribute("aria-disabled", "true");
                    }
                } else {
                    alert(result.message || "Erreur lors du signalement.");
                }
            } catch (err) {
                console.error("Erreur réseau :", err);
                alert("Erreur réseau.");
            }
        });
    }
});