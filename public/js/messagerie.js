document.addEventListener('DOMContentLoaded', function () {

    const messagesContainer = document.querySelector('.messages');
    const form = document.querySelector('.messenger-form');
    const textarea = form.querySelector('textarea[name="content"]');
    const receiverId = form.querySelector('input[name="receiver_id"]').value;

    // ===== Fonction pour charger les messages =====
    async function loadMessages() {
        try {
            const res = await fetch('/messagerie/load');
            if (!res.ok) throw new Error(res.status);

            const messages = await res.json();

            messagesContainer.innerHTML = '';

            messages.forEach(msg => {
                const div = document.createElement('div');

                // Décide si c'est un message envoyé par moi ou reçu
                const isMine = (msg.sender_id == appUser);
                div.className = 'message ' + (isMine ? 'sender' : 'receiver');

                div.innerHTML = `
                    <div class="message-pseudo">
                        ${msg.sender}
                    </div>

                    <div class="message-bubble">
                        ${msg.content}
                    </div>

                    <small class="message-date">
                        ${msg.createdAt}
                    </small>
                `;

                messagesContainer.appendChild(div);
            });

            // Scroll automatique en bas
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

        } catch (e) {
            console.error('Erreur chargement messages :', e);
        }
    }

    // ===== Rafraîchissement automatique =====
    loadMessages(); // premier chargement
    setInterval(loadMessages, 2000); // toutes les 2 secondes

    // ===== Envoi du message en AJAX =====
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const content = textarea.value.trim();
        if (!content) return;

        const formData = new FormData();
        formData.append('content', content);
        formData.append('receiver_id', receiverId);

        try {
            const res = await fetch('/messagerie/envoyer', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) throw new Error(res.status);

            textarea.value = '';

            // Recharge les messages après envoi
            await loadMessages();

        } catch (err) {
            console.error('Erreur envoi message :', err);
        }
    });

});
