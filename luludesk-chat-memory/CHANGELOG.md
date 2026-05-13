# Changelog

All notable changes to **LuluDesk — AI Chat with Memory** are documented here.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

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
