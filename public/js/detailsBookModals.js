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

    // ==========================
    // MODAL AJOUTER À LA BIBLIOTHÈQUE
    // ==========================
    const bookshelfModal = document.getElementById('bookshelf-modal');
    const statusSelect = document.getElementById('bookshelf-status-select');
    const saveBtn = document.getElementById('bookshelf-save-btn');

    if (bookshelfModal && statusSelect && saveBtn) {
        // Ouverture depuis n'importe quel bouton
        document.querySelectorAll('[data-add-to-bookshelf]').forEach(btn => {
            btn.addEventListener('click', () => {
                const loginRedirect = btn.dataset.redirectLogin;
                if (loginRedirect) return window.location.href = loginRedirect;

                const bookId = btn.dataset.bookId;
                if (!bookId) return;

                bookshelfModal.dataset.bookId = bookId;
                statusSelect.value = '';
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
                const resp = await fetch('/bibliotheque/ajouter-ou-mettre-a-jour', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ bookId, readingStatusId: statusId })
                });
                const result = await resp.json();
                if (result.success) {
                    const btn = document.querySelector(`[data-book-id="${bookId}"]`);
                    if (btn) btn.textContent = "Déjà dans ta bibliothèque ✔";
                    closeModal(bookshelfModal);
                } else alert(result.message || "Erreur lors de l'ajout.");
            } catch { alert("Erreur réseau."); }
        });
    }

});
