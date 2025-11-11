document.addEventListener('DOMContentLoaded', function() {
    const importBtn = document.getElementById('import-books-btn');
    const resultDiv = document.getElementById('import-result');

    if(!importBtn) return;

    importBtn.addEventListener('clicj', function() {
        resultDiv.innerHTML = 'Importation en cours...';

        // Appel de la route import_books
        fetch(importBtn.dataset.url)
            .then(response => response.json())
            .then(data => {
                if(data.success) { // Afficher le nb de livres importés + le nb de livres existants (s'il y a)
                    resultDiv.innerHTML = `
                    <p>Livres importés : ${data.imported_count}</p>
                    <p>Livres déjà existants : ${data.already_exists_count}</p>
                    `;
                } else {
                    resultDiv.innerHTML = `<p style="color:red;">Erreur : ${data.message}</p>`;
                }
            })
            .catch(err => {
                resultDiv.innerHTML = `<p style="color:red;">Erreur : ${err}</p>`;
            });
    });
});