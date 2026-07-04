<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Outputs standard <meta> tags, Open Graph tags, and Twitter Card tags
 * into wp_head.
 */
class Maota_MM_Meta_Output {

	public function __construct() {
		add_action( 'wp_head', array( $this, 'render' ), 1 );
	}

	public function render() {
		$data = Maota_MM_Data::instance();
		$page = $data->resolve_current_page();

		echo "\n<!-- Maota Metamarkup -->\n";

		if ( $page['description'] ) {
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $page['description'] ) );
		}

		printf( '<meta name="robots" content="%s" />' . "\n", esc_attr( $data->get_robots_content() ) );

		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $page['url'] ) );

		$this->render_open_graph( $data, $page );
		$this->render_twitter_card( $page );

		echo "<!-- / Maota Metamarkup -->\n";
	}

	private function render_open_graph( $data, $page ) {
		printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( $data->get_site_title() ) );
		printf( '<meta property="og:type" content="%s" />' . "\n", esc_attr( $page['type'] ) );
		printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $page['title'] ) );

		if ( $page['description'] ) {
			printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $page['description'] ) );
		}

		printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $page['url'] ) );

		if ( $page['image'] ) {
			printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $page['image'] ) );
		}
	}

	private function render_twitter_card( $page ) {
		$card = $page['image'] ? 'summary_large_image' : 'summary';

		printf( '<meta name="twitter:card" content="%s" />' . "\n", esc_attr( $card ) );
		printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $page['title'] ) );

		if ( $page['description'] ) {
			printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $page['description'] ) );
		}

		if ( $page['image'] ) {
			printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $page['image'] ) );
		}
	}
}
