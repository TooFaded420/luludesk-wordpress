<?php
/**
 * Uninstall handler — runs when the plugin is deleted from WP Admin.
 * Removes all plugin options and clears scheduled cron events.
 * Does NOT touch any LuluDesk server-side data.
 *
 * @package LuluDesk_Chat_Memory
 */

// Guard: only run when WordPress calls this file directly via delete_plugin().
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete all luludesk_* options.
$luludesk_options = array(
	'luludesk_install_token',
	'luludesk_widget_enabled',
	'luludesk_auto_inject',
	'luludesk_allowed_pages',
	'luludesk_allowed_urls',
	'luludesk_last_heartbeat',
	'luludesk_latest_version',
	// Knowledge Base (v1.1.0+).
	'luludesk_wp_api_key',
	'luludesk_kb_source_id',
	'luludesk_kb_included_post_types',
	'luludesk_kb_connected_at',
	'luludesk_kb_last_update_sent_at',
);

foreach ( $luludesk_options as $option ) {
	delete_option( $option );
}

// Clear the daily heartbeat cron event.
$cron_hook = 'luludesk_daily_heartbeat';
$timestamp  = wp_next_scheduled( $cron_hook );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, $cron_hook );
}
