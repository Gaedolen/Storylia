document.addEventListener('DOMContentLoaded', () => {
    const openBtn = document.getElementById('openProposerModal');
    const modalContainer = document.getElementById('modalContainer');

    if (!openBtn) return;

    // --- Ouvrir modal via AJAX ---
    openBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const url = openBtn.dataset.url;
        const clubId = openBtn.dataset.clubId;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => {
                modalContainer.innerHTML = html;
                initModal(clubId);
            })
            .catch(err => console.error('Erreur AJAX :', err));
    });

    // --- Initialisation modal ---
    function initModal(clubId) {
        const modal = document.getElementById('proposerLivreModal');
        if (!modal) return;
        modal.style.display = 'flex';

        const closeBtn = modal.querySelector('.close');
        const validerBtn = modal.querySelector('#validerBook');
        const searchInput = modal.querySelector('#searchBook');
        const booksGrid = modal.querySelector('#booksGrid');

        // Fermer modal
        closeBtn.addEventListener('click', () => modal.remove());

        // --- Recherche AJAX côté serveur ---
        function debounce(func, delay) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            };
        }

        const performSearch = debounce(() => {
            const query = searchInput.value.trim().toLowerCase();
            if (query.length < 2) {
                booksGrid.innerHTML = '<p>Tapez au moins 2 caractères...</p>';
                validerBtn.disabled = true;
                return;
            }

            fetch(`/livres/recherche-rapide?q=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => {
                    booksGrid.innerHTML = '';
                    if (data.length === 0) {
                        booksGrid.innerHTML = '<p>Aucun livre trouvé.</p>';
                        validerBtn.disabled = true;
                        return;
                    }

                    data.forEach(book => {
                        // Création card
                        const card = document.createElement('div');
                        card.className = 'book-card';
                        card.dataset.title = book.title.toLowerCase();

                        // Checkbox
                        const checkboxDiv = document.createElement('div');
                        checkboxDiv.className = 'checkbox-container';
                        const radio = document.createElement('input');
                        radio.type = 'radio';
                        radio.name = 'selectedBook';
                        radio.value = book.id;
                        checkboxDiv.appendChild(radio);
                        card.appendChild(checkboxDiv);

                        // Image
                        const img = document.createElement('img');
                        img.src = book.cover;
                        img.alt = book.title;
                        card.appendChild(img);

                        // Titre
                        const titleP = document.createElement('p');
                        titleP.className = 'book-title';
                        titleP.textContent = book.title; // safe
                        card.appendChild(titleP);

                        // Auteur
                        const authorP = document.createElement('p');
                        authorP.className = 'book-author';
                        authorP.textContent = book.author; // safe
                        card.appendChild(authorP);

                        booksGrid.appendChild(card);

                        // Écoute radio pour activer validerBtn
                        radio.addEventListener('change', () => validerBtn.disabled = false);
                    });
                })
                .catch(err => console.error('Erreur recherche :', err));
        }, 300);

        searchInput.addEventListener('input', performSearch);

        // --- Valider le livre sélectionné ---
        validerBtn.addEventListener('click', () => {
            const selectedBook = booksGrid.querySelector('input[name="selectedBook"]:checked');
            if (!selectedBook) return;

            if (confirm("Êtes-vous sûr ? Vous ne pourrez pas proposer un second livre pour ce mois.")) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = validerBtn.dataset.action;

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'bookId';
                input.value = selectedBook.value;

                form.appendChild(input);
                
                // --- Ajouter le token CSRF ---
                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                tokenInput.value = validerBtn.dataset.token;

                form.appendChild(tokenInput);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // --- TOGGLE BLOCS MOIS ---
    document.querySelectorAll('.month-header').forEach(header => {
        header.addEventListener('click', () => {
            const block = header.parentElement;
            const content = block.querySelector('.month-content');
            const arrow = block.querySelector('.toggle-arrow');

            content.classList.toggle('collapsed');
            arrow.classList.toggle('rotated');
        });
    });
});
