<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orchestrator: instantiates each subsystem, which self-registers its
 * own hooks in its own constructor.
 */
class Maota_Metamarkup {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		new Maota_MM_Settings();
		new Maota_MM_Meta_Output();
		new Maota_MM_Schema_JsonLD();
		new Maota_MM_LLMs_Txt();
		new Maota_MM_Page_Flags();
	}
}
