# Portfolio Site — Current Guide (June 2026)

This replaces the older phase-by-phase notes in STEP_BY_STEP.md. Your site is a **PHP v5 multi-page portfolio** — not the old single `index.html` homepage.

## What works now (no OpenAI payment needed)

| Feature | URL | Notes |
|---------|-----|-------|
| Homepage | `http://127.0.0.1/index.php` | KPI dashboard, featured projects, section links |
| Projects | `projects.php` | Loaded from `knowledge/projects.json` |
| Career | `career.php` | Visual timeline |
| Certs | `certs.php` | Admin-managed catalog |
| IT Shop | `shop.php` | Cart + orders saved locally |
| Contact | `contact.php` | Messages saved even if email fails |
| Portfolio Assistant | Bottom-right **Ask** button | Searches knowledge + shop — **no API key required** |
| Admin | `admin/index.php` | Local: `admin` / password in `config/local.php` |
| 3D Demo | `pages/demo.html` | Heavy effects kept off main pages |

## OpenAI is optional

Leave `OPENAI_API_KEY` empty in `config/local.php`. The assistant uses:

1. `knowledge/*.json` (career, projects, skills, certs)
2. IT shop product catalog
3. Built-in keyword fallbacks in `js/homepage.js`

When you add a paid API key later, answers can upgrade to GPT — nothing else needs changing.

## Your next steps (recommended order)

1. **Upload CV PDF** — Admin → Certifications & Knowledge → upload → Rebuild knowledge
2. **Review pages on mobile** — resize browser; check shop cart and career timeline
3. **Add shop products** — Admin → Online Store with real photos and prices
4. **Upload cert PDFs** — Admin → Certifications
5. **When ready to go live** — backup `it.gift.edu.pk`, upload project, set server env vars (admin password, contact email)

## Local testing commands

```bat
php scripts/test-health-cli.php
php scripts/test-ai-fallback-cli.php
php scripts/test-forms-cli.php
php scripts/rebuild-knowledge-cli.php
```

## Health check

Open: `http://127.0.0.1/php/health.php`

Expect: `"assistant_mode": "knowledge"` and `"openai_configured": false` — that is normal.

---

See also: [LOCAL_SETUP_WINDOWS.md](LOCAL_SETUP_WINDOWS.md)
