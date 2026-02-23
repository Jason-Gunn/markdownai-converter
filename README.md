# MarkdownAI Converter

MarkdownAI Converter is a WordPress plugin that converts published content into structured Markdown and provides AI-crawl analytics for publishers, agencies, and SEO teams.

> ✏️ Note: This is installable and useable, but not feature complete. Use with caution.

## Core Features

- Markdown conversion for published posts/pages/CPT content.
- Public Markdown API endpoint for crawl access:
  - `GET /wp-json/mdai/v1/markdown/{post_id}`
- Admin content preview with regenerate action.
- Bot interaction tracking (family detection + endpoint/event logging).
- Overview analytics dashboard (KPIs, trend chart, top crawled pages).
- Bot Activity page with filtering, sorting, and CSV export.
- Rule-based content suggestions and scoring for optimization.
- Export & Reports page with printable client performance report (PDF-ready via browser print).
- Weekly email performance summaries (configurable recipient + schedule).
- Period-over-period KPI deltas on Overview.
- Manual “Send Test Weekly Report Now” action for instant email validation.
- Demo data seeding (90 days) for fast testing.

## Security & Compatibility Focus

- Uses WordPress-native capabilities, nonces, and Settings API.
- Stores salted IP hashes instead of raw IP addresses.
- Uses `$wpdb->prepare()` for dynamic SQL.
- Designed to be non-invasive with SEO plugin ecosystems and cache/proxy setups.

## Requirements

- WordPress 6.4+
- PHP 8.0+

## Installation (Local)

1. Copy this plugin folder to:
   - `wp-content/plugins/markdownai-converter`
2. Activate **MarkdownAI Converter** in wp-admin.
3. Open **MarkdownAI Converter → Settings** and save defaults.

## Quick Start

1. Open **Overview** and click **Seed 90 Days Demo Data**.
2. Review dashboard KPIs/charts.
3. Open **Bot Activity** and test CSV export.
4. Open **Suggestions** and analyze a page.
5. Open **Export & Reports** and generate a printable report.

For full validation, use:
- `resources/testing-checklist.md`

## Development Testing

Local unit test setup:

1. Install dependencies:
  - `composer install`
2. Run tests:
  - `composer test`

Current test coverage includes:
- Bot user-agent family detection behavior.
- Date range sanitization logic for analytics.

## CI

GitHub Actions workflow:
- `.github/workflows/ci.yml`

Runs on push/PR to `main` and executes:
- dependency install via Composer
- PHPUnit suite

## Project Docs

- Product spec: `resources/specifications.md`
- Environment map: `resources/environment.md`
- Development handoff log: `resources/dev-updates.md`
- Testing checklist: `resources/testing-checklist.md`

## License

MIT — see `LICENSE`.
