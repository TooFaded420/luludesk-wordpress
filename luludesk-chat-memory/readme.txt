=== LuluDesk — AI Chat with Memory ===
Contributors: luludesk
Tags: ai-chat, chatbot, ai-assistant, customer-support, knowledge-base
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 1.0.0
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
* **Privacy-respecting** — this plugin does NOT send your WordPress user data, page content, or visitor PII to LuluDesk servers in v1. Only visitor chat messages reach LuluDesk. A daily plugin-health heartbeat sends version numbers only (no site URL, no personal data).
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

If you uncheck **Auto-inject in Footer**, the script tag is not added automatically. You can then call `do_action('luludesk_render_widget')` inside your theme template to control placement precisely. (This hook is on the v1.1 roadmap.)

== Screenshots ==

1. LuluDesk settings page in WP Admin → Settings → LuluDesk.
2. Green "Connected" confirmation after entering a valid install token.
3. The chat launcher button in the bottom-right corner of a live WordPress site.
4. Chat widget open, showing a returning visitor conversation with memory context.

== Changelog ==

= 1.0.0 =
* Initial release.
* Admin settings page: token input, Test Connection, page-targeting options.
* Automatic `wp_footer` script injection with per-page filtering.
* Daily heartbeat telemetry (version numbers only, no PII).
* Clean uninstall: removes all plugin options and cron events.

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade steps required.
