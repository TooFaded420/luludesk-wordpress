<?php
/**
 * Knowledge Base settings tab for LuluDesk.
 *
 * Adds a "Knowledge Base" sub-tab to the LuluDesk settings page at
 * WP Admin → Settings → LuluDesk → Knowledge Base.
 *
 * Features:
 *   - Credential paste form: the user generates a wp_api_key + kb_source_id in
 *     the LuluDesk dashboard (Settings → Integrations → WordPress) and pastes
 *     them here. We store them as WP options. We do NOT call /connect from the
 *     WP server: that endpoint is Clerk-session-gated and cannot be reached
 *     server-to-server from WordPress.
 *   - "Run a full sync" link to the LuluDesk dashboard. Full sync is triggered
 *     from the dashboard (also Clerk-gated); incremental save_post/delete
 *     webhooks keep the KB current automatically thereafter.
 *   - "Last update sent: X ago" status block (last outgoing webhook).
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
	 * LuluDesk dashboard URL where the user generates KB credentials and runs a
	 * full sync. Both of those actions are Clerk-session-gated and therefore
	 * happen in the dashboard, not from the WP server.
	 *
	 * @var string
	 */
	const DASHBOARD_URL = 'https://luluclaw.com/app?tab=integrations';

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_luludesk_kb_save_credentials', array( $this, 'ajax_save_credentials' ) );
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
					'saving'      => __( 'Saving…', 'luludesk-chat-memory' ),
					'save_ok'     => __( 'Credentials saved. Your site is now connected.', 'luludesk-chat-memory' ),
					'save_fail'   => __( 'Could not save credentials. Check the values and try again.', 'luludesk-chat-memory' ),
					'invalid'     => __( 'Both the API key and Source ID are required.', 'luludesk-chat-memory' ),
				),
			)
		);
	}

	/**
	 * AJAX: store the wp_api_key + kb_source_id the user pasted from the
	 * LuluDesk dashboard.
	 *
	 * IMPORTANT — why this is a paste form and not a server-to-server call:
	 *   The LuluDesk /connect endpoint is Clerk-session-gated and requires a
	 *   workspace_id that only the authenticated dashboard user has. A WP server
	 *   cannot authenticate to it. So credentials are generated in the dashboard
	 *   and pasted here. These two values are all the webhook signer needs
	 *   (the wp_api_key is the HMAC key; the kb_source_id identifies the source).
	 *
	 * The wp_api_key is stored plain — WP has no native encryption API (same as
	 * install tokens, Stripe keys, etc. in popular WP plugins).
	 */
	public function ajax_save_credentials() {
		check_ajax_referer( 'luludesk_kb_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
		}

		$wp_api_key   = isset( $_POST['wp_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_api_key'] ) ) : '';
		$kb_source_id = isset( $_POST['kb_source_id'] ) ? sanitize_text_field( wp_unslash( $_POST['kb_source_id'] ) ) : '';

		if ( '' === $wp_api_key || '' === $kb_source_id ) {
			wp_send_json_error( array( 'message' => __( 'Both the API key and Source ID are required.', 'luludesk-chat-memory' ) ) );
		}

		// Validate formats issued by the backend:
		//   wp_api_key   = "wpk_" + 64 hex chars (32 random bytes).
		//   kb_source_id = a UUID (Postgres uuid column).
		if ( ! preg_match( '/^wpk_[a-f0-9]{64}$/', $wp_api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'API key format looks wrong. Expected: wpk_ followed by 64 hex characters.', 'luludesk-chat-memory' ) ) );
		}
		if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $kb_source_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Source ID format looks wrong. Expected a UUID.', 'luludesk-chat-memory' ) ) );
		}

		update_option( 'luludesk_wp_api_key', $wp_api_key );
		update_option( 'luludesk_kb_source_id', $kb_source_id );
		update_option( 'luludesk_kb_connected_at', time() );

		wp_send_json_success(
			array(
				'kb_source_id' => $kb_source_id,
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

		// Last update sent = the most recent outgoing webhook (save/delete).
		// Incremental webhooks are the plugin's job; full sync happens in the
		// dashboard, so we surface "last update sent" rather than "last sync".
		$last_update_at = get_option( 'luludesk_kb_last_update_sent_at', '' );
		if ( $last_update_at ) {
			$last_update_label = sprintf(
				/* translators: %s: human-readable time difference */
				__( '%s ago', 'luludesk-chat-memory' ),
				human_time_diff( (int) $last_update_at, time() )
			);
		} else {
			$last_update_label = __( 'No updates sent yet', 'luludesk-chat-memory' );
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
							/* translators: %s: kb source id */
							__( 'Connected to LuluDesk Knowledge Base. Source ID: %s', 'luludesk-chat-memory' ),
							'<code>' . esc_html( $kb_source_id ) . '</code>'
						),
						array( 'code' => array() )
					);
					?>
				</p>
			</div>

			<p>
				<strong><?php esc_html_e( 'Last update sent:', 'luludesk-chat-memory' ); ?></strong>
				<span id="luludesk-last-update-label"><?php echo esc_html( $last_update_label ); ?></span>
			</p>

			<p class="description">
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s: dashboard link */
						__( 'New and updated posts sync automatically. To re-index all existing content, run a full sync from your %s.', 'luludesk-chat-memory' ),
						'<a href="' . esc_url( self::DASHBOARD_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'LuluDesk dashboard', 'luludesk-chat-memory' ) . '</a>'
					),
					array( 'a' => array( 'href' => true, 'target' => true, 'rel' => true ) )
				);
				?>
			</p>

			<?php else : ?>

			<p>
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s: dashboard link */
						__( 'Generate your connection credentials in the %s (Integrations → Connect WordPress site), then paste them below.', 'luludesk-chat-memory' ),
						'<a href="' . esc_url( self::DASHBOARD_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'LuluDesk dashboard', 'luludesk-chat-memory' ) . '</a>'
					),
					array( 'a' => array( 'href' => true, 'target' => true, 'rel' => true ) )
				);
				?>
			</p>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="luludesk-kb-api-key"><?php esc_html_e( 'API Key', 'luludesk-chat-memory' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="luludesk-kb-api-key"
							class="regular-text"
							autocomplete="off"
							placeholder="wpk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="luludesk-kb-source-id"><?php esc_html_e( 'Source ID', 'luludesk-chat-memory' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="luludesk-kb-source-id"
							class="regular-text"
							autocomplete="off"
							placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
						/>
					</td>
				</tr>
			</table>

			<p>
				<button type="button" id="luludesk-save-credentials-btn" class="button button-primary">
					<?php esc_html_e( 'Save connection', 'luludesk-chat-memory' ); ?>
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
