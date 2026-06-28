# Upload This Project to GitHub

## Why it was not uploading

Cursor’s “Connect to GitHub” does **not** push code by itself. Your repo had:

1. **No remote** — `git remote` was empty (no `origin` URL).
2. **Work on `cleanup-phase-1`** — GitHub often expects `main` as the default branch.
3. **Uncommitted changes** — only committed files can be pushed.

Those issues are fixed locally: a publish script is available (see below).

**Your active branch:** `cleanup-phase-1` (has the full v5 site). Branch `main` is older — push `cleanup-phase-1` or merge to `main` before publishing.

---

## Option A — Publish script (recommended)

1. On GitHub, create an **empty** repository (no README, no .gitignore):
   - https://github.com/new
   - Example name: `mywebsite` or `portfolio`
   - Do **not** initialize with README (you already have code).

2. **Commit** any pending changes in Cursor (Source Control panel).

3. In PowerShell:

```powershell
cd "E:\Nabeel Data\mywebsite\ver2\mywebsite"
.\scripts\publish-to-github.ps1 -RepoUrl "https://github.com/YOUR_USERNAME/YOUR_REPO.git"
```

Your latest code is on branch **`cleanup-phase-1`** (not `main`). The script pushes whatever branch you are on. To push that branch explicitly:

```powershell
.\scripts\publish-to-github.ps1 -RepoUrl "https://github.com/YOUR_USERNAME/YOUR_REPO.git" -Branch cleanup-phase-1
```

3. If Git asks to sign in, use:
   - **GitHub username**
   - **Personal Access Token** (not your password) — create at:
     https://github.com/settings/tokens → Generate new token (classic) → scope `repo`

---

## Option B — Cursor Source Control

1. Open **Source Control** (branch icon in the left sidebar).
2. Confirm branch is **`main`** (bottom-left or branch picker).
3. If you see uncommitted changes, **Commit** them first.
4. Click **Publish Branch** or **Sync** / **Push**.
5. If prompted for remote URL, paste:
   `https://github.com/YOUR_USERNAME/YOUR_REPO.git`

If Publish fails, use Option A — it sets the remote explicitly.

---

## Option C — Manual git commands

```powershell
cd "E:\Nabeel Data\mywebsite\ver2\mywebsite"
git checkout main
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
git push -u origin main
```

If `origin` already exists but push fails:

```powershell
git remote set-url origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
git push -u origin main
```

---

## Check that it worked

- Open your repo on GitHub in the browser — you should see `index.php`, `admin/`, `php/`, etc.
- Locally:

```powershell
git remote -v
git status
```

`git remote -v` must show `origin` pointing to your GitHub URL.

---

## Common errors

| Error | Fix |
|-------|-----|
| `remote origin already exists` | `git remote set-url origin https://github.com/...` |
| `Authentication failed` | Use a Personal Access Token, not account password |
| `Repository not found` | Wrong URL, or repo not created yet, or no access |
| `failed to push some refs` | On GitHub repo, pull first: `git pull origin main --rebase` then push |
| Cursor publish does nothing | No remote — run the publish script |

---

## What is NOT uploaded (on purpose)

`.gitignore` excludes secrets and local data:

- `config/local.php` (passwords, API keys)
- `uploads/cv/*`, `uploads/certifications/*` (your PDFs)
- `php/storage/orders/`, contact messages, database, backups

Never commit `config/local.php` or API keys.

---

## Path with spaces

This project lives under `E:\Nabeel Data\...`. Always quote the path in commands:

```powershell
cd "E:\Nabeel Data\mywebsite\ver2\mywebsite"
```
