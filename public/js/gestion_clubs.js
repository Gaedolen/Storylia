document.addEventListener('DOMContentLoaded', () => {

    // Fonction POST JSON
    const postJson = async (url, data) => {
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            return await res.json();
        } catch (err) {
            console.error(err);
            return { success: false, error: 'Erreur réseau' };
        }
    };

    // --- Suspendre un club ---
    document.querySelectorAll('.suspend-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const url = btn.dataset.url;
            const reportId = btn.dataset.reportId;
            const clubId = btn.dataset.clubId;
            const csrfToken = btn.dataset.csrf;

            if (!url) return;

            const confirmSuspend = confirm("Voulez-vous vraiment suspendre ce club ?");
            if (!confirmSuspend) return;

            const data = { _token: csrfToken, report_id: reportId };

            const response = await postJson(url, data);

            if (response.success) {
                const td = btn.closest('td');
                td.innerHTML = `<span class="status traite">Traité</span>`;
                alert('Le club a été suspendu et les mails envoyés.');
            } else {
                alert(response.error || 'Erreur inconnue');
            }
        });
    });

    // --- Ignorer un report ---
    document.querySelectorAll('.unsuspend-btn').forEach(btn => {
        // Ignorer ou réactiver (distinction par présence de data-report-id ou data-club-id)
        btn.addEventListener('click', async () => {
            const url = btn.dataset.url;
            const reportId = btn.dataset.reportId;
            const clubId = btn.dataset.clubId;
            const csrfToken = btn.dataset.csrf;

            if (!url) return;

            // Si c’est un ignore
            if (reportId) {
                const confirmIgnore = confirm("Voulez-vous vraiment ignorer ce signalement ?");
                if (!confirmIgnore) return;

                const data = { _token: csrfToken, report_id: reportId };
                const response = await postJson(url, data);

                if (response.success) {
                    const td = btn.closest('td');
                    td.innerHTML = `<span class="status refuse">Ignoré</span>`;
                } else {
                    alert(response.error || 'Erreur inconnue');
                }
            }

            // Si c’est un reactivate (status "traite")
            if (clubId && btn.classList.contains('reactivate-btn')) {
                const confirmReactivate = confirm("Voulez-vous réactiver ce club ?");
                if (!confirmReactivate) return;

                const data = { _token: csrfToken };
                const response = await postJson(url, data);

                if (response.success) {
                    const td = btn.closest('td');
                    td.innerHTML = `<span class="status active">Actif</span>`;
                    alert('Le club est à nouveau accessible et les mails ont été envoyés.');
                } else {
                    alert(response.error || 'Erreur inconnue');
                }
            }
        });
    });

    // --- Filtres / recherche ---
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            filterForm.submit();
        });
    }

});
