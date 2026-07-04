<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Self-contained GitHub updater. Reads the latest Release from the public
 * repo and plugs it into WordPress's normal plugin-update flow so the plugin
 * can be updated from wp-admin with one click — no wp.org listing, no helper
 * plugin, no token (the repo is public).
 */
class Maota_MM_Updater {

	const REPO_OWNER = 'MaotaMagic';
	const REPO_NAME  = 'maotametamarkup';
	const CACHE_KEY  = 'maota_mm_latest_release';
	const CACHE_TTL  = 6 * HOUR_IN_SECONDS;

	/** Absolute path to the main plugin file. */
	private $file;

	/** e.g. "maota-metamarkup/maota-metamarkup.php". */
	private $basename;

	/** e.g. "maota-metamarkup". */
	private $slug;

	public function __construct( $file ) {
		$this->file     = $file;
		$this->basename = plugin_basename( $file );
		$this->slug     = dirname( $this->basename );

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_source_dir' ), 10, 4 );
	}

	/**
	 * Fetch + cache the latest GitHub release. Returns an object with
	 * ->version, ->package, ->changelog, ->html_url — or null on failure.
	 */
	private function get_latest_release() {
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return $cached ? $cached : null;
		}

		$url      = sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', self::REPO_OWNER, self::REPO_NAME );
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'Maota-Metamarkup-Updater/' . MAOTA_METAMARKUP_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// Cache the failure briefly so a broken/rate-limited API doesn't
			// get hit on every admin page load.
			set_transient( self::CACHE_KEY, '', 15 * MINUTE_IN_SECONDS );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $body ) || empty( $body->tag_name ) || empty( $body->zipball_url ) ) {
			set_transient( self::CACHE_KEY, '', 15 * MINUTE_IN_SECONDS );
			return null;
		}

		$release = (object) array(
			'version'   => ltrim( $body->tag_name, 'vV' ),
			'package'   => $body->zipball_url,
			'changelog' => isset( $body->body ) ? (string) $body->body : '',
			'html_url'  => isset( $body->html_url ) ? $body->html_url : '',
		);

		set_transient( self::CACHE_KEY, $release, self::CACHE_TTL );

		return $release;
	}

	/**
	 * Advertise an available update in the plugins update transient when the
	 * latest release is newer than the installed version.
	 */
	public function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $transient;
		}

		if ( version_compare( $release->version, MAOTA_METAMARKUP_VERSION, '>' ) ) {
			$transient->response[ $this->basename ] = (object) array(
				'slug'        => $this->slug,
				'plugin'      => $this->basename,
				'new_version' => $release->version,
				'package'     => $release->package,
				'url'         => $release->html_url,
				'tested'      => get_bloginfo( 'version' ),
				'requires'    => '6.4',
			);
		} else {
			// Keep the "no update" list accurate so WP doesn't re-offer it.
			unset( $transient->response[ $this->basename ] );
			if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
				$transient->no_update = array();
			}
			$transient->no_update[ $this->basename ] = (object) array(
				'slug'        => $this->slug,
				'plugin'      => $this->basename,
				'new_version' => MAOTA_METAMARKUP_VERSION,
				'package'     => '',
				'url'         => $release->html_url,
			);
		}

		return $transient;
	}

	/**
	 * Provide the "View details" modal content for this plugin.
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || $args->slug !== $this->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$data = get_plugin_data( $this->file, false, false );

		return (object) array(
			'name'          => $data['Name'],
			'slug'          => $this->slug,
			'version'       => $release->version,
			'author'        => $data['Author'],
			'homepage'      => $data['PluginURI'],
			'requires'      => '6.4',
			'requires_php'  => '7.4',
			'tested'        => get_bloginfo( 'version' ),
			'download_link' => $release->package,
			'sections'      => array(
				'description' => wp_kses_post( $data['Description'] ),
				'changelog'   => $this->format_changelog( $release->changelog ),
			),
		);
	}

	/**
	 * Render the release notes (Markdown-ish) into simple HTML for the modal.
	 */
	private function format_changelog( $text ) {
		if ( '' === trim( (string) $text ) ) {
			return __( 'See the GitHub releases page for details.', 'maota-metamarkup' );
		}
		$escaped = esc_html( $text );
		return '<pre style="white-space:pre-wrap;">' . $escaped . '</pre>';
	}

	/**
	 * GitHub zipballs extract to "<owner>-<repo>-<sha>/". Rename that to the
	 * plugin slug so WP installs it to the right folder and keeps it active.
	 */
	public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;

		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->basename ) {
			return $source;
		}

		if ( ! $wp_filesystem ) {
			return $source;
		}

		$desired = trailingslashit( $remote_source ) . $this->slug;
		$source  = untrailingslashit( $source );

		if ( $source === $desired ) {
			return trailingslashit( $desired );
		}

		if ( $wp_filesystem->move( $source, $desired, true ) ) {
			return trailingslashit( $desired );
		}

		return new WP_Error(
			'maota_mm_rename_failed',
			__( 'Could not rename the downloaded plugin folder.', 'maota-metamarkup' )
		);
	}
}
