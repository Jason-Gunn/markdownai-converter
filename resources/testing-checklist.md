# MarkdownAI Converter — Test Checklist (Current Build)

## 1) Install/Activate
1. Copy plugin to `wp-content/plugins/markdownai-converter` (already synced).
2. In wp-admin, activate **MarkdownAI Converter**.
3. Open **MarkdownAI Converter → Settings** and save once.

## 2) Verify DB Tables Created
Check your database for:
- `{wp_prefix}_mdai_content_cache`
- `{wp_prefix}_mdai_bot_events`
- `{wp_prefix}_mdai_daily_aggregates`

## 3) Test Content Preview
1. Go to **MarkdownAI Converter → Content Preview**.
2. Select a published page/post.
3. Confirm generated markdown appears in textarea.
4. Click **Regenerate Markdown** and confirm output refreshes.

## 4) Test Public Markdown Endpoint
Use a published post ID:

PowerShell:
```powershell
Invoke-RestMethod "https://YOUR-SITE/wp-json/mdai/v1/markdown/123"
```

Expected:
- HTTP 200 for published/viewable content.
- JSON includes: `post_id`, `post_type`, `source_url`, `modified_gmt`, `markdown`.

## 5) Generate Bot/Event Traffic
Run endpoint calls repeatedly (manual or script) to generate data:

```powershell
1..20 | ForEach-Object { Invoke-WebRequest "https://YOUR-SITE/wp-json/mdai/v1/markdown/123" -Headers @{"User-Agent"="GPTBot/1.0"} | Out-Null }
1..10 | ForEach-Object { Invoke-WebRequest "https://YOUR-SITE/wp-json/mdai/v1/markdown/123" -Headers @{"User-Agent"="ClaudeBot/1.0"} | Out-Null }
```

### Fast path: seeded demo data (3 months)
1. Open **MarkdownAI Converter → Overview**.
2. Click **Seed 90 Days Demo Data**.
3. Wait for success notice and reload dashboard views.
4. Use **Clear Demo Data** to remove all seeded records.

## 6) Test Bot Activity Admin
1. Open **MarkdownAI Converter → Bot Activity**.
2. Confirm rows appear with timestamp, family, status, latency, bytes, endpoint.
3. Sort columns (e.g., latency, bytes).
4. Filter by date and family views.
5. Click **Export CSV** and verify downloaded file contents.

## 7) Test Overview Dashboard
1. Open **MarkdownAI Converter → Overview**.
2. Validate KPI table updates with selected range.
3. Confirm trend graph appears after traffic exists.
4. Confirm top crawled pages table lists expected pages.

## 8) Test Suggestions Engine
1. Open **MarkdownAI Converter → Suggestions**.
2. Select a published page and click **Analyze Content**.
3. Confirm score and recommendations appear.
4. Validate low-scored content appears near top of **Recent Content Health** table.
5. Edit a page (add headings/links/alt text), re-analyze, and verify score improves.

## 9) Test Settings / Retention
1. In **Settings**, change retention days and save.
2. Disable tracking and verify new endpoint requests are no longer added to bot events.

## 10) Test Export & Reports
1. Open **MarkdownAI Converter → Export & Reports**.
2. Select a date range and click **Apply Range**.
3. Click **Export Bot Activity (CSV)** and verify filtered output.
4. Click **Generate Printable Report (PDF-ready)**.
5. In the report page, click **Print / Save as PDF** and verify the output contains KPIs, trend graph, top pages, and top opportunities.

## 11) Security Sanity Checks
- Non-admin user cannot access plugin admin pages.
- Export endpoint rejects request without nonce/capability.
- Unpublished/private post IDs return 404/403 from REST endpoint.

## 12) Uninstall Behavior
1. Enable **Delete data on uninstall**.
2. Uninstall plugin.
3. Confirm options and custom tables are removed.
