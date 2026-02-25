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
                alert(response.error || 'Impossible de réactiver ce club tant que son créateur est suspendu.');
            }
        });
    });

    // --- Ignorer un report ---
    document.querySelectorAll('.ignore-report-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm("Voulez-vous vraiment ignorer ce report ?")) return;

            const formData = new FormData();
            formData.append('_token', btn.dataset.csrf);

            try {
                const res = await fetch(btn.dataset.url, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' } // pour AJAX
                });

                const response = await res.json();

                if (response.success) {
                    btn.closest('td').innerHTML = `<span class="status refuse">Ignoré</span>`;
                    alert('Le report a été ignoré.');
                } else {
                    alert(response.error);
                }
            } catch (err) {
                console.error(err);
                alert('Erreur réseau ou serveur.');
            }

            // Si c’est un reactivate (status "traite")
            if (clubId && btn.classList.contains('reactivate-btn')) {
                const confirmReactivate = confirm("Voulez-vous réactiver ce club ?");
                if (!confirmReactivate) return;

                // Crée un FormData pour que Symfony récupère le token correctement
                const formData = new FormData();
                formData.append('_token', csrfToken);

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        body: formData
                        // Pas de header JSON ni X-Requested-With
                    });

                    const response = await res.json();

                    if (response.success) {
                        const td = btn.closest('td');
                        td.innerHTML = `<span class="status active">Actif</span>`;
                        alert('Le club est à nouveau accessible et les mails ont été envoyés.');
                    } else {
                        alert(response.error || 'Impossible de réactiver ce club tant que son créateur est suspendu.');
                    }
                } catch (err) {
                    console.error(err);
                    alert('Erreur réseau ou serveur.');
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
