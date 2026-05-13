<?php
/**
 * Knowledge Base settings tab for LuluDesk.
 *
 * Adds a "Knowledge Base" sub-tab to the LuluDesk settings page at
 * WP Admin → Settings → LuluDesk → Knowledge Base.
 *
 * Features:
 *   - "Connect to LuluDesk Knowledge Base" button: POSTs to LuluDesk /connect,
 *     stores returned wp_api_key and kb_source_id as WP options.
 *   - "Sync now" button: triggers a full re-index via /full-sync.
 *   - "Last sync: X ago" status block.
 *   - Post type checkboxes (pulled from public post types).
 *   - Banner when WP REST API appears disabled.
 *
 * Security note on wp_api_key storage:
 *   WordPress has no native secret-encryption API.  The key is stored in
 *   wp_options as plain text (same as install tokens, Stripe keys, etc. do
 *   in popular WP plugins).  Database-level encryption is the recommended
 *   mitigation for high-security environments.
 *
 * @package LuluDesk_Chat_Memory
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LuluDesk_KB_Settings
 */
class LuluDesk_KB_Settings {

	/**
	 * LuluDesk connect endpoint.
	 *
	 * @var string
	 */
	const CONNECT_ENDPOINT = 'https://luluclaw.com/api/integrations/wordpress/connect';

