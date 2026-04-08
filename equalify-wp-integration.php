<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://equalify.uic.edu/
 * @since             1.0.0
 * @package           Equalify_Wp_Integration
 *
 * @wordpress-plugin
 * Plugin Name:       Equalify Wordpress Integration
 * Plugin URI:        https://equalify.uic.edu/
 * Description:       Connect your Wordpress site to Equalify to ensure your Equalify accessibility audits are always up-to-date with your latest content.
 * Version:           1.0.0
 * Author:            UIC Equalify Team
 * Author URI:        https://equalify.uic.edu//
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       equalify-wp-integration
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'EQUALIFY_WP_INTEGRATION_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-equalify-wp-integration-activator.php
 */
function activate_equalify_wp_integration( $network_wide ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-equalify-wp-integration-activator.php';
	Equalify_Wp_Integration_Activator::activate( $network_wide );
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-equalify-wp-integration-deactivator.php
 */
function deactivate_equalify_wp_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-equalify-wp-integration-deactivator.php';
	Equalify_Wp_Integration_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_equalify_wp_integration' );
register_deactivation_hook( __FILE__, 'deactivate_equalify_wp_integration' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-equalify-wp-integration.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_equalify_wp_integration() {

	$plugin = new Equalify_Wp_Integration();
	$plugin->run();

}
run_equalify_wp_integration();
