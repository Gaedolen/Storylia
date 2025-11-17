// Empêche double initialisation
if (!window.searchBookModalInit) {
    window.searchBookModalInit = true;

    function closeModal(modalElement) {
        modalElement.classList.add('hidden');
        modalElement.classList.remove('show');
    }

    function initSearchBookModal() {
        console.log("JS modal chargé");

        // Éléments principaux
        const openBtn = document.getElementById('open-search-book-modal'); 
        const modal = document.getElementById('search-book-modal'); 
        if (!openBtn || !modal) return;

        const closeBtn = modal.querySelector('.close');
        const searchTitle = document.getElementById('searchTitle'); 
        const searchAuthor = document.getElementById('searchAuthor'); 
        const searchResults = document.getElementById('searchResults'); 
        const notFoundBtn = document.getElementById('book-not-found-btn'); 
        const creationForm = document.getElementById('bookCreationForm'); 
        const searchForm = document.getElementById('bookSearchForm'); 
        const formBookCreation = document.getElementById('formBookCreation'); 

        if (!closeBtn || !searchForm || !creationForm || !formBookCreation) return;

        // ----- Ouvrir la modal -----
        openBtn.addEventListener('click', () => {
            console.log("Ouverture modal"); 
            modal.classList.remove('hidden');      
            modal.classList.add('show');           

            // Etat initial : formulaire recherche visible, création caché
            searchForm.classList.remove('hidden');
            creationForm.classList.add('hidden');
            notFoundBtn.classList.add('hidden');
            searchResults.innerHTML = '';
            searchTitle.value = '';
            searchAuthor.value = '';
        });

        // ----- Fermer la modal -----
        closeBtn.addEventListener('click', () => closeModal(modal));
        modal.addEventListener('click', e => { if (e.target === modal) closeModal(modal); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(modal); });

        // ----- Fonction de debounce -----
        function debounce(func, delay) {
            let timeoutId;
            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => func.apply(this, args), delay);
            };
        }

        // ----- Recherche automatique -----
        function performSearch() {
            const title = searchTitle.value.trim();
            const author = searchAuthor.value.trim();

            if(title.length < 2) {
                searchResults.innerHTML = "<p>Veuillez saisir un titre.</p>";
                notFoundBtn.classList.add('hidden');
                return;
            }

            fetch(`/livres/recherche-rapide?q=${encodeURIComponent(title)}&author=${encodeURIComponent(author)}`)
                .then(r => r.json())
                .then(data => {
                    searchResults.innerHTML = '';
                    if(data.length > 0) {
                        const seen = new Set();
                        data.slice(0,5).forEach(book => {
                            const key = book.title.toLowerCase() + '|' + book.author.toLowerCase();
                            if (seen.has(key)) return;
                            seen.add(key);

                            const card = document.createElement('div');
                            card.classList.add('book-card');

                            const link = document.createElement('a');
                            link.href = `/livres/${book.id}`;

                            if(book.cover) {
                                const img = document.createElement('img');
                                img.src = book.cover;
                                img.alt = book.title;
                                img.classList.add('book-cover');
                                link.appendChild(img);
                            }

                            const info = document.createElement('div');
                            info.classList.add('book-info');
                            info.innerHTML = `<strong>${book.title}</strong><br><em>${book.author}</em>`;
                            link.appendChild(info);

                            card.appendChild(link);
                            searchResults.appendChild(card);
                        });
                        notFoundBtn.classList.remove('hidden');
                    } else {
                        searchResults.innerHTML = '<p>Aucun livre correspondant.</p>';
                        notFoundBtn.classList.remove('hidden');
                    }
                })
                .catch(err => {
                    console.error('Erreur recherche :', err);
                    searchResults.innerHTML = '<p style="color:red;">Erreur de connexion.</p>';
                });
        }

        const debouncedSearch = debounce(performSearch, 300);
        searchTitle.addEventListener('input', debouncedSearch);
        searchAuthor.addEventListener('input', debouncedSearch);

        // ----- Bouton "Livre introuvable" -----
        notFoundBtn.addEventListener('click', () => {
            searchForm.classList.add('hidden');
            creationForm.classList.remove('hidden');
            notFoundBtn.classList.add('hidden');
        });

        // ----- Soumission formulaire création -----
        formBookCreation.addEventListener('submit', async (e) => {
            e.preventDefault();

            const title = document.getElementById('bookTitle').value.trim();
            const author = document.getElementById('bookAuthor').value.trim();

            if(!title || !author) {
                alert("Veuillez indiquer le titre et l'auteur du livre que vous cherchez.");
                return;
            }

            try {
                const query = encodeURIComponent(`${title} ${author}`);
                const responseGoogle = await fetch(`https://www.googleapis.com/books/v1/volumes?q=${query}`);
                const googleData = await responseGoogle.json();

                let cover=null, summary=null, isbn=null, pages=null, publishedDate=null;
                let publishers=[], genres=[], subjects=[], format=null, voTitle=null;

                if(googleData.items && googleData.items.length > 0) {
                    const book = googleData.items[0].volumeInfo;
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

                const bookData = {title, voTitle, author, publicationDate: publishedDate,
                                  genres, subjects, summary, isbn, cover, pages, publishers, format};

                const response = await fetch('/livres/creation', {
                    method:'POST',
                    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
                    body: JSON.stringify(bookData)
                });

                const result = await response.json();
                if(result.success) {
                    window.location.href = `/livres/${result.bookId}`;
                } else {
                    alert(result.message || 'Erreur lors de la création du livre.');
                }

            } catch(err) {
                console.error('Erreur création livre :', err);
                alert('Erreur de connexion ou de création du livre.');
            }
        });
    }

    // ----- Initialisation -----
    document.addEventListener('DOMContentLoaded', initSearchBookModal);
}
