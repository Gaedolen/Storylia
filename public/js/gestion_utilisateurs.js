document.addEventListener('DOMContentLoaded', () => {

    // MODAL SUSPENSION
    const suspendModal = document.getElementById('suspendModal');
    const suspendPseudo = document.getElementById('modalPseudo');
    const csrfTokenInput = document.getElementById('csrfToken');
    const reasonSelect = document.getElementById('reasonSelect');
    const otherReasonDiv = document.getElementById('otherReasonDiv');
    const otherReasonInput = document.getElementById('otherReason');
    const suspendForm = document.getElementById('suspendForm');
    const cancelModalBtn = document.getElementById('cancelModal');

    let currentUserId = null;

    // On ne fait rien si le modal n’existe pas
    if (
        suspendModal &&
        suspendPseudo &&
        csrfTokenInput &&
        reasonSelect &&
        otherReasonDiv &&
        otherReasonInput &&
        suspendForm &&
        cancelModalBtn
    ) {

        // Ouvrir modal suspension
        document.querySelectorAll('.suspend-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                currentUserId = btn.dataset.userId;
                suspendPseudo.textContent = btn.dataset.pseudo;
                csrfTokenInput.value = btn.dataset.csrf;

                // Réinitialiser le formulaire
                reasonSelect.value = '';
                otherReasonDiv.classList.add('hidden');
                otherReasonInput.value = '';

                suspendModal.classList.remove('hidden');
            });
        });

        // Fermer modal suspension
        cancelModalBtn.addEventListener('click', () => {
            suspendModal.classList.add('hidden');
            currentUserId = null;
        });

        // Afficher champ "autre raison"
        reasonSelect.addEventListener('change', () => {
            if (reasonSelect.value === 'autres') {
                otherReasonDiv.classList.remove('hidden');
            } else {
                otherReasonDiv.classList.add('hidden');
            }
        });

        // Soumettre suspension
        suspendForm.addEventListener('submit', async e => {
            e.preventDefault();

            if (!currentUserId) {
                alert('Utilisateur non sélectionné.');
                return;
            }

            const reason = reasonSelect.value;
            const otherReason = otherReasonInput.value.trim();
            const finalReason = reason === 'autres' ? otherReason : reason;

            if (!finalReason) {
                alert('Veuillez préciser une raison.');
                return;
            }

            const formData = new FormData();
            formData.append('_token', csrfTokenInput.value);
            formData.append('reason', finalReason);
            formData.append('otherReason', otherReason);

            try {
                const res = await fetch(`/admin/utilisateur/suspendre/${currentUserId}`, {
                    method: 'POST',
                    body: formData
                });

                if (!res.ok) {
                    const text = await res.text();
                    console.error('Erreur serveur:', text);
                    alert('Erreur serveur : consultez la console.');
                    return;
                }

                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Une erreur est survenue.');
                }
            } catch (err) {
                console.error(err);
                alert('Impossible de contacter le serveur.');
            }
        });
    }

    // MODAL CONTACT
    const contactModal = document.getElementById('contactModal');
    const contactPseudo = document.getElementById('contactModalPseudo');
    const contactForm = document.getElementById('contactForm');
    const contactTokenInput = document.getElementById('modal_token');

    if (contactModal && contactPseudo && contactForm && contactTokenInput) {

        document.querySelectorAll('.btn-contact').forEach(btn => {
            btn.addEventListener('click', () => {
                contactModal.classList.add('visible');
                contactPseudo.textContent = btn.dataset.pseudo;
                contactForm.action = btn.dataset.action;
                contactTokenInput.value = btn.dataset.token;
            });
        });

        const closeBtn = contactModal.querySelector('.close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                contactModal.classList.remove('visible');
            });
        }
    }
});
