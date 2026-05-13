<?php
/**
 * Footer script injector for LuluDesk widget.
 *
 * @package LuluDesk_Chat_Memory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LuluDesk_Injector
 */
class LuluDesk_Injector {

	/**
	 * Constructor — register wp_footer hook.
	 */
	public function __construct() {
		add_action( 'wp_footer', array( $this, 'inject_widget' ), 99 );
	}

	/**
	 * Output the widget script tag in wp_footer if conditions are met.
	 */
	public function inject_widget() {
		// Respect the global enabled flag.
		if ( '1' !== get_option( 'luludesk_widget_enabled', '1' ) ) {
			return;
		}

		// Respect the auto-inject flag.
		if ( '1' !== get_option( 'luludesk_auto_inject', '1' ) ) {
			return;
		}

		$token = get_option( 'luludesk_install_token', '' );
		if ( '' === $token ) {
			return;
		}

		// Page-matching check.
		if ( ! $this->should_inject() ) {
			return;
		}

		$script_url = 'https://luluclaw.com/widget/v1/' . rawurlencode( $token ) . '.js';
		?>
		<script
			async
			src="<?php echo esc_url( $script_url ); ?>"
			data-luludesk-plugin-version="<?php echo esc_attr( LULUDESK_VERSION ); ?>"
		></script>
		<?php
	}

	/**
	 * Determine whether the widget should be injected on the current request.
	 *
	 * @return bool
	 */
	private function should_inject() {
		$allowed_pages = get_option( 'luludesk_allowed_pages', 'all' );

		if ( 'all' === $allowed_pages ) {
			return true;
		}

		if ( 'homepage' === $allowed_pages ) {
			return is_front_page();
		}

		$allowed_urls = get_option( 'luludesk_allowed_urls', '' );
		$lines        = $this->parse_url_lines( $allowed_urls );

		if ( empty( $lines ) ) {
			// No URLs configured: fall back to showing on all pages.
			return true;
		}

		$current = $this->current_path_and_url();
		$matched = $this->matches_any( $current, $lines );

		if ( 'include' === $allowed_pages ) {
			return $matched;
		}

		// 'exclude' mode.
		return ! $matched;
	}

	/**
	 * Return the current request URL and path for matching.
	 *
	 * @return string[]
	 */
	private function current_path_and_url() {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		$url  = home_url( $path );
		return array( $path, $url );
	}

	/**
	 * Check whether a current URL/path matches any entry in the list.
	 *
	 * @param string[] $current Array of [ path, full_url ].
	 * @param string[] $lines   List of URL patterns to test against.
	 * @return bool
	 */
	private function matches_any( array $current, array $lines ) {
		foreach ( $lines as $line ) {
			foreach ( $current as $candidate ) {
				// Partial substring match.
				if ( false !== strpos( $candidate, $line ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Parse a newline-separated textarea value into an array of trimmed lines.
	 *
	 * @param string $raw Raw textarea content.
	 * @return string[]
	 */
	private function parse_url_lines( $raw ) {
		if ( '' === $raw ) {
			return array();
		}
		$lines = explode( "\n", $raw );
		$lines = array_map( 'trim', $lines );
		return array_filter( $lines );
	}
}
