<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://equalify.uic.edu/
 * @since      1.0.0
 *
 * @package    Equalify_Wp_Integration
 * @subpackage Equalify_Wp_Integration/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, the public CSS/JS hooks, and registers
 * the CSV endpoint via a custom query var (?equalify_csv=1).
 *
 * @package    Equalify_Wp_Integration
 * @subpackage Equalify_Wp_Integration/public
 * @author     UIC Equalify Team <accessiblity@uic.edu>
 */
class Equalify_Wp_Integration_Public {

	/** @var string */
	private $plugin_name;

	/** @var string */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    The name of the plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/equalify-wp-integration-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/equalify-wp-integration-public.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Register the equalify_csv query variable so WordPress passes it through.
	 *
	 * @since    1.0.0
	 * @param    array    $vars    Existing public query variables.
	 * @return   array
	 */
	public function register_query_var( $vars ) {
		$vars[] = 'equalify_csv';
		return $vars;
	}

	/**
	 * If the equalify_csv query var is present, output the CSV and exit.
	 *
	 * Accessible at: /?equalify_csv=1
	 *
	 * @since    1.0.0
	 */
	public function maybe_output_csv() {
		if ( ! get_query_var( 'equalify_csv' ) ) {
			return;
		}

		$disabled_urls = get_option( 'equalify_disabled_urls', [] );
		$include_pdfs  = (bool) get_option( 'equalify_include_pdfs', 1 );
		$all_urls      = Equalify_Wp_Integration_URLs::get_all( $include_pdfs );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: inline; filename="equalify-urls.csv"' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, [ 'url', 'type' ] );

		foreach ( $all_urls as $item ) {
			if ( ! in_array( $item['url'], $disabled_urls, true ) ) {
				fputcsv( $output, [ $item['url'], $item['type'] ] );
			}
		}

		fclose( $output );
		exit;
	}

	/**
	 * Returns the public URL for the CSV feed.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public static function get_feed_url() {
		return add_query_arg( 'equalify_csv', '1', home_url( '/' ) );
	}
}
