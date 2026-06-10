<?php
/**
 * Daily heartbeat telemetry — no PII, no visitor data.
 *
 * @package LuluDesk_Chat_Memory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LuluDesk_Telemetry
 */
class LuluDesk_Telemetry {

	/**
	 * WP-Cron hook name.
	 */
	const CRON_HOOK = 'luludesk_daily_heartbeat';

	/**
	 * Heartbeat endpoint.
	 */
	const ENDPOINT = 'https://luluclaw.com/api/integrations/wordpress/heartbeat';

	/**
	 * Constructor — register cron and action.
	 */
	public function __construct() {
		add_action( self::CRON_HOOK, array( $this, 'send_heartbeat' ) );
		add_filter( 'cron_schedules', array( $this, 'add_daily_schedule' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'luludesk_daily', self::CRON_HOOK );
		}
	}

	/**
	 * Register a daily cron schedule if not already present.
	 *
	 * @param array $schedules Existing WP cron schedules.
	 * @return array
	 */
	public function add_daily_schedule( $schedules ) {
		if ( ! isset( $schedules['luludesk_daily'] ) ) {
			$schedules['luludesk_daily'] = array(
				'interval' => DAY_IN_SECONDS,
				'display'  => __( 'Once Daily (LuluDesk)', 'luludesk-chat-memory' ),
			);
		}
		return $schedules;
	}

	/**
	 * Send the daily heartbeat ping to LuluDesk.
	 * Failures are logged but never shown to visitors.
	 */
	public function send_heartbeat() {
		$token = get_option( 'luludesk_install_token', '' );

		$payload = array(
			'plugin_version' => LULUDESK_VERSION,
			'wp_version'     => get_bloginfo( 'version' ),
			'php_version'    => PHP_VERSION,
			'token_set'      => '' !== $token,
			'widget_enabled' => '1' === get_option( 'luludesk_widget_enabled', '1' ),
		);

		// Include the install token only when set — lets the server attribute
		// this heartbeat to a workspace. The server expects the field name
		// `install_token` (see /api/integrations/wordpress/heartbeat). Do NOT
		// rename without updating the backend contract.
		if ( '' !== $token ) {
			$payload['install_token'] = $token;
		}

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout'     => 10,
				'redirection' => 3,
				'headers'     => array( 'Content-Type' => 'application/json' ),
				'body'        => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[LuluDesk] Heartbeat failed: ' . $response->get_error_message() );
			return;
		}

		update_option( 'luludesk_last_heartbeat', time() );

		// Surface an "update available" hint when the server reports a newer
		// published plugin version. Stored as an option for the settings screen.
		// The server returns { plugin_latest_version: "x.y.z" }.
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 === $code ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $body ) && ! empty( $body['plugin_latest_version'] ) ) {
				update_option(
					'luludesk_latest_version',
					sanitize_text_field( $body['plugin_latest_version'] )
				);
			}
		}
	}

	/**
	 * Clear the scheduled cron event.
	 * Called from uninstall.php.
	 */
	public static function clear_cron() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}
}
