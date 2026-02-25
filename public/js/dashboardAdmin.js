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

    const forms = document.querySelectorAll('#importBooksForm, #updateBooksForm');

    if (!forms.length) return;

    forms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            openModal();

            const formData = new FormData(form);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Réponse du serveur inattendue (pas du JSON)');
                }

                const data = await response.json();

                loader.style.display = 'none';
                loadingText.style.display = 'none';
                resultText.style.display = 'block';
                closeModal.style.display = 'inline-block';

                if (data.success) {
                    resultText.innerHTML = `Opération terminée : <br>${data.message}`;
                } else {
                    resultText.innerHTML = `Erreur serveur : <br>${data.error || 'Erreur inconnue'}`;
                }

            } catch (err) {
                loader.style.display = 'none';
                loadingText.style.display = 'none';
                resultText.style.display = 'block';
                closeModal.style.display = 'inline-block';
                resultText.innerHTML = `Erreur JS : <br>${err.message}`;
                console.error(err);
            }
        });
    });

});