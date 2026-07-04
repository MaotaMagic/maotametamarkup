<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single data-access layer. Meta output, JSON-LD, and llms.txt all read
 * through this class so the three outputs never drift from each other.
 */
class Maota_MM_Data {

	private static $instance = null;

	private $options = null;

	private $resolved_page = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	private function get_options() {
		if ( null === $this->options ) {
			$saved          = get_option( Maota_MM_Settings::OPTION_NAME, array() );
			$this->options  = $this->merge_defaults( is_array( $saved ) ? $saved : array(), Maota_MM_Settings::get_defaults() );
		}
		return $this->options;
	}

	private function merge_defaults( $saved, $defaults ) {
		$merged = $defaults;
		foreach ( $defaults as $section_key => $fields ) {
			if ( isset( $saved[ $section_key ] ) && is_array( $saved[ $section_key ] ) ) {
				foreach ( $fields as $field_key => $default_value ) {
					if ( array_key_exists( $field_key, $saved[ $section_key ] ) ) {
						$merged[ $section_key ][ $field_key ] = $saved[ $section_key ][ $field_key ];
					}
				}
			}
		}
		// Per-language translations live outside the fixed schema; carry them through.
		if ( isset( $saved['i18n'] ) && is_array( $saved['i18n'] ) ) {
			$merged['i18n'] = $saved['i18n'];
		}
		return $merged;
	}

	public function get_field( $section, $key ) {
		$options = $this->get_options();
		return isset( $options[ $section ][ $key ] ) ? $options[ $section ][ $key ] : '';
	}

	/**
	 * Same as get_field() but resolved to the current language. Used by all
	 * OUTPUT paths. Non-default languages read the per-language value stored
	 * under options['i18n'][ lang ][ key ], falling back to the default-
	 * language (base) value when empty or when WPML is inactive.
	 */
	public function get_translated_field( $section, $key ) {
		$base = $this->get_field( $section, $key );

		if ( ! Maota_MM_I18n::is_translatable( $key ) ) {
			return $base;
		}

		$lang    = Maota_MM_I18n::current_language();
		$default = Maota_MM_I18n::default_language();

		if ( $lang && $lang !== $default ) {
			$translated = $this->get_i18n_value( $lang, $key );
			if ( '' !== trim( (string) $translated ) ) {
				return $translated;
			}
		}

		return $base;
	}

	/** Raw per-language value for a translatable field ('' if none stored). */
	public function get_i18n_value( $lang, $key ) {
		$options = $this->get_options();
		return isset( $options['i18n'][ $lang ][ $key ] ) ? $options['i18n'][ $lang ][ $key ] : '';
	}

	private function get_lines( $section, $key, $translate = false ) {
		$raw = $translate ? $this->get_translated_field( $section, $key ) : $this->get_field( $section, $key );
		if ( '' === trim( (string) $raw ) ) {
			return array();
		}
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		return array_values( array_filter( array_map( 'trim', $lines ) ) );
	}

	public function get_sameas_urls() {
		return $this->get_lines( 'organization', 'org_sameas_urls' );
	}

	public function get_offerings() {
		return $this->get_lines( 'context', 'context_offerings', true );
	}

	public function get_knows_about() {
		return $this->get_lines( 'context', 'context_knows_about', true );
	}

	/* --- Core WP Site Identity (read live, never duplicated into options) --- */

	public function get_site_title() {
		return get_bloginfo( 'name' );
	}

	public function get_tagline() {
		return get_bloginfo( 'description' );
	}

	public function get_site_language() {
		return get_bloginfo( 'language' );
	}

	public function get_home_url() {
		return home_url( '/' );
	}

	public function get_site_icon_url() {
		return get_site_icon_url();
	}

	public function get_site_logo_url() {
		$logo_id = get_theme_mod( 'custom_logo' );
		if ( ! $logo_id ) {
			return '';
		}
		$url = wp_get_attachment_image_url( $logo_id, 'full' );
		return $url ? $url : '';
	}

	/* --- Effective / fallback values --- */

	public function get_effective_org_name() {
		$name = $this->get_field( 'organization', 'org_legal_name' );
		return '' !== $name ? $name : $this->get_site_title();
	}

	public function get_effective_logo_url() {
		$logo = $this->get_field( 'organization', 'org_logo_url' );
		if ( '' !== $logo ) {
			return $logo;
		}
		$site_logo = $this->get_site_logo_url();
		if ( '' !== $site_logo ) {
			return $site_logo;
		}
		return $this->get_site_icon_url();
	}

	public function get_effective_org_description() {
		$description = $this->get_translated_field( 'organization', 'org_description' );
		if ( '' !== $description ) {
			return $description;
		}
		$summary = $this->get_translated_field( 'context', 'context_summary' );
		if ( '' !== $summary ) {
			return $summary;
		}
		return $this->get_tagline();
	}

	public function get_robots_content() {
		$index  = $this->get_field( 'agent_controls', 'robots_index' );
		$follow = $this->get_field( 'agent_controls', 'robots_follow' );
		$index  = $index ? $index : 'index';
		$follow = $follow ? $follow : 'follow';
		return "{$index}, {$follow}";
	}

	/* --- Per-request current-page resolution --- */

	public function resolve_current_page() {
		if ( null !== $this->resolved_page ) {
			return $this->resolved_page;
		}

		$title       = wp_get_document_title();
		$description = '';
		$image       = '';
		$url         = home_url( '/' );
		$type        = 'website';

		if ( is_singular() ) {
			$post = get_queried_object();
			$url  = get_permalink( $post );
			$type = ( 'post' === get_post_type( $post ) ) ? 'article' : 'website';

			$excerpt     = get_the_excerpt( $post );
			$description = $excerpt ? wp_strip_all_tags( $excerpt ) : $this->get_effective_org_description();

			if ( has_post_thumbnail( $post ) ) {
				$image = get_the_post_thumbnail_url( $post, 'large' );
			}
		} elseif ( is_front_page() || is_home() ) {
			$url         = $this->get_home_url();
			$description = $this->get_effective_org_description();
			$title       = $this->get_site_title() . ( $this->get_tagline() ? ' - ' . $this->get_tagline() : '' );
		} else {
			$url         = home_url( '/' );
			$description = $this->get_effective_org_description();
		}

		if ( '' === $image ) {
			$image = $this->get_effective_logo_url();
		}

		$this->resolved_page = compact( 'title', 'description', 'image', 'url', 'type' );

		return $this->resolved_page;
	}
}
