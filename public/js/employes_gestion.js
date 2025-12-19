document.addEventListener('DOMContentLoaded', () => {
  const forms = document.querySelectorAll('.form-delete');
  const modal = document.querySelector('.modal-confirm');
  const btnConfirm = modal.querySelector('.modal-confirm-yes');
  const btnCancel = modal.querySelector('.modal-confirm-no');

  forms.forEach(form => {
    form.addEventListener('submit', function(event) {
      if (form.classList.contains('confirmed')) return;

      event.preventDefault();
      modal.classList.add('active');

      btnConfirm.onclick = () => {
        modal.classList.remove('active');
        form.classList.add('confirmed');
        form.submit();
      };

      btnCancel.onclick = () => {
        modal.classList.remove('active');
      };
    });
  });

  window.addEventListener('click', (e) => {
    if (e.target === modal) modal.classList.remove('active');
  });
});
