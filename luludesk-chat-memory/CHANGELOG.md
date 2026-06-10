# Changelog

All notable changes to **LuluDesk — AI Chat with Memory** are documented here.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.1.2] — 2026-06-10

Contract sync with the live LuluDesk backend, merged on top of the 1.1.1 WordPress.org
submission-blocker fixes.

### Fixed
- **Heartbeat token field name drift.** The daily heartbeat sent the install token as `token`, but the LuluDesk server (`/api/integrations/wordpress/heartbeat`) only reads `install_token` (Zod schema). The token was silently dropped, so heartbeats were never attributed to a workspace. Renamed the payload field to `install_token` (`includes/class-luludesk-telemetry.php`).
- **Knowledge Base connect/sync drift.** The KB tab's "Connect" button POSTed to `/api/integrations/wordpress/connect` and "Sync now" POSTed to `/full-sync` — both endpoints are Clerk-session-gated and require a `workspace_id` that a WordPress server cannot supply, so both always failed with 401. Reworked the KB tab to a paste-credentials flow: the user generates `wp_api_key` + `kb_source_id` in the LuluDesk dashboard and pastes them into the plugin (validated as `wpk_<64 hex>` and a UUID). Full sync is now linked out to the dashboard; incremental webhooks keep the KB current thereafter (`includes/class-luludesk-kb-settings.php`, `assets/kb-admin.js`). This supersedes the 1.1.1 `ajax_sync()` HMAC-signing and `ajax_connect()` validation changes, because those server-to-server handlers no longer exist.
- **Empty-permalink webhook rejection.** When `get_permalink()` returned false the plugin sent `permalink => ''`, which the server rejects (it validates `permalink` as a URL → 400). The save/delete handlers now skip dispatch entirely when no permalink is available (`luludesk-chat-memory.php`).

### Added
- **Update-available awareness.** The heartbeat response includes `plugin_latest_version`; the plugin now stores it in the `luludesk_latest_version` option for a future update notice (`includes/class-luludesk-telemetry.php`).
- Real WordPress.org brand assets (banner + icon PNGs) authored from the LuluDesk design system, plus a `scripts/` zip builder that produces `dist/luludesk-chat-memory.zip` rooted at `luludesk-chat-memory/`.

### Housekeeping
- `uninstall.php` now removes all Knowledge Base options (`luludesk_wp_api_key`, `luludesk_kb_source_id`, `luludesk_kb_included_post_types`, `luludesk_kb_connected_at`, `luludesk_kb_last_update_sent_at`) and `luludesk_latest_version`.
- Removed an unused `FULL_SYNC_ENDPOINT` constant from `class-luludesk-webhook.php`.
- Retained the 1.1.1 WP.org fixes: External Services disclosure, `wp_kses()` for HTML-in-i18n (including the new KB paste-form copy), `mb_substr()` guard, and the `luludesk_render_widget()` placement hook.

## [1.1.1] — 2026-05-13

### Fixed
- `readme.txt` External Services disclosure now lists all 5 endpoints and the exact data sent to each (WP.org P1 requirement).
- HTML in translated strings (`printf()` with markup) refactored to use `wp_kses()` in `class-luludesk-settings.php` and `class-luludesk-kb-settings.php` (WP.org P1 scanner finding).
- `mb_substr()` call in `luludesk-chat-memory.php` guarded with `function_exists()`, with `substr()` fallback for environments where mbstring is not installed.
- Full-sync POST (`ajax_sync()`) is now HMAC-SHA256 signed using `LuluDesk_Webhook::sign()`, matching the webhook signing pattern.
- Connect AJAX handler now validates that `wp_api_key` and `kb_source_id` are non-empty in the LuluDesk response before persisting them; returns HTTP 500 + error message if either is missing.

### Added
- `luludesk_render_widget()` function and `luludesk_render_widget` action hook for manual widget placement in custom themes.

## [1.1.0] — 2026-05-13

### Added
- Knowledge Base tab under WP Admin → Settings → LuluDesk → Knowledge Base
- "Connect to LuluDesk Knowledge Base" button: POSTs to LuluDesk /connect, stores wp_api_key and kb_source_id as WP options
- "Sync now" button: triggers full re-index via LuluDesk /full-sync endpoint
- "Last sync: X ago" status block on KB settings tab
- Post type checkboxes for configuring which content types are included in the KB sync
- REST API accessibility check banner (warns when WP REST API appears disabled)
- `save_post` webhook hook (priority 99): sends HMAC-SHA256 signed `post.saved` event to LuluDesk on every published post save (non-blocking, async via `wp_remote_post` with `blocking => false`)
- `before_delete_post` webhook hook: sends `post.deleted` event when a previously-published post is deleted
- `includes/class-luludesk-webhook.php`: HMAC-SHA256 signing helper class — signs `timestamp.body` payload with wp_api_key
- `assets/kb-admin.js`: admin JS for KB tab (connect, sync, REST check)
- Security note: wp_api_key stored as plain WP option (WP has no native encryption API; database-level encryption recommended for high-security environments)

## [1.0.0] — 2026-05-13

### Added
- Admin settings page under WP Admin → Settings → LuluDesk
- Install token input with format validation (`wt_<uuid>`)
- Test Connection button (server-side HEAD request to widget CDN)
- Widget enabled toggle (default: on)
- Auto-inject in footer toggle (default: on)
- Page targeting: all pages / homepage only / specific URLs / all except specific URLs
- Automatic `wp_footer` script injection with `async` attribute and plugin-version data attribute
- Daily heartbeat cron — sends plugin version, WP version, PHP version, boolean flags only
- Status block showing plugin version and last heartbeat timestamp
- Clean uninstall: removes all `luludesk_*` options and cancels cron event
- GPL v2 license
- WP.org-format readme.txt
