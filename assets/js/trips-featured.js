document.addEventListener('DOMContentLoaded', function(){
  const root = document;
  const chips = root.querySelectorAll('.mrihla-book-chip');
  const cards = root.querySelectorAll('.mrihla-trip-card');
  if (!chips.length || !cards.length) return;

  chips.forEach(ch => ch.addEventListener('click', () => {
    chips.forEach(c => c.classList.remove('is-active'));
    ch.classList.add('is-active');
    const sel = ch.dataset.filter;
    cards.forEach(card => {
      card.style.display = (sel === '*' || card.matches(sel)) ? '' : 'none';
    });
  }));
});


