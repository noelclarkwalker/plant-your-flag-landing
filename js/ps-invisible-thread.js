(function () {
  'use strict';

  var root = document.querySelector('[data-thread-ego]');
  if (!root) return;

  var trigger = root.querySelector('.ps-thread-ego__trigger');
  var reveal = root.querySelector('.ps-thread-ego__reveal');
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function settle() {
    root.classList.add('is-complete', 'is-settled');
    root.classList.remove('is-animating', 'is-expanding', 'is-opening', 'is-revealed');
    trigger.setAttribute('aria-expanded', 'true');
    reveal.removeAttribute('hidden');
    reveal.setAttribute('aria-hidden', 'false');
  }

  function openMoment() {
    if (root.classList.contains('is-complete')) return;

    if (reducedMotion) {
      settle();
      return;
    }

    trigger.setAttribute('aria-expanded', 'true');
    reveal.removeAttribute('hidden');
    root.classList.add('is-animating');

    window.requestAnimationFrame(function () {
      root.classList.add('is-expanding');
    });

    window.setTimeout(function () {
      root.classList.add('is-opening');
    }, 480);

    window.setTimeout(function () {
      root.classList.add('is-revealed');
    }, 720);

    window.setTimeout(settle, 980);
  }

  trigger.addEventListener('click', openMoment);

  trigger.addEventListener('keydown', function (event) {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      openMoment();
    }
  });
})();
