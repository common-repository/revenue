<?php
/**
 * Plugin Name: WowRevenue - Product Bundles, Upsell, Cross Sell, Buy X Get Y, and More!
 * Plugin URI: https://www.wowrevenue.com/
 * Description: Most advanced WooCommerce plugin featuring a powerful campaign builder to create and deploy complex discount offers on E-commerce stores.
 * Version: 1.0.4
 * Author: WowRevenue
 * Author URI: https://wowrevenue.com/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: revenue
 * Domain Path: /languages
 */

// If the file is called directly, abort it

defined( 'ABSPATH' ) || exit;
if ( ! defined( 'REVENUE_FILE' ) ) {
	define( 'REVENUE_FILE', __FILE__ );
}

if ( ! defined( 'REVENUE_PATH' ) ) {
    define( 'REVENUE_PATH', plugin_dir_path( __FILE__ ) );
}

if( ! defined( 'REVENUE_URL' )) {
    define( 'REVENUE_URL',  plugin_dir_url(__FILE__));
}

if( ! defined( 'REVENUE_VER' )) {
    define( 'REVENUE_VER',  '1.0.4');
}

// Include the main Revenue class.
if ( ! class_exists( 'Revenue', false ) ) {
	include_once REVENUE_PATH . '/includes/class-revenue.php';
}

if ( ! class_exists( '\Revenue\Revenue_Install', false ) ) {
    require_once REVENUE_PATH . 'includes/class-revenue-install.php';
}

// Include Revenue Functions
if ( ! class_exists( '\Revenue\Revenue_Functions', false ) ) {
    require_once REVENUE_PATH . '/includes/class-revenue-functions.php';
}
if ( !function_exists('revenue') ) {

    function revenue() {

        if(!isset($GLOBALS['revenue_functions'])) {

            $GLOBALS['revenue_functions'] = new \Revenue\Revenue_Functions(); // Using runtime cache
        }

        return $GLOBALS['revenue_functions'];
    }
}


/**
 * Loads Revenue
 * @since 1.0.0
 */
if(!function_exists('revenue_run')) {

    function revenue_run() {
        return Revenue::init();
    }

}

// Kick off
revenue_run();
