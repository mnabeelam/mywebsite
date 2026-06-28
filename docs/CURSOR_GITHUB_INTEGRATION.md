# Cursor + GitHub integration (API token)

This project can publish to GitHub using a **Personal Access Token** (GitHub API). Cursor does not upload code by itself — it uses **git** behind the Source Control panel.

## Two ways to integrate

### A) Cursor sign-in (OAuth) — for UI features

1. In Cursor: **Settings** (Ctrl+,) → **Account**
2. Click **Sign in with GitHub**
3. Approve in the browser

This links your Cursor account to GitHub (PRs, etc.) but **still requires git remote + push** for this folder.

### B) API token — for push/pull from this project (recommended)

1. Copy `config/github.local.example.php` → `config/github.local.php`
2. Paste your token into `GITHUB_TOKEN`
3. Run once:

```powershell
cd "E:\Nabeel Data\mywebsite\ver2\mywebsite"
.\scripts\github-api-integration.ps1
```

The script:

- Creates `https://github.com/mnabeelam/mywebsite` if missing (GitHub API)
- Sets `origin` remote
- Pushes branch `cleanup-phase-1`
- Stores credentials in **Windows Git Credential Manager** so Cursor **Sync / Push** works afterward

---

## Create the API token

1. Open https://github.com/settings/tokens
2. **Generate new token (classic)**
3. Note: e.g. `Cursor mywebsite`
4. Expiration: your choice (90 days or custom)
5. Scope: check **`repo`** (full control of private repositories)
6. Generate → copy the token (`ghp_...` or `github_pat_...`)

Paste it only in **`config/github.local.php`** — never commit that file.

---

## After integration — use Cursor normally

1. Open **Source Control** (branch icon, left sidebar)
2. Branch should be `cleanup-phase-1`
3. Make changes → **Commit**
4. Click **Sync** or **Push** (arrow icon)

No token needed each time if Windows saved credentials on first API push.

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Repo not on GitHub | Run `scripts\github-api-integration.ps1` again |
| `401 Bad credentials` | Token expired or wrong — create new token |
| `Repository already exists` | Normal — script continues and pushes |
| Cursor Push does nothing | Check `git remote -v` shows `origin` |
| Want different repo name | Edit `GITHUB_REPO` in `config/github.local.php` |

---

## Security

- `config/github.local.php` is in `.gitignore`
- Revoke tokens anytime: https://github.com/settings/tokens
- Do not paste tokens in Cursor chat if others can see the session

---

See also: [GITHUB_UPLOAD.md](GITHUB_UPLOAD.md)
