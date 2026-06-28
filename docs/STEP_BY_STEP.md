# Step-by-Step Implementation Guide
## For Mirza Nabeel Ahmed Portfolio Site

> **Updated June 2026:** See [CURRENT_GUIDE.md](CURRENT_GUIDE.md) for the current PHP v5 site. OpenAI is **optional** — the Portfolio Assistant works without an API key.

> **GitHub + Cursor:** See [CURSOR_GITHUB_INTEGRATION.md](CURSOR_GITHUB_INTEGRATION.md) — use `config/github.local.php` + `scripts/github-api-integration.ps1`.

You do NOT need to edit code yourself. Follow each step in order.
When a step says "Ask Cursor AI", copy the message into the chat.

---

## STEP 0 — What was already done (Phase 1)

These changes are LIVE in your project now:

| File | What changed |
|------|--------------|
| `index.html` | Slimmed from 94 CSS + 110 JS files → **13 CSS + 1 JS** |
| `js/homepage.js` | **NEW** — counters, scroll bar, back-to-top, AI assistant |
| `css/homepage-core.css` | **NEW** — extra layout styles for homepage |
| `index.full-backup.html` | **Backup** of your old heavy homepage |

**Old heavy files are NOT deleted.** They still exist in `css/` and `js/` folders.
They are just not loaded on the homepage anymore.

---

## STEP 1 — See your new fast homepage (YOU do this now)

### 1.1 Open the project in Cursor
1. Open **Cursor**
2. Click **File → Open Folder**
3. Select: `E:\Nabeel Data\mywebsite\ver2\mywebsite`

### 1.2 Preview in browser

**Option A — Simple (double-click)**
1. In Cursor left panel, find `index.html`
2. Right-click → **Reveal in File Explorer**
3. Double-click `index.html` — it opens in your browser

**Option B — Live Server (recommended)**
1. Install extension: **Live Server** (in Cursor extensions)
2. Right-click `index.html` → **Open with Live Server**
3. Browser opens at `http://127.0.0.1:5500/index.html`

### 1.3 Check these things work

| # | What to check | How |
|---|---------------|-----|
| 1 | Page loads quickly | Should appear in 1–2 seconds |
| 2 | Hero title visible | "Technology Leadership & Innovation" |
| 3 | KPI numbers animate | Scroll to "Executive IT Dashboard" — numbers count up |
| 4 | Spinning Earth | Blue/green circle in "Global Technology Leadership" |
| 5 | Profile photo | Your photo in profile section |
| 6 | AI box bottom-right | Type `oracle` → click Ask → answer appears |
| 7 | Footer links | Email, GitHub, LinkedIn clickable |
| 8 | Back to top | Scroll down → blue ↑ button appears bottom-right |

### 1.4 If something looks wrong
Copy this into Cursor chat:

> "Phase 1 homepage check failed: [describe what is wrong, e.g. photo missing, numbers not animating]"

---

## STEP 2 — Upload to your live website (when ready)

### 2.1 Backup live site first
Ask your IT team OR use hosting File Manager to **download a copy** of the current live site.

### 2.2 Upload these files (minimum for Phase 1)

Upload the whole folder, OR at minimum these files:

```
index.html
js/homepage.js
css/homepage-core.css
css/style.css
css/theme.css
css/animation.css
css/hero-redesign.css
css/kpi-cards.css
css/real-earth-section.css
css/live-metrics-engine.css
css/skills.css
css/social.css
css/assistant.css
css/executive-footer.css
css/homepage-polish.css
css/mobile-v4.css
assets/profile.png
pwa/manifest.json
```

### 2.3 How to upload (typical cPanel)
1. Log in to hosting control panel
2. Open **File Manager**
3. Go to your website folder (often `public_html`)
4. Click **Upload**
5. Upload the files listed above (replace old `index.html` when asked)

### 2.4 Test live site
1. Open your website URL in browser
2. Press **Ctrl + F5** (hard refresh)
3. Repeat the checks from Step 1.3

---

## STEP 3 — Phase 2: Security (DONE)

These changes are LIVE in your project now:

| File | What changed |
|------|--------------|
| `.htaccess` | Security headers, no directory listing, backup file blocked |
| `php/upload-cv.php` | Admin-only upload, file type check, 5 MB limit, safe filenames |
| `php/upload-and-process.php` | Secure upload + knowledge rebuild |
| `php/lib/auth.php` | Login sessions, CSRF protection |
| `php/lib/upload.php` | Shared secure upload validation |
| `admin/index.html` | Admin login page (separate from homepage) |
| `admin/dashboard.html` | CV upload dashboard after login |
| `uploads/cv/.htaccess` | Uploaded CV files cannot be downloaded publicly |
| `knowledge/.htaccess` | Knowledge JSON files blocked from public web |

