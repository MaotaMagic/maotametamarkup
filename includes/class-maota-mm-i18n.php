<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPML integration for output localization. Registers the plugin's global
 * text fields as WPML strings so they can be translated per language, and
 * resolves them to the current language at output time. Every method
 * degrades to a no-op that returns the raw value when WPML is not active,
 * so single-language sites are completely unaffected.
 */
class Maota_MM_I18n {

	/** WPML String Translation "domain" these strings live under. */
	const DOMAIN = 'Maota Metamarkup';

	/**
	 * Global text fields that are surfaced in output and worth translating.
	 * Each entry: array( section, key ). The key doubles as the WPML string
	 * name (all keys here are already unique across sections).
	 */
	private static $translatable = array(
		array( 'organization', 'org_description' ),
		array( 'context', 'context_summary' ),
		array( 'context', 'context_offerings' ),
		array( 'context', 'context_knows_about' ),
		array( 'context', 'context_additional_ai' ),
	);

	public static function boot() {
		// Registration only matters for the translator UI, so admin-only.
		add_action( 'admin_init', array( __CLASS__, 'register_strings' ) );
	}

	/** True when WPML (or a compatible provider of its hooks) is active. */
	public static function is_active() {
		return defined( 'ICL_SITEPRESS_VERSION' ) || has_filter( 'wpml_current_language' );
	}

	/**
	 * Make the current option values available in WPML → String Translation.
	 * Registering the current value each load is the standard pattern; WPML
	 * flags existing translations as needing review if the original changes.
	 */
	public static function register_strings() {
		if ( ! self::is_active() ) {
			return;
		}
		$data = Maota_MM_Data::instance();
		foreach ( self::$translatable as $field ) {
			$value = $data->get_field( $field[0], $field[1] );
			if ( '' === trim( (string) $value ) ) {
				continue;
			}
			do_action( 'wpml_register_single_string', self::DOMAIN, $field[1], $value );
		}
	}

	/**
	 * Resolve a string to the current language (or $lang if given). Returns
	 * the original untouched when WPML is inactive or the value is empty.
	 */
	public static function translate( $name, $value, $lang = null ) {
		if ( ! self::is_active() || '' === trim( (string) $value ) ) {
			return $value;
		}
		return apply_filters( 'wpml_translate_single_string', $value, self::DOMAIN, $name, $lang );
	}

	/** Current language code, e.g. "en" / "nb". Null when WPML is inactive. */
	public static function current_language() {
		return self::is_active() ? apply_filters( 'wpml_current_language', null ) : null;
	}

	/**
	 * Active languages as WPML reports them, keyed by code with keys like
	 * 'default_locale', 'native_name', 'url'. Empty array when inactive.
	 */
	public static function active_languages() {
		if ( ! self::is_active() ) {
			return array();
		}
		$langs = apply_filters( 'wpml_active_languages', null );
		return is_array( $langs ) ? $langs : array();
	}

	/**
	 * Convert a URL to its equivalent in a given language (WPML-aware).
	 * Returns the URL unchanged when WPML is inactive.
	 */
	public static function localized_url( $url, $lang ) {
		if ( ! self::is_active() ) {
			return $url;
		}
		return apply_filters( 'wpml_permalink', $url, $lang );
	}
}
