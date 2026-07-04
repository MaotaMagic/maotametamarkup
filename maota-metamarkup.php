<?php
/**
 * Plugin Name:       Maota Metamarkup
 * Plugin URI:        https://maota.no/plugins/maota-metamarkup
 * Update URI:        https://github.com/MaotaMagic/maotametamarkup
 * Description:       Maps site identity and organization context into meta tags, Open Graph tags, JSON-LD structured data, and a virtual llms.txt so search engines and AI agents understand who you are and what your site provides.
 * Version:           1.2.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Maota
 * Author URI:        https://maota.no
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       maota-metamarkup
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MAOTA_METAMARKUP_VERSION', '1.2.0' );
define( 'MAOTA_METAMARKUP_FILE', __FILE__ );
define( 'MAOTA_METAMARKUP_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAOTA_METAMARKUP_URL', plugin_dir_url( __FILE__ ) );

require_once MAOTA_METAMARKUP_DIR . 'includes/class-maota-mm-i18n.php';
require_once MAOTA_METAMARKUP_DIR . 'includes/class-maota-mm-data.php';
require_once MAOTA_METAMARKUP_DIR . 'includes/class-maota-mm-settings.php';
require_once MAOTA_METAMARKUP_DIR . 'includes/class-maota-mm-meta-output.php';
require_once MAOTA_METAMARKUP_DIR . 'includes/class-maota-mm-schema-jsonld.php';
require_once MAOTA_METAMARKUP_DIR . 'includes/class-maota-mm-llms-txt.php';
require_once MAOTA_METAMARKUP_DIR . 'includes/class-maota-mm-page-flags.php';
require_once MAOTA_METAMARKUP_DIR . 'includes/class-maota-mm-updater.php';
require_once MAOTA_METAMARKUP_DIR . 'includes/class-maota-mm-activation.php';
require_once MAOTA_METAMARKUP_DIR . 'includes/class-maota-metamarkup.php';

register_activation_hook( MAOTA_METAMARKUP_FILE, array( 'Maota_MM_Activation', 'activate' ) );
register_deactivation_hook( MAOTA_METAMARKUP_FILE, array( 'Maota_MM_Activation', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Maota_Metamarkup', 'instance' ) );

// GitHub self-updater — only needed where update checks run (admin + cron).
if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
	new Maota_MM_Updater( MAOTA_METAMARKUP_FILE );
}
