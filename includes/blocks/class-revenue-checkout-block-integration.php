<?php
namespace Revenue;

/**
 * Revenue_Checkout_Blocks_Integration class
 *
 * @package  Revenue
 * @since    1.0.0
 */

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
/**
 * Class for integrating with WooCommerce Blocks scripts.
 *
 * @version 1.0.0
 */
class Revenue_Checkout_Blocks_Integration implements IntegrationInterface {
	use SingletonTrait;
	/**
	 * Whether the intregration has been initialized.
	 *
	 * @var boolean
	 */
	protected $is_initialized;

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'revenue';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {

		if ( $this->is_initialized ) {
			return;
		}

		$script_url = REVENUE_URL . 'assets/js/frontend/checkout-block-integration.js';

		wp_register_script(
			'revenue-checkout-block-integration',
			$script_url,
			array( 'wc-blocks-checkout' ),
			REVENUE_VER,
			true
		);
		wp_script_add_data( 'revenue-checkout-block-integration', 'strategy', 'defer' );

		// Load JS translations.
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				'revenue-checkout-block-integration',
				'revenue',
				dirname( REVENUE_BASE ) . '/languages'
			);
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		$this->is_initialized = true;
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'revenue-checkout-block-integration' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		return array(
			'revenue-checkout-block-integration' => 'active',
		);
	}

	/**
	 * Enqueue Integration Script
	 *
	 * @return void
	 */
	public function enqueue_scripts() {

		$style_url = REVENUE_URL . 'assets/css/frontend/checkout-block-integration.css';

		wp_enqueue_style(
			'revenue-checkout-block-integration',
			$style_url,
			'',
			REVENUE_VER,
			'all'
		);
		wp_style_add_data( 'revenue-checkout-block-integration', 'rtl', 'replace' );

		$meta_suffix = _wp_to_kebab_case( __( 'Includes', 'revenue' ) );

		if ( 'includes' !== $meta_suffix ) {
			$inline_css   = array();
			$inline_css[] = 'table.wc-block-cart-items .wc-block-cart-items__row.revx-bundle-item .wc-block-components-product-details__' . $meta_suffix . ' .wc-block-components-product-details__name, .wc-block-components-order-summary-item.revx-bundle-item .wc-block-components-product-details__' . $meta_suffix . ' .wc-block-components-product-details__name { display:block; margin-bottom: 0.5em }';
			$inline_css[] = 'table.wc-block-cart-items .wc-block-cart-items__row.revx-bundle-item .wc-block-components-product-details__' . $meta_suffix . ':not(:first-of-type) .wc-block-components-product-details__name, .wc-block-components-order-summary-item.revx-bundle-item .wc-block-components-product-details__' . $meta_suffix . ':not(:first-of-type) .wc-block-components-product-details__name { display:none }';
			$inline_css[] = 'table.wc-block-cart-items .wc-block-cart-items__row.revx-bundle-item .wc-block-components-product-details__' . $meta_suffix . ' + li:not( .wc-block-components-product-details__' . $meta_suffix . ' ), .wc-block-components-order-summary-item.revx-bundle-item .wc-block-components-product-details__' . $meta_suffix . ' + li:not( .wc-block-components-product-details__' . $meta_suffix . ' ) { margin-top:0.5em }';
			wp_add_inline_style( 'revenue-checkout-block-integration', implode( ' ', $inline_css ) );
		}

	}
}
