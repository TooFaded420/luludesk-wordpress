<?php
/**
 * Main plugin singleton.
 *
 * @package LuluDesk_Chat_Memory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LuluDesk_Plugin
 */
class LuluDesk_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var LuluDesk_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return LuluDesk_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — wire up all subsystems.
	 */
	private function __construct() {
		new LuluDesk_Settings();
		new LuluDesk_Injector();
		new LuluDesk_Telemetry();
	}
}
