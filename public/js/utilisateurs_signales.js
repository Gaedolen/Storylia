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
    const signalementModal = document.getElementById('transmettreModal');

    if (signalementModal) {
        const signalementClose = signalementModal.querySelector('.close');
        const transmettreForm = document.getElementById('transmettreForm');
        const transmettreToken = document.getElementById('transmettre_token');

        document.querySelectorAll('.btn-transmettre').forEach(btn => {
            btn.addEventListener('click', () => {
                transmettreForm.action = btn.dataset.action;
                transmettreToken.value = btn.dataset.token;

                transmettreForm.querySelector('textarea[name="employeMessage"]').value = '';

                signalementModal.style.display = 'block';
            });
        });

        signalementClose.addEventListener('click', () => signalementModal.style.display = 'none');
    }
});
