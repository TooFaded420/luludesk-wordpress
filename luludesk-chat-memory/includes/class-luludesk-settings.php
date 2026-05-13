<?php
/**
 * Admin settings page for LuluDesk.
 *
 * @package LuluDesk_Chat_Memory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LuluDesk_Settings
 */
class LuluDesk_Settings {

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_luludesk_test_connection', array( $this, 'ajax_test_connection' ) );
	}

	/**
	 * Add the settings page under WP Admin → Settings.
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'LuluDesk — AI Chat with Memory', 'luludesk-chat-memory' ),
			__( 'LuluDesk', 'luludesk-chat-memory' ),
			'manage_options',
			'luludesk-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register all settings, sections, and fields.
	 */
	public function register_settings() {
		register_setting(
			'luludesk_settings_group',
			'luludesk_install_token',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_token' ),
				'default'           => '',
			)
		);
		register_setting(
			'luludesk_settings_group',
			'luludesk_widget_enabled',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '1',
			)
		);
		register_setting(
			'luludesk_settings_group',
			'luludesk_auto_inject',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '1',
			)
		);
		register_setting(
			'luludesk_settings_group',
			'luludesk_allowed_pages',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_allowed_pages' ),
				'default'           => 'all',
			)
		);
		register_setting(
			'luludesk_settings_group',
			'luludesk_allowed_urls',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => '',
			)
		);

		add_settings_section(
			'luludesk_main_section',
			__( 'Widget Configuration', 'luludesk-chat-memory' ),
			'__return_false',
			'luludesk-settings'
		);
	}

	/**
	 * Sanitize the install token field.
	 *
	 * @param string $value Raw input value.
	 * @return string Sanitized token or empty string on invalid format.
	 */
	public function sanitize_token( $value ) {
		$value = sanitize_text_field( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( ! preg_match( '/^wt_[a-f0-9\-]{36}$/', $value ) ) {
			add_settings_error(
				'luludesk_install_token',
				'luludesk_invalid_token',
				__( 'Invalid token format. Expected: wt_ followed by a 36-character hex UUID.', 'luludesk-chat-memory' ),
				'error'
			);
			return get_option( 'luludesk_install_token', '' );
		}
		return $value;
	}

	/**
	 * Sanitize the allowed_pages radio value.
	 *
	 * @param string $value Raw input value.
	 * @return string One of: all, homepage, include, exclude.
	 */
	public function sanitize_allowed_pages( $value ) {
		$allowed = array( 'all', 'homepage', 'include', 'exclude' );
		return in_array( $value, $allowed, true ) ? $value : 'all';
	}

	/**
	 * Enqueue admin JS only on our settings page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'settings_page_luludesk-settings' !== $hook ) {
			return;
		}
		wp_enqueue_script(
			'luludesk-admin',
			LULUDESK_PLUGIN_URL . 'assets/admin.js',
			array( 'jquery' ),
			LULUDESK_VERSION,
			true
		);
		wp_localize_script(
			'luludesk-admin',
			'ludeskAdmin',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'luludesk_test_connection' ),
				'i18n'    => array(
					'testing'       => __( 'Testing…', 'luludesk-chat-memory' ),
					'connected'     => __( 'Connected — widget script reachable.', 'luludesk-chat-memory' ),
					'failed'        => __( 'Could not reach widget script. Check your token.', 'luludesk-chat-memory' ),
					'no_token'      => __( 'Please enter a token first.', 'luludesk-chat-memory' ),
				),
			)
		);
	}

	/**
	 * AJAX handler: proxy HEAD request to widget CDN.
	 * Runs server-side to avoid CORS issues.
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'luludesk_test_connection', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';

		if ( ! preg_match( '/^wt_[a-f0-9\-]{36}$/', $token ) ) {
			wp_send_json_error( array( 'message' => 'Invalid token format.' ) );
		}

		$url      = 'https://luluclaw.com/widget/v1/' . rawurlencode( $token ) . '.js';
		$response = wp_remote_head( $url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 === (int) $code ) {
			wp_send_json_success( array( 'code' => $code ) );
		} else {
			wp_send_json_error( array( 'code' => $code ) );
		}
	}

	/**
	 * Render the full settings page HTML.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$token           = get_option( 'luludesk_install_token', '' );
		$widget_enabled  = get_option( 'luludesk_widget_enabled', '1' );
		$auto_inject     = get_option( 'luludesk_auto_inject', '1' );
		$allowed_pages   = get_option( 'luludesk_allowed_pages', 'all' );
		$allowed_urls    = get_option( 'luludesk_allowed_urls', '' );
		$last_heartbeat  = get_option( 'luludesk_last_heartbeat', '' );

		if ( $last_heartbeat ) {
			$heartbeat_label = sprintf(
				/* translators: %s: human-readable time difference */
				__( '%s ago', 'luludesk-chat-memory' ),
				human_time_diff( (int) $last_heartbeat, time() )
			);
		} else {
			$heartbeat_label = __( 'Never', 'luludesk-chat-memory' );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'LuluDesk — AI Chat with Memory', 'luludesk-chat-memory' ); ?></h1>

			<p>
				<?php
				printf(
					/* translators: %s: luluclaw.com link */
					esc_html__( 'Get your install token from %s after signing up.', 'luludesk-chat-memory' ),
					'<a href="https://luluclaw.com/app" target="_blank" rel="noopener noreferrer">luluclaw.com/app</a>'
				);
				?>
			</p>

			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'luludesk_settings_group' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="luludesk_install_token">
								<?php esc_html_e( 'Install Token', 'luludesk-chat-memory' ); ?>
							</label>
						</th>
						<td>
							<input
								type="text"
								id="luludesk_install_token"
								name="luludesk_install_token"
								value="<?php echo esc_attr( $token ); ?>"
								class="regular-text"
								maxlength="64"
								placeholder="wt_xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
								autocomplete="off"
							/>
							<button type="button" id="luludesk-test-btn" class="button button-secondary" style="margin-left:8px;">
								<?php esc_html_e( 'Test Connection', 'luludesk-chat-memory' ); ?>
							</button>
							<span id="luludesk-test-result" style="margin-left:8px;font-weight:600;"></span>
							<p class="description">
								<?php esc_html_e( 'Format: wt_ followed by a UUID. Found in your LuluDesk dashboard.', 'luludesk-chat-memory' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Widget Enabled', 'luludesk-chat-memory' ); ?></th>
						<td>
							<label>
								<input
									type="checkbox"
									name="luludesk_widget_enabled"
									value="1"
									<?php checked( '1', $widget_enabled ); ?>
								/>
								<?php esc_html_e( 'Show the chat widget on this site', 'luludesk-chat-memory' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Auto-inject in Footer', 'luludesk-chat-memory' ); ?></th>
						<td>
							<label>
								<input
									type="checkbox"
									name="luludesk_auto_inject"
									value="1"
									<?php checked( '1', $auto_inject ); ?>
								/>
								<?php esc_html_e( 'Automatically add the widget script to wp_footer', 'luludesk-chat-memory' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Uncheck only if you are manually calling luludesk_render_widget() in your theme.', 'luludesk-chat-memory' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Show Widget On', 'luludesk-chat-memory' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<?php esc_html_e( 'Show Widget On', 'luludesk-chat-memory' ); ?>
								</legend>

								<label>
									<input type="radio" name="luludesk_allowed_pages" value="all"
										<?php checked( 'all', $allowed_pages ); ?> />
									<?php esc_html_e( 'All pages', 'luludesk-chat-memory' ); ?>
								</label><br />

								<label>
									<input type="radio" name="luludesk_allowed_pages" value="homepage"
										<?php checked( 'homepage', $allowed_pages ); ?> />
									<?php esc_html_e( 'Homepage only', 'luludesk-chat-memory' ); ?>
								</label><br />

								<label>
									<input type="radio" name="luludesk_allowed_pages" value="include"
										<?php checked( 'include', $allowed_pages ); ?> />
									<?php esc_html_e( 'Specific URLs only (listed below)', 'luludesk-chat-memory' ); ?>
								</label><br />

								<label>
									<input type="radio" name="luludesk_allowed_pages" value="exclude"
										<?php checked( 'exclude', $allowed_pages ); ?> />
									<?php esc_html_e( 'All pages except specific URLs (listed below)', 'luludesk-chat-memory' ); ?>
								</label>
							</fieldset>

							<textarea
								name="luludesk_allowed_urls"
								rows="5"
								class="large-text"
								style="margin-top:8px;"
								placeholder="/contact&#10;/checkout&#10;https://example.com/my-page"
							><?php echo esc_textarea( $allowed_urls ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'One URL or path per line. Partial matches work (e.g. /blog matches /blog/post-1).', 'luludesk-chat-memory' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Status', 'luludesk-chat-memory' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: 1: plugin version, 2: last heartbeat */
					esc_html__( 'Plugin version: %1$s | Last heartbeat: %2$s', 'luludesk-chat-memory' ),
					esc_html( LULUDESK_VERSION ),
					esc_html( $heartbeat_label )
				);
				?>
			</p>
		</div>
		<?php
	}
}
