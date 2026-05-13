# LuluDesk — AI Chat with Memory

> WordPress plugin for [LuluDesk](https://luluclaw.com) — AI chat that remembers your visitors across sessions.

[![WordPress Plugin](https://img.shields.io/badge/WordPress-6.0%2B-blue)](https://wordpress.org/plugins/luludesk-chat-memory/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)](https://php.net)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## What this plugin does

Injects the LuluDesk widget script into your WordPress site's `wp_footer`. Visitors get a persistent AI chat that remembers them across sessions — no session-resets, no "Hi, how can I help you?" every time.

**One-time setup:**
1. Sign up at [luluclaw.com](https://luluclaw.com)
2. Copy your install token from the dashboard
3. Paste it into **WP Admin → Settings → LuluDesk**
4. Done — the launcher button appears immediately

---

## Features (v1.0.0)

- Token input with format validation (`wt_<uuid>`)
- One-click **Test Connection** button (server-side HEAD request, no CORS)
- **Widget enabled** / **Auto-inject** toggle
- **Page targeting**: all pages / homepage only / include list / exclude list
- Daily health heartbeat (version numbers only, no PII)
- Clean uninstall removes all `luludesk_*` options and cron events

---

## WordPress.org

Plugin listing: **coming soon — currently in review**

---

## Developer installation

```bash
# Clone into your WP plugins directory
git clone https://github.com/TooFaded420/luludesk-wordpress.git \
  wp-content/plugins/luludesk-chat-memory

# Or via WP-CLI
wp plugin install luludesk-chat-memory --activate
```

**Requirements:** PHP 7.4+, WordPress 6.0+

---

## File structure

```
luludesk-chat-memory/
  luludesk-chat-memory.php       # Plugin entry point + headers
  readme.txt                      # WP.org submission readme
  uninstall.php                   # Clean removal handler
  assets/
    admin.js                      # Settings page JS (test-connection XHR)
    icon-128x128.png              # TODO: design icon
    icon-256x256.png              # TODO: design icon
    banner-772x250.png            # TODO: design banner
    screenshot-*.png              # TODO: take screenshots
  includes/
    class-luludesk-plugin.php    # Singleton boot
    class-luludesk-settings.php  # Admin settings page + AJAX handler
    class-luludesk-injector.php  # wp_footer injection + page targeting
    class-luludesk-telemetry.php # Daily heartbeat cron
```

---

## Contributing

1. Fork the repo
2. Create a feature branch (`git checkout -b feat/my-feature`)
3. Ensure `php -l` passes on PHP 7.4, 8.0, and 8.2
4. Open a pull request

Code style follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).

---

## Privacy

This plugin does **not** send WordPress user data, visitor IP addresses, or page content to LuluDesk. See the [Privacy section in readme.txt](readme.txt) for full details.

---

## License

GPL v2 or later. See [LICENSE](../LICENSE).
