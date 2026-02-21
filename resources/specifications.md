# MarkdownAI Converter — Product Specification & Build Plan

## 1) Product Vision
`MarkdownAI Converter` helps publishers expose high-quality, crawlable Markdown versions of site content for AI agents, while giving publishers analytics, optimization guidance, and client-ready reporting.

## 2) Core Principles
- Use WordPress-native resources first (Settings API, REST API, Cron, WP_List_Table, capability checks, nonces, transients, object cache).
- Security-first by default (least privilege, sanitization, escaping, rate limiting, privacy-safe storage).
- Compatibility-first with SEO/caching ecosystems (Yoast, Rank Math, AIOSEO, Cloudflare, host-level caching, CDNs).
- Non-invasive behavior (no forced theme edits, no front-end script bloat unless explicitly enabled).

## 3) Users & Publisher Value
- **Site Admin / Agency Owner:** needs clear KPI dashboards and PDF reports for clients.
- **Content Team:** needs page-level optimization suggestions and Markdown preview.
- **Technical SEO / Growth Teams:** needs trend analysis and exportable datasets.

## 4) Feature Set (Prioritized)

## Phase 1 — MVP (High impact, low risk)
1. **Markdown Generation Engine**
	- Convert posts/pages/CPT content to normalized Markdown.
	- Preserve headings, links, lists, tables, images, code blocks, canonical/source URL.
	- Add per-page metadata block (title, modified date, language, taxonomy tags).

2. **Admin UI (Clean, professional)**
	- Top-level admin menu: `MarkdownAI Converter`.
	- Subpages: Overview, Content Preview, Bot Activity, Suggestions, Export & Reports, Settings.
	- Use `WP_List_Table` for sortable/filterable data grids.

3. **Page-Level Preview**
	- Select post/page/CPT item and preview generated Markdown.
	- Side-by-side: rendered HTML excerpt vs Markdown output.
	- “Copy Markdown” and “Regenerate” actions.

4. **Bot Crawl Tracker (Foundational)**
	- Track requests to plugin markdown endpoints and optional sitemap/feed endpoints.
	- Capture: timestamp, endpoint, content ID, user agent, referer, response status, bytes, latency.
	- Normalize bot family (OpenAI, Anthropic, Google-Extended, Perplexity, Claudebot, unknown).

5. **Basic Analytics Dashboard**
	- KPI cards: total bot hits, unique bot families, most-viewed content, crawl frequency.
	- Date filters: 24h, 7d, 30d, custom.
	- Sortable table of top crawled pages.

6. **CSV Export**
	- Export raw activity logs and aggregated summaries to CSV.
	- Export filters respected (date range, bot family, content type).

## Phase 2 — Publisher Intelligence
1. **Content Optimization Suggestions**
	- Rule-based checks (non-AI-generated):
	  - Missing/weak heading hierarchy.
	  - Overly long paragraphs.
	  - Missing alt text.
	  - Thin content indicators.
	  - Low internal link density.
	  - Missing FAQ/schema-friendly sections.
	- Suggestion score per page and actionable fixes.

2. **Advanced Bot Analytics**
	- Trend charts for crawl volume by bot family over time.
	- Engagement-style metrics for bot traffic:
	  - New vs returning bot hits (UA + IP hash heuristic).
	  - Average crawl depth per session heuristic.
	  - Content freshness vs crawl latency.
	- Heatmap of top sections/content types crawled.

3. **Excel-Compatible Export**
	- Primary export remains CSV (native, stable).
	- Add “Excel-ready CSV templates” and column presets.
	- Optional `.xlsx` addon in later release (kept modular to avoid heavy core dependency).

4. **Client-Ready PDF Report**
	- Printable report with logo/header, date range, KPIs, trend charts, top pages, recommendations.
	- One-click “Generate PDF” from dashboard filters.

## Phase 3 — Ecosystem & Scale
1. **Automation & Scheduling**
	- Scheduled markdown regeneration (via WP-Cron).
	- Scheduled weekly/monthly emailed reports.

2. **Comparative Performance Views**
	- Period-over-period deltas (week-over-week, month-over-month).
	- Alerting thresholds (crawl drop, spike anomalies).

3. **APIs & Integrations**
	- Secure REST endpoints for analytics and markdown retrieval.
	- Optional connectors for BI tools (via CSV/REST, no invasive lock-in).

