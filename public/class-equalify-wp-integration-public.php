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

		$token = get_option( 'equalify_csv_token', '' );
		$provided = isset( $_GET['equalify_token'] ) ? sanitize_text_field( wp_unslash( $_GET['equalify_token'] ) ) : '';

		if ( ! $token || ! hash_equals( $token, $provided ) ) {
			status_header( 403 );
			exit;
		}

		$disabled_ids = get_option( 'equalify_disabled_ids', [] );
		$include_pdfs = (bool) get_option( 'equalify_include_pdfs', 1 );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: inline; filename="equalify-urls.csv"' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, [ 'url', 'type' ] );

		Equalify_Wp_Integration_URLs::stream_all(
			$include_pdfs,
			function ( $item ) use ( $output, $disabled_ids ) {
				if ( ! in_array( $item['post_id'], $disabled_ids, true ) ) {
					fputcsv( $output, [ $item['url'], $item['type'] ] );
				}
			}
		);

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
		// Generate a token on demand for sites that existed before the plugin
		// was network-activated and therefore never ran activate().
		if ( ! get_option( 'equalify_csv_token' ) ) {
			update_option( 'equalify_csv_token', bin2hex( random_bytes( 32 ) ) );
		}

		return add_query_arg(
			[
				'equalify_csv'   => '1',
				'equalify_token' => get_option( 'equalify_csv_token', '' ),
			],
			home_url( '/' )
		);
	}
}
