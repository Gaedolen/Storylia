document.addEventListener('DOMContentLoaded', function() {
    const importBtn = document.getElementById('import-books-btn');
    const resultDiv = document.getElementById('import-result');

    if (!importBtn) return;

    importBtn.addEventListener('click', function() {
        resultDiv.innerHTML = '<p>Importation en cours...</p>';

        // Vérifier que l'URL est définie
        const url = importBtn.dataset.url;
        if (!url) {
            resultDiv.innerHTML = '<p style="color:red;">Erreur : URL d’importation non définie</p>';
            return;
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `
                    <p>Livres importés : ${data.imported_count}</p>
                    <p>Livres déjà existants : ${data.already_exists_count}</p>
                `;
            } else {
                resultDiv.innerHTML = `<p style="color:red;">Erreur : ${data.message}</p>`;
            }
        })
        .catch(err => {
            console.error(err);
            resultDiv.innerHTML = `<p style="color:red;">Erreur : ${err}</p>`;
        });
    });
});
