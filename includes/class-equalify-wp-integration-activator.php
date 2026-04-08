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
	/**
	 * Generates a secret token for each site that doesn't already have one.
	 * When network-activated, seeds all existing sites in one pass.
	 *
	 * @param bool $network_wide True when activated network-wide in multisite.
	 */
	public static function activate( $network_wide = false ) {
		if ( $network_wide && is_multisite() ) {
			$sites = get_sites( [ 'number' => 0, 'fields' => 'ids' ] );
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );
				self::ensure_token();
				restore_current_blog();
			}
		} else {
			self::ensure_token();
		}
	}

	/**
	 * Generates and stores a token for the current site if one doesn't exist.
	 * Safe to call multiple times — never overwrites an existing token.
	 */
	public static function ensure_token() {
		if ( ! get_option( 'equalify_csv_token' ) ) {
			update_option( 'equalify_csv_token', bin2hex( random_bytes( 32 ) ) );
		}
	}

}
