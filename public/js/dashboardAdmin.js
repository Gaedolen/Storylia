document.addEventListener('DOMContentLoaded', function() {

    const loadingModal = document.getElementById('loadingModal');
    const loader = document.getElementById('loader');
    const loadingText = document.getElementById('loadingText');
    const resultText = document.getElementById('resultText');
    const closeModal = document.getElementById('closeModal');

    function openModal() {
        loader.style.display = 'block';
        loadingText.style.display = 'block';
        resultText.style.display = 'none';
        closeModal.style.display = 'none';
        loadingModal.style.display = 'flex';
    }

    function closeModalFunc() {
        loadingModal.style.display = 'none';
    }

    closeModal.addEventListener('click', closeModalFunc);

    // Gestion des boutons import / update
    document.querySelectorAll('.btn-import, .btn-ajout').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();

            const url = btn.getAttribute('href');
            console.log('Clic sur', url);

            openModal();

            try {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();

                loader.style.display = 'none';
                loadingText.style.display = 'none';
                resultText.style.display = 'block';
                closeModal.style.display = 'inline-block';

                resultText.innerHTML = `Opération terminée : <br>${data.message}`;
            } catch (err) {
                loader.style.display = 'none';
                loadingText.style.display = 'none';
                resultText.style.display = 'block';
                closeModal.style.display = 'inline-block';

                resultText.innerHTML = `Erreur : ${err}`;
            }
        });
    });
});
