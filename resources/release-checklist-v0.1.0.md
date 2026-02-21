# MarkdownAI Converter — Release Checklist v0.1.0

## Release Scope
This checklist is for shipping initial public release `v0.1.0` from branch `main`.

## 1) Code and Version Readiness
- [x] Plugin header version set to `0.1.0` in `markdownai-converter.php`
- [x] `MDAI_PLUGIN_VERSION` constant set to `0.1.0`
- [x] License file present (`MIT`)
- [x] README present and aligned with implemented features
- [x] Environment and handoff docs present in `resources/`

## 2) Functional QA (WordPress Admin)
- [ ] Activate plugin successfully
- [ ] Verify custom tables created (`mdai_content_cache`, `mdai_bot_events`, `mdai_daily_aggregates`)
- [ ] Content Preview generates markdown and regenerates via nonce action
- [ ] REST endpoint works for published posts: `/wp-json/mdai/v1/markdown/{id}`
- [ ] Bot Activity logs are visible, sortable, filterable, and CSV export downloads
- [ ] Overview shows KPIs, trend graph, top crawled pages, and KPI deltas
- [ ] Suggestions page returns score + recommendations
- [ ] Export & Reports page generates printable report
- [ ] Weekly report settings save correctly
- [ ] “Send Test Weekly Report Now” sends mail (or fails with useful notice)

## 3) Security and Permission QA
- [ ] Non-admin cannot access plugin admin pages
- [ ] Admin post actions reject invalid/missing nonce
- [ ] Export and report actions require `manage_options`
- [ ] Endpoint returns 404/403 for non-viewable content

## 4) Data and Retention QA
- [ ] Demo data seed creates 90-day activity history
- [ ] Demo data clear removes seeded rows only
- [ ] Retention days setting is enforced by daily cron cleanup

## 5) Environment/Tooling Notes
- [x] VS Code diagnostics clean (`No errors found` in workspace checks)
- [ ] PHP CLI lint run (`php -l`) — blocked in current shell (`php` not available on PATH)
  - Suggested alternative: run lint inside devcontainer or WordPress Docker container

## 6) Git Release Prep
- [ ] Ensure working tree is clean before tagging
- [ ] Confirm `origin/main` contains release commit
- [ ] Create annotated tag:
  - `git tag -a v0.1.0 -m "Release v0.1.0"`
- [ ] Push tag:
  - `git push origin v0.1.0`

## 7) GitHub Release Publication
- [ ] Create GitHub release from tag `v0.1.0`
- [ ] Title: `MarkdownAI Converter v0.1.0`
- [ ] Paste release notes from `resources/release-notes-v0.1.0.md`
- [ ] Mark as latest release

## Suggested Final Verification Command Set
```powershell
git status --short
git log --oneline -5
```
