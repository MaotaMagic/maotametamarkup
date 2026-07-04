<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Serves a virtual /llms.txt built from the same data as the meta/JSON-LD
 * output, so there is exactly one source of truth.
 */
class Maota_MM_LLMs_Txt {

	const QUERY_VAR = 'maota_mm_llms_txt';

	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register_rewrite' ) );
		add_filter( 'query_vars', array( $this, 'register_query_var' ) );
		// Priority 0: must run before core's redirect_canonical() (default
		// priority 10), which would otherwise redirect this virtual URL
		// to a trailing-slash variant before we get a chance to serve it.
		add_action( 'template_redirect', array( $this, 'maybe_serve' ), 0 );
	}

	public static function register_rewrite() {
		add_rewrite_rule( '^llms\.txt$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
	}

	public function register_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	public function maybe_serve() {
		if ( ! get_query_var( self::QUERY_VAR ) ) {
			return;
		}

		$this->maybe_switch_language();

		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );

		echo $this->build_content();
		exit;
	}

	/**
	 * Allow /llms.txt?lang=xx to serve a specific language. WPML's directory
	 * and parameter URL modes don't reach this virtual endpoint, so we switch
	 * the active language ourselves from an explicit ?lang= language code.
	 */
	private function maybe_switch_language() {
		if ( ! Maota_MM_I18n::is_active() || ! isset( $_GET['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public read-only endpoint.
			return;
		}
		$lang   = sanitize_key( wp_unslash( $_GET['lang'] ) );
		$active = Maota_MM_I18n::active_languages();
		if ( '' !== $lang && isset( $active[ $lang ] ) ) {
			do_action( 'wpml_switch_language', $lang );
		}
	}

	private function clean_text( $text ) {
		return html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' );
	}

	private function build_content() {
		$data = Maota_MM_Data::instance();

		$lines = array();
		$lines[] = '# ' . $data->get_site_title();
		$lines[] = '';

		// Blockquote: the short "what this site provides" pitch — prefer the
		// context summary, then the org description, then the tagline.
		$summary = $data->get_translated_field( 'context', 'context_summary' );
		if ( '' === trim( (string) $summary ) ) {
			$summary = $data->get_translated_field( 'organization', 'org_description' );
		}
		if ( '' === trim( (string) $summary ) ) {
			$summary = $data->get_tagline();
		}
		if ( $summary ) {
			$lines[] = '> ' . $this->clean_text( $summary );
			$lines[] = '';
		}

		// Fuller organization description as a paragraph, when it adds something
		// beyond the blockquote.
		$org_description = $data->get_translated_field( 'organization', 'org_description' );
		if ( $org_description && trim( (string) $org_description ) !== trim( (string) $summary ) ) {
			$lines[] = $this->clean_text( $org_description );
			$lines[] = '';
		}

		$offerings = $data->get_offerings();
		if ( $offerings ) {
			$lines[] = '## Offerings';
			foreach ( $offerings as $offering ) {
				$lines[] = '- ' . $this->clean_text( $offering );
			}
			$lines[] = '';
		}

		$pages = get_pages(
			array(
				'sort_column' => 'menu_order',
				'meta_key'    => Maota_MM_Page_Flags::META_KEY,
				'meta_value'  => '1',
			)
		);
		if ( $pages ) {
			$lines[] = '## Key Pages';
			$lines[] = '- [' . __( 'Home', 'maota-metamarkup' ) . '](' . $data->get_home_url() . ')';
			foreach ( $pages as $page ) {
				$excerpt = get_the_excerpt( $page );
				$line    = '- [' . $this->clean_text( get_the_title( $page ) ) . '](' . get_permalink( $page ) . ')';
				if ( $excerpt ) {
					$line .= ': ' . $this->clean_text( $excerpt );
				}
				$lines[] = $line;
			}
			$lines[] = '';
		}

		$additional = $data->get_translated_field( 'context', 'context_additional_ai' );
		if ( $additional ) {
			$lines[] = '## Additional Context';
			$lines[] = $this->clean_text( $additional );
			$lines[] = '';
		}

		$languages = Maota_MM_I18n::active_languages();
		if ( count( $languages ) > 1 ) {
			$lines[] = '## Languages';
			$lines[] = 'This site is available in multiple languages. Language-specific versions of this file:';
			foreach ( $languages as $code => $lang ) {
				$label = ! empty( $lang['native_name'] ) ? $lang['native_name'] : $code;
				// Use an explicit ?lang= code, which this endpoint resolves in
				// any WPML URL mode (the language directory prefix does not
				// reach this virtual file).
				$url   = add_query_arg( 'lang', $code, $data->get_home_url() . 'llms.txt' );
				$lines[] = '- ' . $this->clean_text( $label ) . ': ' . $url;
			}
			$lines[] = '';
		}

		return trim( implode( "\n", $lines ) ) . "\n";
	}
}
