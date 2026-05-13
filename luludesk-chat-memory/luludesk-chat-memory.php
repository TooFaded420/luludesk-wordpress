<?php
/**
 * Plugin Name:       LuluDesk — AI Chat with Memory
 * Plugin URI:        https://luluclaw.com/wordpress
 * Description:       AI chat widget that remembers your visitors across sessions. Zero-config install.
 * Version:           1.1.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            LuluDesk
 * Author URI:        https://luluclaw.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       luludesk-chat-memory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LULUDESK_VERSION', '1.1.1' );
define( 'LULUDESK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LULUDESK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LULUDESK_PLUGIN_FILE', __FILE__ );

require_once LULUDESK_PLUGIN_DIR . 'includes/class-luludesk-plugin.php';
require_once LULUDESK_PLUGIN_DIR . 'includes/class-luludesk-settings.php';
require_once LULUDESK_PLUGIN_DIR . 'includes/class-luludesk-injector.php';
require_once LULUDESK_PLUGIN_DIR . 'includes/class-luludesk-telemetry.php';
require_once LULUDESK_PLUGIN_DIR . 'includes/class-luludesk-webhook.php';
require_once LULUDESK_PLUGIN_DIR . 'includes/class-luludesk-kb-settings.php';

/**
 * Boot the plugin.
 */
function luludesk_init() {
	LuluDesk_Plugin::get_instance();

	// Boot KB settings (registers AJAX handlers and settings).
	new LuluDesk_KB_Settings();
}
add_action( 'plugins_loaded', 'luludesk_init' );

/**
 * Activation hook — set default options.
 */
function luludesk_activate() {
	add_option( 'luludesk_widget_enabled', '1' );
	add_option( 'luludesk_auto_inject', '1' );
	add_option( 'luludesk_allowed_pages', 'all' );
	add_option( 'luludesk_allowed_urls', '' );
	add_option( 'luludesk_install_token', '' );
	// KB defaults — empty until user connects.
	add_option( 'luludesk_wp_api_key', '' );
	add_option( 'luludesk_kb_source_id', '' );
	add_option( 'luludesk_kb_included_post_types', array( 'page', 'post' ) );
}
register_activation_hook( __FILE__, 'luludesk_activate' );

// ── luludesk_render_widget() — manual widget placement helper ─────────────────

/**
 * Render the LuluDesk widget script inline.
 *
 * Call this function directly in your theme template, or fire it via
 * do_action( 'luludesk_render_widget' ), when Auto-inject in Footer is
 * disabled and you need precise placement control.
 *
 * Example (theme template):
 *   <?php luludesk_render_widget(); ?>
 * or:
 *   <?php do_action( 'luludesk_render_widget' ); ?>
 */
function luludesk_render_widget() {
	if ( class_exists( 'LuluDesk_Injector' ) ) {
		( new LuluDesk_Injector() )->inject_script();
	}
}
add_action( 'luludesk_render_widget', 'luludesk_render_widget' );

// ── save_post webhook (priority 99 — after most other handlers) ───────────────

/**
 * Fires after a post is saved to the database.
 *
 * Skips:
 *   - Auto-saves and revisions.
 *   - Non-published posts (drafts, private, etc.) — only public content.
 *   - When the KB is not connected (no wp_api_key stored).
 *
 * Sends an async (non-blocking) POST to LuluDesk so the WP admin save flow
 * is not slowed down.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an update.
 */
function luludesk_on_save_post( $post_id, $post, $update ) {
	// Skip auto-saves and revisions.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Only sync published posts.
	if ( 'publish' !== $post->post_status ) {
		return;
	}

	// Only sync post types included in the KB setting.
	$included_types = get_option( 'luludesk_kb_included_post_types', array( 'page', 'post' ) );
	if ( ! is_array( $included_types ) || ! in_array( $post->post_type, $included_types, true ) ) {
		return;
	}

	// Build the webhook instance (returns null when KB is not connected).
	$webhook = LuluDesk_Webhook::from_options();
	if ( null === $webhook ) {
		return;
	}

	$permalink     = get_permalink( $post_id );
	$content         = wp_strip_all_tags( $post->post_content );
	$content_excerpt = function_exists( 'mb_substr' )
		? mb_substr( $content, 0, 500 )
		: substr( $content, 0, 500 );

	$payload = array(
		'event'           => 'post.saved',
		'post_id'         => $post_id,
		'post_type'       => $post->post_type,
		'permalink'       => $permalink ? $permalink : '',
		'modified_gmt'    => $post->post_modified_gmt,
		'title'           => $post->post_title,
		'content_excerpt' => $content_excerpt,
	);

	$webhook->dispatch( $payload );
}
add_action( 'save_post', 'luludesk_on_save_post', 99, 3 );

// ── before_delete_post webhook ────────────────────────────────────────────────

/**
 * Fires before a post is deleted.
 *
 * Only fires for previously-published posts — avoids noise from
 * draft/private/auto-draft deletions that were never in the KB.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object being deleted.
 */
function luludesk_on_before_delete_post( $post_id, $post ) {
	// Skip revisions.
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Only signal deletion when the post was published (in the KB).
	// Drafts / private posts were never synced, so no chunks to remove.
	if ( 'publish' !== $post->post_status ) {
		return;
	}

	// Only for included post types.
	$included_types = get_option( 'luludesk_kb_included_post_types', array( 'page', 'post' ) );
	if ( ! is_array( $included_types ) || ! in_array( $post->post_type, $included_types, true ) ) {
		return;
	}

	$webhook = LuluDesk_Webhook::from_options();
	if ( null === $webhook ) {
		return;
	}

	$permalink = get_permalink( $post_id );

	$payload = array(
		'event'     => 'post.deleted',
		'post_id'   => $post_id,
		'post_type' => $post->post_type,
		'permalink' => $permalink ? $permalink : '',
	);

	$webhook->dispatch( $payload );
}
add_action( 'before_delete_post', 'luludesk_on_before_delete_post', 10, 2 );
