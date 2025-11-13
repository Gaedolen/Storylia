document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('open-search-book-modal'); // Bouton d'ouverture
    const modal = document.getElementById('search-book-modal'); // Modal
    const closeBtn = modal.querySelector('.close') // Bouton de fermeture (x)
    const searchTitle = document.getElementById('searchTitle'); // Champ de saisie titre
    const searchAuthor = document.getElementById('searchAuthor'); // Champ de saisie auteur
    const searchResults = document.getElementById('searchResults') // Résultats recherche
    const notFoundBtn = document.getElementById('book-not-found-btn'); // Bouton "livre introuvable"
    const creationForm = document.getElementById('bookCreationForm'); // formulaire création
    const searchForm = document.getElementById('bookSearchForm'); // wrapper formulaire recherche
    const formBookCreation = document.getElementById('formBookCreation'); // formulaire création submit

    // Si l'un des élèments essentiels n'existe pas, on arrête le script
    if (!openBtn || !modal || !closeBtn || !searchForm || !creationForm || !formBookCreation) return;

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
        // Forcer le formulaire à être caché
        creationForm.classList.add('hidden');
        searchForm.style.display = 'block';
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

    // ** Fonction de recherche automatique **
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
                    const seen = new Set(); // Filtrer les doublons côté JS

                    data.slice(0, 5).forEach(book => {
                        const key = book.title.toLowerCase() + '|' + book.author.toLowerCase();
                        if (seen.has(key)) return;
                        seen.add(key);

                        const card = document.createElement('div');
                        card.classList.add('book-card');

                        // Lien vers la page du livre
                        const link = document.createElement('a');
                        link.href = `/livres/${book.id}`;

                        // Image
                        if (book.cover) {
                            const img = document.createElement('img');
                            img.src = book.cover;
                            img.alt = book.title;
                            img.classList.add('book-cover');
                            link.appendChild(img);
                        }

                        // Titre et auteur
                        const info = document.createElement('div');
                        info.classList.add('book-info');
                        info.innerHTML = `<strong>${book.title}</strong><br><em>${book.author}</em>`;
                        link.appendChild(info);

                        card.appendChild(link);
                        searchResults.appendChild(card);
                    });

                    // Affiche le bouton "livre introuvable"
                    notFoundBtn.style.display = 'block';
                } else {
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

    // Lors du clic sur le btn "livre introuvable"
    notFoundBtn.addEventListener('click', () => {
        // On cache le formulaire de recherche
        searchForm.style.display = 'none';
        // On affiche le formulaire
        creationForm.classList.remove('hidden');
        // On cache le btn "livre introuvable"
        notFoundBtn.style.display = 'none';
    });

    // ** Soumission du formulaire **
    formBookCreation.addEventListener('submit', async (e) => {
        e.preventDefault(); // On empêche le rechargement de la page

        const title = document.getElementById('bookTitle').value.trim();
        const author = document.getElementById('bookAuthor').value.trim();


        if (!title || !author) {
            alert("Veuillez indiquer le titre et l'auteur du livre que vous cherchez.");
            return;
        }

        try {
            // Recherche sur Google Books
            const query = encodeURIComponent(`${title} ${author}`);
            const responseGoogle = await fetch(`https://www.googleapis.com/books/v1/volumes?q=${query}`);
            const googleData = await responseGoogle.json();

            // Variables par défaut
            let cover = null;
            let summary = null;
            let isbn = null;
            let pages = null;
            let publishedDate = null;
            let publishers = [];
            let genres = [];
            let subjects = [];
            let format = null;
            let voTitle = null;

            // Si un résultat est trouvé
            if(googleData.item && googleData.item.length > 0) {
                const book = googleData.item[0].volumeInfo;

                cover = book.imageLinks?.thumbnail || null;
                summary = book.description || null;
                isbn = book.industryIdentifiers?.[0]?.identifier || null;
                pages = book.pageCount || null;
                publishedDate = book.publishedDate || null;
                publishers = book.publisher ? [book.publisher] : [];
                genres = book.categories || [];
                subjects = book.mainCategory ? [book.mainCategory] : (book.categories || []);
                format = book.printType || null;
                voTitle = book.title && book.title !== title ? book.title : null;
            }

            // Construction de l'objet à envoyer à Symfony
            const bookData = {
                title: title,
                voTitle: voTitle,
                author: author,
                publicationDate: publishedDate,
                genres: genres,
                subjects: subjects,
                summary: summary,
                isbn: isbn,
                cover: cover,
                pages: pages,
                publishers: publishers,
                format: format
            };

            // Envoi au back de symfony
            const response = await fetch('/livres/creation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(bookData)
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = `/livres/${result.bookId}`;
            } else {
                alert(result.message || 'Erreur lors de la création du livre.');
            }

        } catch (err) {
            console.error('Erreur création livre :', err);
            alert('Erreur de connexion ou de création du livre.');
        }
    });
});