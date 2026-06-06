/* Overlaytop — header glass, mobile drawer, search overlay. Vanilla JS, no deps. */
(function () {
  'use strict';
  var doc = document;

  /* Sticky glass header on scroll */
  var header = doc.getElementById('site-header');
  if (header) {
    var onScroll = function () {
      header.classList.toggle('is-stuck', window.scrollY > 8);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* Mobile drawer */
  var drawer = doc.getElementById('mobile-drawer');
  var backdrop = doc.querySelector('.drawer-backdrop');
  var openBtn = doc.querySelector('.js-drawer-open');
  var closeEls = doc.querySelectorAll('.js-drawer-close');

  function setDrawer(open) {
    if (!drawer) return;
    drawer.classList.toggle('is-open', open);
    if (backdrop) backdrop.classList.toggle('is-open', open);
    drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
    if (open) { drawer.removeAttribute('inert'); } else { drawer.setAttribute('inert', ''); }
    if (openBtn) openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    doc.body.style.overflow = open ? 'hidden' : '';
    if (open) {
      var first = drawer.querySelector('a, button');
      if (first) first.focus();
    } else if (openBtn) {
      openBtn.focus();
    }
  }
  if (openBtn) openBtn.addEventListener('click', function () { setDrawer(true); });
  Array.prototype.forEach.call(closeEls, function (el) {
    el.addEventListener('click', function () { setDrawer(false); });
  });

  /* Search overlay */
  var overlay = doc.querySelector('.js-search-overlay');
  var searchOpen = doc.querySelector('.js-search-open');

  function setSearch(open) {
    if (!overlay) return;
    overlay.classList.toggle('is-open', open);
    if (open) { overlay.removeAttribute('inert'); } else { overlay.setAttribute('inert', ''); }
    doc.body.style.overflow = open ? 'hidden' : '';
    if (open) {
      var input = overlay.querySelector('input[type=search], input[type=text]');
      if (input) { setTimeout(function () { input.focus(); }, 60); }
    } else if (searchOpen) {
      searchOpen.focus();
    }
  }
  if (searchOpen) searchOpen.addEventListener('click', function () { setSearch(true); });
  if (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) setSearch(false);
    });
  }

  /* Escape closes any open overlay */
  doc.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
      setDrawer(false);
      setSearch(false);
    }
  });
})();
