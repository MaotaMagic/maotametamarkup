<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPML integration for output localization. Translations are stored per
 * language inside the plugin's own option and edited on the settings page
 * (switching language via the WPML admin bar). This class only handles
 * language detection; the storage/resolution lives in Maota_MM_Data.
 *
 * Every method degrades to a safe default when WPML is not active, so
 * single-language sites are completely unaffected.
 */
class Maota_MM_I18n {

	/**
	 * Global text fields that are surfaced in output and are edited
	 * per-language. Each entry: array( section, key ). Keys are unique
	 * across sections, so the key alone is used to store translations.
	 */
	public static function translatable_fields() {
		return array(
			array( 'organization', 'org_description' ),
			array( 'context', 'context_summary' ),
			array( 'context', 'context_offerings' ),
			array( 'context', 'context_knows_about' ),
			array( 'context', 'context_additional_ai' ),
		);
	}

	/** True when a field key is one of the per-language translatable fields. */
	public static function is_translatable( $key ) {
		foreach ( self::translatable_fields() as $field ) {
			if ( $field[1] === $key ) {
				return true;
			}
		}
		return false;
	}

	/** True when WPML (or a compatible provider of its hooks) is active. */
	public static function is_active() {
		return defined( 'ICL_SITEPRESS_VERSION' ) || has_filter( 'wpml_current_language' );
	}

	/**
	 * Current language code, e.g. "en" / "nb". On the front end this is the
	 * page's language; in wp-admin it is the language chosen in the admin
	 * bar. Null when WPML is inactive.
	 */
	public static function current_language() {
		return self::is_active() ? apply_filters( 'wpml_current_language', null ) : null;
	}

	/** Site default language code. Null when WPML is inactive. */
	public static function default_language() {
		return self::is_active() ? apply_filters( 'wpml_default_language', null ) : null;
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
