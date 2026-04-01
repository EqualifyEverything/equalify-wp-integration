<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://equalify.uic.edu/
 * @since      1.0.0
 *
 * @package    Equalify_Wp_Integration
 * @subpackage Equalify_Wp_Integration/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Registers the Settings > Equalify URL Feed admin page, handles URL
 * enable/disable toggling, and enqueues admin assets.
 *
 * @package    Equalify_Wp_Integration
 * @subpackage Equalify_Wp_Integration/admin
 * @author     UIC Equalify Team <accessiblity@uic.edu>
 */
class Equalify_Wp_Integration_Admin {

	/** @var string */
	private $plugin_name;

	/** @var string */
	private $version;

	/** Number of URL rows to show per page in the admin table. */
	const URLS_PER_PAGE = 20;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    The name of this plugin.
	 * @param    string    $version        The current version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/equalify-wp-integration-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/equalify-wp-integration-admin.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Add the plugin page under Settings in the WP admin menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		add_options_page(
			__( 'Equalify URL Feed', 'equalify-wp-integration' ),
			__( 'Equalify URL Feed', 'equalify-wp-integration' ),
			'manage_options',
			'equalify-url-feed',
			[ $this, 'display_plugin_admin_page' ]
		);
	}

	/**
	 * Render the admin settings page.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$include_pdfs  = (bool) get_option( 'equalify_include_pdfs', true );
		$all_urls      = Equalify_Wp_Integration_URLs::get_all( $include_pdfs );
		$disabled_urls = get_option( 'equalify_disabled_urls', [] );
		$total         = count( $all_urls );
		$current_page  = max( 1, intval( $_GET['paged'] ?? 1 ) );
		$per_page      = self::URLS_PER_PAGE;
		$total_pages   = max( 1, (int) ceil( $total / $per_page ) );
		$current_page  = min( $current_page, $total_pages );
		$offset        = ( $current_page - 1 ) * $per_page;
		$page_urls     = array_slice( $all_urls, $offset, $per_page );
		$feed_url      = Equalify_Wp_Integration_Public::get_feed_url();

		include plugin_dir_path( __FILE__ ) . 'partials/equalify-wp-integration-admin-display.php';
	}

	/**
	 * Handle the POST action that saves plugin options.
	 *
	 * Hooked to admin_post_equalify_save_options.
	 *
	 * @since    1.0.0
	 */
	public function handle_save_options() {
		check_admin_referer( 'equalify_save_options' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'equalify-wp-integration' ) );
		}

		update_option( 'equalify_include_pdfs', isset( $_POST['include_pdfs'] ) ? true : false );

		wp_safe_redirect(
			add_query_arg(
				[ 'page' => 'equalify-url-feed' ],
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Handle the POST action that toggles a single URL's disabled state.
	 *
	 * Hooked to admin_post_equalify_toggle_url.
	 *
	 * @since    1.0.0
	 */
	public function handle_toggle_url() {
		check_admin_referer( 'equalify_toggle_url' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'equalify-wp-integration' ) );
		}

		$url           = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		$toggle_action = isset( $_POST['toggle_action'] ) ? sanitize_text_field( wp_unslash( $_POST['toggle_action'] ) ) : '';
		$paged         = max( 1, intval( $_POST['paged'] ?? 1 ) );

		if ( $url ) {
			$disabled_urls = get_option( 'equalify_disabled_urls', [] );

			if ( 'disable' === $toggle_action ) {
				if ( ! in_array( $url, $disabled_urls, true ) ) {
					$disabled_urls[] = $url;
				}
			} elseif ( 'enable' === $toggle_action ) {
				$disabled_urls = array_values( array_filter( $disabled_urls, fn( $u ) => $u !== $url ) );
			}

			update_option( 'equalify_disabled_urls', $disabled_urls );
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'  => 'equalify-url-feed',
					'paged' => $paged,
				],
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}
}
