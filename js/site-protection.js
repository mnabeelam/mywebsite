(function () {
  'use strict';

  document.addEventListener('contextmenu', function (event) {
    event.preventDefault();
  });

  document.addEventListener('keydown', function (event) {
    var key = (event.key || '').toLowerCase();
    var blocked =
      key === 'f12' ||
      (event.ctrlKey && event.shiftKey && (key === 'i' || key === 'j' || key === 'c')) ||
      (event.ctrlKey && key === 'u') ||
      (event.metaKey && event.altKey && key === 'i');

    if (blocked) {
      event.preventDefault();
      event.stopPropagation();
    }
  });
})();
