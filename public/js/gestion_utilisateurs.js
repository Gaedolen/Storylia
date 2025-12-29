document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('suspendModal');
    const modalPseudo = document.getElementById('modalPseudo');
    const csrfTokenInput = document.getElementById('csrfToken');
    const reasonSelect = document.getElementById('reasonSelect');
    const otherReasonDiv = document.getElementById('otherReasonDiv');
    const otherReasonInput = document.getElementById('otherReason');
    let currentUserId = null;

    // Ouvrir modal
    document.querySelectorAll('.suspend-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentUserId = btn.dataset.userId;
            modalPseudo.textContent = btn.dataset.pseudo;
            csrfTokenInput.value = btn.dataset.csrf;
            reasonSelect.value = '';
            otherReasonDiv.classList.add('hidden');
            otherReasonInput.value = '';
            modal.classList.remove('hidden');
        });
    });

    // Fermer modal
    document.getElementById('cancelModal').addEventListener('click', () => {
        modal.classList.add('hidden');
    });

    // Afficher champ autre raison
    reasonSelect.addEventListener('change', () => {
        if (reasonSelect.value === 'autres') {
            otherReasonDiv.classList.remove('hidden');
        } else {
            otherReasonDiv.classList.add('hidden');
        }
    });

    // Soumettre suspension
    document.getElementById('suspendForm').addEventListener('submit', async e => {
        e.preventDefault();
        let reason = reasonSelect.value === 'autres' ? otherReasonInput.value.trim() : reasonSelect.value;
        if (!reason) {
            alert('Veuillez préciser une raison.');
            return;
        }
        const res = await fetch(`/admin/utilisateur/suspendre/${currentUserId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ _token: csrfTokenInput.value, reason: reason, otherReason: otherReasonInput.value })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.error || 'Une erreur est survenue.');
    });

    // Réactiver utilisateur
    document.querySelectorAll('.unsuspend-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const userId = btn.dataset.userId;
            const csrf = btn.dataset.csrf;
            const res = await fetch(`/admin/utilisateur/unsuspendre/${userId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ _token: csrf })
            });
            const data = await res.json();
            if (data.success) location.reload();
            else alert(data.error || 'Une erreur est survenue.');
        });
    });
});
