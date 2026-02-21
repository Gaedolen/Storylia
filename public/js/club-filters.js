document.addEventListener('DOMContentLoaded', () => {
    const openBtn = document.getElementById('open-filtres');
    const closeBtn = document.getElementById('close-filtres');
    const filters = document.querySelector('.filters');
    const overlay = document.getElementById('filtres-overlay');

    if(openBtn && closeBtn && filters && overlay) {
        openBtn.addEventListener('click', () => {
            filters.classList.add('active');
            overlay.classList.remove('hidden');
        });

        closeBtn.addEventListener('click', () => {
            filters.classList.remove('active');
            overlay.classList.add('hidden');
        });

        overlay.addEventListener('click', () => {
            filters.classList.remove('active');
            overlay.classList.add('hidden');
        });
    }
});
