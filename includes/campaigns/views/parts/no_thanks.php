<?php
namespace Revenue;

/**
 * Display the price container
 *
 * This template is used to render the price container
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variables used in this file.
 *
 * @var array $generated_style Array of styles data.
 * @var WC_Product  $offered_product product object
 */
if(!$generated_styles || !$current_campaign || !$offered_product) {
	return;
}
global $product;

$className = '';
$product_id = $offered_product->get_id();
