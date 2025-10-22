document.addEventListener('DOMContentLoaded', () => {
    // Sélectionne tous les input de type "file" ayant la classe .file-input
    const fileInputs = document.querySelectorAll('.file-input');

    fileInputs.forEach(input => {
        // On cible le conteneur parent et le span qui doit afficher le nom
        const wrapper = input.closest('.file-input-wrapper');
        const fileNameSpan = wrapper.querySelector('.file-name');
        const label = wrapper.querySelector('.custom-file-label');

        // Quand un fichier est sélectionné
        input.addEventListener('change', event => {
            const file = event.target.files[0]; // Récupère le premier fichier
            if (file) {
                // Affiche le nom du fichier dans le <span>
                fileNameSpan.textContent = file.name;
                fileNameSpan.style.color = '#714B2F';
                label.textContent = 'Changer de photo…';
            } else {
                // Si aucun fichier n'est choisi
                fileNameSpan.textContent = 'Aucun fichier sélectionné';
                fileNameSpan.style.color = '#714B2F';
                label.textContent = 'Choisir une photo…';
            }
        });
    });
});