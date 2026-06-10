=== LuluDesk — AI Chat with Memory ===
Contributors: luludesk
Tags: ai-chat, chatbot, ai-assistant, customer-support, knowledge-base
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 1.1.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI chat widget that remembers your visitors across sessions. Zero-config install — paste your token and go.

== Description ==

**LuluDesk** brings persistent AI-powered chat to your WordPress site. Unlike disposable chat widgets that start every conversation from scratch, LuluDesk remembers each visitor across sessions — so your AI can greet returning customers by name, recall past questions, and give smarter answers over time.

= How it works =

1. Sign up for free at [luluclaw.com](https://luluclaw.com).
2. Copy your install token from the LuluDesk dashboard.
3. Paste it into **Settings → LuluDesk** and save.
4. The chat launcher button appears on your site immediately.

That's it. No server setup, no build steps, no API keys to manage yourself.

= Why LuluDesk? =

* **Memory across sessions** — the defining wedge. Your AI assistant knows who it's talking to.
* **Zero-config** — one token, one save, done.
* **Free tier available** — start for free; upgrade when you need more conversations or advanced knowledge-base features.
* **Privacy-respecting** — this plugin sends data to LuluDesk's cloud service. See the 'External Services' section below for the complete list of endpoints and what is sent.
* **Works with any theme** — pure `wp_footer` injection, no shortcodes required (though a manual shortcode path is on the roadmap).

= Page targeting =

Choose where the widget appears:

* All pages
* Homepage only
* Specific URLs (partial match, one per line)
* All pages *except* specific URLs

= Telemetry transparency =

Once per day the plugin sends a small health ping to LuluDesk containing:

* Plugin version, WordPress version, PHP version
* Whether a token is set (boolean)
* Whether the widget is enabled (boolean)

No site URL. No visitor data. No WordPress user data. You can verify this in `includes/class-luludesk-telemetry.php`.

== Installation ==

**From the WordPress Plugin Directory (recommended):**

1. Go to **Plugins → Add New** in your WP Admin.
2. Search for "LuluDesk".
3. Click **Install Now**, then **Activate**.
4. Go to **Settings → LuluDesk** and enter your install token.

**Manual upload:**

1. Download the plugin ZIP from the WordPress Plugin Directory.
2. Go to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP and activate.
4. Go to **Settings → LuluDesk** and enter your install token.

**WP-CLI:**

`wp plugin install luludesk-chat-memory --activate`

== Frequently Asked Questions ==

= Do I need a LuluDesk account? =

Yes. Sign up for free at [luluclaw.com](https://luluclaw.com). The free tier includes a generous conversation quota — no credit card required to start.

= Is the widget free? =

The WordPress plugin is free forever. LuluDesk has a free tier; paid plans unlock higher conversation volumes and advanced knowledge-base features. All feature gating happens server-side — the plugin itself never changes.

= Does it work on WordPress.com? =

On WordPress.com, third-party plugins require the **Business plan** or higher. On self-hosted WordPress (wordpress.org), it works on all plans.

= What data does this plugin send to LuluDesk? =

* **Visitor chat messages** — sent directly from the visitor's browser to LuluDesk servers via the widget script. The plugin itself does not intercept or relay chat messages.
* **Daily health heartbeat** — plugin version, WordPress version, PHP version, and two boolean flags (token set / widget enabled). No site URL, no IP address, no personal data.

See the 'External Services' section for the complete list of all endpoints and what is transmitted.

= Does it collect WordPress user data? =

No. In v1 the plugin does not read, send, or expose any WordPress user profile data.

= Can I delete my LuluDesk data? =

Yes. Log in to [luluclaw.com/app](https://luluclaw.com/app), go to your workspace settings, and use the data-deletion options there. Uninstalling this plugin removes all local options but leaves your LuluDesk account and conversation history intact until you delete them from the dashboard.

= The widget isn't showing. What should I check? =

1. Confirm the install token is saved (**Settings → LuluDesk**).
2. Click **Test Connection** — a green confirmation means the token is reachable.
3. Ensure **Widget Enabled** and **Auto-inject in Footer** are both checked.
4. Check the **Show Widget On** radio — it may be set to a page filter that excludes your current page.
5. Check that your theme calls `wp_footer()` — a small number of older themes omit this.

= Can I place the widget manually? =

If you uncheck **Auto-inject in Footer**, the script tag is not added automatically. You can then call `luludesk_render_widget()` or `do_action('luludesk_render_widget')` inside your theme template to control placement precisely.

== Screenshots ==

1. LuluDesk settings page in WP Admin → Settings → LuluDesk.
2. Green "Connected" confirmation after entering a valid install token.
3. The chat launcher button in the bottom-right corner of a live WordPress site.
4. Chat widget open, showing a returning visitor conversation with memory context.

== External Services ==

This plugin connects to LuluDesk's cloud service (https://luluclaw.com) for AI chat
functionality. The following data is sent to LuluDesk's servers:

1. **Widget script load** (`https://luluclaw.com/widget/v1/{install_token}.js`)
   * What: A `<script>` tag is injected into your site footer on pages where the
     widget is enabled. The browser of every visitor loads this script.
   * Data sent: your install_token (visible in page HTML), and standard browser
     headers (user agent, referrer, IP) sent by the visitor's browser.
   * When: on every page load while the widget is enabled.

2. **Plugin telemetry heartbeat** (`POST https://luluclaw.com/api/integrations/wordpress/heartbeat`)
   * What: a daily ping confirming the plugin is installed.
   * Data sent: plugin version, WordPress version, PHP version, your install_token
     (if set), and a boolean indicating whether the widget is enabled. No site URL,
     no visitor data, no page content.
   * When: once per day, scheduled via WP Cron.

3. **Content sync webhooks** (`POST https://luluclaw.com/api/integrations/wordpress/webhook`)
   * What: once the Knowledge Base is connected, publishing, updating, or deleting
     a post of an included type sends a small signed payload so LuluDesk can
     refresh its knowledge of your site.
   * Data sent: post ID, post type, permalink, modification timestamp, post title,
     and a short content excerpt (first 500 chars, plain text), signed with
     HMAC-SHA256 using your connection key.
   * When: only after Knowledge Base is connected, on every post save/delete.

Note on connecting the Knowledge Base: you generate your connection credentials
(an API key and Source ID) inside the LuluDesk dashboard and paste them into the
plugin's Knowledge Base tab. The plugin does NOT send your site URL or call a
connect endpoint from your server — connecting and running a full re-index both
happen in the dashboard (they require an authenticated dashboard session that a
WordPress server cannot supply). After the initial connection, the incremental
webhook above keeps your content in sync automatically.

LuluDesk's terms of service: https://luluclaw.com/terms
LuluDesk's privacy policy: https://luluclaw.com/privacy

You can stop all data flow by deactivating the plugin. Uninstalling clears
all locally-stored plugin options.

== Changelog ==

= 1.1.2 =
* Fixed: daily heartbeat now sends the install token under the field name the LuluDesk server expects (`install_token`), so heartbeats are correctly attributed to your workspace.
* Fixed: Knowledge Base connection now uses a paste-credentials flow — generate your API key and Source ID in the LuluDesk dashboard, then paste them into the plugin. (The previous in-plugin "Connect" and "Sync now" buttons called dashboard-authenticated endpoints that a WordPress server cannot reach.)
* Fixed: post save/delete webhooks are no longer dispatched when WordPress cannot resolve a permalink, avoiding a server-side validation rejection.
* Added: "update available" awareness — the heartbeat now reads the latest published plugin version reported by the server.
* Housekeeping: uninstall now removes all Knowledge Base options; removed an unused endpoint constant; merged the WP.org submission-blocker fixes from 1.1.1.

= 1.1.1 =
* Fixed: readme.txt External Services disclosure now lists all endpoints and data sent (WP.org P1 requirement).
* Fixed: HTML in translated strings now uses wp_kses() to satisfy WP.org scanner.
* Fixed: mb_substr() now guarded with function_exists() + falls back to substr().
* Fixed: Full-sync POST request is now HMAC-SHA256 signed, same as webhooks.
* Fixed: Connect AJAX handler validates wp_api_key and kb_source_id before saving options.
* Added: luludesk_render_widget() function and action hook for manual widget placement.

= 1.1.0 =
* Knowledge Base tab: connect your WP site to LuluDesk KB, sync now button, last-sync status, post type selection.
* Auto-sync on publish: `save_post` hook sends signed webhook to LuluDesk when you publish or update a post.
* Auto-delete on trash: `before_delete_post` hook removes KB chunks when a published post is deleted.
* HMAC-SHA256 webhook signing (class-luludesk-webhook.php) — replay-window protection.
* Banner warning when WP REST API appears disabled (required for KB sync).

= 1.0.0 =
* Initial release.
* Admin settings page: token input, Test Connection, page-targeting options.
* Automatic `wp_footer` script injection with per-page filtering.
* Daily heartbeat telemetry (version numbers only, no PII).
* Clean uninstall: removes all plugin options and cron events.

== Upgrade Notice ==

= 1.1.2 =
Contract sync with the live LuluDesk backend (heartbeat attribution, Knowledge Base connection, webhook permalink handling) plus the 1.1.1 WP.org compliance fixes. No database changes required. If you use the Knowledge Base, re-connect from the new paste-credentials form under Settings → LuluDesk → Knowledge Base.

= 1.1.1 =
Bug fixes and WP.org compliance improvements. No database changes required.

= 1.1.0 =
New Knowledge Base sync feature. No database changes required. After upgrading, visit Settings → LuluDesk → Knowledge Base to connect your site.

= 1.0.0 =
Initial release. No upgrade steps required.
