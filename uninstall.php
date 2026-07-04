<?php
/**
 * Fired when the plugin is deleted via wp-admin. Removes the plugin's
 * single stored option; no other side effects.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'maota_metamarkup_options' );
