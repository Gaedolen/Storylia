document.addEventListener("DOMContentLoaded", () => {
    // --- Modal suppression ---
    const removeModal = document.getElementById("confirm-remove-modal");
    const removeCloseBtn = removeModal.querySelector(".close");
    const removeCancelBtn = document.getElementById("confirm-remove-cancel");
    const removeConfirmBtn = document.getElementById("confirm-remove-yes");
    let currentBookCard = null;

    document.body.addEventListener("click", (e) => {
        const btn = e.target.closest(".bookshelf-remove-btn");
        if (!btn) return;

        currentBookCard = btn.closest(".book-card");
        removeModal.dataset.bookId = currentBookCard.dataset.bookId;
        removeModal.style.display = "flex";
    });

    const closeRemoveModal = () => {
        removeModal.style.display = "none";
        currentBookCard = null;
    };

    removeCloseBtn.addEventListener("click", closeRemoveModal);
    removeCancelBtn.addEventListener("click", closeRemoveModal);
    window.addEventListener("click", e => {
        if (e.target === removeModal) closeRemoveModal();
    });

    removeConfirmBtn.addEventListener("click", async () => {
        if (!currentBookCard) return;

        const bookId = currentBookCard.dataset.bookId;

        try {
            const response = await fetch('/bibliotheque/supprimer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ bookId })
            });

            const result = await response.json();

            if (result.success) {
                currentBookCard.remove();
                closeRemoveModal();
            }
        } catch (err) {
            console.error(err);
            alert("Erreur réseau. Veuillez réessayer.");
        }
    });

    // --- Modal déplacement ---
    const moveModal = document.getElementById("bookshelf-move-modal");
    const moveCloseBtn = moveModal.querySelector(".close");
    const moveConfirmBtn = document.getElementById("bookshelf-move-confirm");

    let currentMoveBookCard = null;
    let currentMoveBookId = null; // RENOMMÉ pour plus de clarté

    // Ouvrir le modal
    document.body.addEventListener("click", e => {
        const btn = e.target.closest(".bookshelf-move-btn");
        if (!btn) return;

        currentMoveBookId = btn.dataset.bookId;
        currentMoveBookCard = btn.closest(".book-card");
        const currentStatusKey = btn.dataset.currentStatusKey;

        moveModal.style.display = "flex";

        moveModal.querySelectorAll(".status-btn").forEach(b => {
            b.classList.toggle("active", b.dataset.statusKey === currentStatusKey);
        });

        console.log("DEBUG MOVE:", currentMoveBookId, currentStatusKey);
    });

    // Sélection d'un nouveau statut
    moveModal.querySelectorAll(".status-btn").forEach(b => {
        b.addEventListener("click", () => {
            moveModal.querySelectorAll(".status-btn").forEach(btn => btn.classList.remove("active"));
            b.classList.add("active");
        });
    });

    // Fermer le modal
    const closeMoveModal = () => {
        moveModal.style.display = "none";
        currentMoveBookId = null;
        currentMoveBookCard = null;
    };

    moveCloseBtn.addEventListener("click", closeMoveModal);
    window.addEventListener("click", e => {
        if (e.target === moveModal) closeMoveModal();
    });

    // Confirmer le déplacement
    moveConfirmBtn.addEventListener("click", async () => {
        if (!currentMoveBookId || !currentMoveBookCard) return;

        const selected = moveModal.querySelector(".status-btn.active");
        if (!selected) {
            alert("Veuillez sélectionner un statut.");
            return;
        }

        const statusId = selected.dataset.statusId;
        const statusKey = selected.dataset.statusKey;

        const url = currentMoveBookCard.querySelector(".bookshelf-move-btn").dataset.moveUrl;
        console.log("DEBUG MOVE URL:", url);

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    readingStatusId: statusId
                })
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

            const result = await response.json();
            console.log("DEBUG RESULT:", result);

            if (result.success) {
                const category = document.querySelector(`.category[data-status-key="${statusKey}"] .category-books`);
                if (category && currentMoveBookCard instanceof Node) {
                    category.appendChild(currentMoveBookCard); // déplacer le livre
                    currentMoveBookCard.querySelector(".bookshelf-move-btn").dataset.currentStatusKey = statusKey;
                }
                closeMoveModal(); // fermer le modal après
            } else {
                alert(result.message || "Erreur lors du déplacement.");
            }
        } catch (err) {
            console.error(err);
            alert("Erreur réseau. Veuillez réessayer.");
        }
    });

    document.querySelectorAll('.category').forEach(category => {

        const track = category.querySelector('.category-books');
        const btnLeft = category.querySelector('.carousel-arrow.left');
        const btnRight = category.querySelector('.carousel-arrow.right');

        if (!track || !btnLeft || !btnRight) return;

        btnLeft.onclick = () => {
            track.scrollBy({ left: -150, behavior: "smooth" });
        };

        btnRight.onclick = () => {
            track.scrollBy({ left: 150, behavior: "smooth" });
        };

    });
});
