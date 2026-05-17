# wordpress.org Submission Runbook — LuluDesk Chat Memory

Status as of this PR: **ready for screenshot capture, then submit.**

## What's already done

| Item | Status | Evidence |
|---|---|---|
| Plugin header (Name, Version, License, Text Domain) | ✅ | `luludesk-chat-memory.php` lines 1-15 |
| GPLv2 LICENSE file at repo root | ✅ | `LICENSE` |
| `readme.txt` in wp.org format | ✅ | Description, Installation, FAQ, External Services, Screenshots, Changelog all populated |
| `Tested up to` bumped to current WP | ✅ | `readme.txt` line 5 → `6.7` |
| Settings API + nonce coverage | ✅ | `register_setting` x5, `check_ajax_referer` x4 |
| `$_POST`/`$_GET` sanitization | ✅ | `sanitize_text_field` + `wp_unslash`, `sanitize_key` |
| Token regex validation | ✅ | `class-luludesk-settings.php:177` |
| `current_user_can('manage_options')` guards | ✅ | All admin handlers |
| No `eval()`, `base64_decode`, CDN-loaded JS | ✅ | grep clean |
| Escape coverage (`esc_html`/`esc_attr`/`esc_url`/`esc_textarea`) | ✅ | Every PHP echo into HTML is escaped with the correct primitive |
| `uninstall.php` cleans options | ✅ | wp.org reviewers love this |
| Plugin slug `luludesk-chat-memory` available | ✅ | wp.org returns search redirect (no existing plugin) |
| Icon 128x128 + 256x256 | ✅ | `assets/icon-{128,256}x{128,256}.png` (brand mark, chat bubble + memory dots) |
| Banner 772x250 + 1544x500 | ✅ | `assets/banner-{772x250,1544x500}.png` |
| `== Screenshots ==` captions in readme.txt | ✅ | 4 captions, lines 116-119 |

## What still blocks submission

### Screenshots (4 files) — MUST be real captures

wp.org reviewers reject AI-generated UI mockups. Each screenshot must be a real capture of the plugin running in WordPress admin.

Capture procedure:

1. Spin up a clean WP 6.7 install. Easiest path:
   ```bash
   docker run --rm -p 8080:80 \
     -v $(pwd)/luludesk-chat-memory:/var/www/html/wp-content/plugins/luludesk-chat-memory \
     wordpress:6.7
   ```
2. Activate **LuluDesk — AI Chat with Memory** in Plugins.
3. Sign up at https://luluclaw.com and grab an install token (`wt_*`).
4. Capture screenshots at exactly 1280px wide (wp.org standard):
   - **screenshot-1.png** — Settings → LuluDesk page, empty token field, "Test Connection" button visible.
   - **screenshot-2.png** — Same page after pasting a valid token and clicking Test Connection. Green "Connected" badge visible.
   - **screenshot-3.png** — A page on the live WP frontend showing the LuluDesk chat launcher button in the bottom-right corner.
   - **screenshot-4.png** — Chat widget open, with a 2-3 message conversation that demonstrates memory ("Welcome back, [name]…").
5. Save all four into `luludesk-chat-memory/assets/` and delete the matching `.png.txt` placeholders.

### wp.org author account (manual user step)

1. Create account at https://wordpress.org/support/register.php using `jrlop99@gmail.com` (or whichever address you want associated with the plugin).
2. Enable 2FA in your account profile.
3. Once screenshots are in, build the submission zip:
   ```bash
   cd luludesk-chat-memory
   zip -r ../luludesk-chat-memory.zip . \
     -x "assets/*" "assets/generate-brand-assets.py" "readme.md" "CHANGELOG.md" \
        "*.png.txt"
   ```
   (wp.org marketing assets live in SVN `/assets/`, not in the plugin zip.)
4. Submit at https://wordpress.org/plugins/developers/add/ — review queue is 1-14 days.
5. After approval, you'll get SVN credentials. Commit the plugin to `/trunk/`, tag releases under `/tags/1.1.1/`, and upload icon/banner/screenshots to `/assets/`.

## Why the audit took less work than the readiness scan suggested

The initial scan flagged 4 hard blockers; only 2 were real:

- **"esc_attr count is 3, likely gaps"** — false positive. `esc_attr` is just one of four escape primitives. Every echo into HTML in this plugin uses the right one (`esc_url` for hrefs, `esc_textarea` for textarea bodies, `esc_html` for text content, `esc_attr` for attribute values).
- **"empty Screenshots section"** — false positive. Captions were already at lines 116-119; the original grep didn't read enough lines after the section header.

The two real items were the placeholder PNGs (3 of 7 resolved here) and the `Tested up to` bump (resolved here).