### STEP 3A — Set admin password on server (YOU or IT team)

On your hosting server, add these environment variables:

```
ADMIN_USERNAME=your_admin_username
ADMIN_PASSWORD=your_strong_password
```

Apache example in site root `.htaccess` (below the security rules):

```
SetEnv ADMIN_USERNAME your_admin_username
SetEnv ADMIN_PASSWORD your_strong_password
```

See `config/env-example.txt` and `config/README_API_KEY.txt` for full instructions.

**Never paste your real password in Cursor chat.**

### STEP 3B — Test admin (after server env vars are set)

| # | What to check | Pass? |
|---|---------------|-------|
| 1 | Homepage still loads fast | ☐ |
| 2 | Open `admin/index.html` — login page appears | ☐ |
| 3 | Wrong password → error message | ☐ |
| 4 | Correct password → goes to dashboard | ☐ |
| 5 | Upload a PDF CV → success message | ☐ |
| 6 | Logout works | ☐ |

**Local PC note:** Admin login needs PHP + server env vars. Double-clicking HTML files will show "Admin is not configured" until uploaded to PHP hosting with env vars set.

### STEP 3C — Tell Cursor the result

**If all good:**
> Phase 2 security check: everything works, continue to Phase 3 AI.

**If problem:**
> Phase 2 security check failed: [describe problem]

---

## STEP 4 — Phase 3: Smart AI assistant (OpenAI) — NEXT

### 4.1 Get OpenAI API key (you do this)
1. Go to https://platform.openai.com
2. Create account / log in
3. Go to **API Keys** → **Create new secret key**
4. Copy the key (starts with `sk-`)
5. **Never share the key in email or chat**

### 4.2 Add key on server (you or IT team)
On Apache/cPanel hosting, add to `.htaccess` in site root:

```
SetEnv OPENAI_API_KEY sk-your-actual-key-here
```

Or ask IT: "Please set environment variable OPENAI_API_KEY on the web server."

### 4.3 Ask Cursor AI to wire it up

> "Do Phase 3 AI: connect the homepage AI assistant to php/chat-api.php and OpenAI. Use my knowledge folder for context. Keep API key on server only."

### After Phase 3 — YOU test
1. Open homepage
2. In AI box type: "What Oracle experience do you have?"
3. You should get a real AI answer (not just keyword match)

---

## STEP 5 — Phase 4: Knowledge base (CV upload)

### 5.1 Put your CV in the project
1. Save your CV as PDF
2. Copy it to: `knowledge/cv.pdf` in your project folder

### 5.2 Ask Cursor AI

> "Do Phase 4 knowledge base: index knowledge/cv.pdf so the AI assistant can answer questions about my career."

### 5.3 Test
Ask AI: "Summarize my IT experience"

---

## STEP 6 — Optional demo pages (heavy 3D effects)

Your old datacenter 3D, particles, and extra engines are still in the project.
They can live on a separate demo page so the homepage stays fast.

**Ask Cursor AI when you want this:**

> "Create pages/demo.html that loads the datacenter 3D and particle effects. Do NOT add them back to index.html."

---

## Quick reference — files that matter

| Purpose | File you care about |
|---------|---------------------|
| Main homepage | `index.html` |
| Homepage behavior | `js/homepage.js` |
| Your photo | `assets/profile.png` |
| AI server | `php/chat-api.php` |
| Admin login | `admin/index.html` |
| Admin dashboard | `admin/dashboard.html` |
| API key instructions | `config/README_API_KEY.txt` |
| Admin password setup | `config/env-example.txt` |
| Old homepage backup | `index.full-backup.html` |
| This guide | `docs/STEP_BY_STEP.md` |

---

## Messages to copy into Cursor (in order)

```
1. "Phase 1 homepage check: everything works, continue to Phase 2 security."
   OR
   "Phase 1 homepage check failed: [problem]"

2. "Phase 2 security check: everything works, continue to Phase 3 AI."

3. "Do Phase 3 AI: connect homepage assistant to OpenAI via php/chat-api.php."

4. "Do Phase 4 knowledge base: index my CV from knowledge/cv.pdf."

5. "Create pages/demo.html for datacenter 3D effects without slowing homepage."
```

---

## Glossary

| Term | Meaning |
|------|---------|
| index.html | Your main website page |
| homepage.js | One file that runs counters, AI, scroll features |
| Phase | One group of improvements |
| .htaccess | Server config file for security |
| API key | Password for OpenAI — server only |
| Live Server | Tool to preview site on your PC before uploading |
