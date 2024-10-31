<?php

/**
 * Revenue Setup
 *
 * @package Revenue
 * @since 1.0.0
 */

use Revenue\Revenue_Bundle_Discount;
use Revenue\Revenue_Buy_X_Get_Y;
use Revenue\Revenue_Campaign;
use Revenue\Revenue_Analytics;
use Revenue\Revenue_Menu;
use Revenue\Revenue_Server;
use Revenue\Revenue_Install;
use Revenue\Revenue_Normal_Discount;
use Revenue\Revenue_Volume_Discount;

defined('ABSPATH') || exit;

final class Revenue
{

	/**
	 * Containt Instance of this class
	 *
	 * @var Revenue
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Contains class instances
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $container = [];

	private function __construct()
	{

		$this->define_constants();

		register_activation_hook(REVENUE_FILE, [$this, 'activate']);
		register_deactivation_hook(REVENUE_FILE, [$this, 'deactivate']);

		$this->include_ajax();

		$this->include_revenue_menu();

		add_action('init',[$this,'init_menu']);

		add_action('admin_init',[$this, 'activation_redirect']);

		add_action('woocommerce_loaded', [$this, 'init_plugin']);

		// Register admin notices to container and load notices

		add_action('plugins_loaded', [$this, 'woocommerce_not_loaded'], 11);
	}

	/**
	 * Initializes the Revenue class
	 *
	 * Checks for an existing Revenue instance
	 * and if it doesn't find one, creates it.
	 *
	 * @return Revenue
	 * @since 1.0.0
	 */
	public static function init()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Magic Getter Functions
	 *
	 * @param string $name
	 * @return Class Instance
	 */
	public function __get($name)
	{
		if (array_key_exists($name, $this->container)) {
			return $this->container[$name];
		}
	}


	/**
	 * Initialize localization setup for localization
	 *
	 * @uses load_plugin_textdomain()
	 */
	public function localization_setup()
	{
		load_plugin_textdomain('revenue', false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}

	/**
	 * Loads after woocommerce loaded
	 *
	 * @return void
	 */
	public function init_plugin()
	{

        /**
		 * Action triggered before Revenue initialization begins.
		 */
		do_action( 'before_revenue_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		// Includes Files
		$this->includes();

		// Load init hooks
		$this->init_hooks();

		do_action('revenue_loaded');
	}

	/**
	 * Define Required Constants
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function define_constants()
	{
		define('REVENUE_BASE', plugin_basename(__FILE__));
	}

	/**
	 * Include all required files
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function includes()
	{

		require_once REVENUE_PATH . 'includes/traits/SingletonTrait.php';
		require_once REVENUE_PATH . 'includes/class-revenue-campaign.php';
		require_once REVENUE_PATH . 'includes/class-revenue-analytics.php';
		require_once REVENUE_PATH . 'includes/rest-api/class-revenue-campaign-rest-controller.php';
		require_once REVENUE_PATH . 'includes/rest-api/class-revenue-analytics-rest-controller.php';
		require_once REVENUE_PATH . 'includes/rest-api/class-revenue-settings-rest-controller.php';
		require_once REVENUE_PATH . 'includes/rest-api/class-revenue-server.php';

		// Campaigns
		require_once REVENUE_PATH . 'includes/campaigns/class-revenue-normal-discount.php';
		require_once REVENUE_PATH . 'includes/campaigns/class-revenue-bundle-discount.php';
		require_once REVENUE_PATH . 'includes/campaigns/class-revenue-volume-discount.php';
		require_once REVENUE_PATH . 'includes/campaigns/class-revenue-buy-x-get-y.php';

		// require_once REVENUE_PATH . 'includes/campaigns/class-revenue-spending-goal.php';




		if ( $this->is_request( 'frontend' ) || $this->is_rest_api_request() ) {
			$this->frontend_includes();
		}
	}

	public function include_revenue_menu() {
		if (is_admin()) {
			require_once REVENUE_PATH . 'includes/admin/class-revenue-menu.php';
		}
	}

	public function init_menu() {
		if (is_admin()) {
			new Revenue_Menu();
		}
	}

	/**
	 * Include all required file on frontend
	 *
	 * @return void
	 */
	public function frontend_includes() {
		require_once REVENUE_PATH . 'includes/class-revenue-frontend-scripts.php';
	}
	/**
	 * Initialize the actions
	 *
	 * @return void
	 */
	public function init_hooks()
	{
		// Localize our plugin
		add_action('init', [$this, 'localization_setup']);

		// initialize the classes
		add_action('init', [$this, 'init_classes'], 4);

		add_action('plugins_loaded', [$this, 'after_plugins_loaded'],9);

		add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'plugin_action_links']);

		add_action('wp_enqueue_scripts',[$this,'load_scripts']);
	}



