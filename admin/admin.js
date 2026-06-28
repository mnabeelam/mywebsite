
(function () {
  'use strict';

  var API = '../php/';
  var visitorState = {
    rows: [],
    search: '',
    page: 1,
    pageSize: 5
  };

  function showMessage(el, text, isError) {
    if (!el) return;
    el.hidden = false;
    el.textContent = text;
    el.className = 'message' + (isError ? ' error' : '');
  }

  function hideMessage(el) {
    if (!el) return;
    el.hidden = true;
    el.textContent = '';
    el.className = 'message';
  }

  async function readAdminJson(response) {
    var text = await response.text();
    if (!text || !text.trim()) {
      throw new Error('Server returned an empty response. Check PHP is running and upload size limits (post_max_size / upload_max_filesize).');
    }
    try {
      return JSON.parse(text);
    } catch (error) {
      var preview = text.replace(/\s+/g, ' ').trim().slice(0, 180);
      throw new Error('Server returned an invalid response. ' + (preview || 'Check PHP error logs.'));
    }
  }

  function adminPermissions(session) {
    if (session && session.session && session.session.permissions) {
      return session.session.permissions;
    }
    return ['*'];
  }

  function permissionMatches(granted, required) {
    if (granted === '*' || granted === required) {
      return true;
    }
    if (granted.slice(-2) === '.*') {
      var prefix = granted.slice(0, -1);
      return required.indexOf(prefix) === 0;
    }
    return false;
  }

  function hasAdminPermission(session, permission) {
    var perms = adminPermissions(session);
    for (var i = 0; i < perms.length; i++) {
      if (permissionMatches(perms[i], permission)) {
        return true;
      }
    }
    return false;
  }

  var ADMIN_TAB_PERMISSIONS = {
    account: 'account.view',
    network: 'network.view',
    content: 'content.view',
    shop: 'shop.view',
    backup: 'backup.view',
    updates: 'backup.view',
    users: 'users.view'
  };

  function applyAdminNavPermissions(session) {
    var tabs = document.querySelectorAll('.admin-nav-item[data-tab]');
    var firstVisible = '';
    tabs.forEach(function (tab) {
      var name = tab.getAttribute('data-tab') || '';
      var perm = ADMIN_TAB_PERMISSIONS[name];
      var visible = !perm || hasAdminPermission(session, perm);
      tab.hidden = !visible;
      if (visible && !firstVisible) {
        firstVisible = name;
      }
    });
    return firstVisible || 'account';
  }

  function formatVisitorTime(value) {
    if (!value) return '';
    var date = new Date(value);
    if (isNaN(date.getTime())) return value;
    return date.toLocaleString(undefined, {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    });
  }

  function visitorSearchHaystack(item) {
    return [
      item.time || '',
      formatVisitorTime(item.time),
      item.ip || '',
      item.referrer || '',
      item.page || '',
      item.user_agent || ''
    ].join(' ').toLowerCase();
  }

  function filterVisitorRows(rows, search) {
    var query = (search || '').trim().toLowerCase();
    if (!query) return rows.slice();
    return rows.filter(function (item) {
      return visitorSearchHaystack(item).indexOf(query) !== -1;
    });
  }

  function renderVisitorPanel() {
    var tableWrap = document.getElementById('visitorTableWrap');
    var pagination = document.getElementById('visitorPagination');
    var meta = document.getElementById('visitorResultMeta');
    if (!tableWrap) return;

    var filtered = filterVisitorRows(visitorState.rows, visitorState.search);
    var totalPages = Math.max(1, Math.ceil(filtered.length / visitorState.pageSize));
    if (visitorState.page > totalPages) visitorState.page = totalPages;
    if (visitorState.page < 1) visitorState.page = 1;

    var start = (visitorState.page - 1) * visitorState.pageSize;
    var pageRows = filtered.slice(start, start + visitorState.pageSize);

    if (meta) {
      if (!visitorState.rows.length) {
        meta.textContent = 'No visitor activity logged yet.';
      } else if (!filtered.length) {
        meta.textContent = 'No visitors match your search.';
      } else {
        meta.textContent = 'Showing ' + (start + 1) + '-' + (start + pageRows.length) +
          ' of ' + filtered.length + ' visitors · Page ' + visitorState.page + ' of ' + totalPages;
      }
    }

    tableWrap.textContent = '';
    var table = document.createElement('table');
    table.className = 'admin-table visitor-table';
    table.innerHTML = '<thead><tr><th>Date &amp; Time</th><th>IP</th><th>From</th><th>Page</th><th>Browser</th><th>Action</th></tr></thead>';
    var tbody = document.createElement('tbody');

    if (!pageRows.length) {
      var emptyRow = document.createElement('tr');
      var emptyCell = document.createElement('td');
      emptyCell.colSpan = 6;
      emptyCell.textContent = filtered.length ? 'No rows on this page.' : 'No visitor activity logged yet.';
      emptyRow.appendChild(emptyCell);
      tbody.appendChild(emptyRow);
    } else {
      pageRows.forEach(function (item) {
        var tr = document.createElement('tr');
        var timeCell = document.createElement('td');
        timeCell.className = 'visitor-time-cell';
        timeCell.textContent = formatVisitorTime(item.time);
        var ipCell = document.createElement('td');
        ipCell.textContent = item.ip || '';
        var refCell = document.createElement('td');
        refCell.textContent = item.referrer || 'Direct visit';
        var pageCell = document.createElement('td');
        pageCell.textContent = item.page || '';
        var uaCell = document.createElement('td');
        uaCell.textContent = (item.user_agent || '').substring(0, 60);
        var actionCell = document.createElement('td');
        var blockBtn = document.createElement('button');
        blockBtn.type = 'button';
        blockBtn.className = 'table-btn secondary';
        blockBtn.textContent = 'Block';
        blockBtn.dataset.ip = item.ip || '';
        blockBtn.dataset.action = 'block_ip';
        actionCell.appendChild(blockBtn);
        tr.appendChild(timeCell);
        tr.appendChild(ipCell);
        tr.appendChild(refCell);
        tr.appendChild(pageCell);
        tr.appendChild(uaCell);
        tr.appendChild(actionCell);
        tbody.appendChild(tr);
      });
    }

    table.appendChild(tbody);
    tableWrap.appendChild(table);

    if (pagination) {
      pagination.textContent = '';
      if (filtered.length > visitorState.pageSize) {
        var prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'visitor-page-btn secondary';
        prevBtn.textContent = 'Previous';
        prevBtn.disabled = visitorState.page <= 1;
        prevBtn.addEventListener('click', function () {
          visitorState.page -= 1;
          renderVisitorPanel();
        });

        var pagesWrap = document.createElement('div');
        pagesWrap.className = 'visitor-page-list';
        for (var p = 1; p <= totalPages; p++) {
          (function (pageNumber) {
            var pageBtn = document.createElement('button');
            pageBtn.type = 'button';
            pageBtn.className = 'visitor-page-btn' + (pageNumber === visitorState.page ? ' is-active' : '');
            pageBtn.textContent = String(pageNumber);
            pageBtn.addEventListener('click', function () {
              visitorState.page = pageNumber;
              renderVisitorPanel();
            });
            pagesWrap.appendChild(pageBtn);
          })(p);
        }

        var nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'visitor-page-btn secondary';
        nextBtn.textContent = 'Next';
        nextBtn.disabled = visitorState.page >= totalPages;
        nextBtn.addEventListener('click', function () {
          visitorState.page += 1;
          renderVisitorPanel();
        });

        pagination.appendChild(prevBtn);
        pagination.appendChild(pagesWrap);
        pagination.appendChild(nextBtn);
      }
    }
  }

  function bindVisitorControls() {
    var searchInput = document.getElementById('visitorSearch');
    if (!searchInput || searchInput.dataset.bound) return;
    searchInput.dataset.bound = '1';
    searchInput.addEventListener('input', function () {
      visitorState.search = searchInput.value || '';
      visitorState.page = 1;
      renderVisitorPanel();
    });
  }

  function stripSensitiveUrlParams() {
    if (!window.location.search) return;

    var params = new URLSearchParams(window.location.search);
    if (params.has('username') || params.has('password')) {
      history.replaceState(null, '', window.location.pathname);
      return 'Security warning: never put username or password in the URL. If your password appeared in the address bar, change it.';
    }
    return '';
  }

  async function fetchSession() {
    var response = await fetch(API + 'admin-session.php', {
      credentials: 'same-origin'
    });

    var text = await response.text();
    try {
      return JSON.parse(text);
    } catch (error) {
      throw new Error('PHP backend did not respond correctly. Open admin/index.php through mywebsite.local with PHP enabled.');
    }
  }

  async function initLoginPage() {
    var form = document.getElementById('loginForm');
    var message = document.getElementById('loginMessage');
    if (!form) return;

    var urlWarning = stripSensitiveUrlParams();
    if (urlWarning) {
      showMessage(message, urlWarning, true);
    }

    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      hideMessage(message);

      var body = new FormData(form);

      try {
        var response = await fetch(API + 'admin-login.php', {
          method: 'POST',
          body: body,
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'fetch'
          }
        });

        var data = {};
        try {
          data = JSON.parse(await response.text());
        } catch (error) {
          showMessage(message, 'Server error: PHP login endpoint returned an invalid response.', true);
          return;
        }

        if (!response.ok) {
          showMessage(message, data.error || 'Login failed.', true);
          return;
        }

        window.location.href = data.redirect || 'dashboard.php';
      } catch (error) {
        showMessage(message, 'Cannot reach PHP backend. Use http://mywebsite.local/admin/index.php instead of opening HTML files directly.', true);
      }
    });

    try {
      var session = await fetchSession();
      if (session.authenticated) {
        window.location.href = 'dashboard.php';
      }
    } catch (error) {
      showMessage(message, error.message, true);
    }
  }

  async function initDashboardPage() {
    var welcome = document.getElementById('welcomeText');
    var uploadForm = document.getElementById('uploadForm');
    var message = document.getElementById('dashboardMessage');
    var logoutBtn = document.getElementById('logoutBtn');
    if (!document.querySelector('.admin-layout')) return;

    var session;
    try {
      session = await fetchSession();
    } catch (error) {
      showMessage(message, error.message, true);
      return;
    }

    if (!session.authenticated) {
      window.location.href = 'index.php';
      return;
    }

    if (welcome) {
      var profile = session.session || {};
      var welcomeText = 'Signed in as ' + (profile.username || session.username || 'admin');
      if (profile.role_label) {
        welcomeText += ' · ' + profile.role_label;
      }
      welcome.textContent = welcomeText + '.';
    }

    var initialTab = applyAdminNavPermissions(session);

    loadKnowledgeStatus();
    loadSiteStats();
    loadRuntimeUpgradeBanner();
    loadAccountSettings(session);
    loadShopAdmin(session);
    loadCertAdmin(session);
    loadBackupAdmin(session);
    loadUsersAdmin(session);
    bindRuntimeUpgradeControls();
    bindRuntimeUpdatesTab(session);
    bindAccessControls(session);
    bindVisitorControls();
    initAdminTabs(initialTab);

    if (logoutBtn) {
      logoutBtn.addEventListener('click', async function () {
        var latestSession = await fetchSession();
        var body = new FormData();
        body.append('csrf_token', latestSession.csrf_token || '');

        await fetch(API + 'admin-logout.php', {
          method: 'POST',
          body: body,
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'fetch'
          }
        });
        window.location.href = 'index.php';
      });
    }

    if (uploadForm) {
      uploadForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      hideMessage(message);

      var fileInput = document.getElementById('cvFile');
      if (!fileInput || !fileInput.files || !fileInput.files[0]) {
        showMessage(message, 'Please choose a file first.', true);
        return;
      }

      try {
        var latestSession = await fetchSession();
        var body = new FormData();
        body.append('cv', fileInput.files[0]);
        body.append('csrf_token', latestSession.csrf_token || '');

        var response = await fetch(API + 'upload-and-process.php', {
          method: 'POST',
          body: body,
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'fetch'
          }
        });

        var data = JSON.parse(await response.text());

        if (!response.ok) {
          showMessage(message, data.error || 'Upload failed.', true);
          return;
        }

        showMessage(message, data.message || 'Upload completed successfully.', false);
        uploadForm.reset();
        loadKnowledgeStatus();
      } catch (error) {
        showMessage(message, 'Upload failed. Check PHP is running on mywebsite.local.', true);
      }
    });
    }

    var rebuildBtn = document.getElementById('rebuildBtn');
    if (rebuildBtn) {
      rebuildBtn.addEventListener('click', async function () {
        hideMessage(message);
        try {
          var latestSession = await fetchSession();
          var body = new FormData();
          body.append('csrf_token', latestSession.csrf_token || '');

          var response = await fetch(API + 'build-knowledge.php', {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: {
              'X-Requested-With': 'fetch'
            }
          });

          var data = JSON.parse(await response.text());
          if (!response.ok) {
            showMessage(message, data.error || 'Rebuild failed.', true);
            return;
          }

          showMessage(message, data.message || 'Knowledge index rebuilt.', false);
          loadKnowledgeStatus();
        } catch (error) {
          showMessage(message, 'Rebuild failed.', true);
        }
      });
    }
  }

  async function postAccessAction(session, payload) {
    var body = new FormData();
    Object.keys(payload).forEach(function (key) {
      body.append(key, payload[key]);
    });
    body.append('csrf_token', session.csrf_token || '');

    var response = await fetch(API + 'admin-access.php', {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'fetch' }
    });

    var data = JSON.parse(await response.text());
    if (!response.ok) {
      throw new Error(data.error || 'Request failed.');
    }
    return data;
  }

  async function postSettingsAction(session, payload) {
    var body = new FormData();
    Object.keys(payload).forEach(function (key) {
      body.append(key, payload[key]);
    });
    body.append('csrf_token', session.csrf_token || '');

    var response = await fetch(API + 'admin-settings.php', {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'fetch' }
    });

    var data = await readAdminJson(response);
    if (!response.ok) {
      throw new Error(data.error || 'Settings request failed.');
    }
    return data;
  }

  function initAdminTabs(initialTab) {
    var tabs = document.querySelectorAll('.admin-nav-item');
    var pages = document.querySelectorAll('.admin-page');
    if (!tabs.length || !pages.length) return;

    function showTab(tabName) {
      tabs.forEach(function (tab) {
        var active = tab.getAttribute('data-tab') === tabName;
        tab.classList.toggle('is-active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      pages.forEach(function (page) {
        var isActive = page.id === 'page-' + tabName;
        page.classList.toggle('is-active', isActive);
      });
      document.body.setAttribute('data-admin-theme', tabName);
      var main = document.querySelector('.admin-main');
      if (main) {
        main.scrollTop = 0;
      }
      try {
        sessionStorage.setItem('adminActiveTab', tabName);
      } catch (error) {}
      if (tabName === 'updates') {
        loadUpdatesAdminPanel();
      }
    }

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        if (tab.hidden) return;
        showTab(tab.getAttribute('data-tab') || 'account');
      });
    });

    var saved = initialTab || 'account';
    if (!initialTab) {
      try {
        saved = sessionStorage.getItem('adminActiveTab') || 'account';
      } catch (error) {}
    }
    var savedTab = document.querySelector('.admin-nav-item[data-tab="' + saved + '"]');
    if (!savedTab || savedTab.hidden) {
      saved = initialTab || 'account';
    }
    if (document.getElementById('page-' + saved)) {
      showTab(saved);
    }
  }

  async function loadAccountSettings(session) {
    var passwordForm = document.getElementById('passwordForm');
    var contactForm = document.getElementById('contactSettingsForm');
    if (!passwordForm && !contactForm) return;

    function hideAccountPasswordForm() {
      if (!passwordForm) return;
      passwordForm.hidden = true;
      passwordForm.reset();
      ['currentPassword', 'newPassword', 'confirmPassword'].forEach(function (id) {
        var field = document.getElementById(id);
        if (field) field.required = false;
      });
      var showBtn = document.getElementById('showPasswordFormBtn');
      if (showBtn) showBtn.hidden = false;
    }

    function showAccountPasswordForm() {
      if (!passwordForm) return;
      passwordForm.hidden = false;
      ['currentPassword', 'newPassword', 'confirmPassword'].forEach(function (id) {
        var field = document.getElementById(id);
        if (field) field.required = true;
      });
      var showBtn = document.getElementById('showPasswordFormBtn');
      if (showBtn) showBtn.hidden = true;
      var current = document.getElementById('currentPassword');
      if (current) current.focus();
    }

    try {
      var response = await fetch(API + 'admin-settings.php', {
        credentials: 'same-origin'
      });
      var data = await readAdminJson(response);
      if (!response.ok) {
        throw new Error(data.error || 'Could not load settings.');
      }

      var settings = data.settings || {};
      var usernameInput = document.getElementById('settingsUsername');
      if (usernameInput) usernameInput.value = settings.username || '';
      var usernameDisplay = document.getElementById('accountUsernameDisplay');
      if (usernameDisplay) usernameDisplay.textContent = settings.username || '—';
      var emailDisplay = document.getElementById('accountEmailDisplay');
      if (emailDisplay) {
        emailDisplay.textContent = settings.effective_contact_email || settings.contact_email || 'Not set';
      }
      document.getElementById('settingsContactEmail').value = settings.contact_email || '';
      document.getElementById('settingsDisplayEmail').value = settings.display_email || '';
      document.getElementById('settingsPhone').value = settings.phone || '';
      document.getElementById('settingsLinkedin').value = settings.linkedin || '';

      var status = document.getElementById('settingsStatus');
      if (status) {
        status.textContent = settings.effective_contact_email
          ? 'Active notification email: ' + settings.effective_contact_email
          : 'No notification email set yet — form messages use config/local.php if available.';
      }

      updateTwoFactorUi(data.two_factor || {});
    } catch (error) {
      showMessage(document.getElementById('dashboardMessage'), error.message, true);
    }

    var showPasswordBtn = document.getElementById('showPasswordFormBtn');
    if (showPasswordBtn && !showPasswordBtn.dataset.bound) {
      showPasswordBtn.dataset.bound = '1';
      showPasswordBtn.addEventListener('click', showAccountPasswordForm);
    }

    var cancelPasswordBtn = document.getElementById('cancelPasswordFormBtn');
    if (cancelPasswordBtn && !cancelPasswordBtn.dataset.bound) {
      cancelPasswordBtn.dataset.bound = '1';
      cancelPasswordBtn.addEventListener('click', hideAccountPasswordForm);
    }

    if (passwordForm && !passwordForm.dataset.bound) {
      passwordForm.dataset.bound = '1';
      passwordForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        hideMessage(document.getElementById('dashboardMessage'));
        try {
          var latestSession = await fetchSession();
          var result = await postSettingsAction(latestSession, {
            action: 'change_password',
            username: document.getElementById('settingsUsername').value,
            current_password: document.getElementById('currentPassword').value,
            new_password: document.getElementById('newPassword').value,
            confirm_password: document.getElementById('confirmPassword').value
          });
          hideAccountPasswordForm();
          if (document.getElementById('settingsUsername')) {
            document.getElementById('settingsUsername').value = (result.settings && result.settings.username) || '';
          }
          var usernameDisplay = document.getElementById('accountUsernameDisplay');
          if (usernameDisplay && result.settings) {
            usernameDisplay.textContent = result.settings.username || '—';
          }
          showMessage(document.getElementById('dashboardMessage'), result.message || 'Password updated.', false);
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        }
      });
    }

    if (contactForm && !contactForm.dataset.bound) {
      contactForm.dataset.bound = '1';
      contactForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        hideMessage(document.getElementById('dashboardMessage'));
        try {
          var latestSession = await fetchSession();
          var result = await postSettingsAction(latestSession, {
            action: 'save_contact',
            contact_email: document.getElementById('settingsContactEmail').value,
            display_email: document.getElementById('settingsDisplayEmail').value,
            phone: document.getElementById('settingsPhone').value,
            linkedin: document.getElementById('settingsLinkedin').value
          });
          var status = document.getElementById('settingsStatus');
          if (status && result.settings) {
            status.textContent = result.settings.effective_contact_email
              ? 'Active notification email: ' + result.settings.effective_contact_email
              : 'Contact settings saved.';
          }
          var emailDisplay = document.getElementById('accountEmailDisplay');
          if (emailDisplay && result.settings) {
            emailDisplay.textContent = result.settings.effective_contact_email || result.settings.contact_email || 'Not set';
          }
          showMessage(document.getElementById('dashboardMessage'), result.message || 'Contact settings saved.', false);
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        }
      });
    }

    bindTwoFactorControls();
  }

  function renderTwoFactorQr(uri) {
    var qrImg = document.getElementById('twoFactorQr');
    if (!qrImg || !uri) {
      return;
    }
    if (typeof qrcode !== 'function') {
      qrImg.hidden = true;
      return;
    }
    try {
      var qrObj = qrcode(0, 'M');
      qrObj.addData(uri);
      qrObj.make();
      qrImg.src = qrObj.createDataURL(4, 4);
      qrImg.hidden = false;
    } catch (error) {
      qrImg.hidden = true;
    }
  }

  function updateTwoFactorUi(twoFactor) {
    var statusEl = document.getElementById('twoFactorStatus');
    var beginBtn = document.getElementById('begin2faBtn');
    var disableBtn = document.getElementById('disable2faBtn');
    var setupBox = document.getElementById('twoFactorSetup');
    var disableForm = document.getElementById('disable2faForm');
    var enabled = !!(twoFactor && twoFactor.enabled);

    if (statusEl) {
      statusEl.textContent = enabled
        ? '2FA is ON. Login requires a code from your authenticator app.'
        : '2FA is OFF. Enable it to require a 6-digit code after your password.';
    }
    if (beginBtn) beginBtn.hidden = enabled;
    if (disableBtn) disableBtn.hidden = !enabled;
    if (disableForm) disableForm.hidden = !enabled;
    if (setupBox && enabled) setupBox.hidden = true;
  }

  function bindTwoFactorControls() {
    var beginBtn = document.getElementById('begin2faBtn');
    var disableBtn = document.getElementById('disable2faBtn');
    var setupBox = document.getElementById('twoFactorSetup');
    var confirmForm = document.getElementById('confirm2faForm');
    var disableForm = document.getElementById('disable2faForm');

    if (beginBtn && !beginBtn.dataset.bound) {
      beginBtn.dataset.bound = '1';
      beginBtn.addEventListener('click', async function () {
        hideMessage(document.getElementById('dashboardMessage'));
        try {
          var latestSession = await fetchSession();
          var result = await postSettingsAction(latestSession, { action: '2fa_begin_setup' });
          var setup = result.setup || {};
          if (setupBox) setupBox.hidden = false;
          renderTwoFactorQr(setup.provisioning_uri || '');
          var secretEl = document.getElementById('twoFactorSecret');
          if (secretEl) secretEl.textContent = setup.secret || '—';
          showMessage(document.getElementById('dashboardMessage'), result.message || 'Scan QR code and confirm.', false);
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        }
      });
    }

    if (disableBtn && !disableBtn.dataset.bound) {
      disableBtn.dataset.bound = '1';
      disableBtn.addEventListener('click', function () {
        if (disableForm) disableForm.hidden = false;
      });
    }

    if (confirmForm && !confirmForm.dataset.bound) {
      confirmForm.dataset.bound = '1';
      confirmForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        hideMessage(document.getElementById('dashboardMessage'));
        try {
          var latestSession = await fetchSession();
          var result = await postSettingsAction(latestSession, {
            action: '2fa_confirm_setup',
            otp_code: document.getElementById('confirm2faCode').value
          });
          updateTwoFactorUi(result.two_factor || { enabled: true });
          if (setupBox) setupBox.hidden = true;
          showMessage(document.getElementById('dashboardMessage'), result.message || '2FA enabled.', false);
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        }
      });
    }

    if (disableForm && !disableForm.dataset.bound) {
      disableForm.dataset.bound = '1';
      disableForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        hideMessage(document.getElementById('dashboardMessage'));
        try {
          var latestSession = await fetchSession();
          var result = await postSettingsAction(latestSession, {
            action: '2fa_disable',
            otp_code: document.getElementById('disable2faCode').value
          });
          updateTwoFactorUi(result.two_factor || { enabled: false });
          showMessage(document.getElementById('dashboardMessage'), result.message || '2FA disabled.', false);
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        }
      });
    }
  }

  function renderAccessControls(access) {
    var tableWrap = document.getElementById('visitorTableWrap');
    var blockedWrap = document.getElementById('blockedIpsWrap');
    var whitelistInput = document.getElementById('adminWhitelist');
    var whitelistStatus = document.getElementById('whitelistStatus');

    if (whitelistInput) {
      whitelistInput.value = (access.admin_ip_whitelist || []).join('\n');
    }

    if (whitelistStatus) {
      whitelistStatus.textContent = 'Your current IP: ' + (access.current_ip || 'unknown') +
        (access.admin_ip_allowed ? ' (allowed)' : ' (not in whitelist)');
    }

    if (tableWrap) {
      visitorState.rows = access.visitor_log || [];
      renderVisitorPanel();
    }

    if (blockedWrap) {
      blockedWrap.textContent = '';
      var blockedHeading = document.createElement('strong');
      blockedHeading.textContent = 'Blocked IPs: ';
      blockedWrap.appendChild(blockedHeading);
      var blocked = access.blocked_ips || [];
      if (!blocked.length) {
        blockedWrap.appendChild(document.createTextNode('None'));
      } else {
        var list = document.createElement('div');
        list.className = 'blocked-list';
        blocked.forEach(function (ip) {
          var chip = document.createElement('span');
          chip.textContent = ip + ' ';
          var unblockBtn = document.createElement('button');
          unblockBtn.type = 'button';
          unblockBtn.className = 'table-btn secondary';
          unblockBtn.textContent = 'Unblock';
          unblockBtn.dataset.ip = ip;
          unblockBtn.dataset.action = 'unblock_ip';
          chip.appendChild(unblockBtn);
          list.appendChild(chip);
        });
        blockedWrap.appendChild(list);
      }
    }
  }

  function bindAccessControls(session) {
    document.addEventListener('click', async function (event) {
      var target = event.target;
      if (!target || !target.dataset || !target.dataset.action) return;

      try {
        var latestSession = await fetchSession();
        var result = await postAccessAction(latestSession, {
          action: target.dataset.action,
          ip: target.dataset.ip || ''
        });
        if (result.access) {
          renderAccessControls(result.access);
        }
        showMessage(document.getElementById('dashboardMessage'), result.message || 'Updated.', false);
      } catch (error) {
        showMessage(document.getElementById('dashboardMessage'), error.message, true);
      }
    });

    var saveWhitelistBtn = document.getElementById('saveWhitelistBtn');
    if (saveWhitelistBtn) {
      saveWhitelistBtn.addEventListener('click', async function () {
        var whitelistInput = document.getElementById('adminWhitelist');
        try {
          var latestSession = await fetchSession();
          var result = await postAccessAction(latestSession, {
            action: 'save_whitelist',
            whitelist: whitelistInput ? whitelistInput.value : ''
          });
          if (result.access) {
            renderAccessControls(result.access);
          }
          showMessage(document.getElementById('dashboardMessage'), result.message || 'Whitelist saved.', false);
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        }
      });
    }

    var addCurrentIpBtn = document.getElementById('addCurrentIpBtn');
    if (addCurrentIpBtn) {
      addCurrentIpBtn.addEventListener('click', async function () {
        try {
          var latestSession = await fetchSession();
          var result = await postAccessAction(latestSession, { action: 'add_current_ip' });
          if (result.access) {
            renderAccessControls(result.access);
          }
          showMessage(document.getElementById('dashboardMessage'), result.message || 'Current IP added.', false);
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        }
      });
    }
  }

  async function loadSiteStats() {
    var statsEl = document.getElementById('siteStats');
    var messagesEl = document.getElementById('recentMessages');
    var ordersEl = document.getElementById('recentShopOrders');
    if (!statsEl) return;

    try {
      var response = await fetch(API + 'admin-stats.php', {
        credentials: 'same-origin'
      });
      var text = await response.text();
      var data = JSON.parse(text);

      if (!response.ok) {
        statsEl.textContent = data.error || 'Could not load site stats.';
        return;
      }

      var visitors = data.visitors || {};
      var pendingOrders = data.pending_shop_orders || 0;
      statsEl.textContent = 'Visitors: ' + (visitors.total || 0) +
        ' | Today: ' + (visitors.today || 0) +
        ' | Page views: ' + (visitors.page_views || 0) +
        ' | Contact messages: ' + (data.contact_messages || 0) +
        ' | Shop orders: ' + (data.shop_orders || 0) +
        (pendingOrders ? ' (' + pendingOrders + ' pending)' : '');

      if (data.access) {
        renderAccessControls(data.access);
      }

      if (messagesEl && data.recent_messages && data.recent_messages.length) {
        messagesEl.textContent = '';
        var list = document.createElement('ul');
        data.recent_messages.forEach(function (item) {
          var li = document.createElement('li');
          li.textContent = (item.name || 'Unknown') + ' · ' + (item.service || 'General') +
            ' (' + (item.email || '') + '): ' + (item.message || '').substring(0, 100);
          list.appendChild(li);
        });
        messagesEl.appendChild(list);
      } else if (messagesEl) {
        messagesEl.textContent = 'No contact messages yet.';
      }

      if (ordersEl) {
        ordersEl.textContent = '';
        var orders = data.recent_shop_orders || [];
        if (!orders.length) {
          ordersEl.textContent = 'No shop orders yet.';
        } else {
          var table = document.createElement('table');
          table.className = 'admin-table';
          table.innerHTML = '<thead><tr><th>Time</th><th>Customer</th><th>Product</th><th>Total</th><th>Status</th></tr></thead>';
          var tbody = document.createElement('tbody');
          orders.forEach(function (order) {
            var tr = document.createElement('tr');
            tr.innerHTML =
              '<td>' + formatVisitorTime(order.created || '') + '</td>' +
              '<td>' + (order.customer_name || '') + '<br>' + (order.customer_email || '') + '</td>' +
              '<td>' + (order.product_name || '') + ' × ' + (order.quantity || 1) + '</td>' +
              '<td>' + (order.currency || 'PKR') + ' ' + (order.total || 0) + '</td>' +
              '<td>' + (order.status || 'pending') + '</td>';
            tbody.appendChild(tr);
          });
          table.appendChild(tbody);
          ordersEl.appendChild(table);
        }
      }
    } catch (error) {
      statsEl.textContent = 'Could not load site stats.';
      if (ordersEl) {
        ordersEl.textContent = 'Could not load shop orders.';
      }
    }
  }

  async function loadKnowledgeStatus() {
    var statusEl = document.getElementById('knowledgeStatus');
    if (!statusEl) return;

    try {
      var response = await fetch(API + 'knowledge-status.php', {
        credentials: 'same-origin'
      });
      var data = JSON.parse(await response.text());
      var info = data.knowledge || {};
      statusEl.textContent = 'Knowledge entries: ' + (info.entry_count || 0) +
        (info.updated ? ' | Last updated: ' + info.updated : '');
    } catch (error) {
      statusEl.textContent = 'Could not load knowledge status.';
    }
  }

  async function postShopAction(session, payload) {
    var body = new FormData();
    Object.keys(payload).forEach(function (key) {
      body.append(key, payload[key]);
    });
    body.append('csrf_token', session.csrf_token || '');

    var response = await fetch(API + 'shop-admin.php', {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'fetch' }
    });

    var data = await readAdminJson(response);
    if (!response.ok) {
      throw new Error(data.error || 'Shop request failed.');
    }
    return data;
  }

  var shopOptions = { brands: [], categories: [] };
  var productExistingImages = [];
  var productPendingPreviewUrls = [];
  var SHOP_MAX_IMAGES = 10;

  function getProductImages(product) {
    if (Array.isArray(product.images) && product.images.length) {
      return product.images.slice();
    }
    if (product.image) {
      return [product.image];
    }
    return [];
  }

  function syncProductKeepImagesField() {
    var field = document.getElementById('productKeepImages');
    if (field) {
      field.value = JSON.stringify(productExistingImages);
    }
  }

  function revokeProductPendingPreviewUrls() {
    productPendingPreviewUrls.forEach(function (url) {
      URL.revokeObjectURL(url);
    });
    productPendingPreviewUrls = [];
  }

  function renderProductImagesPreview(selectedFiles) {
    var wrap = document.getElementById('productImagesPreview');
    if (!wrap) return;

    revokeProductPendingPreviewUrls();
    wrap.textContent = '';
    syncProductKeepImagesField();

    var totalCount = productExistingImages.length + (selectedFiles ? selectedFiles.length : 0);
    if (totalCount === 0) {
      var empty = document.createElement('p');
      empty.className = 'admin-note';
      empty.textContent = 'No pictures selected yet.';
      wrap.appendChild(empty);
      return;
    }

    productExistingImages.forEach(function (path) {
      var item = document.createElement('div');
      item.className = 'product-image-preview-item';

      var img = document.createElement('img');
      img.className = 'product-image-preview';
      img.src = '../' + path.replace(/^\//, '');
      img.alt = 'Saved product image';

      var removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'product-image-remove';
      removeBtn.textContent = 'Remove';
      removeBtn.addEventListener('click', function () {
        productExistingImages = productExistingImages.filter(function (savedPath) {
          return savedPath !== path;
        });
        renderProductImagesPreview(document.getElementById('productImages') ? document.getElementById('productImages').files : null);
      });

      item.appendChild(img);
      item.appendChild(removeBtn);
      wrap.appendChild(item);
    });

    if (selectedFiles) {
      Array.prototype.forEach.call(selectedFiles, function (file, index) {
        if (productExistingImages.length + index >= SHOP_MAX_IMAGES) {
          return;
        }
        var item = document.createElement('div');
        item.className = 'product-image-preview-item is-pending';

        var img = document.createElement('img');
        img.className = 'product-image-preview';
        var objectUrl = URL.createObjectURL(file);
        productPendingPreviewUrls.push(objectUrl);
        img.src = objectUrl;
        img.alt = file.name || 'New product image';

        var label = document.createElement('span');
        label.className = 'product-image-pending-label';
        label.textContent = 'New';

        item.appendChild(img);
        item.appendChild(label);
        wrap.appendChild(item);
      });
    }

    if (totalCount >= SHOP_MAX_IMAGES) {
      var limit = document.createElement('p');
      limit.className = 'admin-note';
      limit.textContent = 'Maximum ' + SHOP_MAX_IMAGES + ' pictures per product.';
      wrap.appendChild(limit);
    }
  }

  function populateShopSelects(options) {
    shopOptions = options || shopOptions;
    var brandSelect = document.getElementById('productBrand');
    var categorySelect = document.getElementById('productCategory');
    if (!brandSelect || !categorySelect) return;

    brandSelect.textContent = '';
    categorySelect.textContent = '';

    (shopOptions.brands || []).forEach(function (brand) {
      var option = document.createElement('option');
      option.value = brand;
      option.textContent = brand;
      brandSelect.appendChild(option);
    });

    (shopOptions.categories || []).forEach(function (category) {
      var option = document.createElement('option');
      option.value = category;
      option.textContent = category;
      categorySelect.appendChild(option);
    });
  }

  function toggleShopCustomField(selectId, wrapId) {
    var select = document.getElementById(selectId);
    var wrap = document.getElementById(wrapId);
    if (!select || !wrap) return;
    wrap.hidden = select.value !== 'Other';
  }

  function syncProductStockField() {
    var track = document.getElementById('productTrackStock');
    var stock = document.getElementById('productStock');
    if (!track || !stock) return;
    stock.disabled = !track.checked;
    if (track.checked && (stock.value === '' || stock.value === '0')) {
      stock.value = '1';
    }
  }

  function setSelectValueWithCustom(selectId, customWrapId, customInputId, value, options) {
    var select = document.getElementById(selectId);
    var customInput = document.getElementById(customInputId);
    if (!select) return;

    value = value || '';
    if (options.indexOf(value) !== -1) {
      select.value = value;
      if (customInput) customInput.value = '';
    } else if (value !== '') {
      select.value = 'Other';
      if (customInput) customInput.value = value;
    } else {
      select.value = options[0] || '';
      if (customInput) customInput.value = '';
    }
    toggleShopCustomField(selectId, customWrapId);
  }

  function resetProductForm() {
    var form = document.getElementById('productForm');
    if (!form) return;
    form.reset();
    document.getElementById('productId').value = '';
    document.getElementById('productKeepImages').value = '[]';
    productExistingImages = [];
    revokeProductPendingPreviewUrls();
    renderProductImagesPreview(null);
    document.getElementById('productCurrency').value = 'PKR';
    document.getElementById('productCostPrice').value = '';
    document.getElementById('productStock').value = '1';
    document.getElementById('productTrackStock').checked = true;
    document.getElementById('productActive').checked = true;
    populateShopSelects(shopOptions);
    setSelectValueWithCustom('productBrand', 'productBrandCustomWrap', 'productBrandCustom', '', shopOptions.brands || []);
    setSelectValueWithCustom('productCategory', 'productCategoryCustomWrap', 'productCategoryCustom', 'Accessory', shopOptions.categories || []);
    syncProductStockField();
  }

  function fillProductForm(product) {
    document.getElementById('productId').value = product.id || '';
    document.getElementById('productName').value = product.name || '';
    document.getElementById('productDescription').value = product.description || '';
    document.getElementById('productPrice').value = product.price || 0;
    document.getElementById('productCostPrice').value = product.cost_price || 0;
    document.getElementById('productCurrency').value = product.currency || 'PKR';
    document.getElementById('productStock').value = product.track_stock ? (product.stock || 1) : 1;
    document.getElementById('productTrackStock').checked = product.track_stock !== undefined
      ? !!product.track_stock
      : ((product.stock || 0) > 0);
    document.getElementById('productActive').checked = !!product.active;
    productExistingImages = getProductImages(product).filter(function (path) {
      return path !== 'assets/shop/placeholder.svg';
    });
    var productImagesInput = document.getElementById('productImages');
    if (productImagesInput) {
      productImagesInput.value = '';
    }
    renderProductImagesPreview(null);
    setSelectValueWithCustom('productBrand', 'productBrandCustomWrap', 'productBrandCustom', product.brand || '', shopOptions.brands || []);
    setSelectValueWithCustom('productCategory', 'productCategoryCustomWrap', 'productCategoryCustom', product.category || 'Accessory', shopOptions.categories || []);
    syncProductStockField();
  }

  function renderShopAdmin(shop) {
    var productsWrap = document.getElementById('productsTableWrap');
    var ordersWrap = document.getElementById('ordersTableWrap');
    var shopStats = document.getElementById('shopStats');

    if (shopStats) {
      shopStats.textContent = 'Products: ' + (shop.product_count || 0) +
        ' | Visible on site: ' + (shop.active_product_count || 0) +
        ' | Orders: ' + (shop.order_count || 0);
    }

    if (productsWrap) {
      productsWrap.textContent = '';
      var products = shop.products || [];
      var table = document.createElement('table');
      table.className = 'admin-table';
      table.innerHTML = '<thead><tr><th>Picture</th><th>Brand</th><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Visible</th><th>Actions</th></tr></thead>';
      var tbody = document.createElement('tbody');

      if (!products.length) {
        var emptyRow = document.createElement('tr');
        var emptyCell = document.createElement('td');
        emptyCell.colSpan = 8;
        emptyCell.textContent = 'No products yet. Add your first product above.';
        emptyRow.appendChild(emptyCell);
        tbody.appendChild(emptyRow);
      } else {
        products.forEach(function (product) {
          var tr = document.createElement('tr');
          var stockText = product.track_stock ? String(product.stock || 0) : 'Auto';

          var imageCell = document.createElement('td');
          var thumb = document.createElement('img');
          thumb.className = 'product-table-thumb';
          thumb.src = '../' + (product.image || 'assets/shop/placeholder.svg');
          thumb.alt = product.name || 'Product';
          imageCell.appendChild(thumb);
          var imageCount = getProductImages(product).filter(function (path) {
            return path !== 'assets/shop/placeholder.svg';
          }).length;
          if (imageCount > 1) {
            var countLabel = document.createElement('span');
            countLabel.className = 'product-table-image-count';
            countLabel.textContent = '+' + (imageCount - 1);
            imageCell.appendChild(countLabel);
          }

          var cells = [
            imageCell,
            product.brand || '',
            product.name || '',
            product.category || '',
            (product.currency || 'PKR') + ' ' + (product.price || 0),
            stockText,
            product.active ? 'Yes' : 'No'
          ];

          cells.forEach(function (value) {
            if (value instanceof HTMLElement) {
              tr.appendChild(value);
              return;
            }
            var td = document.createElement('td');
            td.textContent = value;
            tr.appendChild(td);
          });

          var actionCell = document.createElement('td');
          var editBtn = document.createElement('button');
          editBtn.type = 'button';
          editBtn.className = 'table-btn secondary';
          editBtn.textContent = 'Edit';
          editBtn.dataset.shopAction = 'edit_product';
          editBtn.dataset.productId = product.id || '';
          editBtn.productData = product;
          var deleteBtn = document.createElement('button');
          deleteBtn.type = 'button';
          deleteBtn.className = 'table-btn secondary';
          deleteBtn.textContent = 'Delete';
          deleteBtn.dataset.shopAction = 'delete_product';
          deleteBtn.dataset.productId = product.id || '';
          actionCell.appendChild(editBtn);
          actionCell.appendChild(deleteBtn);
          tr.appendChild(actionCell);
          tbody.appendChild(tr);
        });
      }

      table.appendChild(tbody);
      productsWrap.appendChild(table);
    }

    if (ordersWrap) {
      ordersWrap.textContent = '';
      var orders = shop.orders || [];
      var orderTable = document.createElement('table');
      orderTable.className = 'admin-table';
      orderTable.innerHTML = '<thead><tr><th>Time</th><th>Customer</th><th>Product</th><th>Qty</th><th>Total</th><th>Status</th><th>Update</th></tr></thead>';
      var orderBody = document.createElement('tbody');

      if (!orders.length) {
        var emptyOrderRow = document.createElement('tr');
        var emptyOrderCell = document.createElement('td');
        emptyOrderCell.colSpan = 7;
        emptyOrderCell.textContent = 'No shop orders yet.';
        emptyOrderRow.appendChild(emptyOrderCell);
        orderBody.appendChild(emptyOrderRow);
      } else {
        orders.forEach(function (order) {
          var row = document.createElement('tr');
          row.innerHTML =
            '<td>' + (order.created || '') + '</td>' +
            '<td>' + (order.customer_name || '') + '<br>' + (order.customer_email || '') + '<br>' + (order.customer_phone || '') + '</td>' +
            '<td>' + (order.product_name || '') + '</td>' +
            '<td>' + (order.quantity || 0) + '</td>' +
            '<td>' + (order.currency || 'PKR') + ' ' + (order.total || 0) + '</td>' +
            '<td>' + (order.status || 'pending') + '</td>';
          var updateCell = document.createElement('td');
          var select = document.createElement('select');
          ['pending', 'confirmed', 'shipped', 'completed', 'cancelled'].forEach(function (status) {
            var option = document.createElement('option');
            option.value = status;
            option.textContent = status;
            if ((order.status || 'pending') === status) {
              option.selected = true;
            }
            select.appendChild(option);
          });
          var saveBtn = document.createElement('button');
          saveBtn.type = 'button';
          saveBtn.className = 'table-btn secondary';
          saveBtn.textContent = 'Save';
          saveBtn.dataset.shopAction = 'update_order';
          saveBtn.dataset.orderId = order.id || '';
          saveBtn.orderSelect = select;
          updateCell.appendChild(select);
          updateCell.appendChild(saveBtn);
          row.appendChild(updateCell);
          orderBody.appendChild(row);
        });
      }

      orderTable.appendChild(orderBody);
      ordersWrap.appendChild(orderTable);
    }

    populateShopProductPickers(shop.products || []);
  }

  function formatReportMoney(value, currency) {
    return (currency || 'PKR') + ' ' + Number(value || 0).toLocaleString(undefined, {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2
    });
  }

  function populateShopProductPickers(products) {
    ['purchaseProductId', 'stockOutProductId'].forEach(function (id) {
      var select = document.getElementById(id);
      if (!select) return;
      var current = select.value;
      select.textContent = '';
      if (!products.length) {
        var emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = 'Add a product first';
        select.appendChild(emptyOption);
        select.disabled = true;
        return;
      }
      select.disabled = false;
      products.forEach(function (product) {
        var option = document.createElement('option');
        option.value = product.id || '';
        var label = [product.brand, product.name].filter(Boolean).join(' · ') || product.name || 'Product';
        if (product.track_stock) {
          label += ' (' + (product.stock || 0) + ' in stock)';
        }
        option.textContent = label;
        select.appendChild(option);
      });
      if (current) {
        select.value = current;
      }
    });
  }

  function setDefaultReportDates() {
    var fromInput = document.getElementById('reportFrom');
    var toInput = document.getElementById('reportTo');
    if (!fromInput || !toInput || toInput.value) return;
    var today = new Date();
    var from = new Date(today);
    from.setDate(from.getDate() - 30);
    toInput.value = today.toISOString().slice(0, 10);
    fromInput.value = from.toISOString().slice(0, 10);
  }

  function syncReportDateFields() {
    var reportType = document.getElementById('reportType');
    var fromInput = document.getElementById('reportFrom');
    var toInput = document.getElementById('reportTo');
    if (!reportType || !fromInput || !toInput) return;
    var inventory = reportType.value === 'inventory';
    fromInput.disabled = inventory;
    toInput.disabled = inventory;
  }

  function renderReportSummary(report) {
    var wrap = document.getElementById('reportSummary');
    if (!wrap || !report) return;
    wrap.textContent = '';
    var summary = report.summary || {};
    var items = [];

    if (report.type === 'inventory') {
      items = [
        ['Products', summary.products || 0],
        ['Units in stock', summary.units || 0],
        ['Stock value (cost)', formatReportMoney(summary.stock_value, summary.currency)],
        ['Retail value', formatReportMoney(summary.retail_value, summary.currency)],
        ['Potential profit', formatReportMoney(summary.potential_profit, summary.currency)]
      ];
    } else if (report.type === 'stock') {
      items = [
        ['Movements', summary.movements || 0],
        ['Stock in qty', summary.stock_in_qty || 0],
        ['Stock out qty', summary.stock_out_qty || 0],
        ['Purchased qty', summary.purchase_qty || 0],
        ['Sold qty', summary.sale_qty || 0]
      ];
    } else if (report.type === 'sales') {
      items = [
        ['Sales entries', summary.orders || 0],
        ['Units sold', summary.units_sold || 0],
        ['Revenue', formatReportMoney(summary.revenue, summary.currency)]
      ];
    } else if (report.type === 'purchase') {
      items = [
        ['Entries', summary.entries || 0],
        ['Units purchased', summary.units_purchased || 0],
        ['Purchase cost', formatReportMoney(summary.purchase_cost, summary.currency)]
      ];
    } else if (report.type === 'profit') {
      items = [
        ['Sales entries', summary.sales_count || 0],
        ['Units sold', summary.units_sold || 0],
        ['Revenue', formatReportMoney(summary.revenue, summary.currency)],
        ['Cost of goods', formatReportMoney(summary.cost_of_goods, summary.currency)],
        ['Gross profit', formatReportMoney(summary.gross_profit, summary.currency)],
        ['Margin', (summary.margin_percent || 0) + '%']
      ];
    }

    items.forEach(function (item) {
      var card = document.createElement('div');
      card.className = 'report-summary-card';
      card.innerHTML = '<span class="report-summary-label">' + item[0] + '</span><strong>' + item[1] + '</strong>';
      wrap.appendChild(card);
    });
  }

  function renderShopReport(report) {
    var wrap = document.getElementById('reportTableWrap');
    if (!wrap) return;
    wrap.textContent = '';
    renderReportSummary(report);

    var rows = report.rows || [];
    if (!rows.length) {
      var empty = document.createElement('p');
      empty.className = 'admin-note';
      empty.textContent = 'No records found for this report.';
      wrap.appendChild(empty);
      return;
    }

    var table = document.createElement('table');
    table.className = 'admin-table';
    var thead = document.createElement('thead');
    var tbody = document.createElement('tbody');
    var headers = [];
    var rowMapper = null;

    if (report.type === 'inventory') {
      headers = ['Brand', 'Product', 'Category', 'Stock', 'Unit Cost', 'Sale Price', 'Stock Value', 'Retail Value', 'Potential Profit', 'Visible'];
      rowMapper = function (row) {
        return [
          row.brand || '',
          row.product_name || '',
          row.category || '',
          row.stock_label || row.stock || '',
          formatReportMoney(row.unit_cost, row.currency),
          formatReportMoney(row.unit_price, row.currency),
          formatReportMoney(row.stock_value, row.currency),
          formatReportMoney(row.retail_value, row.currency),
          formatReportMoney(row.potential_profit, row.currency),
          row.active ? 'Yes' : 'No'
        ];
      };
    } else if (report.type === 'stock') {
      headers = ['Date', 'Type', 'In/Out', 'Brand', 'Product', 'Qty', 'Unit Cost', 'Unit Price', 'Total Cost', 'Total Sale', 'Reference', 'Note'];
      rowMapper = function (row) {
        return [
          row.created || '',
          row.type || '',
          row.direction || '',
          row.brand || '',
          row.product_name || '',
          row.quantity || 0,
          formatReportMoney(row.unit_cost, row.currency),
          formatReportMoney(row.unit_price, row.currency),
          formatReportMoney(row.total_cost, row.currency),
          formatReportMoney(row.total_sale, row.currency),
          row.reference || '',
          row.note || ''
        ];
      };
    } else if (report.type === 'sales') {
      headers = ['Date', 'Order', 'Brand', 'Product', 'Qty', 'Unit Price', 'Total', 'Note'];
      rowMapper = function (row) {
        return [
          row.created || '',
          row.reference || '',
          row.brand || '',
          row.product_name || '',
          row.quantity || 0,
          formatReportMoney(row.unit_price, row.currency),
          formatReportMoney(row.total_sale, row.currency),
          row.note || ''
        ];
      };
    } else if (report.type === 'purchase') {
      headers = ['Date', 'Type', 'Brand', 'Product', 'Qty', 'Unit Cost', 'Total Cost', 'Reference', 'Note'];
      rowMapper = function (row) {
        return [
          row.created || '',
          row.type || '',
          row.brand || '',
          row.product_name || '',
          row.quantity || 0,
          formatReportMoney(row.unit_cost, row.currency),
          formatReportMoney(row.total_cost, row.currency),
          row.reference || '',
          row.note || ''
        ];
      };
    } else if (report.type === 'profit') {
      headers = ['Brand', 'Product', 'Qty Sold', 'Revenue', 'Cost', 'Profit', 'Margin'];
      rowMapper = function (row) {
        return [
          row.brand || '',
          row.product_name || '',
          row.quantity || 0,
          formatReportMoney(row.revenue, row.currency),
          formatReportMoney(row.cost, row.currency),
          formatReportMoney(row.profit, row.currency),
          (row.margin_percent || 0) + '%'
        ];
      };
    }

    if (!rowMapper) return;

    var headRow = document.createElement('tr');
    headers.forEach(function (header) {
      var th = document.createElement('th');
      th.textContent = header;
      headRow.appendChild(th);
    });
    thead.appendChild(headRow);

    rows.forEach(function (row) {
      var tr = document.createElement('tr');
      rowMapper(row).forEach(function (value) {
        var td = document.createElement('td');
        td.textContent = value;
        tr.appendChild(td);
      });
      tbody.appendChild(tr);
    });

    table.appendChild(thead);
    table.appendChild(tbody);
    wrap.appendChild(table);
  }

  async function loadShopReport() {
    var reportType = document.getElementById('reportType');
    if (!reportType) return;

    syncReportDateFields();
    var params = new URLSearchParams();
    params.set('report', reportType.value || 'inventory');
    if (reportType.value !== 'inventory') {
      var from = document.getElementById('reportFrom');
      var to = document.getElementById('reportTo');
      if (from && from.value) params.set('from', from.value);
      if (to && to.value) params.set('to', to.value);
    }

    try {
      var response = await fetch(API + 'shop-admin.php?' + params.toString(), {
        credentials: 'same-origin'
      });
      var data = await readAdminJson(response);
      if (!response.ok) {
        throw new Error(data.error || 'Could not load report.');
      }
      renderShopReport(data.report || {});
    } catch (error) {
      showMessage(document.getElementById('dashboardMessage'), error.message, true);
    }
  }

  async function loadShopAdmin(session) {
    var productForm = document.getElementById('productForm');
    if (!productForm) return;

    try {
      var response = await fetch(API + 'shop-admin.php', {
        credentials: 'same-origin'
      });
      var data = await readAdminJson(response);
      if (!response.ok) {
        throw new Error(data.error || 'Could not load shop data.');
      }
      populateShopSelects((data.shop && data.shop.options) || {});
      resetProductForm();
      renderShopAdmin(data.shop || {});
      setDefaultReportDates();
      syncReportDateFields();
      loadShopReport();
    } catch (error) {
      showMessage(document.getElementById('dashboardMessage'), error.message, true);
    }

    if (!productForm.dataset.bound) {
      productForm.dataset.bound = '1';

      productForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        hideMessage(document.getElementById('dashboardMessage'));
        try {
          var latestSession = await fetchSession();
          var body = new FormData(productForm);
          body.append('action', 'save_product');
          body.delete('product_images[]');
          var productImagesInput = document.getElementById('productImages');
          if (productImagesInput && productImagesInput.files) {
            Array.prototype.forEach.call(productImagesInput.files, function (file) {
              body.append('product_images[]', file);
            });
          }
          body.set('keep_images', JSON.stringify(productExistingImages));
          if (document.getElementById('productTrackStock').checked) {
            body.set('track_stock', '1');
          } else {
            body.delete('track_stock');
          }
          if (document.getElementById('productActive').checked) {
            body.set('active', '1');
          } else {
            body.delete('active');
          }
          body.append('csrf_token', latestSession.csrf_token || '');

          var response = await fetch(API + 'shop-admin.php', {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch' }
          });
          var result = await readAdminJson(response);
          if (!response.ok) {
            throw new Error(result.error || 'Product could not be saved.');
          }
          populateShopSelects((result.shop && result.shop.options) || shopOptions);
          renderShopAdmin(result.shop || {});
          resetProductForm();
          loadShopReport();
          showMessage(document.getElementById('dashboardMessage'), result.message || 'Product saved.', false);
          loadSiteStats();
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        }
      });
    }

    var resetProductBtn = document.getElementById('resetProductBtn');
    if (resetProductBtn && !resetProductBtn.dataset.bound) {
      resetProductBtn.dataset.bound = '1';
      resetProductBtn.addEventListener('click', resetProductForm);
    }

    var productBrand = document.getElementById('productBrand');
    var productCategory = document.getElementById('productCategory');
    var productTrackStock = document.getElementById('productTrackStock');
    var productImages = document.getElementById('productImages');

    if (productBrand && !productBrand.dataset.bound) {
      productBrand.dataset.bound = '1';
      productBrand.addEventListener('change', function () {
        toggleShopCustomField('productBrand', 'productBrandCustomWrap');
      });
    }
    if (productCategory && !productCategory.dataset.bound) {
      productCategory.dataset.bound = '1';
      productCategory.addEventListener('change', function () {
        toggleShopCustomField('productCategory', 'productCategoryCustomWrap');
      });
    }
    if (productTrackStock && !productTrackStock.dataset.bound) {
      productTrackStock.dataset.bound = '1';
      productTrackStock.addEventListener('change', syncProductStockField);
    }
    if (productImages && !productImages.dataset.bound) {
      productImages.dataset.bound = '1';
      productImages.addEventListener('change', function () {
        renderProductImagesPreview(productImages.files);
      });
    }

    var loadReportBtn = document.getElementById('loadReportBtn');
    if (loadReportBtn && !loadReportBtn.dataset.bound) {
      loadReportBtn.dataset.bound = '1';
      loadReportBtn.addEventListener('click', loadShopReport);
    }

    var reportType = document.getElementById('reportType');
    if (reportType && !reportType.dataset.bound) {
      reportType.dataset.bound = '1';
      reportType.addEventListener('change', function () {
        syncReportDateFields();
      });
    }

    var purchaseForm = document.getElementById('purchaseForm');
    if (purchaseForm && !purchaseForm.dataset.bound) {
      purchaseForm.dataset.bound = '1';
      purchaseForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        hideMessage(document.getElementById('dashboardMessage'));
        try {
          var latestSession = await fetchSession();
          var result = await postShopAction(latestSession, {
            action: 'record_purchase',
            product_id: document.getElementById('purchaseProductId').value,
            quantity: document.getElementById('purchaseQuantity').value,
            unit_cost: document.getElementById('purchaseUnitCost').value,
            note: document.getElementById('purchaseNote').value
          });
          renderShopAdmin(result.shop || {});
          purchaseForm.reset();
          document.getElementById('purchaseQuantity').value = '1';
          loadShopReport();
          showMessage(document.getElementById('dashboardMessage'), result.message || 'Purchase recorded.', false);
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        }
      });
    }

    var stockOutForm = document.getElementById('stockOutForm');
    if (stockOutForm && !stockOutForm.dataset.bound) {
      stockOutForm.dataset.bound = '1';
      stockOutForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        hideMessage(document.getElementById('dashboardMessage'));
        try {
          var latestSession = await fetchSession();
          var result = await postShopAction(latestSession, {
            action: 'record_stock_out',
            product_id: document.getElementById('stockOutProductId').value,
            quantity: document.getElementById('stockOutQuantity').value,
            note: document.getElementById('stockOutNote').value
          });
          renderShopAdmin(result.shop || {});
          stockOutForm.reset();
          document.getElementById('stockOutQuantity').value = '1';
          loadShopReport();
          showMessage(document.getElementById('dashboardMessage'), result.message || 'Stock out recorded.', false);
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        }
      });
    }

    if (!document.body.dataset.shopActionsBound) {
      document.body.dataset.shopActionsBound = '1';
      document.addEventListener('click', async function (event) {
      var target = event.target;
      if (!target || !target.dataset || !target.dataset.shopAction) return;

      try {
        var latestSession = await fetchSession();
        if (target.dataset.shopAction === 'edit_product') {
          fillProductForm(target.productData || {});
          showMessage(document.getElementById('dashboardMessage'), 'Product loaded for editing.', false);
          return;
        }

        if (target.dataset.shopAction === 'delete_product') {
          if (!window.confirm('Delete this product?')) return;
          var deleteResult = await postShopAction(latestSession, {
            action: 'delete_product',
            id: target.dataset.productId || ''
          });
          renderShopAdmin(deleteResult.shop || {});
          showMessage(document.getElementById('dashboardMessage'), deleteResult.message || 'Product deleted.', false);
          loadSiteStats();
          return;
        }

        if (target.dataset.shopAction === 'update_order') {
          var statusSelect = target.orderSelect;
          var updateResult = await postShopAction(latestSession, {
            action: 'update_order_status',
            order_id: target.dataset.orderId || '',
            status: statusSelect ? statusSelect.value : 'pending'
          });
          renderShopAdmin(updateResult.shop || {});
          showMessage(document.getElementById('dashboardMessage'), updateResult.message || 'Order updated.', false);
          loadSiteStats();
        }
      } catch (error) {
        showMessage(document.getElementById('dashboardMessage'), error.message, true);
      }
    });
    }
  }

  async function postCertAction(session, formData) {
    formData.append('csrf_token', session.csrf_token || '');

    var response = await fetch(API + 'cert-admin.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'fetch' }
    });

    var data = await readAdminJson(response);
    if (!response.ok) {
      throw new Error(data.error || 'Certification request failed.');
    }
    return data;
  }

  function resetCertForm() {
    var form = document.getElementById('certForm');
    if (!form) return;
    form.reset();
    document.getElementById('certId').value = '';
    document.getElementById('certSortOrder').value = '0';
    document.getElementById('certActive').checked = true;
    var hint = document.getElementById('certFileHint');
    if (hint) {
      hint.textContent = 'Choose a file first — the form will auto-fill from the document.';
    }
    setCertExtractStatus('');
  }

  function fillCertForm(cert) {
    document.getElementById('certId').value = cert.id || '';
    document.getElementById('certTitle').value = cert.title || '';
    document.getElementById('certIssuer').value = cert.issuer || '';
    document.getElementById('certYear').value = cert.year || '';
    document.getElementById('certDescription').value = cert.description || '';
    document.getElementById('certSortOrder').value = cert.sort_order || 0;
    document.getElementById('certActive').checked = !!cert.active;
    var hint = document.getElementById('certFileHint');
    if (hint) {
      hint.textContent = cert.filename
        ? 'Current file: ' + (cert.original_name || cert.filename) + '. Choose a new file to replace it and auto-fill fields again.'
        : 'Choose a file to auto-fill the fields from the document.';
    }
    setCertExtractStatus('');
  }

  function setCertExtractStatus(text, isError) {
    var status = document.getElementById('certExtractStatus');
    if (!status) return;
    if (!text) {
      status.hidden = true;
      status.textContent = '';
      status.className = 'admin-note';
      return;
    }
    status.hidden = false;
    status.textContent = text;
    status.className = 'admin-note' + (isError ? ' message error' : '');
  }

  function applyCertExtracted(extracted) {
    if (!extracted) return;
    var fields = [
      ['certTitle', extracted.title || ''],
      ['certIssuer', extracted.issuer || ''],
      ['certYear', extracted.year || ''],
      ['certDescription', extracted.description || '']
    ];

    fields.forEach(function (item) {
      var el = document.getElementById(item[0]);
      if (!el) return;
      el.value = item[1];
      el.classList.toggle('is-autofilled', item[1] !== '');
    });

    var titleEl = document.getElementById('certTitle');
    if (titleEl) {
      titleEl.focus();
      titleEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  async function extractCertFromFile(file) {
    if (!file) {
      setCertExtractStatus('Choose a certificate file first.', true);
      return;
    }

    setCertExtractStatus('Reading certificate document...', false);
    try {
      var latestSession = await fetchSession();
      var body = new FormData();
      body.append('action', 'extract_cert');
      body.append('certificate', file);
      body.append('csrf_token', latestSession.csrf_token || '');

      var response = await fetch(API + 'cert-admin.php', {
        method: 'POST',
        body: body,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'fetch' }
      });

      var data = await readAdminJson(response);
      if (!response.ok) {
        throw new Error(data.error || 'Could not read certificate file.');
      }

      applyCertExtracted(data.extracted || {});

      var filledCount = ['title', 'issuer', 'year', 'description'].filter(function (key) {
        return !!(data.extracted && data.extracted[key]);
      }).length;

      if (filledCount === 0) {
        setCertExtractStatus('File accepted, but no text was found. Enter details manually, then save.', true);
      } else {
        setCertExtractStatus((data.message || 'Fields auto-filled.') + ' (' + filledCount + ' fields filled)', false);
        var contentTab = document.querySelector('.admin-nav-item[data-tab="content"]');
        if (contentTab) contentTab.click();
        showMessage(document.getElementById('dashboardMessage'), 'Review the green-highlighted fields, then click Save Certification.', false);
      }
    } catch (error) {
      setCertExtractStatus(error.message, true);
    }
  }

  function renderCertAdmin(summary) {
    var wrap = document.getElementById('certsTableWrap');
    var stats = document.getElementById('certStats');
    var certs = summary.certifications || [];

    if (stats) {
      stats.textContent = 'Certifications: ' + (summary.certification_count || 0) +
        ' | Visible on homepage: ' + (summary.active_certification_count || 0) +
        ' | With files: ' + (summary.file_count || 0);
    }

    if (!wrap) return;
    wrap.textContent = '';

    var table = document.createElement('table');
    table.className = 'admin-table';
    table.innerHTML = '<thead><tr><th>Title</th><th>Issuer</th><th>File</th><th>Visible</th><th>Actions</th></tr></thead>';
    var tbody = document.createElement('tbody');

    if (!certs.length) {
      var emptyRow = document.createElement('tr');
      var emptyCell = document.createElement('td');
      emptyCell.colSpan = 5;
      emptyCell.textContent = 'No certifications yet.';
      emptyRow.appendChild(emptyCell);
      tbody.appendChild(emptyRow);
    } else {
      certs.forEach(function (cert) {
        var tr = document.createElement('tr');
        tr.innerHTML =
          '<td>' + (cert.title || '') + '</td>' +
          '<td>' + (cert.issuer || '') + '</td>' +
          '<td>' + ((cert.filename || '') ? (cert.original_name || cert.filename) : 'No file') + '</td>' +
          '<td>' + (cert.active ? 'Yes' : 'No') + '</td>';
        var actionCell = document.createElement('td');
        if (cert.filename) {
          var viewLink = document.createElement('a');
          viewLink.href = '../php/cert-file.php?id=' + encodeURIComponent(cert.id || '');
          viewLink.target = '_blank';
          viewLink.rel = 'noopener noreferrer';
          viewLink.textContent = 'View';
          actionCell.appendChild(viewLink);
          actionCell.appendChild(document.createTextNode(' '));
        }
        var editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.className = 'table-btn secondary';
        editBtn.textContent = 'Edit';
        editBtn.dataset.certAction = 'edit_cert';
        editBtn.certData = cert;
        var deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'table-btn secondary';
        deleteBtn.textContent = 'Delete';
        deleteBtn.dataset.certAction = 'delete_cert';
        deleteBtn.dataset.certId = cert.id || '';
        actionCell.appendChild(editBtn);
        actionCell.appendChild(deleteBtn);
        tr.appendChild(actionCell);
        tbody.appendChild(tr);
      });
    }

    table.appendChild(tbody);
    wrap.appendChild(table);
  }

  async function loadCertAdmin(session) {
    var certForm = document.getElementById('certForm');
    if (!certForm) return;

    try {
      var response = await fetch(API + 'cert-admin.php', {
        credentials: 'same-origin'
      });
      var data = await readAdminJson(response);
      if (!response.ok) {
        throw new Error(data.error || 'Could not load certifications.');
      }
      renderCertAdmin(data.certifications || {});
    } catch (error) {
      showMessage(document.getElementById('dashboardMessage'), error.message, true);
    }

    certForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      hideMessage(document.getElementById('dashboardMessage'));
      try {
        var latestSession = await fetchSession();
        var body = new FormData(certForm);
        body.append('action', 'save_cert');
        if (document.getElementById('certActive').checked) {
          body.set('active', '1');
        } else {
          body.delete('active');
        }
        var result = await postCertAction(latestSession, body);
        renderCertAdmin(result.certifications || {});
        resetCertForm();
        showMessage(document.getElementById('dashboardMessage'), result.message || 'Certification saved.', false);
      } catch (error) {
        showMessage(document.getElementById('dashboardMessage'), error.message, true);
      }
    });

    var resetCertBtn = document.getElementById('resetCertBtn');
    if (resetCertBtn) {
      resetCertBtn.addEventListener('click', resetCertForm);
    }

    var certFileInput = document.getElementById('certFile');
    var autoFillCertBtn = document.getElementById('autoFillCertBtn');

    if (autoFillCertBtn && !autoFillCertBtn.dataset.bound) {
      autoFillCertBtn.dataset.bound = '1';
      autoFillCertBtn.addEventListener('click', function () {
        if (!certFileInput || !certFileInput.files || !certFileInput.files[0]) {
          setCertExtractStatus('Choose a certificate file first.', true);
          return;
        }
        extractCertFromFile(certFileInput.files[0]);
      });
    }

    if (certFileInput && !certFileInput.dataset.bound) {
      certFileInput.dataset.bound = '1';
      certFileInput.addEventListener('change', function () {
        if (!certFileInput.files || !certFileInput.files[0]) {
          return;
        }
        extractCertFromFile(certFileInput.files[0]);
      });
    }

    document.addEventListener('click', async function (event) {
      var target = event.target;
      if (!target || !target.dataset || !target.dataset.certAction) return;

      try {
        var latestSession = await fetchSession();
        if (target.dataset.certAction === 'edit_cert') {
          fillCertForm(target.certData || {});
          showMessage(document.getElementById('dashboardMessage'), 'Certification loaded for editing.', false);
          return;
        }

        if (target.dataset.certAction === 'delete_cert') {
          if (!window.confirm('Delete this certification?')) return;
          var body = new FormData();
          body.append('action', 'delete_cert');
          body.append('id', target.dataset.certId || '');
          var deleteResult = await postCertAction(latestSession, body);
          renderCertAdmin(deleteResult.certifications || {});
          showMessage(document.getElementById('dashboardMessage'), deleteResult.message || 'Certification deleted.', false);
        }
      } catch (error) {
        showMessage(document.getElementById('dashboardMessage'), error.message, true);
      }
    });
  }

  async function refreshDashboardAfterDataChange(session) {
    loadKnowledgeStatus();
    loadSiteStats();
    loadAccountSettings(session);
    loadCertAdmin(session);
    loadShopAdmin(session);
    loadBackupAdmin(session);
  }

  function formatBackupBytes(bytes) {
    bytes = Number(bytes) || 0;
    if (bytes < 1024) {
      return bytes + ' B';
    }
    if (bytes < 1048576) {
      return (bytes / 1024).toFixed(1) + ' KB';
    }
    return (bytes / 1048576).toFixed(1) + ' MB';
  }

  function formatBackupDate(value) {
    if (!value) {
      return '—';
    }
    var date = new Date(value);
    if (isNaN(date.getTime())) {
      return value;
    }
    return date.toLocaleString();
  }

  function renderBackupSummary(summary) {
    var wrap = document.getElementById('backupSummary');
    if (!wrap) {
      return;
    }

    wrap.textContent = '';
    var items = [
      ['Shop products', summary.shop_products || 0],
      ['Shop orders', summary.shop_orders || 0],
      ['Certifications', summary.certifications || 0],
      ['Contact messages', summary.contact_messages || 0],
      ['Visitor total', summary.visitor_total || 0],
      ['Knowledge entries', summary.knowledge_entries || 0],
      ['Stored backups', summary.stored_backups || 0]
    ];

    items.forEach(function (item) {
      var card = document.createElement('div');
      card.className = 'report-summary-card';
      card.innerHTML = '<span class="report-summary-label">' + item[0] + '</span><strong>' + item[1] + '</strong>';
      wrap.appendChild(card);
    });
  }

  var backupCapability = {
    zip: true,
    encryption: true
  };

  function renderBackupIncludes(data) {
    var includesList = document.getElementById('backupIncludesList');
    var excludesWrap = document.getElementById('backupExcludesWrap');
    var capabilityNote = document.getElementById('backupCapabilityNote');
    var createBtn = document.getElementById('createBackupBtn');

    if (capabilityNote) {
      if (data.zip_available === false) {
        capabilityNote.hidden = false;
        capabilityNote.textContent = 'Backup is unavailable: enable the PHP zip extension (extension=zip in php.ini) and restart your web server.';
      } else if (data.encryption_available === false) {
        capabilityNote.hidden = false;
        capabilityNote.textContent = 'Password-protected backups require PHP ZipArchive encryption support.';
      } else {
        capabilityNote.hidden = true;
        capabilityNote.textContent = '';
      }
    }

    if (createBtn) {
      backupCapability.zip = data.zip_available !== false;
      backupCapability.encryption = data.encryption_available !== false;
      createBtn.disabled = !backupCapability.zip || !backupCapability.encryption;
    }

    if (includesList) {
      includesList.textContent = '';
      (data.includes || []).forEach(function (item) {
        var li = document.createElement('li');
        li.textContent = item;
        includesList.appendChild(li);
      });
    }

    if (excludesWrap) {
      excludesWrap.textContent = '';
      var heading = document.createElement('p');
      heading.className = 'backup-excludes-title';
      heading.textContent = 'Not included (by design):';
      excludesWrap.appendChild(heading);

      (data.excludes || []).forEach(function (item) {
        var block = document.createElement('div');
        block.className = 'backup-exclude-item';
        if (typeof item === 'string') {
          block.textContent = item;
        } else {
          var title = document.createElement('strong');
          title.textContent = item.title || '';
          var reason = document.createElement('span');
          reason.textContent = item.reason || '';
          block.appendChild(title);
          block.appendChild(reason);
        }
        excludesWrap.appendChild(block);
      });
    }
  }

  function renderStoredBackups(backups) {
    var wrap = document.getElementById('storedBackupsWrap');
    if (!wrap) {
      return;
    }

    wrap.textContent = '';
    if (!backups || !backups.length) {
      wrap.textContent = 'No stored backups yet. Create one above.';
      return;
    }

    var table = document.createElement('table');
    table.className = 'admin-table';
    table.innerHTML = '<thead><tr><th>File</th><th>Created</th><th>Size</th><th></th></tr></thead>';
    var tbody = document.createElement('tbody');

    backups.forEach(function (item) {
      var tr = document.createElement('tr');
      var fileCell = document.createElement('td');
      fileCell.textContent = item.filename || '';
      var createdCell = document.createElement('td');
      createdCell.textContent = formatBackupDate(item.created);
      var sizeCell = document.createElement('td');
      sizeCell.textContent = formatBackupBytes(item.size);
      var actionCell = document.createElement('td');
      var downloadBtn = document.createElement('button');
      downloadBtn.type = 'button';
      downloadBtn.className = 'table-btn secondary';
      downloadBtn.textContent = 'Download';
      downloadBtn.addEventListener('click', async function () {
        hideMessage(document.getElementById('dashboardMessage'));
        try {
          await triggerBackupDownload(item.filename || '');
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        }
      });
      actionCell.appendChild(downloadBtn);
      tr.appendChild(fileCell);
      tr.appendChild(createdCell);
      tr.appendChild(sizeCell);
      tr.appendChild(actionCell);
      tbody.appendChild(tr);
    });

    table.appendChild(tbody);
    wrap.appendChild(table);
  }

  function renderBackupAdmin(data) {
    data = data || {};
    renderBackupSummary(data.summary || {});
    renderBackupIncludes(data);
    renderStoredBackups(data.backups || []);
  }

  async function triggerBackupDownload(filename) {
    if (!filename) {
      throw new Error('Backup filename missing.');
    }

    var response = await fetch(API + 'backup-admin.php?download=' + encodeURIComponent(filename), {
      credentials: 'same-origin'
    });

    if (!response.ok) {
      var data = {};
      try {
        data = await readAdminJson(response);
      } catch (error) {
        throw new Error('Backup download failed.');
      }
      throw new Error(data.error || 'Backup download failed.');
    }

    var blob = await response.blob();
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  }

  var runtimeUpgradePollId = null;
  var runtimeUpgradePlanCache = null;

  function stopRuntimeUpgradePoll() {
    if (runtimeUpgradePollId) {
      clearInterval(runtimeUpgradePollId);
      runtimeUpgradePollId = null;
    }
  }

  function startRuntimeUpgradePoll() {
    stopRuntimeUpgradePoll();
    runtimeUpgradePollId = setInterval(function () {
      pollRuntimeUpgradeJob();
    }, 3000);
  }

  function openRuntimeUpgradeModal() {
    var modal = document.getElementById('runtimeUpgradeModal');
    if (modal) modal.hidden = false;
    renderRuntimeImpactSummaries(runtimeUpgradePlanCache || runtimeDefaultImpactPlan());
    fetchRuntimeUpgradePlan().then(function (plan) {
      runtimeUpgradePlanCache = plan;
      renderRuntimeUpgradeModal(plan);
      updateRuntimeUpgradeBanner(plan);
    }).catch(function (error) {
      var summary = document.getElementById('runtimeUpgradeModalSummary');
      if (summary) {
        summary.textContent = 'Live check unavailable (' + error.message + '). Default guidance is shown below.';
      }
      renderRuntimeImpactSummaries(runtimeDefaultImpactPlan());
    });
  }

  function closeRuntimeUpgradeModal() {
    var modal = document.getElementById('runtimeUpgradeModal');
    if (modal) modal.hidden = true;
    var confirmInput = document.getElementById('runtimeUpgradeConfirm');
    if (confirmInput) confirmInput.value = '';
  }

  function updateUpdatesNavBadge(plan) {
    var badge = document.getElementById('updatesNavBadge');
    if (!badge) return;
    var updates = plan.updates_available || 0;
    if (updates > 0) {
      badge.hidden = false;
      badge.textContent = String(updates);
    } else {
      badge.hidden = true;
      badge.textContent = '';
    }
  }

  function formatHistoryType(entry) {
    var type = entry.type || '';
    if (type === 'deferred') {
      var mode = entry.mode || '';
      if (mode === 'remind_later') return 'Deferred — remind later';
      if (mode === 'tab_only') return 'Cancelled — saved in System Updates';
      if (mode === 'ask_again') return 'Closed — ask again next visit';
      return 'Deferred';
    }
    if (type === 'upgrade_completed') return 'Upgrade completed';
    if (type === 'upgrade_failed') return 'Upgrade failed';
    return type || 'Event';
  }

  function renderUpdatesAdminPanel(plan) {
    var reminder = document.getElementById('updatesReminderStatus');
    var clearBtn = document.getElementById('updatesClearDeferBtn');
    var pendingMeta = document.getElementById('updatesPendingMeta');
    var pendingWrap = document.getElementById('updatesPendingWrap');
    var historyWrap = document.getElementById('updatesHistoryWrap');
    if (!pendingMeta || !pendingWrap || !historyWrap) return;

    var prefs = plan.prefs || {};
    var suppressed = plan.banner_suppressed || prefs.banner_suppressed;
    var mode = prefs.suppress_mode || '';

    if (reminder) {
      if ((plan.updates_available || 0) === 0) {
        reminder.textContent = 'All monitored runtimes are up to date. No pending upgrades.';
      } else if (!suppressed) {
        reminder.textContent = 'Banner reminders are active. Pending updates also appear on this tab.';
      } else if (mode === 'remind_later') {
        reminder.textContent = 'You chose to upgrade later. The banner is hidden until pending package versions change.';
      } else if (mode === 'tab_only') {
        reminder.textContent = 'You cancelled the upgrade prompt. Pending updates are listed below — review and approve when ready.';
      } else {
        reminder.textContent = 'Reminder preferences saved.';
      }
    }

    if (clearBtn) {
      clearBtn.hidden = !suppressed;
    }

    var pending = (plan.packages || []).filter(function (pkg) {
      return pkg.update_available;
    });
    if (pending.length) {
      pendingMeta.textContent = pending.length + ' package(s) waiting for approval.';
    } else {
      pendingMeta.textContent = 'No pending runtime upgrades.';
    }

    pendingWrap.textContent = '';
    if (!pending.length) {
      pendingWrap.innerHTML = '<p class="admin-note">Nothing pending.</p>';
    } else {
      var pendingTable = document.createElement('table');
      pendingTable.className = 'admin-table';
      pendingTable.innerHTML = '<thead><tr><th>Package</th><th>Installed</th><th>Available</th><th>Site impact</th></tr></thead>';
      var tbody = document.createElement('tbody');
      pending.forEach(function (pkg) {
        var row = document.createElement('tr');
        [
          pkg.label || pkg.name || '',
          pkg.installed || '—',
          pkg.latest_stable || '—',
          pkg.site_impact || ''
        ].forEach(function (text) {
          var td = document.createElement('td');
          td.textContent = text;
          row.appendChild(td);
        });
        tbody.appendChild(row);
      });
      pendingTable.appendChild(tbody);
      pendingWrap.appendChild(pendingTable);
    }

    var history = plan.history || [];
    historyWrap.textContent = '';
    if (!history.length) {
      historyWrap.innerHTML = '<p class="admin-note">No upgrade history yet.</p>';
      return;
    }

    var historyTable = document.createElement('table');
    historyTable.className = 'admin-table';
    historyTable.innerHTML = '<thead><tr><th>Date &amp; time</th><th>Event</th><th>By</th><th>Packages</th><th>Details</th></tr></thead>';
    var historyBody = document.createElement('tbody');
    history.forEach(function (entry) {
      var row = document.createElement('tr');
      var packages = (entry.packages_pending || entry.packages_upgraded || []).join(', ');
      var details = entry.note || '';
      if (entry.compatibility_ok === false && entry.type === 'upgrade_completed') {
        details = (details ? details + ' ' : '') + 'Compatibility check reported issues.';
      }
      [
        formatVisitorTime(entry.at),
        formatHistoryType(entry),
        entry.by || '—',
        packages || '—',
        details || '—'
      ].forEach(function (text) {
        var td = document.createElement('td');
        td.textContent = text;
        row.appendChild(td);
      });
      historyBody.appendChild(row);
    });
    historyTable.appendChild(historyBody);
    historyWrap.appendChild(historyTable);
  }

  async function loadUpdatesAdminPanel() {
    try {
      var plan = await fetchRuntimeUpgradePlan();
      runtimeUpgradePlanCache = plan;
      renderUpdatesAdminPanel(plan);
      updateUpdatesNavBadge(plan);
    } catch (error) {
      var pendingMeta = document.getElementById('updatesPendingMeta');
      if (pendingMeta) pendingMeta.textContent = error.message;
    }
  }

  function bindRuntimeUpgradeControls() {
    var cancelBtn = document.getElementById('runtimeUpgradeCancelBtn');
    if (cancelBtn && !cancelBtn.dataset.bound) {
      cancelBtn.dataset.bound = '1';
      cancelBtn.addEventListener('click', function () {
        cancelRuntimeUpgrade();
      });
    }

    var approveBtn = document.getElementById('runtimeUpgradeApproveBtn');
    if (approveBtn && !approveBtn.dataset.bound) {
      approveBtn.dataset.bound = '1';
      approveBtn.addEventListener('click', function () {
        approveRuntimeUpgrade();
      });
    }

    var reviewBtn = document.getElementById('runtimeUpgradeReviewBtn');
    if (reviewBtn && !reviewBtn.dataset.bound) {
      reviewBtn.dataset.bound = '1';
      reviewBtn.addEventListener('click', function () {
        openRuntimeUpgradeModal();
      });
    }

    var panelBtn = document.getElementById('runtimePanelApproveBtn');
    if (panelBtn && !panelBtn.dataset.bound) {
      panelBtn.dataset.bound = '1';
      panelBtn.addEventListener('click', function () {
        openRuntimeUpgradeModal();
      });
    }
  }

  function bindRuntimeUpdatesTab(session) {
    var reviewBtn = document.getElementById('updatesReviewBtn');
    if (reviewBtn && !reviewBtn.dataset.bound) {
      reviewBtn.dataset.bound = '1';
      reviewBtn.addEventListener('click', function () {
        openRuntimeUpgradeModal();
      });
    }

    var clearBtn = document.getElementById('updatesClearDeferBtn');
    if (clearBtn && !clearBtn.dataset.bound) {
      clearBtn.dataset.bound = '1';
      clearBtn.addEventListener('click', function () {
        clearRuntimeDeferPrefs(session);
      });
    }
  }

  async function deferRuntimeUpgrade(mode, silent) {
    var message = document.getElementById('dashboardMessage');
    if (!silent) hideMessage(message);
    try {
      var session = await fetchSession();
      var response = await fetch(API + 'runtime-upgrade.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'fetch'
        },
        body: JSON.stringify({
          action: 'defer',
          defer_mode: mode,
          csrf_token: session.csrf_token || ''
        })
      });
      var data = await readAdminJson(response);
      if (!response.ok) {
        throw new Error(data.error || 'Could not save preference.');
      }
      if (!silent) {
        showMessage(message, data.message || 'Preference saved.', false);
      }
      closeRuntimeUpgradeModal();
      var plan = data.plan || await fetchRuntimeUpgradePlan();
      runtimeUpgradePlanCache = plan;
      updateRuntimeUpgradeBanner(plan);
      renderUpdatesAdminPanel(plan);
      updateUpdatesNavBadge(plan);
    } catch (error) {
      if (!silent) {
        showMessage(message, error.message, true);
      } else {
        closeRuntimeUpgradeModal();
      }
    }
  }

  async function cancelRuntimeUpgrade() {
    closeRuntimeUpgradeModal();

    var cached = runtimeUpgradePlanCache || {};
    if ((cached.updates_available || 0) > 0 && !cached.job_busy) {
      await deferRuntimeUpgrade('tab_only', true);
    }
  }

  async function clearRuntimeDeferPrefs(session) {
    var message = document.getElementById('dashboardMessage');
    hideMessage(message);
    try {
      var latest = session || await fetchSession();
      var response = await fetch(API + 'runtime-upgrade.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'fetch'
        },
        body: JSON.stringify({
          action: 'clear_defer',
          csrf_token: latest.csrf_token || ''
        })
      });
      var data = await readAdminJson(response);
      if (!response.ok) {
        throw new Error(data.error || 'Could not restore reminders.');
      }
      showMessage(message, data.message || 'Reminders enabled.', false);
      var plan = data.plan || await fetchRuntimeUpgradePlan();
      runtimeUpgradePlanCache = plan;
      updateRuntimeUpgradeBanner(plan);
      renderUpdatesAdminPanel(plan);
      updateUpdatesNavBadge(plan);
    } catch (error) {
      showMessage(message, error.message, true);
    }
  }

  function runtimeDefaultImpactPlan() {
    return {
      updates_available: 0,
      summary: 'Could not load update status. Read the guidance below.',
      impact_summary: {
        approve: {
          title: 'If you approve & run updates',
          website_summary: 'Normally installs newer PHP/Node/Git on this PC. If PHP changes, visitors may lose access for 1–3 minutes during Apache restart.',
          points: [
            'Website page files are not automatically rewritten.',
            'After restart, homepage, shop, and contact should behave the same.',
          ],
        },
        cancel: {
          title: 'If you cancel (keep current versions)',
          website_summary: 'Visitors keep browsing with no interruption. Nothing on the live site changes.',
          points: [
            'No downtime and no service restart.',
            'You can review again from the System Updates tab.',
          ],
        },
      },
    };
  }

  function fillImpactList(listEl, titleEl, websiteEl, section) {
    if (!listEl) return;
    if (titleEl && section && section.title) {
      titleEl.textContent = section.title;
    }
    if (websiteEl) {
      websiteEl.textContent = section && section.website_summary ? section.website_summary : '';
    }
    listEl.textContent = '';
    var points = section && section.points || [];
    if (!points.length) {
      var empty = document.createElement('li');
      empty.className = 'runtime-impact-placeholder';
      empty.textContent = 'No details available.';
      listEl.appendChild(empty);
      return;
    }
    points.forEach(function (point) {
      var li = document.createElement('li');
      li.textContent = point;
      listEl.appendChild(li);
    });
  }

  function renderRuntimeImpactSummaries(plan) {
    var impact = plan.impact_summary || {};
    fillImpactList(
      document.getElementById('runtimeImpactApproveList'),
      document.getElementById('runtimeImpactApproveTitle'),
      document.getElementById('runtimeImpactApproveWebsite'),
      impact.approve
    );
    fillImpactList(
      document.getElementById('runtimeImpactCancelList'),
      document.getElementById('runtimeImpactCancelTitle'),
      document.getElementById('runtimeImpactCancelWebsite'),
      impact.cancel
    );
    fillImpactList(
      document.getElementById('runtimePanelApproveList'),
      document.getElementById('runtimePanelApproveTitle'),
      document.getElementById('runtimePanelApproveWebsite'),
      impact.approve
    );
    fillImpactList(
      document.getElementById('runtimePanelCancelList'),
      document.getElementById('runtimePanelCancelTitle'),
      document.getElementById('runtimePanelCancelWebsite'),
      impact.cancel
    );

    var lead = document.getElementById('runtimeWebsiteImpactLead');
    if (lead) {
      var updates = plan.updates_available || 0;
      if (updates > 0) {
        lead.hidden = false;
        lead.textContent = 'For visitors: UPDATE may cause brief downtime if PHP changes. CANCEL keeps the site online with no changes.';
      } else {
        lead.hidden = false;
        lead.textContent = 'For visitors: everything is already up to date. UPDATE installs nothing. CANCEL leaves the site exactly as it is now.';
      }
    }
  }

  function renderRuntimeUpgradeModal(plan) {
    var summary = document.getElementById('runtimeUpgradeModalSummary');
    var impactWrap = document.getElementById('runtimeUpgradeImpactWrap');
    if (!summary || !impactWrap) return;

    summary.textContent = plan.summary || '';
    renderRuntimeImpactSummaries(plan);
    var table = document.createElement('table');
    table.className = 'admin-table';
    table.innerHTML = '<thead><tr><th>Package</th><th>Versions</th><th>Site impact</th><th>What changes</th></tr></thead>';
    var tbody = document.createElement('tbody');
    (plan.packages || []).forEach(function (pkg) {
      if (!pkg.update_available) return;
      var row = document.createElement('tr');
      var versionText = (pkg.installed || '—') + ' → ' + (pkg.latest_stable || '—');
      var changes = (pkg.will_change || []).join('; ');
      var cells = [pkg.label || pkg.name || '', versionText, pkg.site_impact || '', changes];
      cells.forEach(function (text) {
        var td = document.createElement('td');
        td.textContent = text;
        row.appendChild(td);
      });
      tbody.appendChild(row);
    });
    table.appendChild(tbody);
    impactWrap.textContent = '';
    impactWrap.appendChild(table);

    if (plan.execution && !plan.execution.allowed) {
      var note = document.createElement('p');
      note.className = 'admin-note';
      note.textContent = 'Automatic upgrade blocked: ' + (plan.execution.reasons || []).join(' ');
      impactWrap.appendChild(note);
    }

    var approveBtn = document.getElementById('runtimeUpgradeApproveBtn');
    if (approveBtn) {
      var blocked = !!(plan.execution && !plan.execution.allowed);
      var noUpdates = !(plan.updates_available > 0);
      approveBtn.disabled = blocked || noUpdates;
    }
  }

  function updateRuntimeUpgradeBanner(plan) {
    var banner = document.getElementById('runtimeUpgradeBanner');
    var title = document.getElementById('runtimeUpgradeBannerTitle');
    var text = document.getElementById('runtimeUpgradeBannerText');
    var panelBtn = document.getElementById('runtimePanelApproveBtn');
    if (!banner || !title || !text) return;

    var updates = plan.updates_available || 0;
    var jobBusy = plan.job_busy;
    var active = plan.active_job;

    if (jobBusy && active) {
      banner.hidden = false;
      title.textContent = 'Runtime upgrade in progress';
      text.textContent = 'Status: ' + (active.status || 'running') + ' — ' + (active.step || '') +
        '. Apache may restart briefly.';
      if (panelBtn) panelBtn.hidden = true;
      updateUpdatesNavBadge(plan);
      return;
    }

    if (updates > 0 && plan.banner_suppressed) {
      banner.hidden = true;
      if (panelBtn) panelBtn.hidden = false;
      updateUpdatesNavBadge(plan);
      return;
    }

    if (updates > 0) {
      banner.hidden = false;
      title.textContent = updates + ' runtime update(s) available';
      var approveHint = (plan.impact_summary && plan.impact_summary.approve && plan.impact_summary.approve.points[0])
        ? plan.impact_summary.approve.points[0]
        : '';
      var cancelHint = (plan.impact_summary && plan.impact_summary.cancel && plan.impact_summary.cancel.points[0])
        ? plan.impact_summary.cancel.points[0]
        : '';
      text.textContent = 'Approve: ' + approveHint + ' | Cancel: ' + cancelHint;
      if (panelBtn) panelBtn.hidden = false;
      updateUpdatesNavBadge(plan);
      return;
    }

    banner.hidden = true;
    if (panelBtn) panelBtn.hidden = true;
    updateUpdatesNavBadge(plan);
  }

  async function fetchRuntimeUpgradePlan() {
    var response = await fetch(API + 'runtime-upgrade.php?action=plan', { credentials: 'same-origin' });
    var data = await readAdminJson(response);
    if (!response.ok) {
      throw new Error(data.error || 'Could not load upgrade plan.');
    }
    return data.plan || {};
  }

  async function loadRuntimeUpgradeBanner() {
    try {
      var plan = await fetchRuntimeUpgradePlan();
      runtimeUpgradePlanCache = plan;
      updateRuntimeUpgradeBanner(plan);
      renderRuntimeUpgradeModal(plan);
      renderRuntimeImpactSummaries(plan);
      renderUpdatesAdminPanel(plan);

      if (plan.job_busy) {
        startRuntimeUpgradePoll();
      }
    } catch (error) {
      var bannerText = document.getElementById('runtimeUpgradeBannerText');
      if (bannerText) bannerText.textContent = error.message;
    }
  }

  async function pollRuntimeUpgradeJob() {
    try {
      var response = await fetch(API + 'runtime-upgrade.php?action=job', { credentials: 'same-origin' });
      var data = await readAdminJson(response);
      if (!response.ok) return;

      var job = data.job;
      var logEl = document.getElementById('runtimeUpgradeJobLog');
      if (logEl && job && job.log_tail) {
        logEl.hidden = false;
        logEl.textContent = job.log_tail;
      }

      if (!job || !job.status) return;

      var plan = await fetchRuntimeUpgradePlan();
      plan.job_busy = job.status === 'queued' || job.status === 'running';
      plan.active_job = job;
      runtimeUpgradePlanCache = plan;
      updateRuntimeUpgradeBanner(plan);

      if (job.status === 'completed') {
        stopRuntimeUpgradePoll();
        showMessage(
          document.getElementById('dashboardMessage'),
          'Runtime upgrade completed. Compatibility: ' + (job.compatibility_ok ? 'OK' : 'check failed') + '.',
          !job.compatibility_ok
        );
        loadRuntimeVersions();
        loadUpdatesAdminPanel();
      } else if (job.status === 'failed') {
        stopRuntimeUpgradePoll();
        showMessage(document.getElementById('dashboardMessage'), job.error || 'Runtime upgrade failed.', true);
        loadUpdatesAdminPanel();
      }
    } catch (error) {
      stopRuntimeUpgradePoll();
    }
  }

  async function approveRuntimeUpgrade() {
    var confirmInput = document.getElementById('runtimeUpgradeConfirm');
    var message = document.getElementById('dashboardMessage');
    hideMessage(message);

    if (!confirmInput || confirmInput.value.trim() !== 'APPROVE UPGRADE') {
      showMessage(message, 'Type APPROVE UPGRADE to confirm.', true);
      return;
    }

    if (!window.confirm('Run approved runtime updates now? Apache may restart and the site may be unavailable for 1–3 minutes.')) {
      return;
    }

    try {
      var session = await fetchSession();
      var response = await fetch(API + 'runtime-upgrade.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'fetch'
        },
        body: JSON.stringify({
          action: 'start',
          confirm_text: 'APPROVE UPGRADE',
          csrf_token: session.csrf_token || ''
        })
      });
      var data = await readAdminJson(response);
      if (!response.ok) {
        throw new Error(data.error || 'Could not start upgrade.');
      }

      showMessage(message, data.message || 'Upgrade started.', false);
      closeRuntimeUpgradeModal();
      var logEl = document.getElementById('runtimeUpgradeJobLog');
      if (logEl) {
        logEl.hidden = false;
        logEl.textContent = 'Upgrade queued...';
      }
      await loadRuntimeUpgradeBanner();
      startRuntimeUpgradePoll();
    } catch (error) {
      showMessage(message, error.message, true);
    }
  }

  function renderRuntimeReport(runtime) {
    var meta = document.getElementById('runtimeUpdatesMeta');
    var tableWrap = document.getElementById('runtimeComponentsWrap');
    var compatWrap = document.getElementById('runtimeCompatibilityWrap');
    var stepsEl = document.getElementById('runtimeUpgradeSteps');
    if (!meta || !tableWrap) return;

    var components = runtime.components || [];
    var compat = runtime.compatibility || {};
    var policy = runtime.policy || {};
    var updates = runtime.updates_available || 0;

    meta.textContent = updates > 0
      ? updates + ' runtime update(s) available. Site code is not auto-rewritten — run compatibility check after any PHP upgrade.'
      : 'Runtime versions look current. Compatibility checks run against this site’s code.';

    var table = document.createElement('table');
    table.className = 'admin-table';
    table.innerHTML = '<thead><tr><th>Tool</th><th>Installed</th><th>Latest stable</th><th>Status</th><th>Notes</th></tr></thead>';
    var tbody = document.createElement('tbody');
    components.forEach(function (item) {
      var row = document.createElement('tr');
      var status = item.status || 'unknown';
      var statusLabel = status === 'current' ? 'Current' : (status === 'update_available' ? 'Update available' : 'Unknown');
      var cells = [
        item.name || '',
        item.installed || '—',
        item.latest_stable || '—',
        statusLabel,
        item.recommendation || ''
      ];
      cells.forEach(function (text) {
        var td = document.createElement('td');
        td.textContent = text;
        row.appendChild(td);
      });
      tbody.appendChild(row);
    });
    table.appendChild(tbody);
    tableWrap.textContent = '';
    tableWrap.appendChild(table);

    if (compatWrap) {
      var lines = [];
      (compat.checks || []).forEach(function (check) {
        lines.push((check.ok ? '✓' : '✗') + ' ' + check.label + (check.detail ? ' (' + check.detail + ')' : ''));
      });
      compatWrap.textContent = lines.length ? 'Site compatibility: ' + lines.join(' | ') : '';
    }

    if (stepsEl && policy.how_to_upgrade) {
      stepsEl.textContent = '';
      policy.how_to_upgrade.forEach(function (step) {
        var li = document.createElement('li');
        li.textContent = step;
        stepsEl.appendChild(li);
      });
    }
  }

  async function loadRuntimeVersions() {
    var panel = document.getElementById('runtimeUpdatesPanel');
    if (!panel) return;

    try {
      var response = await fetch(API + 'runtime-versions.php', { credentials: 'same-origin' });
      var data = await readAdminJson(response);
      if (!response.ok) {
        throw new Error(data.error || 'Could not load runtime report.');
      }
      renderRuntimeReport(data.runtime || {});
    } catch (error) {
      var meta = document.getElementById('runtimeUpdatesMeta');
      if (meta) meta.textContent = error.message;
    }

    var refreshBtn = document.getElementById('refreshRuntimeBtn');
    if (refreshBtn && !refreshBtn.dataset.bound) {
      refreshBtn.dataset.bound = '1';
      refreshBtn.addEventListener('click', function () {
        loadRuntimeVersions();
      });
    }

    var verifyBtn = document.getElementById('verifyRuntimeBtn');
    if (verifyBtn && !verifyBtn.dataset.bound) {
      verifyBtn.dataset.bound = '1';
      verifyBtn.addEventListener('click', async function () {
        try {
          var response = await fetch(API + 'runtime-versions.php?action=verify', { credentials: 'same-origin' });
          var data = await readAdminJson(response);
          if (!response.ok) {
            throw new Error(data.error || 'Compatibility check failed.');
          }
          var compat = data.compatibility || {};
          var msg = compat.ok
            ? 'All compatibility checks passed for the current PHP runtime.'
            : 'Some compatibility checks failed. Review the report and missing extensions.';
          showMessage(document.getElementById('dashboardMessage'), msg, !compat.ok);
          if (data.compatibility) {
            renderRuntimeReport({ components: [], compatibility: compat, policy: {}, updates_available: 0 });
          }
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        }
      });
    }
  }

  async function loadBackupAdmin(session) {
    var createForm = document.getElementById('createBackupForm');
    var createBtn = document.getElementById('createBackupBtn');
    if (!createForm || !createBtn) {
      return;
    }

    try {
      loadRuntimeVersions();
      var response = await fetch(API + 'backup-admin.php', {
        credentials: 'same-origin'
      });
      var data = await readAdminJson(response);
      if (!response.ok) {
        throw new Error(data.error || 'Could not load backup data.');
      }
      renderBackupAdmin(data.backup || {});
    } catch (error) {
      showMessage(document.getElementById('dashboardMessage'), error.message, true);
    }

    if (!createForm.dataset.bound) {
      createForm.dataset.bound = '1';
      createForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        hideMessage(document.getElementById('dashboardMessage'));

        var passwordInput = document.getElementById('createBackupPassword');
        var confirmInput = document.getElementById('createBackupPasswordConfirm');
        var password = passwordInput ? passwordInput.value : '';
        var confirmPassword = confirmInput ? confirmInput.value : '';
        var minLength = 8;

        if (password.length < minLength) {
          showMessage(document.getElementById('dashboardMessage'), 'Backup password must be at least ' + minLength + ' characters.', true);
          return;
        }

        if (password !== confirmPassword) {
          showMessage(document.getElementById('dashboardMessage'), 'Backup passwords do not match.', true);
          return;
        }

        createBtn.disabled = true;
        try {
          var latestSession = await fetchSession();
          var body = new FormData(createForm);
          body.append('action', 'create_backup');
          body.append('csrf_token', latestSession.csrf_token || '');

          var response = await fetch(API + 'backup-admin.php', {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch' }
          });
          var result = await readAdminJson(response);
          if (!response.ok) {
            throw new Error(result.error || 'Backup could not be created.');
          }

          renderBackupAdmin(result.backup || {});
          await triggerBackupDownload((result.created && result.created.filename) || '');
          createForm.reset();
          showMessage(document.getElementById('dashboardMessage'), result.message || 'Backup created.', false);
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        } finally {
          createBtn.disabled = !backupCapability.zip || !backupCapability.encryption;
        }
      });
    }

    var restoreForm = document.getElementById('restoreBackupForm');
    if (restoreForm && !restoreForm.dataset.bound) {
      restoreForm.dataset.bound = '1';
      restoreForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        hideMessage(document.getElementById('dashboardMessage'));

        var confirmInput = document.getElementById('restoreConfirm');
        if (!confirmInput || confirmInput.value.trim() !== 'RESTORE') {
          showMessage(document.getElementById('dashboardMessage'), 'Type RESTORE to confirm restore.', true);
          return;
        }

        var fileInput = document.getElementById('restoreBackupFile');
        if (!fileInput || !fileInput.files || !fileInput.files.length) {
          showMessage(document.getElementById('dashboardMessage'), 'Choose a backup ZIP file.', true);
          return;
        }

        try {
          var latestSession = await fetchSession();
          var body = new FormData(restoreForm);
          body.append('action', 'restore_backup');
          body.append('csrf_token', latestSession.csrf_token || '');

          var response = await fetch(API + 'backup-admin.php', {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch' }
          });
          var result = await readAdminJson(response);
          if (!response.ok) {
            throw new Error(result.error || 'Restore failed.');
          }

          restoreForm.reset();
          renderBackupAdmin(result.backup || {});
          await refreshDashboardAfterDataChange(session);
          showMessage(document.getElementById('dashboardMessage'), result.message || 'Backup restored.', false);
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        }
      });
    }

    var resetForm = document.getElementById('resetAllDataForm');
    if (resetForm && !resetForm.dataset.bound) {
      resetForm.dataset.bound = '1';
      resetForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        hideMessage(document.getElementById('dashboardMessage'));

        var confirmInput = document.getElementById('resetConfirm');
        if (!confirmInput || confirmInput.value.trim() !== 'RESET ALL') {
          showMessage(document.getElementById('dashboardMessage'), 'Type RESET ALL to confirm reset.', true);
          return;
        }

        if (!window.confirm('Reset all website data? An automatic backup will be created first.')) {
          return;
        }

        try {
          var latestSession = await fetchSession();
          var body = new FormData(resetForm);
          body.append('action', 'reset_all');
          body.append('csrf_token', latestSession.csrf_token || '');
          if (!document.getElementById('resetKeepAdminAuth').checked) {
            body.delete('keep_admin_auth');
          }

          var response = await fetch(API + 'backup-admin.php', {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch' }
          });
          var result = await readAdminJson(response);
          if (!response.ok) {
            throw new Error(result.error || 'Reset failed.');
          }

          resetForm.reset();
          document.getElementById('resetKeepAdminAuth').checked = true;
          renderBackupAdmin(result.backup || {});
          await refreshDashboardAfterDataChange(session);
          showMessage(document.getElementById('dashboardMessage'), result.message || 'Website data reset.', false);
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        }
      });
    }
  }

  var usersAdminState = {
    users: [],
    roles: [],
    canEdit: false,
    database: {}
  };

  function renderUsersDatabaseSummary(database) {
    var wrap = document.getElementById('usersDatabaseSummary');
    var note = document.getElementById('usersDatabaseNote');
    if (!wrap) return;

    wrap.textContent = '';
    if (!database || !database.ready) {
      if (note) {
        note.hidden = false;
        note.textContent = 'Database is not available. Check DB settings in config/local.php and PHP PDO support.';
      }
      return;
    }

    if (note) note.hidden = true;
    [
      ['Driver', database.driver || 'sqlite'],
      ['Users', database.users || 0],
      ['Products', database.products || 0],
      ['Orders', database.orders || 0],
      ['Messages', database.contacts || 0],
      ['Certifications', database.certifications || 0]
    ].forEach(function (pair) {
      var block = document.createElement('div');
      block.className = 'report-stat';
      block.innerHTML = '<span class="report-stat-label">' + pair[0] + '</span><strong class="report-stat-value">' + pair[1] + '</strong>';
      wrap.appendChild(block);
    });
  }

  function fillUserRoleSelect(select, roles, selected) {
    if (!select) return;
    select.textContent = '';
    (roles || []).forEach(function (role) {
      var option = document.createElement('option');
      option.value = role.id;
      option.textContent = role.label + (role.description ? ' — ' + role.description : '');
      if (selected && selected === role.id) {
        option.selected = true;
      }
      select.appendChild(option);
    });
  }

  function renderUsersTable(session) {
    var wrap = document.getElementById('usersTableWrap');
    if (!wrap) return;

    wrap.textContent = '';
    var table = document.createElement('table');
    table.className = 'admin-table';
    table.innerHTML = '<thead><tr><th>Username</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th>Actions</th></tr></thead>';
    var tbody = document.createElement('tbody');

    if (!usersAdminState.users.length) {
      var emptyRow = document.createElement('tr');
      var emptyCell = document.createElement('td');
      emptyCell.colSpan = 7;
      emptyCell.textContent = 'No user accounts yet.';
      emptyRow.appendChild(emptyCell);
      tbody.appendChild(emptyRow);
    } else {
      usersAdminState.users.forEach(function (user) {
        var tr = document.createElement('tr');
        tr.innerHTML =
          '<td>' + (user.username || '') + '</td>' +
          '<td>' + (user.display_name || '—') + '</td>' +
          '<td>' + (user.email || '—') + '</td>' +
          '<td>' + (user.role_label || user.role || '—') + '</td>' +
          '<td>' + (user.active ? 'Active' : 'Inactive') + '</td>' +
          '<td>' + (user.last_login_at ? formatVisitorTime(user.last_login_at) : 'Never') + '</td>';

        var actionsCell = document.createElement('td');
        if (usersAdminState.canEdit) {
          var editBtn = document.createElement('button');
          editBtn.type = 'button';
          editBtn.className = 'secondary compact-btn';
          editBtn.textContent = 'Edit';
          editBtn.addEventListener('click', function () {
            openEditUserDialog(user, session);
          });
          actionsCell.appendChild(editBtn);

          var currentId = session.session && session.session.user_id;
          if (user.id !== currentId) {
            var deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'danger compact-btn';
            deleteBtn.textContent = 'Delete';
            deleteBtn.addEventListener('click', function () {
              deleteUserAccount(user, session);
            });
            actionsCell.appendChild(deleteBtn);
          }
        } else {
          actionsCell.textContent = '—';
        }
        tr.appendChild(actionsCell);
        tbody.appendChild(tr);
      });
    }

    table.appendChild(tbody);
    wrap.appendChild(table);
  }

  function openEditUserDialog(user, session) {
    var displayName = window.prompt('Display name', user.display_name || user.username || '');
    if (displayName === null) return;
    var email = window.prompt('Email (optional)', user.email || '');
    if (email === null) return;

    var roleOptions = usersAdminState.roles.map(function (role, index) {
      return (index + 1) + '. ' + role.label + ' (' + role.id + ')';
    }).join('\n');
    var roleChoice = window.prompt('Role:\n' + roleOptions, user.role || 'viewer');
    if (roleChoice === null) return;

    var matchedRole = usersAdminState.roles.find(function (role) {
      return role.id === roleChoice || role.label === roleChoice;
    });
    if (!matchedRole && /^\d+$/.test(roleChoice)) {
      var roleIndex = parseInt(roleChoice, 10) - 1;
      matchedRole = usersAdminState.roles[roleIndex];
    }
    if (!matchedRole) {
      showMessage(document.getElementById('dashboardMessage'), 'Unknown role selected.', true);
      return;
    }

    var activeAnswer = window.confirm('Active account? OK = active, Cancel = inactive');
    var password = window.prompt('New password (leave blank to keep current)', '');
    if (password === null) return;

    updateUserAccount(session, {
      action: 'update_user',
      user_id: String(user.id),
      display_name: displayName,
      email: email,
      role: matchedRole.id,
      active: activeAnswer ? '1' : '',
      password: password
    });
  }

  async function postUsersAction(session, payload) {
    var body = new FormData();
    Object.keys(payload).forEach(function (key) {
      if (payload[key] !== undefined && payload[key] !== null) {
        body.append(key, payload[key]);
      }
    });
    body.append('csrf_token', session.csrf_token || '');

    var response = await fetch(API + 'users-admin.php', {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'fetch' }
    });
    var data = await readAdminJson(response);
    if (!response.ok) {
      throw new Error(data.error || 'User request failed.');
    }
    return data;
  }

  async function updateUserAccount(session, payload) {
    try {
      var result = await postUsersAction(session, payload);
      await loadUsersAdmin(await fetchSession());
      showMessage(document.getElementById('dashboardMessage'), result.message || 'User updated.', false);
    } catch (error) {
      showMessage(document.getElementById('dashboardMessage'), error.message, true);
    }
  }

  async function deleteUserAccount(user, session) {
    if (!window.confirm('Delete user "' + user.username + '"? This cannot be undone.')) {
      return;
    }
    try {
      var latestSession = await fetchSession();
      var result = await postUsersAction(latestSession, {
        action: 'delete_user',
        user_id: String(user.id)
      });
      await loadUsersAdmin(latestSession);
      showMessage(document.getElementById('dashboardMessage'), result.message || 'User deleted.', false);
    } catch (error) {
      showMessage(document.getElementById('dashboardMessage'), error.message, true);
    }
  }

  async function loadUsersAdmin(session) {
    if (!document.getElementById('page-users')) return;
    if (!hasAdminPermission(session, 'users.view')) return;

    usersAdminState.canEdit = hasAdminPermission(session, 'users.edit');
    var createPanel = document.getElementById('usersCreatePanel');
    var readOnlyNote = document.getElementById('usersReadOnlyNote');
    if (createPanel) createPanel.hidden = !usersAdminState.canEdit;
    if (readOnlyNote) readOnlyNote.hidden = usersAdminState.canEdit;

    try {
      var response = await fetch(API + 'users-admin.php', {
        credentials: 'same-origin'
      });
      var data = await readAdminJson(response);
      if (!response.ok) {
        throw new Error(data.error || 'Could not load users.');
      }

      usersAdminState.users = data.users || [];
      usersAdminState.roles = data.roles || [];
      usersAdminState.database = data.database || {};
      renderUsersDatabaseSummary(usersAdminState.database);
      fillUserRoleSelect(document.getElementById('newUserRole'), usersAdminState.roles);
      renderUsersTable(session);
    } catch (error) {
      var usersPage = document.getElementById('page-users');
      if (usersPage && usersPage.classList.contains('is-active')) {
        showMessage(document.getElementById('dashboardMessage'), error.message, true);
      }
    }

    var createForm = document.getElementById('createUserForm');
    if (createForm && usersAdminState.canEdit && !createForm.dataset.bound) {
      createForm.dataset.bound = '1';
      createForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        hideMessage(document.getElementById('dashboardMessage'));
        try {
          var latestSession = await fetchSession();
          var result = await postUsersAction(latestSession, {
            action: 'create_user',
            username: document.getElementById('newUserUsername').value,
            display_name: document.getElementById('newUserDisplayName').value,
            email: document.getElementById('newUserEmail').value,
            role: document.getElementById('newUserRole').value,
            password: document.getElementById('newUserPassword').value,
            active: document.getElementById('newUserActive').checked ? '1' : ''
          });
          createForm.reset();
          document.getElementById('newUserActive').checked = true;
          await loadUsersAdmin(latestSession);
          showMessage(document.getElementById('dashboardMessage'), result.message || 'User created.', false);
        } catch (error) {
          showMessage(document.getElementById('dashboardMessage'), error.message, true);
        }
      });
    }
  }

  if (document.getElementById('loginForm')) {
    initLoginPage();
  }

  if (document.querySelector('.admin-layout')) {
    initDashboardPage();
  }
})();
