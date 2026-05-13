<?php
/**
 * Plugin Name:       LuluDesk — AI Chat with Memory
 * Plugin URI:        https://luluclaw.com/wordpress
 * Description:       AI chat widget that remembers your visitors across sessions. Zero-config install.
 * Version:           1.0.0
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

define( 'LULUDESK_VERSION', '1.0.0' );
define( 'LULUDESK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LULUDESK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LULUDESK_PLUGIN_FILE', __FILE__ );

require_once LULUDESK_PLUGIN_DIR . 'includes/class-luludesk-plugin.php';
require_once LULUDESK_PLUGIN_DIR . 'includes/class-luludesk-settings.php';
require_once LULUDESK_PLUGIN_DIR . 'includes/class-luludesk-injector.php';
require_once LULUDESK_PLUGIN_DIR . 'includes/class-luludesk-telemetry.php';

/**
 * Boot the plugin.
 */
function luludesk_init() {
	LuluDesk_Plugin::get_instance();
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
}
register_activation_hook( __FILE__, 'luludesk_activate' );
