<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://equalify.uic.edu/
 * @since      1.0.0
 *
 * @package    Equalify_Wp_Integration
 * @subpackage Equalify_Wp_Integration/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Equalify_Wp_Integration
 * @subpackage Equalify_Wp_Integration/includes
 * @author     UIC Equalify Team <accessiblity@uic.edu>
 */
class Equalify_Wp_Integration {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Equalify_Wp_Integration_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'EQUALIFY_WP_INTEGRATION_VERSION' ) ) {
			$this->version = EQUALIFY_WP_INTEGRATION_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'equalify-wp-integration';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_cache_hooks();
		$this->define_multisite_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Equalify_Wp_Integration_Loader. Orchestrates the hooks of the plugin.
	 * - Equalify_Wp_Integration_i18n. Defines internationalization functionality.
	 * - Equalify_Wp_Integration_Admin. Defines all hooks for the admin area.
	 * - Equalify_Wp_Integration_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-equalify-wp-integration-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-equalify-wp-integration-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-equalify-wp-integration-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-equalify-wp-integration-public.php';

		/**
		 * Shared URL-fetching helper used by both admin and public.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-equalify-wp-integration-urls.php';

		$this->loader = new Equalify_Wp_Integration_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Equalify_Wp_Integration_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Equalify_Wp_Integration_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Equalify_Wp_Integration_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_post_equalify_toggle_url', $plugin_admin, 'handle_toggle_url' );
		$this->loader->add_action( 'admin_post_equalify_save_options', $plugin_admin, 'handle_save_options' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Equalify_Wp_Integration_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_filter( 'query_vars', $plugin_public, 'register_query_var' );
		$this->loader->add_action( 'template_redirect', $plugin_public, 'maybe_output_csv' );

	}

	/**
	 * Register hooks that invalidate the URL cache when content changes.
	 *
	 * Uses add_action directly (rather than the loader) because the callbacks
	 * are static methods — the loader pattern is designed for object instances.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	/**
	 * When a new site is created in a multisite network, ensure it gets its own token.
	 * Hooked to wp_initialize_site, which fires after the site's options table is ready.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_multisite_hooks() {
		if ( is_multisite() ) {
			add_action( 'wp_initialize_site', function( $new_site ) {
				switch_to_blog( $new_site->blog_id );
				Equalify_Wp_Integration_Activator::ensure_token();
				restore_current_blog();
			} );
		}
	}

	private function define_cache_hooks() {
		// Any post saved, updated, trashed, or status-changed.
		add_action( 'save_post',    [ 'Equalify_Wp_Integration_URLs', 'flush_cache' ] );
		// Any post permanently deleted (fires after the row is removed).
		add_action( 'deleted_post', [ 'Equalify_Wp_Integration_URLs', 'flush_cache' ] );
		// The PDF include toggle changed — affects which URLs appear in the feed.
		add_action( 'update_option_equalify_include_pdfs', [ 'Equalify_Wp_Integration_URLs', 'flush_cache' ] );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Equalify_Wp_Integration_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
