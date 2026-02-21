# MarkdownAI Converter v0.1.0

Initial public release of MarkdownAI Converter.

## Highlights

- Converts published WordPress content into normalized Markdown.
- Exposes markdown via REST endpoint for AI crawlers:
  - `GET /wp-json/mdai/v1/markdown/{post_id}`
- Tracks bot interactions (family detection, endpoint hits, status, bytes, latency).
- Adds publisher-facing analytics:
  - KPI summary
  - Daily trend visualization
  - Top crawled pages
  - Period-over-period KPI deltas
- Adds optimization Suggestions engine with rule-based scoring and recommendations.
- Adds Export & Reports workflow:
  - CSV export
  - Printable performance report (PDF-ready via browser print)
- Adds weekly email report scheduling and manual “Send Test Weekly Report Now” action.
- Adds 90-day demo data seeding for realistic testing.

## Admin Areas Included

- Overview
- Content Preview
- Bot Activity
- Suggestions
- Export & Reports
- Settings

## Security & Compatibility Notes

- Uses WordPress-native capabilities, nonces, and Settings API.
- Uses sanitized/escaped inputs/outputs and prepared SQL parameters.
- Stores salted IP hashes (not raw IP addresses).
- Designed to avoid invasive changes to canonical/sitemap ownership.

## Technical Notes

- Custom tables created on activation:
  - `mdai_content_cache`
  - `mdai_bot_events`
  - `mdai_daily_aggregates`
- Includes daily cron aggregation and retention cleanup jobs.
- Includes custom weekly cron schedule for report emails.

## Known Limitations (v0.1.0)

- Native PDF engine not bundled yet (uses print-to-PDF workflow).
- CLI `php -l` validation was not runnable in current shell due missing PHP executable on PATH; run lint in devcontainer/containerized PHP for full CLI validation.

## Upgrade / Install

1. Install plugin into `wp-content/plugins/markdownai-converter`.
2. Activate in wp-admin.
3. Open Settings and save defaults.
4. Optionally seed demo data from Overview for immediate dashboards.

## Suggested Tag

- `v0.1.0`
