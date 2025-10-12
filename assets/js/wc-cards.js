document.addEventListener('click', function(e) {
  const sw = e.target.closest('.wc-swatch');
  if (!sw || sw.disabled) return;

  const card  = sw.closest('.wc-card');
  if (!card) return;

  // 1) Image
  const media   = card.querySelector('.wc-card__media img, .wc-card__img');
  const newSrc  = sw.dataset.img;
  const newSet  = sw.dataset.srcset;
  if (media && newSrc) {
    if (media.tagName === 'IMG') {
      media.src = newSrc;
      if (newSet) media.srcset = newSet;
    } else {
      const innerImg = card.querySelector('.wc-card__media img');
      if (innerImg) {
        innerImg.src = newSrc;
        if (newSet) innerImg.srcset = newSet;
      }
    }
  }

  // 2) Prix variation (remplace le prix générique)
  const priceEl = card.querySelector('.wc-card__price');
  if (priceEl && sw.dataset.pricehtml) {
    priceEl.innerHTML = sw.dataset.pricehtml;
  }

  // 3) Etat visuel actif
  card.querySelectorAll('.wc-swatch').forEach(el => el.classList.remove('is-active'));
  sw.classList.add('is-active');
});