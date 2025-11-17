document.addEventListener('DOMContentLoaded', () => {
    // Ouvrir modal
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) modal.style.display = 'flex';
        });
    });

    const modal = document.getElementById('edit-cover-modal');
    if (!modal) return;

    const closeBtn = modal.querySelector('.close');
    const form = modal.querySelector('form');
    const inputField = modal.querySelector('input[name="cover"]');

    // Fermer modal
    closeBtn.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', e => {
        if (e.target === modal) modal.style.display = 'none';
    });

    // Afficher le nom du fichier sélectionné
    inputField.addEventListener('change', () => {
        const display = modal.querySelector('.file-name');
        if (display && inputField.files.length > 0) {
            display.textContent = inputField.files[0].name;
        }
    });

    // Soumission AJAX
    form.addEventListener('submit', e => {
        e.preventDefault();

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Met à jour l'image sur la page
                const img = document.querySelector('.livre-infos img');
                if(img) img.src = data.newCover + '?t=' + new Date().getTime(); // cache-busting

                modal.style.display = 'none';
                inputField.value = '';
                const fileNameDisplay = modal.querySelector('.file-name');
                if(fileNameDisplay) fileNameDisplay.textContent = '';
            } else {
                alert(data.message || 'Erreur lors de la mise à jour.');
            }
        })
        .catch(() => alert('Erreur réseau, réessayez.'));
    });

    // ==== MODAL AJOUTER SUJETS ====
    const subjectModal = document.getElementById('add-subject-modal');

    if (subjectModal) {
        const closeBtnSubj = subjectModal.querySelector('.close');
        const formSubj = document.getElementById("edit-subjects-form");
        const container = formSubj.querySelector('#subjects-container');
        const addBtn = document.getElementById('add-subject-btn');
        const subjectList = document.getElementById("subject-list");
        const maxSubjects = parseInt(subjectModal.dataset.available, 10);

        // Fermeture modal
        closeBtnSubj.addEventListener('click', () => subjectModal.style.display = 'none');
        window.addEventListener('click', e => { if (e.target === subjectModal) subjectModal.style.display = 'none'; });

        // Ajouter un champ
        addBtn.addEventListener('click', () => {

            const currentCount = container.querySelectorAll('.subject-field').length;
            if (currentCount >= maxSubjects) {
                alert(`Maximum de ${maxSubjects} thèmes atteint.`);
                return;
            }

            const div = document.createElement('div');
            div.className = 'subject-field';

            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'subjects[]';
            input.placeholder = "Nouveau thème";

            // Vérification doublons en live
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

        // Soumission AJAX nouvelle version
        formSubj.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(formSubj);

            const response = await fetch(formSubj.action, {
                method: "POST",
                body: formData
            });

            const data = await response.json();

            if (data.success) {

                // Met à jour la liste sur la page
                subjectList.innerHTML = "";
                data.newSubjects.forEach(s => {
                    const tag = document.createElement("span");
                    tag.className = "subject-item";
                    tag.textContent = s;
                    subjectList.appendChild(tag);
                });

                // Reset & fermer
                formSubj.reset();
                container.innerHTML = ""; // Effacer les champs créés
                subjectModal.style.display = 'none';
            }
        });
    }
});
