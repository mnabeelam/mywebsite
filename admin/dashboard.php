<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/lib/bootstrap.php';
require_once __DIR__ . '/../php/lib/auth.php';
require_once __DIR__ . '/../php/lib/site-services.php';

requireAdminIpAllowed();

if (!isAdminAuthenticated()) {
    redirectTo('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Admin Dashboard | Portfolio</title>
<link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
</head>
<body class="admin-app" data-admin-theme="account">
<div class="admin-layout">
  <header class="admin-topbar">
    <div class="admin-topbar-brand">
      <span class="admin-topbar-kicker">Portfolio Admin</span>
      <strong class="admin-topbar-title">Mirza Nabeel Ahmed</strong>
    </div>
    <p id="siteStats" class="admin-topbar-stats">Loading site stats...</p>
    <div class="admin-topbar-actions">
      <p id="welcomeText" class="admin-topbar-user">Signed in.</p>
      <button id="logoutBtn" type="button" class="secondary topbar-btn">Logout</button>
    </div>
  </header>

  <div class="admin-body">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-head">
        <span class="admin-sidebar-eyebrow">Control panel</span>
        <strong class="admin-sidebar-title">Navigation</strong>
      </div>
      <nav class="admin-nav">
        <button type="button" class="admin-nav-item admin-tab is-active" data-tab="account" aria-selected="true">
          <span class="admin-nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
          </span>
          <span class="admin-nav-copy">
            <span class="admin-nav-label">Account &amp; Contact</span>
            <span class="admin-nav-desc">Profile, password, inbox</span>
          </span>
          <span class="admin-nav-arrow" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
          </span>
        </button>
        <button type="button" class="admin-nav-item admin-tab" data-tab="network" aria-selected="false">
          <span class="admin-nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </span>
          <span class="admin-nav-copy">
            <span class="admin-nav-label">Network</span>
            <span class="admin-nav-desc">Access &amp; security</span>
          </span>
          <span class="admin-nav-arrow" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
          </span>
        </button>
        <button type="button" class="admin-nav-item admin-tab" data-tab="content" aria-selected="false">
          <span class="admin-nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M8 7h8M8 11h6"/></svg>
          </span>
          <span class="admin-nav-copy">
            <span class="admin-nav-label">Certifications &amp; Knowledge</span>
            <span class="admin-nav-desc">CV, certs, AI data</span>
          </span>
          <span class="admin-nav-arrow" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
          </span>
        </button>
        <button type="button" class="admin-nav-item admin-tab" data-tab="shop" aria-selected="false">
          <span class="admin-nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          </span>
          <span class="admin-nav-copy">
            <span class="admin-nav-label">Online Store</span>
            <span class="admin-nav-desc">Products, stock, orders</span>
          </span>
          <span class="admin-nav-arrow" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
          </span>
        </button>
        <button type="button" class="admin-nav-item admin-tab" data-tab="backup" aria-selected="false">
          <span class="admin-nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/></svg>
          </span>
          <span class="admin-nav-copy">
            <span class="admin-nav-label">Backup &amp; Data</span>
            <span class="admin-nav-desc">Restore, reset, runtime</span>
          </span>
          <span class="admin-nav-arrow" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
          </span>
        </button>
        <button type="button" class="admin-nav-item admin-tab" data-tab="updates" aria-selected="false">
          <span class="admin-nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          </span>
          <span class="admin-nav-copy">
            <span class="admin-nav-label">System Updates <span id="updatesNavBadge" class="admin-nav-badge" hidden></span></span>
            <span class="admin-nav-desc">Pending &amp; history</span>
          </span>
          <span class="admin-nav-arrow" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
          </span>
        </button>
        <button type="button" class="admin-nav-item admin-tab" data-tab="users" aria-selected="false" hidden>
          <span class="admin-nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </span>
          <span class="admin-nav-copy">
            <span class="admin-nav-label">Users &amp; Roles</span>
            <span class="admin-nav-desc">Accounts &amp; permissions</span>
          </span>
          <span class="admin-nav-arrow" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
          </span>
        </button>
      </nav>
      <div class="admin-sidebar-foot">
        <a class="admin-sidebar-link" href="index.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          Back to login
        </a>
        <a class="admin-sidebar-link" href="../index.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          Homepage
        </a>
      </div>
    </aside>

    <main class="admin-main">
      <div id="runtimeUpgradeBanner" class="runtime-upgrade-banner" hidden role="status" aria-live="polite">
        <div class="runtime-upgrade-banner-body">
          <strong id="runtimeUpgradeBannerTitle">Runtime updates available</strong>
          <p id="runtimeUpgradeBannerText" class="admin-note"></p>
        </div>
        <div class="admin-actions">
          <button type="button" id="runtimeUpgradeReviewBtn" class="secondary">Review &amp; approve</button>
        </div>
      </div>
      <p id="dashboardMessage" class="message page-message" hidden></p>

      <div id="runtimeUpgradeModal" class="runtime-upgrade-modal" hidden role="dialog" aria-modal="true" aria-labelledby="runtimeUpgradeModalTitle">
        <div class="runtime-upgrade-modal-card">
          <h2 id="runtimeUpgradeModalTitle">Approve runtime updates</h2>
          <p id="runtimeUpgradeModalSummary" class="admin-note"></p>
          <div id="runtimeWebsiteImpactLead" class="runtime-website-lead">
            For visitors: everything is up to date today. <strong>Update</strong> installs nothing. <strong>Cancel</strong> leaves the site exactly as it is now.
          </div>
          <div class="runtime-impact-grid">
            <div class="runtime-impact-card runtime-impact-approve">
              <h3 id="runtimeImpactApproveTitle">If you approve &amp; run updates</h3>
              <p id="runtimeImpactApproveWebsite" class="runtime-impact-website-summary">Nothing happens when all tools are current — your website keeps working as it does now.</p>
              <ul id="runtimeImpactApproveList" class="runtime-impact-list">
                <li>No packages will be installed if nothing newer is available.</li>
                <li>Homepage, shop, contact, and admin stay online.</li>
                <li>Your website files are not edited automatically.</li>
              </ul>
            </div>
            <div class="runtime-impact-card runtime-impact-cancel">
              <h3 id="runtimeImpactCancelTitle">If you cancel (keep current versions)</h3>
              <p id="runtimeImpactCancelWebsite" class="runtime-impact-website-summary">Visitors see no change — the site stays online on current versions.</p>
              <ul id="runtimeImpactCancelList" class="runtime-impact-list">
                <li>No downtime and no Apache restart.</li>
                <li>All pages, products, and settings remain unchanged.</li>
                <li>You can review updates again from the <strong>System Updates</strong> tab.</li>
              </ul>
            </div>
          </div>
          <div id="runtimeUpgradeImpactWrap" class="admin-table-wrap"></div>
          <p class="admin-note"><strong>What will NOT change:</strong> website PHP/HTML/CSS/JS source files are not modified automatically.</p>
          <label for="runtimeUpgradeConfirm">Type <code>APPROVE UPGRADE</code> to confirm</label>
          <input id="runtimeUpgradeConfirm" type="text" autocomplete="off" placeholder="APPROVE UPGRADE">
          <div class="admin-actions">
            <button type="button" id="runtimeUpgradeApproveBtn">Run approved updates</button>
            <button type="button" id="runtimeUpgradeCancelBtn" class="secondary">Cancel</button>
          </div>
          <pre id="runtimeUpgradeJobLog" class="runtime-upgrade-log" hidden></pre>
        </div>
      </div>

    <div id="page-account" class="admin-page is-active">
      <header class="page-hero">
        <p class="page-hero-eyebrow">Section 01</p>
        <h2 class="page-hero-title">Account &amp; Contact</h2>
        <p class="page-hero-desc">Profile, password, public contact details, and recent inbox messages.</p>
      </header>
      <div class="page-content">
      <section class="admin-panel account-panel">
        <h2>Account</h2>
        <dl class="account-summary">
          <div class="account-summary-row">
            <dt>Username</dt>
            <dd id="accountUsernameDisplay">—</dd>
          </div>
          <div class="account-summary-row">
            <dt>Notification email</dt>
            <dd id="accountEmailDisplay">—</dd>
          </div>
        </dl>
        <div class="admin-actions account-actions">
          <button type="button" id="showPasswordFormBtn" class="secondary">Change password</button>
        </div>
        <form id="passwordForm" class="admin-product-form account-password-form" hidden>
          <input id="settingsUsername" name="username" type="hidden" value="">
          <p class="admin-note">Enter your current password, then choose a new one (minimum 8 characters).</p>
          <label for="currentPassword">Current password</label>
          <input id="currentPassword" name="current_password" type="password" autocomplete="current-password" minlength="1">
          <label for="newPassword">New password</label>
          <input id="newPassword" name="new_password" type="password" autocomplete="new-password" minlength="8">
          <label for="confirmPassword">Confirm new password</label>
          <input id="confirmPassword" name="confirm_password" type="password" autocomplete="new-password" minlength="8">
          <div class="admin-actions">
            <button type="submit">Update Password</button>
            <button type="button" id="cancelPasswordFormBtn" class="secondary">Cancel</button>
          </div>
        </form>
      </section>

      <section class="admin-panel" id="twoFactorPanel">
        <h2>Two-Factor Authentication (2FA)</h2>
        <p id="twoFactorStatus" class="admin-note">Loading 2FA status...</p>
        <div class="admin-actions">
          <button type="button" id="begin2faBtn" class="secondary">Enable 2FA</button>
          <button type="button" id="disable2faBtn" class="secondary" hidden>Disable 2FA</button>
        </div>
        <div id="twoFactorSetup" class="account-password-form" hidden>
          <p class="admin-note">Scan this QR code in Google Authenticator, Microsoft Authenticator, or similar app.</p>
          <img id="twoFactorQr" alt="2FA QR code" width="220" height="220" hidden>
          <p class="admin-note">Manual key: <code id="twoFactorSecret">—</code></p>
          <form id="confirm2faForm" class="admin-product-form">
            <label for="confirm2faCode">Enter the 6-digit code from your app</label>
            <input id="confirm2faCode" name="otp_code" type="text" inputmode="numeric" pattern="[0-9]{6,8}" maxlength="8" required>
            <button type="submit">Confirm and enable 2FA</button>
          </form>
        </div>
        <form id="disable2faForm" class="admin-product-form" hidden>
          <label for="disable2faCode">Authentication code (required to disable)</label>
          <input id="disable2faCode" name="otp_code" type="text" inputmode="numeric" pattern="[0-9]{6,8}" maxlength="8" required>
          <button type="submit" class="secondary">Disable 2FA</button>
        </form>
      </section>

      <section class="admin-panel">
        <h2>Contact Settings</h2>
        <p class="admin-note">Manage contact details used for form notifications and the public site.</p>
        <form id="contactSettingsForm" class="admin-product-form">
          <label for="settingsContactEmail">Notification email (receives form messages)</label>
          <input id="settingsContactEmail" name="contact_email" type="email" maxlength="180">
          <label for="settingsDisplayEmail">Public display email</label>
          <input id="settingsDisplayEmail" name="display_email" type="email" maxlength="180">
          <label for="settingsPhone">Phone / WhatsApp</label>
          <input id="settingsPhone" name="phone" type="text" maxlength="40">
          <label for="settingsLinkedin">LinkedIn URL</label>
          <input id="settingsLinkedin" name="linkedin" type="url" maxlength="300" placeholder="https://www.linkedin.com/in/...">
          <div class="admin-actions">
            <button type="submit">Save Contact Settings</button>
          </div>
        </form>
        <p id="settingsStatus" class="admin-note"></p>
      </section>

      <section class="admin-panel">
        <h2>Recent Messages</h2>
        <div id="recentMessages" class="admin-note">Loading messages...</div>
      </section>

      <section class="admin-panel">
        <h2>Recent Shop Orders</h2>
        <p class="admin-note">New online store orders appear here and in <strong>Online Store</strong> (Section 04).</p>
        <div id="recentShopOrders" class="admin-table-wrap">Loading orders...</div>
      </section>
      </div>
    </div>

    <div id="page-network" class="admin-page">
      <header class="page-hero">
        <p class="page-hero-eyebrow">Section 02</p>
        <h2 class="page-hero-title">Network &amp; Visitors</h2>
        <p class="page-hero-desc">Traffic logs, IP blocks, and admin access rules.</p>
      </header>
      <div class="page-content">
      <section class="admin-panel">
        <h2>Visitor Records</h2>
        <p class="admin-note">Search visitors and block unwanted IPs from the public site.</p>
        <div class="visitor-toolbar">
          <div class="visitor-toolbar-field">
            <label for="visitorSearch">Search date &amp; time</label>
            <input id="visitorSearch" type="search" placeholder="e.g. 2026-06-25 or 15:30" autocomplete="off">
          </div>
          <p id="visitorResultMeta" class="visitor-result-meta">Loading visitors...</p>
        </div>
        <div id="visitorTableWrap" class="admin-table-wrap visitor-table-wrap"></div>
        <nav id="visitorPagination" class="visitor-pagination" aria-label="Visitor pages"></nav>
        <div id="blockedIpsWrap" class="admin-note"></div>
      </section>

      <section class="admin-panel">
        <h2>Admin IP Whitelist</h2>
        <p class="admin-note">When you add IPs here, only those IPs can open the admin dashboard. Leave empty to allow all IPs (local testing).</p>
        <label for="adminWhitelist">Approved admin IPs (one per line)</label>
        <textarea id="adminWhitelist" rows="4" placeholder="127.0.0.1&#10;::1"></textarea>
        <div class="admin-actions">
          <button id="saveWhitelistBtn" type="button">Save Whitelist</button>
          <button id="addCurrentIpBtn" type="button" class="secondary">Add My Current IP</button>
        </div>
        <p id="whitelistStatus" class="admin-note"></p>
      </section>
      </div>
    </div>

    <div id="page-content" class="admin-page">
      <header class="page-hero">
        <p class="page-hero-eyebrow">Section 03</p>
        <h2 class="page-hero-title">Certifications &amp; Knowledge</h2>
        <p class="page-hero-desc">CV uploads, certificate catalog, and assistant knowledge base.</p>
      </header>
      <div class="page-content">
      <section class="admin-panel">
        <h2>CV Knowledge Upload</h2>
        <p id="knowledgeStatus" class="admin-note">Knowledge status loading...</p>
        <form id="uploadForm">
          <p class="admin-note">Upload your CV (PDF or DOCX). The site builds a searchable knowledge base for the Quick Assistant.</p>
          <input id="cvFile" name="cv" type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
          <button type="submit">Upload and Build Knowledge</button>
        </form>
        <button id="rebuildBtn" type="button" class="secondary">Rebuild Knowledge Index</button>
      </section>

      <section class="admin-panel">
        <h2>Certifications</h2>
        <p class="admin-note">Upload a certificate file first to auto-fill fields. Files stay private — only name and details appear on the homepage.</p>
        <form id="certForm" class="admin-product-form" enctype="multipart/form-data">
          <input id="certId" name="id" type="hidden" value="">
          <label for="certFile">Certificate file (PDF, JPG, PNG, WEBP — max 5 MB)</label>
          <input id="certFile" name="certificate" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
          <div class="admin-actions">
            <button id="autoFillCertBtn" type="button" class="secondary">Auto-fill from file</button>
          </div>
          <p id="certFileHint" class="admin-note">Choose a file, then click Auto-fill or wait a moment for automatic reading.</p>
          <p id="certExtractStatus" class="admin-note" hidden></p>
          <label for="certTitle">Certification title</label>
          <input id="certTitle" name="title" type="text" maxlength="200" required>
          <label for="certIssuer">Issuing organization</label>
          <input id="certIssuer" name="issuer" type="text" maxlength="120">
          <div class="admin-form-grid">
            <div>
              <label for="certYear">Year (optional)</label>
              <input id="certYear" name="year" type="text" maxlength="12" placeholder="2024">
            </div>
            <div>
              <label for="certSortOrder">Display order</label>
              <input id="certSortOrder" name="sort_order" type="number" min="0" step="1" value="0">
            </div>
          </div>
          <label for="certDescription">Description / knowledge notes</label>
          <textarea id="certDescription" name="description" rows="3" maxlength="1000"></textarea>
          <label class="admin-checkbox">
            <input id="certActive" name="active" type="checkbox" checked>
            Show on homepage
          </label>
          <div class="admin-actions">
            <button id="saveCertBtn" type="submit">Save Certification</button>
            <button id="resetCertBtn" type="button" class="secondary">Clear Form</button>
          </div>
        </form>
        <p id="certStats" class="admin-note">Loading certifications...</p>
        <div id="certsTableWrap" class="admin-table-wrap"></div>
      </section>
      </div>
    </div>

    <div id="page-shop" class="admin-page">
      <header class="page-hero">
        <p class="page-hero-eyebrow">Section 04</p>
        <h2 class="page-hero-title">Online Store</h2>
        <p class="page-hero-desc">Products, stock movements, inventory reports, and customer orders.</p>
      </header>
      <div class="page-content">
      <section class="admin-panel">
        <h2>Shop Products</h2>
        <p class="admin-note">Upload one or more product pictures, then fill in brand, category, details, price, and stock. Stock reduces automatically when customers order. Adding the same brand and model again merges stock into one listing.</p>
        <form id="productForm" class="admin-product-form" enctype="multipart/form-data">
          <input id="productId" name="id" type="hidden" value="">
          <input id="productKeepImages" name="keep_images" type="hidden" value="[]">

          <label for="productImages">Product pictures (select multiple — JPG, PNG, WEBP, max 5 MB each, up to 10)</label>
          <input id="productImages" name="product_images[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
          <div id="productImagesPreview" class="product-images-preview"></div>

          <label for="productBrand">Brand / company</label>
          <select id="productBrand" name="brand" required></select>
          <div id="productBrandCustomWrap" hidden>
            <label for="productBrandCustom">Custom brand</label>
            <input id="productBrandCustom" name="brand_custom" type="text" maxlength="80">
          </div>

          <label for="productName">Product name</label>
          <input id="productName" name="name" type="text" maxlength="200" required placeholder="e.g. USB-C Hub 7-in-1">

          <label for="productCategory">Type / category</label>
          <select id="productCategory" name="category" required></select>
          <div id="productCategoryCustomWrap" hidden>
            <label for="productCategoryCustom">Custom category</label>
            <input id="productCategoryCustom" name="category_custom" type="text" maxlength="80">
          </div>

          <label for="productDescription">Detail / description</label>
          <textarea id="productDescription" name="description" rows="4" maxlength="2000" placeholder="Features, specifications, warranty, etc."></textarea>

          <div class="admin-form-grid">
            <div>
              <label for="productPrice">Sale price</label>
              <input id="productPrice" name="price" type="number" min="0" step="0.01" required>
            </div>
            <div>
              <label for="productCostPrice">Cost / purchase price</label>
              <input id="productCostPrice" name="cost_price" type="number" min="0" step="0.01" placeholder="For profit reports">
            </div>
            <div>
              <label for="productCurrency">Currency</label>
              <input id="productCurrency" name="currency" type="text" maxlength="8" value="PKR">
            </div>
            <div>
              <label for="productStock">Available stock</label>
              <input id="productStock" name="stock" type="number" min="0" step="1" value="1">
            </div>
          </div>

          <label class="admin-checkbox">
            <input id="productTrackStock" name="track_stock" type="checkbox" checked>
            Track stock automatically (reduces when orders are placed)
          </label>

          <label class="admin-checkbox">
            <input id="productActive" name="active" type="checkbox" checked>
            Show on website
          </label>

          <div class="admin-actions">
            <button id="saveProductBtn" type="submit">Save Product</button>
            <button id="resetProductBtn" type="button" class="secondary">Clear Form</button>
          </div>
        </form>
        <div id="productsTableWrap" class="admin-table-wrap"></div>
      </section>

      <section class="admin-panel">
        <h2>Stock In / Out</h2>
        <p class="admin-note">Record purchases (stock in) or manual stock out for damage, returns, or adjustments.</p>
        <div class="admin-form-grid report-forms-grid">
          <form id="purchaseForm" class="admin-product-form">
            <h3>Purchase / Stock In</h3>
            <label for="purchaseProductId">Product</label>
            <select id="purchaseProductId" name="product_id" required></select>
            <label for="purchaseQuantity">Quantity</label>
            <input id="purchaseQuantity" name="quantity" type="number" min="1" step="1" value="1" required>
            <label for="purchaseUnitCost">Unit cost</label>
            <input id="purchaseUnitCost" name="unit_cost" type="number" min="0" step="0.01" required>
            <label for="purchaseNote">Note (optional)</label>
            <input id="purchaseNote" name="note" type="text" maxlength="500" placeholder="Supplier, invoice, etc.">
            <div class="admin-actions">
              <button type="submit">Record Purchase</button>
            </div>
          </form>

          <form id="stockOutForm" class="admin-product-form">
            <h3>Stock Out</h3>
            <label for="stockOutProductId">Product</label>
            <select id="stockOutProductId" name="product_id" required></select>
            <label for="stockOutQuantity">Quantity</label>
            <input id="stockOutQuantity" name="quantity" type="number" min="1" step="1" value="1" required>
            <label for="stockOutNote">Reason (optional)</label>
            <input id="stockOutNote" name="note" type="text" maxlength="500" placeholder="Damaged, returned, etc.">
            <div class="admin-actions">
              <button type="submit">Record Stock Out</button>
            </div>
          </form>
        </div>
      </section>

      <section class="admin-panel">
        <h2>Inventory &amp; Reports</h2>
        <p class="admin-note">Generate inventory, stock movement, sales, purchase, and profit reports.</p>
        <div class="report-toolbar">
          <div class="report-toolbar-field">
            <label for="reportType">Report type</label>
            <select id="reportType">
              <option value="inventory">Inventory Summary</option>
              <option value="stock">Stock In / Out</option>
              <option value="sales">Sales</option>
              <option value="purchase">Purchase / Stock In</option>
              <option value="profit">Profit</option>
            </select>
          </div>
          <div class="report-toolbar-field">
            <label for="reportFrom">From</label>
            <input id="reportFrom" type="date">
          </div>
          <div class="report-toolbar-field">
            <label for="reportTo">To</label>
            <input id="reportTo" type="date">
          </div>
          <div class="report-toolbar-field report-toolbar-action">
            <button id="loadReportBtn" type="button">Generate Report</button>
          </div>
        </div>
        <div id="reportSummary" class="report-summary"></div>
        <div id="reportTableWrap" class="admin-table-wrap"></div>
      </section>

      <section class="admin-panel">
        <h2>Shop Orders</h2>
        <p id="shopStats" class="admin-note">Loading shop orders...</p>
        <div id="ordersTableWrap" class="admin-table-wrap"></div>
      </section>
      </div>
    </div>

    <div id="page-backup" class="admin-page">
      <header class="page-hero">
        <p class="page-hero-eyebrow">Section 05</p>
        <h2 class="page-hero-title">Backup &amp; Data</h2>
        <p class="page-hero-desc">Password-protected backups, restore, and full data reset.</p>
      </header>
      <div class="page-content">
      <section class="admin-panel">
        <h2>Website Data Summary</h2>
        <p class="admin-note">Overview of data stored on this website.</p>
        <div id="backupSummary" class="report-summary"></div>
      </section>

      <section class="admin-panel" id="runtimeUpdatesPanel">
        <h2>Runtime Versions &amp; Updates</h2>
        <p class="admin-note">Checks PHP and related tools. OS packages are not auto-upgraded from the website; use the scripts below after backup.</p>
        <p id="runtimeUpdatesMeta" class="admin-note">Loading runtime report...</p>
        <div id="runtimeComponentsWrap" class="admin-table-wrap"></div>
        <div id="runtimeCompatibilityWrap" class="admin-note"></div>
        <div class="runtime-impact-grid" id="runtimePanelImpactGrid">
          <div class="runtime-impact-card runtime-impact-approve">
            <h3 id="runtimePanelApproveTitle">If you approve &amp; run updates</h3>
            <p id="runtimePanelApproveWebsite" class="runtime-impact-website-summary"></p>
            <ul id="runtimePanelApproveList" class="runtime-impact-list">
              <li class="runtime-impact-placeholder">Loading…</li>
            </ul>
          </div>
          <div class="runtime-impact-card runtime-impact-cancel">
            <h3 id="runtimePanelCancelTitle">If you cancel (keep current versions)</h3>
            <p id="runtimePanelCancelWebsite" class="runtime-impact-website-summary"></p>
            <ul id="runtimePanelCancelList" class="runtime-impact-list">
              <li class="runtime-impact-placeholder">Loading…</li>
            </ul>
          </div>
        </div>
        <div class="admin-actions">
          <button type="button" id="refreshRuntimeBtn" class="secondary">Refresh report</button>
          <button type="button" id="verifyRuntimeBtn" class="secondary">Run compatibility check</button>
          <button type="button" id="runtimePanelApproveBtn" class="secondary" hidden>Approve available updates</button>
        </div>
        <details class="admin-note" style="margin-top:0.75rem">
          <summary>How to upgrade safely (Windows)</summary>
          <ol id="runtimeUpgradeSteps" class="admin-note" style="margin:0.5rem 0 0 1rem"></ol>
          <p class="admin-note">Scripts: <code>scripts\check-runtime-versions.ps1</code> and <code>scripts\upgrade-runtimes.ps1 -Apply</code></p>
        </details>
      </section>

      <section class="admin-panel">
        <h2>Create Backup</h2>
        <p class="admin-note">Creates a password-protected ZIP archive of shop inventory, certifications, knowledge base, contact messages, visitor stats, site settings, access policy, admin login, and uploaded files.</p>
        <p id="backupCapabilityNote" class="admin-note admin-warning" hidden></p>
        <ul id="backupIncludesList" class="backup-detail-list admin-note"></ul>
        <div id="backupExcludesWrap" class="backup-excludes admin-note"></div>
        <form id="createBackupForm" class="admin-product-form">
          <label for="createBackupPassword">Backup password</label>
          <input id="createBackupPassword" name="backup_password" type="password" autocomplete="new-password" minlength="8" required placeholder="At least 8 characters">
          <label for="createBackupPasswordConfirm">Confirm backup password</label>
          <input id="createBackupPasswordConfirm" type="password" autocomplete="new-password" minlength="8" required placeholder="Re-enter password">
          <p class="admin-note">You will need this password to restore the backup later. It is not stored on the server.</p>
          <div class="admin-actions">
            <button type="submit" id="createBackupBtn">Create &amp; Download Backup</button>
          </div>
        </form>
      </section>

      <section class="admin-panel">
        <h2>Stored Backups</h2>
        <p class="admin-note">Recent backups saved on the server (last 8 kept automatically).</p>
        <div id="storedBackupsWrap" class="admin-table-wrap"></div>
      </section>

      <section class="admin-panel">
        <h2>Restore from Backup</h2>
        <p class="admin-note admin-warning">Restoring replaces current website data with the contents of the backup file.</p>
        <form id="restoreBackupForm" class="admin-product-form">
          <label for="restoreBackupFile">Backup ZIP file</label>
          <input id="restoreBackupFile" name="backup_file" type="file" accept=".zip,application/zip" required>
          <label for="restoreBackupPassword">Backup password</label>
          <input id="restoreBackupPassword" name="backup_password" type="password" autocomplete="current-password" required placeholder="Password used when backup was created">
          <label for="restoreConfirm">Type RESTORE to confirm</label>
          <input id="restoreConfirm" name="confirm" type="text" autocomplete="off" placeholder="RESTORE">
          <div class="admin-actions">
            <button type="submit" class="secondary">Restore Backup</button>
          </div>
        </form>
      </section>

      <section class="admin-panel backup-danger-panel">
        <h2>Reset All Website Data</h2>
        <p class="admin-note admin-warning">Clears shop, certifications, knowledge, messages, visitors, and settings. An automatic backup is created before reset.</p>
        <form id="resetAllDataForm" class="admin-product-form">
          <label for="resetBackupPassword">Backup password (for automatic backup before reset)</label>
          <input id="resetBackupPassword" name="backup_password" type="password" autocomplete="new-password" minlength="8" required placeholder="At least 8 characters">
          <label class="admin-checkbox">
            <input id="resetKeepAdminAuth" name="keep_admin_auth" type="checkbox" value="1" checked>
            Keep admin login credentials
          </label>
          <label for="resetConfirm">Type RESET ALL to confirm</label>
          <input id="resetConfirm" name="confirm" type="text" autocomplete="off" placeholder="RESET ALL">
          <div class="admin-actions">
            <button type="submit" class="danger">Reset All Data</button>
          </div>
        </form>
      </section>
      </div>
    </div>

    <div id="page-updates" class="admin-page">
      <header class="page-hero">
        <p class="page-hero-eyebrow">Section 06</p>
        <h2 class="page-hero-title">System Updates</h2>
        <p class="page-hero-desc">Pending runtime upgrades, reminder preferences, and full upgrade history.</p>
      </header>
      <div class="page-content">
      <section class="admin-panel">
        <h2>Reminder status</h2>
        <p id="updatesReminderStatus" class="admin-note">Loading…</p>
        <div class="admin-actions">
          <button type="button" id="updatesReviewBtn">Review &amp; approve updates</button>
          <button type="button" id="updatesClearDeferBtn" class="secondary" hidden>Show banner reminders again</button>
        </div>
      </section>

      <section class="admin-panel">
        <h2>Pending upgrades</h2>
        <p id="updatesPendingMeta" class="admin-note">Loading pending packages…</p>
        <div id="updatesPendingWrap" class="admin-table-wrap"></div>
      </section>

      <section class="admin-panel">
        <h2>Upgrade history</h2>
        <p class="admin-note">Deferred choices, completed upgrades, and failures with date and time.</p>
        <div id="updatesHistoryWrap" class="admin-table-wrap"></div>
      </section>
      </div>
    </div>

    <div id="page-users" class="admin-page">
      <header class="page-hero">
        <p class="page-hero-eyebrow">Section 07</p>
        <h2 class="page-hero-title">Users &amp; Roles</h2>
        <p class="page-hero-desc">Manage admin accounts, roles, and database-backed access control.</p>
      </header>
      <div class="page-content">
      <section class="admin-panel">
        <h3>Database</h3>
        <div id="usersDatabaseSummary" class="report-summary"></div>
        <p id="usersDatabaseNote" class="admin-note" hidden></p>
      </section>

      <section class="admin-panel" id="usersCreatePanel">
        <h3>Create user</h3>
        <form id="createUserForm" class="admin-form">
          <label for="newUserUsername">Username</label>
          <input id="newUserUsername" name="username" type="text" autocomplete="off" required minlength="3" maxlength="40" pattern="[A-Za-z0-9._-]+" placeholder="letters, numbers, dot, dash, underscore">
          <label for="newUserDisplayName">Display name</label>
          <input id="newUserDisplayName" name="display_name" type="text" autocomplete="name" maxlength="120" placeholder="Optional">
          <label for="newUserEmail">Email</label>
          <input id="newUserEmail" name="email" type="email" autocomplete="email" maxlength="180" placeholder="Optional">
          <label for="newUserRole">Role</label>
          <select id="newUserRole" name="role" required></select>
          <label for="newUserPassword">Password</label>
          <input id="newUserPassword" name="password" type="password" autocomplete="new-password" required minlength="8">
          <label class="admin-checkbox">
            <input id="newUserActive" name="active" type="checkbox" value="1" checked>
            Active account
          </label>
          <div class="admin-actions">
            <button type="submit">Create user</button>
          </div>
        </form>
      </section>

      <section class="admin-panel">
        <h3>Admin accounts</h3>
        <p id="usersReadOnlyNote" class="admin-note" hidden>You have view-only access to user accounts.</p>
        <div id="usersTableWrap" class="admin-table-wrap"></div>
      </section>
      </div>
    </div>

    </main>
  </div>
</div>
<script src="qrcode.min.js"></script>
<script src="admin.js"></script>
</body>
</html>