## 5) Additional Features Worth Adding
- **Robots/Policy Advisor:** checks `robots.txt` and AI-related directives; warns on contradictory rules.
- **“AI Crawl Readiness” Score:** combines technical + content checks into a single benchmark.
- **Content Change Impact View:** compare content updates vs subsequent bot crawl behavior.
- **Data Retention Controls:** configurable purge windows for privacy/compliance.

## 6) Technical Architecture (WordPress-First)
- **Data storage**
  - `wp_options`: plugin settings and feature flags.
  - Custom DB tables for high-volume log events and aggregates (faster than post meta at scale).
  - Transients/object cache for dashboard query acceleration.

- **Core WP systems to use**
  - Settings API for admin configuration.
  - REST API for dashboard data loading and export endpoints.
  - WP-Cron for scheduled jobs.
  - Capability checks: `manage_options` for admin, custom caps for reports if needed.
  - Nonces + `current_user_can()` across all admin and REST write actions.

- **UI stack**
  - Start with server-rendered admin pages + `WP_List_Table`.
  - Add selective JS components for charts/tables (bundled locally, no third-party CDN dependency).

## 7) Security Requirements
- Sanitize on input, escape on output everywhere.
- Strict REST permission callbacks.
- Store IP as salted hash (not raw IP) to reduce privacy risk.
- Rate limit high-frequency endpoints and suspicious bot floods.
- Validate file exports and report generation permissions.
- Prevent SQL injection via `$wpdb->prepare()` and schema validation.
- Add uninstall cleanup option (retain/delete data toggle).

## 8) Compatibility & Non-Interference
- Do not alter canonical tags, sitemap ownership, or SEO plugin metadata by default.
- Run in read-only mode relative to post content unless user explicitly enables write-back features.
- Detect known SEO plugins (Yoast/RankMath/AIOSEO) and avoid duplicate outputs where possible.
- Avoid changing cache headers globally.
- Make markdown endpoints cache-friendly but safe with Cloudflare/host caches.
- Graceful degradation when reverse proxies/CDNs strip referers or modify headers.

## 9) Data Model (Initial)
- `mdai_content_cache`
  - `id`, `post_id`, `post_modified_gmt`, `markdown_blob`, `checksum`, `generated_at`
- `mdai_bot_events`
  - `id`, `event_time`, `bot_family`, `user_agent`, `ip_hash`, `endpoint`, `post_id`, `status_code`, `bytes_sent`, `latency_ms`, `referer_host`
- `mdai_daily_aggregates`
  - `id`, `event_date`, `bot_family`, `post_id`, `hits`, `unique_signatures`, `avg_latency_ms`

## 10) Build Roadmap (Execution Plan)

## Sprint 0 — Foundation (1 week)
- Plugin skeleton, activation/deactivation hooks, DB migrations.
- Settings page scaffold and capabilities.
- Security baseline (nonces, permission callbacks, sanitization helpers).

## Sprint 1 — Markdown + Preview (1–2 weeks)
- Markdown conversion engine with deterministic output.
- Content preview page (selector + output panel).
- Regeneration controls and caching logic.

## Sprint 2 — Tracking + Dashboard MVP (2 weeks)
- Bot event logging pipeline.
- Overview dashboard KPIs + top pages table + date filters.
- CSV export pipeline.

## Sprint 3 — Suggestions + Trends (2 weeks)
- Content suggestion rules and scoring.
- Trend charts + bot-family filters + sortable analytics tables.
- Retention policy settings.

## Sprint 4 — PDF + Polish (1–2 weeks)
- Client-ready PDF report templates.
- UX polish for admin flows and report export.
- Compatibility testing with major SEO plugins and common cache/CDN setups.

## 11) QA, Testing, and Release Gates
- Unit tests for markdown conversion and suggestion rules.
- Integration tests for REST permissions and exports.
- Performance tests on large sites (event log volume and dashboard queries).
- Compatibility matrix:
  - WordPress latest + previous major version.
  - PHP supported range.
  - Yoast, Rank Math, AIOSEO active/inactive combinations.
  - Cloudflare proxy on/off, common host caching plugins.

## 12) Success Metrics
- Publisher outcomes:
  - Increase in AI bot crawl coverage of priority pages.
  - Faster recrawl after content updates.
  - Improved content readiness score over time.
- Product outcomes:
  - Dashboard load time under target.
  - Low error rate on exports/reports.
  - High weekly active usage by site admins/agencies.

## 13) Immediate Next Actions
1. Confirm MVP scope cut for first release (recommended: Phase 1 + CSV).
2. Freeze DB schema + endpoint contracts.
3. Create plugin scaffold and implement Sprint 0.
4. Build Sprint 1 in parallel with conversion test fixtures.
