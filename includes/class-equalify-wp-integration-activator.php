<?php

/**
 * Fired during plugin activation
 *
 * @link       https://equalify.uic.edu/
 * @since      1.0.0
 *
 * @package    Equalify_Wp_Integration
 * @subpackage Equalify_Wp_Integration/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Equalify_Wp_Integration
 * @subpackage Equalify_Wp_Integration/includes
 * @author     UIC Equalify Team <accessiblity@uic.edu>
 */
class Equalify_Wp_Integration_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Generate a secret token the first time the plugin is activated.
		// Skip if one already exists so reactivation doesn't invalidate existing feed URLs.
		if ( ! get_option( 'equalify_csv_token' ) ) {
			update_option( 'equalify_csv_token', bin2hex( random_bytes( 32 ) ) );
		}
	}

}
