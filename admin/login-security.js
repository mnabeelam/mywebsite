if (window.location.search) {
  var params = new URLSearchParams(window.location.search);
  if (params.has('username') || params.has('password')) {
    history.replaceState(null, '', window.location.pathname + '?error=1');
    window.location.replace('index.php?error=' + encodeURIComponent(
      'Security warning: login must use the form, never the URL. Please change your password if it appeared in the address bar.'
    ));
  }
}
