<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation/deactivation callbacks: registers and flushes the llms.txt
 * rewrite rule.
 */
class Maota_MM_Activation {

	public static function activate() {
		Maota_MM_LLMs_Txt::register_rewrite();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}
}
