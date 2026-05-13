# Changelog

All notable changes to **LuluDesk — AI Chat with Memory** are documented here.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

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
