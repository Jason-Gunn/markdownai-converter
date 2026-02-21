# MarkdownAI Converter — Development Updates & Handoff

## Audience
This document is a detailed handoff for a new engineer (or LLM session) to continue work without prior chat history.

## Project Snapshot
MarkdownAI Converter is a WordPress plugin that:
1. Generates Markdown representations of published site content.
2. Exposes markdown via API for AI crawlers.
3. Tracks bot interactions and trends.
4. Provides publisher-facing optimization suggestions.
5. Exports data and generates printable client-ready reports.

## What Has Been Implemented

### 1) Plugin foundation
- Main plugin bootstrap with activation/deactivation hooks.
- Namespaced architecture under `MDAI\`.
- Admin menu structure created with subpages:
  - Overview
  - Content Preview
  - Bot Activity
  - Suggestions
  - Export & Reports
  - Settings

### 2) Data model + lifecycle
- DB tables created on activation:
  - `mdai_content_cache`
  - `mdai_bot_events`
  - `mdai_daily_aggregates`
- DB schema version saved in option.
- Daily cron hook installed for aggregate + cleanup tasks.
- Uninstall file supports optional full data cleanup.

### 3) Settings/security baseline
- WordPress Settings API implemented.
- Settings include:
  - Enable/disable bot tracking
  - Retention days
  - Delete data on uninstall
- Sanitization and bounds checks in settings sanitize callback.
- Admin pages protected by `manage_options` checks.

### 4) Markdown conversion workflow
- Markdown generation service with cache table storage.
- Front matter metadata includes title/source URL/post type/modified/language.
- Basic HTML-to-Markdown conversion for common content structures.
- Content Preview page supports:
  - content selection
  - output preview
  - nonce-protected regenerate action

### 5) Public REST endpoint
- Endpoint: `GET /wp-json/mdai/v1/markdown/{id}`
- Returns markdown payload for published + viewable posts.
- Applies cache headers for CDN/proxy friendliness.
- Logs each request to bot events table.

### 6) Bot tracking + activity logs
- User-agent family normalization implemented (OpenAI, Anthropic, Google, etc.).
- Bot event logger records:
  - timestamp
  - family
  - user agent
  - endpoint
  - post ID
  - status
  - bytes
  - latency
  - referer host
  - salted IP hash
- Bot Activity admin page includes:
  - sortable table (`WP_List_Table`)
  - date and family filtering
  - CSV export

### 7) Analytics dashboard
- Overview page includes:
  - date-range filtering (quick 7d/30d + custom)
  - KPI cards/table
  - daily trend chart (inline SVG)
  - top crawled pages table
- Analytics service centralizes KPI/trend/top-page query logic.

### 8) Demo data seeding
- One-click seeded event generation for 90 days.
- One-click seeded event cleanup.
- Seeded data marked via dedicated UA prefix (`MDAI-Demo/...`) to enable safe deletion.

### 9) Suggestions engine (publisher value)
- Rule-based analyzer for content quality and AI extraction readiness.
- Current checks:
  - thin content
  - weak heading structure
  - long paragraphs
  - missing image alt text
  - low internal link count
  - missing FAQ-style section
- Suggestions page includes:
  - single-page analysis
  - recommendation list with severity
  - recent content health table sorted by lowest score

### 10) Export & Reports
- Export & Reports page added.
- CSV export available for bot events with date filters.
- Printable report generation implemented with:
  - KPI summary
  - trend chart
  - top crawled pages
  - top content opportunities
- Report page has print button; browser “Save as PDF” is current PDF workflow.

### 11) Weekly reporting + KPI deltas
- Added weekly report scheduling using WP-Cron custom schedule (`mdai_weekly`).
- Added settings for:
  - enable/disable weekly reports
  - report recipient email
- Implemented weekly email summary job with KPI and top-page snapshot.
- Added manual admin action on Export & Reports to send a test weekly report immediately.
- Added period-over-period KPI deltas on Overview (current period vs previous equal-length period).

## Current Behavior Summary
- Tracking is only recorded when setting is enabled.
- Non-admin users cannot access plugin admin/report/export features.
- REST endpoint is public but constrained to published/viewable posts.
- Retention cleanup runs in daily cron and removes old event/aggregate data.

## Files Added/Updated (high-value map)
- `markdownai-converter.php`
- `uninstall.php`
- `includes/class-mdai-plugin.php`
- `includes/class-mdai-installer.php`
- `includes/class-mdai-settings.php`
- `includes/class-mdai-markdown-service.php`
- `includes/class-mdai-rest.php`
- `includes/class-mdai-bot-detector.php`
- `includes/class-mdai-bot-tracker.php`
- `includes/class-mdai-analytics.php`
- `includes/class-mdai-report.php`
- `includes/class-mdai-demo-data.php`
- `includes/class-mdai-suggestions.php`
- `includes/admin/class-mdai-admin.php`
- `includes/admin/class-mdai-bot-activity-table.php`
- `resources/specifications.md`
- `resources/testing-checklist.md`
- `resources/environment.md`
- `resources/dev-updates.md`

## Known Gaps / Next Priority Work

### A) Reporting enhancements
- Native PDF binary generation (currently browser print-to-PDF flow).
- Branded report templates (logo/custom color + agency metadata).
- Scheduled report generation and email delivery.

### B) Analytics depth
- Bot family trend breakdown in chart (stacked/segmented).
- New vs returning bot signature metric.
- Period-over-period delta views on Overview.
- Alerting thresholds (drops/spikes).

### C) Crawl surface
- Markdown index endpoint and/or markdown sitemap.
- Optional robots/AI policy diagnostics.

### D) Compatibility and hardening
- Explicit compatibility checks with Yoast/RankMath/AIOSEO active states.
- Reverse proxy/CDN edge cases (forwarded headers, cached status responses).
- Optional rate limiting for markdown endpoint abuse patterns.

### E) Testing and CI
- Add PHPUnit unit tests for markdown conversion and suggestion scoring.
- Add integration tests for REST permissions and export/report actions.
- Add PHPCS + PHPStan + PHPUnit workflow in CI.

## Suggested Git Commit Grouping
Use these thematic commits for cleaner history:
1. `feat(core): bootstrap plugin foundation, settings, schema, lifecycle hooks`
2. `feat(markdown): add markdown generation, caching, and preview page`
3. `feat(tracking): add bot event tracking, activity table, CSV export`
4. `feat(analytics): add overview KPI dashboard and trend visualization`
5. `feat(testing): add demo data seeding and cleanup controls`
6. `feat(content): add rule-based suggestions engine and admin UI`
7. `feat(reporting): add export/reports page and printable client report`
8. `feat(reporting): add weekly email scheduling and KPI period deltas`
9. `docs: add specs, environment reference, and handoff updates`

## Suggested Next Session Prompt (for LLM)
"Continue MarkdownAI Converter from resources/dev-updates.md. Implement native PDF generation for client reports with secure admin action, add scheduled weekly report emails via WP-Cron, and add PHPUnit tests for suggestions scoring. Keep WordPress-native architecture and existing security checks."

## Release Prep Artifacts (v0.1.0)
- Release checklist created: `resources/release-checklist-v0.1.0.md`
- Suggested release notes created: `resources/release-notes-v0.1.0.md`
- QA pass summary:
  - Workspace diagnostics clean via editor checks.
  - GitHub repo and branch are active (`main`).
  - CLI `php` executable unavailable in current shell, so `php -l` must be run in container/devcontainer for final CLI lint confirmation.
