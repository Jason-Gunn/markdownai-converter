# MarkdownAI Converter — Environment Reference

## Purpose
This document describes where project files live, where the WordPress test environment is located, and which container/dev tooling is currently available.

## Start Here Next Session (Important)
1. Open this repo for source edits:
	- `C:\Users\info\OneDrive\Documents\Working Files\Projects\WordPress plugins\MarkdownAI Converter`
2. Confirm the plugin copy WordPress is actually executing:
	- `C:\Users\info\OneDrive\Documents\Working Files\Projects\WordPress plugins\WordPress Development Environment\wp-content\plugins\markdownai-converter`
3. If behavior in wp-admin does not match repo code, compare/sync these two locations before debugging features.

## Project Locations

### Plugin source workspace (authoring)
- `C:\Users\info\OneDrive\Documents\Working Files\Projects\WordPress plugins\MarkdownAI Converter`

### WordPress test environment workspace
- `C:\Users\info\OneDrive\Documents\Working Files\Projects\WordPress plugins\WordPress Development Environment`

### Active plugin deploy target in local WP env
- `C:\Users\info\OneDrive\Documents\Working Files\Projects\WordPress plugins\WordPress Development Environment\wp-content\plugins\markdownai-converter`

## Plugin File Map (current)

### Root files
- `markdownai-converter.php` — plugin bootstrap/header + lifecycle hooks registration
- `uninstall.php` — optional cleanup based on plugin setting

### Core include files
- `includes/class-mdai-plugin.php` — main boot orchestration and action hooks
- `includes/class-mdai-installer.php` — DB schema creation + daily cron aggregation/cleanup
- `includes/class-mdai-settings.php` — Settings API registration + sanitization/defaults
- `includes/class-mdai-markdown-service.php` — markdown generation and content cache persistence
- `includes/class-mdai-rest.php` — public REST endpoint: `/wp-json/mdai/v1/markdown/{id}`
- `includes/class-mdai-bot-detector.php` — user-agent to bot-family normalization
- `includes/class-mdai-bot-tracker.php` — bot event logging with privacy-safe IP hashing
- `includes/class-mdai-analytics.php` — KPI/trend/top-page analytics queries
- `includes/class-mdai-report.php` — report dataset builder for printable report
- `includes/class-mdai-demo-data.php` — synthetic event seeding/cleanup for testing
- `includes/class-mdai-suggestions.php` — rule-based content optimization scoring

### Admin files
- `includes/admin/class-mdai-admin.php` — admin pages + export/report/demo actions
- `includes/admin/class-mdai-bot-activity-table.php` — WP_List_Table implementation for activity logs

### Resource docs
- `resources/specifications.md` — product specification and roadmap
- `resources/testing-checklist.md` — end-to-end validation checklist
- `resources/environment.md` — this environment reference
- `resources/dev-updates.md` — implementation handoff log

## Database Tables Created by Plugin
On activation, plugin creates:
- `{wp_prefix}_mdai_content_cache`
- `{wp_prefix}_mdai_bot_events`
- `{wp_prefix}_mdai_daily_aggregates`

## WordPress Environment Setup (external project)

### Docker Compose
From `WordPress Development Environment/docker-compose.yml`:
- `db` service: `mysql:5.7`
- `wordpress` service: `wordpress:latest`
- Port mapping: `8080:80`
- `wp-content` bind mount: `./wp-content:/var/www/html/wp-content`
- Additional plugin bind mount currently references another plugin path (`LiteNiteLite`).

### Devcontainer
From `.devcontainer/devcontainer.json`:
- Base image: `mcr.microsoft.com/vscode/devcontainers/php:8.2`
- Feature: Composer installed via devcontainer feature
- VS Code extensions include Intelephense, PHPCS, Xdebug, Copilot, GitLens

## Sync/Deploy Workflow Between Workspaces
Current sync pattern used during development:
1. Build/edit in plugin source workspace.
2. Copy all files into WordPress test environment plugin folder.
3. Activate plugin in wp-admin and test.

### Verified difference discovered on 2026-02-23
- WordPress was running the plugin from the `WordPress Development Environment\wp-content\plugins\markdownai-converter` folder while some edits were being made only in the source repo folder.
- This caused "changes not visible" confusion until files were synced.
- Treat the WP deploy target path as source-of-truth for runtime behavior.

PowerShell command used:
```powershell
$source = "C:\Users\info\OneDrive\Documents\Working Files\Projects\WordPress plugins\MarkdownAI Converter"
$dest = "C:\Users\info\OneDrive\Documents\Working Files\Projects\WordPress plugins\WordPress Development Environment\wp-content\plugins\markdownai-converter"
if (Test-Path $dest) { Remove-Item -Path $dest -Recurse -Force }
New-Item -Path $dest -ItemType Directory | Out-Null
Copy-Item -Path (Join-Path $source '*') -Destination $dest -Recurse -Force
```

## Runtime Notes
- Plugin assumes WordPress admin capabilities (`manage_options`) for all admin/report/export actions.
- REST markdown endpoint is public for published/viewable posts only.
- Bot tracking can be disabled in plugin Settings.
- Retention window defaults to 90 days and is enforceable by cron cleanup.
- Native PDF depends on bundled vendor library presence; if unavailable, UI falls back to printable HTML report flow.

## Tooling Notes (Current Machine)
- `composer` is not available in the current host shell.
- Practical implication: dependency installation/bundling should be done in a container/devcontainer/CI build step, then shipped with release artifacts as needed.

## Security Notes
- Admin actions use nonces and capability checks.
- SQL queries use `$wpdb->prepare()` for dynamic parameters.
- IP addresses are stored as salted hashes, not raw values.
- Uninstall data deletion is opt-in via settings.