	/**
	 * Initialize all classes
	 *
	 * @return void
	 */
	public function init_classes()
	{
		new Revenue_Campaign();
        Revenue_Analytics::instance()->init();

		// Load REST API
		Revenue_Server::instance()->init();

		Revenue_Normal_Discount::instance()->init();
		Revenue_Bundle_Discount::instance()->init();
		Revenue_Volume_Discount::instance()->init();
		Revenue_Buy_X_Get_Y::instance()->init();

	}

	public function load_scripts() {
		Revenue_Frontend_Scripts::load_scripts();
	}
	/**
	 * Plugin action links
	 * Use custom links on Plugin actions
	 *
	 * @param array $links
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function plugin_action_links($links)
	{

		return $links;
	}

	/**
	 * Actions after all plugins loaded
	 *
	 * @return void
	 */
	public function after_plugins_loaded()
	{
		// Actions After plugin loaded

	}


	/**
	 * Check whether woocommerce is installed and active
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function has_woocommerce()
	{
		return class_exists('WooCommerce');
	}

	/**
	 * Check whether woocommerce is installed
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function is_woocommerce_installed()
	{
		return file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );
	}

	/**
	 * Handles scenerios when WooCommerce is not active
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function woocommerce_not_loaded()
	{
		if (did_action('woocommerce_loaded') || !is_admin()) {
			return;
		}
	}

	/**
	 * Include ajax
	 *
	 * @return void
	 */
	public function include_ajax() {

		if ( ! class_exists( '\Revenue\Revenue_Ajax', false ) ) {
			require_once REVENUE_PATH . 'includes/class-revenue-ajax.php';
			new Revenue\Revenue_Ajax();
		}
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! $this->is_rest_api_request();
		}
	}

	/**
	 * Returns true if the request is a non-legacy REST API request.
	 *
	 * Legacy REST requests should still run some extra code for backwards compatibility.
	 *
	 * @todo: replace this function once core WP function is available: https://core.trac.wordpress.org/ticket/42061.
	 *
	 * @return bool
	 */
	public function is_rest_api_request() {
        if ( ! isset( $_SERVER['REQUEST_URI'] ) || empty( $_SERVER['REQUEST_URI'] ) ) {
            return false;
        }

        // Unslash and sanitize the REQUEST_URI
        $request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

        $rest_prefix         = trailingslashit( rest_get_url_prefix() );
        $is_rest_api_request = ( false !== strpos( $request_uri, $rest_prefix ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash

        /**
         * Whether this is a REST API request.
         *
         * @since 1.0.0
         */
        return apply_filters( 'revenue_is_rest_api_request', $is_rest_api_request );
    }


	/**
	 * Placeholder for activation function
	 *
	 * Nothing being called here yet.
	 */
	public function activate()
	{
		if (!$this->has_woocommerce()) {
			// Handle if woocommerce not active
		}

		$installer = new Revenue_Install();
		$installer->install();
	}

	/**
	 * Placeholder for deactivate function
	 *
	 * Nothing being called here yet.
	 */
	public function deactivate()
	{
	}

	public function activation_redirect() {
		if (get_transient('_revenue_activation_redirect')) {
			// Delete the transient to avoid repeated redirects
			delete_transient('_revenue_activation_redirect');

			$slug = revenue()->get_admin_menu_slug();
			// Perform the redirect
			wp_safe_redirect(admin_url( 'admin.php?page=' . $slug . '#/' ));
			exit;
		}
	}
}
