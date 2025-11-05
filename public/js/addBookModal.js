document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('open-search-book-modal'); // Bouton d'ouverture
    const modal = document.getElementById('search-book-modal'); // Modal
    const closeBtn = modal.querySelector('.close') // Bouton de fermeture (x)
    const searchTitle = document.getElementById('searchTitle'); // Champ de saisie titre
    const searchAuthor = document.getElementById('searchAuthor'); // Champ de saisie auteur
    const searchResults = document.getElementById('searchResults') // Résultats recherche
    const notFoundBtn = document.getElementById('book-not-found-btn'); // Bouton "livre introuvable"

    // Si l'un des élèments essentiels n'existe pas, on arrête le script
    if (!openBtn || !modal || !closeBtn) return;

    // Fonction de débouncing
    function debounce(func, delay) {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // Gestion de l'ouverture de la modal
    openBtn.addEventListener('click', () => {
        modal.classList.remove('hidden'); // On retire la classe 'hidden' pour afficher la modal
        searchResults.innerHTML = ''; // On vide les résultats précédents
        notFoundBtn.style.display = 'none'; // On cache le bouton "livre introuvable"
        // On vide les champs de saisie pour recommencer une recherche
        searchTitle.value = '';
        searchAuthor.value = '';
    });

    // Gestion de la fermeture de la modal
    closeBtn.addEventListener('click', () => modal.classList.add('hidden'));

    // Fermer en cliquant hors de la modal
    modal.addEventListener('click', e => {
        if(e.target === modal) modal.classList.add('hidden');
    });

    // Fermer avec la touche Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') modal.classList.add('hidden');
    });

    // Fonction de recherche automatique
    function performSearch() {
        const title = searchTitle.value.trim(); // Récupère le titre
        const author = searchAuthor.value.trim(); // Récupère l'auteur

        // Si moins de 2 caractères dans les deux champs, il ne se passe rien
        if(title.length < 2) {
            searchResults.innerHTML = "<p>Veuillez saisir un titre.</p>"; // Vide les résultats
            notFoundBtn.style.display = 'none'; // Cache le bouton
            return;
        }

        // Requêt AJAX vers le serveur pour récupérer les livres correspondants
        fetch(`/livres/recherche-rapide?q=${encodeURIComponent(title)}&author=${encodeURIComponent(author)}`)
            .then(r => r.json()) // On convertit la réponse en JSON
            .then(data => {
                searchResults.innerHTML = ''; // vide les résultats précédents
                if(data.length > 0) {
                    // Si on a des résultats, on affiche la liste des 3 premiers
                    const ul = document.createElement('ul');
                    const seen = new Set(); // Filtrer les doublons côté JS

                    data.slice(0, 5).forEach(book => {
                        const key = book.title.toLowerCase() + '|' + book.author.toLowerCase();
                        if(seen.has(key)) return; // skip si déjà affiché
                        seen.add(key);

                        const li = document.createElement('li');
                        li.textContent = `${book.title} - ${book.author}`;
                        ul.appendChild(li);
                    });
                    searchResults.appendChild(ul);

                    // Affiche le bouton "livre introuvable"
                    notFoundBtn.style.display = 'block';
                } else {
                    // Si aucun résultat, affiche un message et le bouton
                    searchResults.innerHTML = '<p>Aucun livre correspondant.</p>';
                    notFoundBtn.style.display = 'block';
                }
            })
            .catch(err => {
                // En cas d'erreur de connexion ou serveur => affiche un message
                console.error('Erreur recherche :', err);
                searchResults.innerHTML = '<p style="color:red;">Erreur de connexion.</p>';
            });
    }
    
    // Evènement de saisie => chaque frappe dans le champ titre et / ou auteur, on lance la recherche
    const debouncedSearch = debounce(performSearch, 300);
    searchTitle.addEventListener('input', debouncedSearch);
    searchAuthor.addEventListener('input', debouncedSearch);
});