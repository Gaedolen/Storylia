document.addEventListener('DOMContentLoaded', () => {

    // ==========================
    // FONCTIONS UTILES
    // ==========================
    const openModal = (modal) => { modal.style.display = 'flex'; };
    const closeModal = (modal) => { modal.style.display = 'none'; };

    // Fermer modal au clic en dehors
    const setupCloseOutside = (modal) => {
        window.addEventListener('click', e => {
            if (e.target === modal) closeModal(modal);
        });
    };

    // ==========================
    // TOUS LES BOUTONS DE MODAL
    // ==========================
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const loginRedirect = btn.dataset.redirectLogin;
            if (loginRedirect) return window.location.href = loginRedirect;

            const modalId = btn.dataset.modal;
            const modal = document.getElementById(modalId);
            if (!modal) return;

            openModal(modal);
        });
    });

    // ==========================
    // FERMETURE DE TOUTES LES MODALS
    // ==========================
    document.querySelectorAll('.modal').forEach(modal => {
        const closeBtn = modal.querySelector('.close');
        if (closeBtn) closeBtn.addEventListener('click', () => closeModal(modal));
        setupCloseOutside(modal);
    });

    // ==========================
    // MODAL MODIFIER COUVERTURE
    // ==========================
    const coverModal = document.getElementById('edit-cover-modal');
    if (coverModal) {
        const form = coverModal.querySelector('form');
        const inputField = coverModal.querySelector('input[name="cover"]');

        inputField.addEventListener('change', () => {
            const display = coverModal.querySelector('.file-name');
            if (display && inputField.files.length > 0) {
                display.textContent = inputField.files[0].name;
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);

            try {
                const resp = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await resp.json();
                if (data.success) {
                    const img = document.querySelector('.livre-infos img');
                    if (img) img.src = data.newCover + '?t=' + new Date().getTime();

                    closeModal(coverModal);
                    inputField.value = '';
                    const fileNameDisplay = coverModal.querySelector('.file-name');
                    if (fileNameDisplay) fileNameDisplay.textContent = '';
                } else alert(data.message || "Erreur lors de la mise à jour.");
            } catch {
                alert('Erreur réseau, réessayez.');
            }
        });
    }

    // ==========================
    // MODAL AJOUTER SUJETS
    // ==========================
    const subjectModal = document.getElementById('add-subject-modal');
    if (subjectModal) {
        const formSubj = document.getElementById("edit-subjects-form");
        if (formSubj) {
            const container = formSubj.querySelector('#subjects-container');
            const addBtn = document.getElementById('add-subject-btn');
            const subjectList = document.getElementById("subject-list");
            const maxSubjects = parseInt(subjectModal.dataset.available, 10);

            addBtn.addEventListener('click', () => {
                const currentCount = container.querySelectorAll('.subject-field').length;
                if (currentCount >= maxSubjects) return alert(`Maximum de ${maxSubjects} thèmes atteint.`);

                const div = document.createElement('div');
                div.className = 'subject-field';

                const input = document.createElement('input');
                input.type = 'text';
                input.name = 'subjects[]';
                input.placeholder = "Nouveau thème";
                input.className = 'subject-input';

                input.addEventListener('input', () => {
                    const values = [...container.querySelectorAll('input[name="subjects[]"]')]
                        .map(i => i.value.trim().toLowerCase())
                        .filter(v => v !== "");
                    const count = values.filter(v => v === input.value.trim().toLowerCase()).length;
                    if (count > 1) input.classList.add('duplicate-error');
                    else input.classList.remove('duplicate-error');
                });

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.textContent = '−';
                removeBtn.className = 'remove-subject-btn';
                removeBtn.addEventListener('click', () => div.remove());

                div.appendChild(input);
                div.appendChild(removeBtn);
                container.appendChild(div);
            });

            formSubj.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(formSubj);
                try {
                    const resp = await fetch(formSubj.action, { method: 'POST', body: formData });
                    const data = await resp.json();
                    if (data.success) {
                        subjectList.innerHTML = "";
                        data.newSubjects.forEach(s => {
                            const tag = document.createElement("span");
                            tag.className = "subject-item";
                            tag.textContent = s;
                            subjectList.appendChild(tag);
                        });
                        formSubj.reset();
                        container.innerHTML = "";
                        closeModal(subjectModal);
                    }
                } catch { alert("Erreur réseau."); }
            });
        }
    }

    // ==========================
    // MODAL MODIFIER / AJOUTER RÉSUMÉ
    // ==========================
    const summaryModal = document.getElementById('edit-summary-modal');
    const summaryForm = document.getElementById('edit-summary-form');
    if (summaryForm) {
        const textarea = summaryForm.querySelector('textarea');
        const counter = summaryModal?.querySelector('#char-count');
        const updateBtn = document.querySelector('button.btn-main[data-modal="edit-summary-modal"]') 
                          || document.querySelector('button.btn-main:last-of-type');

        updateBtn?.addEventListener('click', () => {
            openModal(summaryModal);
            textarea?.focus();
            if (counter) counter.textContent = `${textarea.value.length} / 1000`;
        });

        textarea?.addEventListener('input', () => {
            if (counter) counter.textContent = `${textarea.value.length} / 1000`;
        });

        summaryForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(summaryForm);
            try {
                const resp = await fetch(summaryForm.action, { method: 'POST', body: formData });
                const data = await resp.json();
                if (data.success) {
                    const summaryBlock = document.querySelector('.livre-resume p');
                    if (summaryBlock) summaryBlock.textContent = data.summary;
                    closeModal(summaryModal);
                }
            } catch { alert("Erreur réseau."); }
        });
    }

    // Modal d'ajout à la bibliothèque
    const bookshelfModal = document.getElementById('bookshelf-modal');
    const statusSelect = document.getElementById('bookshelf-status-select');
    const saveBtn = document.getElementById('bookshelf-save-btn');

    if (bookshelfModal && statusSelect && saveBtn) {

        // Ouvrir modal depuis n'importe quel bouton
        document.querySelectorAll('[data-add-to-bookshelf]').forEach(btn => {
            btn.addEventListener('click', () => {

                const bookId = btn.dataset.bookId;
                if (!bookId) return;

                // Stocker l'ID du livre et l'ID du bookshelf (si existe)
                bookshelfModal.dataset.bookId = bookId;
                bookshelfModal.dataset.bookshelfId = btn.dataset.bookshelfId || '';

                // Afficher le statut actuel dans la modal
                const currentStatusSpan = bookshelfModal.querySelector('.bookshelf-current-status');
                if (currentStatusSpan) {
                    // Si le dataset existe, on l'utilise, sinon "Aucun"
                    currentStatusSpan.textContent = btn.dataset.currentStatusLabel || 'Aucun';
                }

                // Reset sélection
                statusSelect.value = '';

                // Ouvrir modal
                openModal(bookshelfModal);
            });
        });

        // Fermer modal
        const closeBtn = bookshelfModal.querySelector('.close');
        if (closeBtn) closeBtn.addEventListener('click', () => closeModal(bookshelfModal));
        setupCloseOutside(bookshelfModal);

        // Sauvegarder le statut
        saveBtn.addEventListener('click', async () => {
            const bookId = bookshelfModal.dataset.bookId;
            const statusId = statusSelect.value;
            if (!statusId) return alert("Choisis un statut.");

            try {
                let url, body;
                const bookshelfId = bookshelfModal.dataset.bookshelfId;

                if (bookshelfId) {
                    // Déplacer un livre déjà en bibliothèque
                    url = `/bibliotheque/livres/${bookshelfId}/deplacer`;
                    body = JSON.stringify({ readingStatusId: statusId });
                } else {
                    // Ajouter un nouveau livre
                    url = '/bibliotheque/ajouter-ou-mettre-a-jour';
                    body = JSON.stringify({ bookId, readingStatusId: statusId });
                }

                const resp = await fetch(url, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest' 
                    },
                    body
                });

                const result = await resp.json();

                if (result.success) {

                    // Mettre à jour le bouton principal côté DOM
                    const btn = document.querySelector(`[data-book-id="${bookId}"]`);
                    if (btn) {
                        btn.textContent = 'Déplacer';
                        btn.dataset.bookshelfId = result.bookshelfId;
                        btn.dataset.currentStatusLabel = result.readingStatusLabel; // pour la prochaine ouverture
                    }

                    // Mettre à jour le badge statut actuel dans la modal
                    const currentStatusSpan = bookshelfModal.querySelector('.bookshelf-current-status');
                    if (currentStatusSpan) {
                        currentStatusSpan.textContent = result.readingStatusLabel;
                    }

                    // Mettre à jour la data-bookshelf-id dans la modal
                    bookshelfModal.dataset.bookshelfId = result.bookshelfId;

                    // Fermer modal
                    closeModal(bookshelfModal);

                } else {
                    alert(result.message || "Erreur côté serveur.");
                }

            } catch (err) {
                console.error(err);
                alert("Erreur réseau, réessayez.");
            }
        });
    }

    // Modal d'avis
    const reviewModal = document.getElementById('leave-review-modal');
    const reviewForm = document.getElementById('leave-review-form');

    if (reviewModal && reviewForm) {

        const textarea = reviewForm.querySelector('#review-text');
        const counter = reviewModal.querySelector('#char-count-review');

        // Compteur dynamique
        textarea.addEventListener('input', () => {
            if (counter) counter.textContent = `${textarea.value.length} / 1000`;
        });

        // Envoi
        reviewForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const bookId = reviewForm.dataset.bookId || reviewModal.dataset.bookId;
            const reviewText = textarea.value.trim();

            if (!bookId) {
                alert("Livre introuvable.");
                return;
            }

            if (reviewText.length === 0) {
                alert("Veuillez écrire un avis avant de valider.");
                return;
            }

            try {
                const resp = await fetch('/laisser-avis', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest' 
                    },
                    body: JSON.stringify({
                        bookId: bookId,
                        review: reviewText
                    })
                });

                const result = await resp.json();

                if (result.success) {
                    const submitBtn = reviewForm.querySelector('button[type="submit"]');

                    submitBtn.textContent = 'Avis laissé ✔';
                    submitBtn.disabled = true;

                    textarea.value = '';
                    if (counter) counter.textContent = '0 / 1000';

                    closeModal(reviewModal);

                    // Cherche le bouton extérieur
                    const externalBtn = document.querySelector('[data-modal="leave-review-modal"]');

                    if (externalBtn) {
                        externalBtn.textContent = 'Avis laissé ✓';
                        externalBtn.classList.add('disabled-review-btn');
                        externalBtn.disabled = true;
                    }
                } else {
                    alert(result.message || "Erreur lors de l'enregistrement.");
                }
            } catch (error) {
                console.error(error);
                alert("Erreur réseau, réessayez.");
            }
        });
    }

    // Modal date de lecture
    const readingModal = document.getElementById('reading-date-modal');
    const readingForm = document.getElementById('reading-date-form');

    if (readingModal && readingForm) {

        const checkboxToday = document.getElementById('reading-today');
        const datePicker = document.getElementById('reading-date-picker');

        // Empêche d'utiliser les deux options
        checkboxToday.addEventListener('change', () => {
            if (checkboxToday.checked) {
                datePicker.value = "";
                datePicker.disabled = true;
            } else {
                datePicker.disabled = false;
            }
        });

        datePicker.addEventListener('input', () => {
            if (datePicker.value) {
                checkboxToday.checked = false;
            }
        });

        readingForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const bookId = readingForm.dataset.bookId;
            let chosenDate = null;

            if (checkboxToday.checked) {
                chosenDate = new Date().toISOString().split('T')[0];
            } 
            else if (datePicker.value) {
                chosenDate = datePicker.value;
            } 
            else {
                alert("Veuillez choisir une date.");
                return;
            }

            try {
                const resp = await fetch('/ajouter-date-lecture', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        bookId: bookId,
                        readingDate: chosenDate
                    })
                });

                const result = await resp.json();

                if (result.success) {
                    closeModal(readingModal);
                    alert("Date de lecture enregistrée !");
                } else {
                    alert(result.message || "Erreur.");
                }
            } catch (err) {
                console.error(err);
                alert("Erreur réseau.");
            }
        });
    }

    // ==========================
    // MODAL DE NOTE
    // ==========================
    const leaveBtn = document.getElementById('leave-rating-btn');
    const modal = document.getElementById('rating-modal');

    if (leaveBtn && modal) {

        const rateUrl = leaveBtn.dataset.rateUrl;
        const stars = modal.querySelectorAll('#rating-stars .star');
        const ratingValue = modal.querySelector('#rating-value');
        const submitBtn = modal.querySelector('#submit-rating-btn');
        let selectedRating = 0;

        leaveBtn.addEventListener('click', () => openModal(modal));

        modal.querySelector('#close-rating-btn')
            ?.addEventListener('click', () => closeModal(modal));

        setupCloseOutside(modal);

        // Hover / Click étoiles
        stars.forEach(star => {
            star.addEventListener('mouseover', () => highlightStars(star.dataset.value));
            star.addEventListener('mouseout', () => highlightStars(selectedRating));
            star.addEventListener('click', () => {
                selectedRating = star.dataset.value;
                ratingValue.textContent = selectedRating;
                highlightStars(selectedRating);
            });
        });

        function highlightStars(val) {
            stars.forEach(s => {
                s.textContent = s.dataset.value <= val ? '★' : '☆';
            });
        }

        // Envoi
        submitBtn?.addEventListener('click', () => {
            if (selectedRating == 0) {
                alert("Veuillez sélectionner une note.");
                return;
            }

            fetch(rateUrl, {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ rating: selectedRating })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    leaveBtn.textContent = "Note laissée ✓";
                    leaveBtn.disabled = true;
                    closeModal(modal);
                } else {
                    alert(data.message);
                }
            });
        });
    }

    // ==========================
    // MODAL SIGNALER AVIS
    // ==========================
    const reportModal = document.getElementById('report-modal');
    const form = document.getElementById('report-form');
    const reviewIdInput = document.getElementById('report-review-id');

    if (reportModal && form && reviewIdInput) {

        document.querySelectorAll('.review-report-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                reviewIdInput.value = btn.dataset.reviewId;
                reportModal.style.display = 'flex';
            });
        });

        reportModal.querySelector('.close')
            ?.addEventListener('click', () => {
                reportModal.style.display = 'none';
                form.reset();
            });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const payload = {
                reviewId: reviewIdInput.value,
                reason: document.getElementById('report-reason').value,
                message: document.getElementById('report-message').value
            };

            const response = await fetch('/report/review', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (result.success) {
                alert('Signalement envoyé');
                reportModal.style.display = 'none';
                form.reset();
            } else {
                alert(result.message || 'Erreur lors du signalement');
            }
        });
    }

    // ==================================
    // MODAL SIGNALER UN PB SUR LE LIVRE
    // ==================================
    const reportBookModal = document.getElementById('report-book-modal');
    const reportBookForm = document.getElementById('report-book-form');

    if (reportBookModal && reportBookForm) {

        const textarea = reportBookForm.querySelector('#report-book-message');
        const counter = reportBookForm.querySelector('#report-book-char-count');

        // Ouvrir modal
        document.querySelectorAll('[data-modal="report-book-modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                openModal(reportBookModal);

                // Initialiser le compteur
                if (textarea && counter) {
                    counter.textContent = `${textarea.value.length} / ${textarea.maxLength}`;
                    textarea.focus();
                }
            });
        });

        // Fermer modal
        const closeBtn = reportBookModal.querySelector('.close');
        if (closeBtn) closeBtn.addEventListener('click', () => {
            closeModal(reportBookModal);
            reportBookForm.reset();
            if (counter) counter.textContent = `0 / ${textarea.maxLength}`;
        });

        // Compteur dynamique
        if (textarea && counter) {
            textarea.addEventListener('input', () => {
                counter.textContent = `${textarea.value.length} / ${textarea.maxLength}`;
            });
        }

        // Soumission du formulaire
        reportBookForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const payload = {
                book_id: reportBookForm.querySelector('input[name="book_id"]').value,
                reason: reportBookForm.querySelector('#report-book-reason').value,
                message: textarea.value
            };

            const csrfToken = reportBookForm.querySelector('input[name="_token"]').value;

            try {
                const response = await fetch('/report/book', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    alert('Signalement envoyé !');
                    closeModal(reportBookModal);
                    reportBookForm.reset();
                    if (counter) counter.textContent = `0 / ${textarea.maxLength}`;
                } else {
                    alert(result.message || 'Erreur lors du signalement.');
                }
            } catch (err) {
                console.error(err);
                alert('Erreur réseau, réessayez.');
            }
        });
    }
});
