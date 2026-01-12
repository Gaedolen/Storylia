document.addEventListener('DOMContentLoaded', function () {

    /* ========= MODAL CONTACT ========= */
    const contactModal = document.getElementById('contactModal');
    const contactClose = contactModal.querySelector('.close');
    const contactForm = document.getElementById('contactForm');

    // Tous les boutons "Contacter"
    document.querySelectorAll('.btn-contact').forEach(btn => {
        btn.addEventListener('click', function () {
            // Injecte l'action complète
            contactForm.action = this.dataset.action;

            // CSRF token
            contactForm.querySelector('input[name="_token"]').value = this.dataset.token;

            // Titre de la modal
            contactModal.querySelector('#modalTitle').textContent =
                `Envoyer un mail à ${this.dataset.pseudo}`;

            // Affiche la modal
            contactModal.style.display = 'block';
        });
    });

    // Fermer la modal
    contactClose.addEventListener('click', () => contactModal.style.display = 'none');
    window.addEventListener('click', e => {
        if (e.target === contactModal) contactModal.style.display = 'none';
    });


    /* ========= MODAL TRANSMETTRE ========= */
    const signalementModal = document.getElementById('signalementModal');
    const signalementClose = signalementModal.querySelector('.close');

    document.querySelectorAll('.btn-transmettre').forEach(btn => {
        btn.addEventListener('click', () => {
            signalementModal.style.display = 'block';

            const userId = btn.dataset.userid;
            const selectReported = signalementModal.querySelector('#form_signalement_reported');
            if (selectReported) {
                selectReported.value = userId;
            }
        });
    });

    signalementClose.addEventListener('click', () => signalementModal.style.display = 'none');

    window.addEventListener('click', e => {
        if (e.target === contactModal) contactModal.style.display = 'none';
        if (e.target === signalementModal) signalementModal.style.display = 'none';
    });
});