	/**
	 * LuluDesk full-sync endpoint.
	 *
	 * @var string
	 */
	const FULL_SYNC_ENDPOINT = 'https://luluclaw.com/api/integrations/wordpress/full-sync';

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_luludesk_kb_connect', array( $this, 'ajax_connect' ) );
		add_action( 'wp_ajax_luludesk_kb_sync', array( $this, 'ajax_sync' ) );
		add_action( 'wp_ajax_luludesk_kb_check_rest', array( $this, 'ajax_check_rest' ) );
	}

	/**
	 * Register KB-related settings (post types preference).
	 */
	public function register_settings() {
		register_setting(
			'luludesk_kb_settings_group',
			'luludesk_kb_included_post_types',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_post_types' ),
				'default'           => array( 'page', 'post' ),
			)
		);
	}

	/**
	 * Sanitize post types array — only allow known public post type slugs.
	 *
	 * @param mixed $value Raw input.
	 * @return array
	 */
	public function sanitize_post_types( $value ) {
		if ( ! is_array( $value ) ) {
			return array( 'page', 'post' );
		}
		$public_types = array_keys( get_post_types( array( 'public' => true ), 'names' ) );
		$sanitized    = array();
		foreach ( $value as $type ) {
			$type = sanitize_key( $type );
			if ( in_array( $type, $public_types, true ) ) {
				$sanitized[] = $type;
			}
		}
		return empty( $sanitized ) ? array( 'page', 'post' ) : $sanitized;
	}

	/**
	 * Enqueue admin JS on LuluDesk settings page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'settings_page_luludesk-settings' !== $hook ) {
			return;
		}
		wp_enqueue_script(
			'luludesk-kb-admin',
			LULUDESK_PLUGIN_URL . 'assets/kb-admin.js',
			array( 'jquery' ),
			LULUDESK_VERSION,
			true
		);
		wp_localize_script(
			'luludesk-kb-admin',
			'ludeskKbAdmin',
			array(
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'luludesk_kb_action' ),
				'connected'    => ! empty( get_option( 'luludesk_wp_api_key', '' ) ),
				'kb_source_id' => get_option( 'luludesk_kb_source_id', '' ),
				'i18n'         => array(
					'connecting'   => __( 'Connecting…', 'luludesk-chat-memory' ),
					'connect_ok'   => __( 'Connected! Save your API key — it will not be shown again.', 'luludesk-chat-memory' ),
					'connect_fail' => __( 'Connection failed. Check your site URL and try again.', 'luludesk-chat-memory' ),
					'syncing'      => __( 'Syncing…', 'luludesk-chat-memory' ),
					'sync_ok'      => __( 'Sync complete.', 'luludesk-chat-memory' ),
					'sync_fail'    => __( 'Sync failed. Check your connection and try again.', 'luludesk-chat-memory' ),
				),
			)
		);
	}

	/**
	 * AJAX: POST to LuluDesk /connect and store returned credentials.
	 */
	public function ajax_connect() {
		check_ajax_referer( 'luludesk_kb_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
		}

		$install_token = get_option( 'luludesk_install_token', '' );
		$wp_site_url   = home_url();

		$payload = array(
			'wp_site_url'   => $wp_site_url,
			'install_token' => $install_token,
		);

		$response = wp_remote_post(
			self::CONNECT_ENDPOINT,
			array(
				'timeout' => 15,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code < 200 || $code >= 300 ) {
			$msg = is_array( $data ) && isset( $data['message'] ) ? $data['message'] : 'HTTP ' . $code;
			wp_send_json_error( array( 'message' => $msg, 'code' => $code ) );
		}

		// Store credentials.
		// wp_api_key is stored plain — WP has no native encryption API.
		$wp_api_key      = is_array( $data ) && isset( $data['wp_api_key'] ) ? sanitize_text_field( $data['wp_api_key'] ) : '';
		$kb_source_id    = is_array( $data ) && isset( $data['kb_source_id'] ) ? sanitize_text_field( $data['kb_source_id'] ) : '';
		$plugin_config_url = is_array( $data ) && isset( $data['plugin_config_url'] ) ? esc_url_raw( $data['plugin_config_url'] ) : '';

		if ( empty( $wp_api_key ) || empty( $kb_source_id ) ) {
			wp_send_json_error(
				array( 'message' => __( 'LuluDesk returned an invalid response. Please try again or contact support.', 'luludesk-chat-memory' ) ),
				500
			);
			return;
		}

		update_option( 'luludesk_wp_api_key', $wp_api_key );
		update_option( 'luludesk_kb_source_id', $kb_source_id );
		update_option( 'luludesk_kb_connected_at', time() );

		// Update post types preference if user saved the form before connecting.
		$selected_types = get_option( 'luludesk_kb_included_post_types', array( 'page', 'post' ) );
		update_option( 'luludesk_kb_included_post_types', $selected_types );

		wp_send_json_success(
			array(
				'wp_api_key'       => $wp_api_key,
				'kb_source_id'     => $kb_source_id,
				'plugin_config_url' => $plugin_config_url,
				'webhook_url'      => is_array( $data ) && isset( $data['webhook_url'] ) ? $data['webhook_url'] : '',
			)
		);
	}

	/**
	 * AJAX: trigger full sync via LuluDesk /full-sync.
	 */
	public function ajax_sync() {
		check_ajax_referer( 'luludesk_kb_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
		}

		$kb_source_id = get_option( 'luludesk_kb_source_id', '' );
		if ( empty( $kb_source_id ) ) {
			wp_send_json_error( array( 'message' => 'Not connected to LuluDesk Knowledge Base.' ) );
		}

		$wp_api_key = get_option( 'luludesk_wp_api_key', '' );
		$webhook    = new LuluDesk_Webhook( $wp_api_key, $kb_source_id );
		$timestamp  = time();
		$body_json  = wp_json_encode( array( 'kb_source_id' => $kb_source_id ) );
		$signature  = $webhook->sign( $timestamp, $body_json );

		$response = wp_remote_post(
			self::FULL_SYNC_ENDPOINT,
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type'           => 'application/json',
					'X-LuluDesk-Signature'   => $signature,
					'X-LuluDesk-Timestamp'   => (string) $timestamp,
				),
				'body'    => $body_json,
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code < 200 || $code >= 300 ) {
			$msg = is_array( $data ) && isset( $data['message'] ) ? $data['message'] : 'HTTP ' . $code;
			wp_send_json_error( array( 'message' => $msg ) );
		}

		update_option( 'luludesk_kb_last_synced_at', time() );

		wp_send_json_success(
			array(
				'pages_synced'    => is_array( $data ) ? ( $data['pages_synced'] ?? 0 ) : 0,
				'chunks_upserted' => is_array( $data ) ? ( $data['chunks_upserted'] ?? 0 ) : 0,
			)
		);
	}

	/**
	 * AJAX: check whether the WP REST API is publicly accessible.
	 *
	 * Called on page load so the settings page can show a warning if the
	 * REST API appears disabled (some hardened sites disable it).
	 */
	public function ajax_check_rest() {
		check_ajax_referer( 'luludesk_kb_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
		}

		$rest_url = rest_url( 'wp/v2/posts' ) . '?per_page=1';
		$response = wp_remote_get( $rest_url, array( 'timeout' => 5 ) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_success( array( 'accessible' => false, 'code' => 0 ) );
			return;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		// 200 or 401 both indicate the REST API is responding.
		// 404 or no response indicates it may be disabled.
		$accessible = in_array( $code, array( 200, 401, 403 ), true );

		wp_send_json_success( array( 'accessible' => $accessible, 'code' => $code ) );
	}

	/**
	 * Render the Knowledge Base settings tab HTML.
	 *
	 * Called from LuluDesk_Settings::render_settings_page() when the "kb" tab
	 * is active.
	 */
	public function render_kb_tab() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$api_key      = get_option( 'luludesk_wp_api_key', '' );
		$kb_source_id = get_option( 'luludesk_kb_source_id', '' );
		$connected    = ! empty( $api_key ) && ! empty( $kb_source_id );

		$last_synced_at = get_option( 'luludesk_kb_last_synced_at', '' );
		if ( $last_synced_at ) {
			$last_sync_label = sprintf(
				/* translators: %s: human-readable time difference */
				__( '%s ago', 'luludesk-chat-memory' ),
				human_time_diff( (int) $last_synced_at, time() )
			);
		} else {
			$last_sync_label = __( 'Never', 'luludesk-chat-memory' );
		}

		$included_types = get_option( 'luludesk_kb_included_post_types', array( 'page', 'post' ) );
		if ( ! is_array( $included_types ) ) {
			$included_types = array( 'page', 'post' );
		}

		$public_post_types = get_post_types( array( 'public' => true ), 'objects' );
		?>

		<div id="luludesk-kb-tab">
			<h2><?php esc_html_e( 'Knowledge Base', 'luludesk-chat-memory' ); ?></h2>
			<p>
				<?php esc_html_e( 'Connect your WordPress site to LuluDesk to automatically sync published content into your AI assistant\'s knowledge base.', 'luludesk-chat-memory' ); ?>
			</p>

			<?php // REST API accessibility banner — populated by JS on load. ?>
			<div id="luludesk-rest-api-warning" style="display:none;" class="notice notice-warning">
				<p>
					<?php
					esc_html_e(
						'Your site\'s REST API appears disabled. KB auto-sync requires it. See Settings → Permalinks or contact your host.',
						'luludesk-chat-memory'
					);
					?>
				</p>
			</div>

			<?php if ( $connected ) : ?>

			<div class="notice notice-success inline" style="padding:8px 12px;margin-bottom:16px;">
				<p>
					<?php
					echo wp_kses(
						sprintf(
							/* translators: %s: KB source ID */
							__( 'Connected to LuluDesk Knowledge Base. Source ID: %s', 'luludesk-chat-memory' ),
							'<code>' . esc_html( $kb_source_id ) . '</code>'
						),
						array( 'code' => array() )
					);
					?>
				</p>
			</div>

			<p>
				<strong><?php esc_html_e( 'Last sync:', 'luludesk-chat-memory' ); ?></strong>
				<span id="luludesk-last-sync-label"><?php echo esc_html( $last_sync_label ); ?></span>
			</p>

			<p>
				<button type="button" id="luludesk-sync-btn" class="button button-primary">
					<?php esc_html_e( 'Sync now', 'luludesk-chat-memory' ); ?>
				</button>
				<span id="luludesk-sync-result" style="margin-left:8px;font-weight:600;"></span>
			</p>

			<?php else : ?>

			<p>
				<button type="button" id="luludesk-connect-btn" class="button button-primary">
					<?php esc_html_e( 'Connect to LuluDesk Knowledge Base', 'luludesk-chat-memory' ); ?>
				</button>
				<span id="luludesk-connect-result" style="margin-left:8px;font-weight:600;"></span>
			</p>

			<?php endif; ?>

			<hr />

			<h3><?php esc_html_e( 'Included Content Types', 'luludesk-chat-memory' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Choose which post types are included in the Knowledge Base sync.', 'luludesk-chat-memory' ); ?>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'luludesk_kb_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Post Types', 'luludesk-chat-memory' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<?php esc_html_e( 'Included post types', 'luludesk-chat-memory' ); ?>
								</legend>
								<?php foreach ( $public_post_types as $post_type ) : ?>
									<label style="display:block;margin-bottom:4px;">
										<input
											type="checkbox"
											name="luludesk_kb_included_post_types[]"
											value="<?php echo esc_attr( $post_type->name ); ?>"
											<?php checked( in_array( $post_type->name, $included_types, true ) ); ?>
										/>
										<?php echo esc_html( $post_type->label ); ?>
										<code style="color:#999;font-size:11px;">(<?php echo esc_html( $post_type->name ); ?>)</code>
									</label>
								<?php endforeach; ?>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'Default: Pages and Posts. Changes take effect on next sync.', 'luludesk-chat-memory' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save post types', 'luludesk-chat-memory' ) ); ?>
			</form>
		</div>
		<?php
	}
}
