# luludesk-wordpress

WordPress plugin repository for **LuluDesk — AI Chat with Memory**.

This is the official WordPress plugin for [LuluDesk](https://luluclaw.com) — a hosted AI chat platform that gives your visitors persistent memory across sessions.

WordPress.org listing: **coming soon — currently in review**

---

## Quick start

### Install from WordPress.org (recommended once listed)

1. WP Admin → Plugins → Add New → search "LuluDesk"
2. Install & Activate
3. Settings → LuluDesk → enter your install token

### WP-CLI

```bash
wp plugin install luludesk-chat-memory --activate
```

### Developer / manual install

```bash
git clone https://github.com/TooFaded420/luludesk-wordpress.git \
  path/to/wp-content/plugins/luludesk-chat-memory
```

Then activate via WP Admin or `wp plugin activate luludesk-chat-memory`.

---

## Repository layout

```
.github/workflows/     GitHub Actions — PHP lint + WPCS
luludesk-chat-memory/  The distributable plugin directory
  ├── luludesk-chat-memory.php
  ├── readme.txt           (WP.org submission)
  ├── readme.md
  ├── CHANGELOG.md
  ├── uninstall.php
  ├── assets/
  └── includes/
```

---

## Contributing

1. Fork and clone this repo
2. Create a branch: `git checkout -b feat/your-feature`
3. Make changes inside `luludesk-chat-memory/`
4. Ensure all PHP files pass `php -l` on PHP 7.4, 8.0, and 8.2
5. Open a pull request against `main`

Code style: [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/).

---

## License

GPL v2 or later. See [LICENSE](LICENSE).
