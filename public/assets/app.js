document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const submitter = event.submitter;
      if (!submitter || submitter.dataset.busy === 'true') return;
      submitter.dataset.busy = 'true';
      submitter.dataset.label = submitter.textContent;
      submitter.textContent = 'Elaborazione…';
      submitter.classList.add('is-loading');
    });
  });
});
