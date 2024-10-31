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
if(!$generated_styles || !$offered_product || !$current_campaign) {
	return;
}
echo wp_kses(revenue()->tag_wrapper($current_campaign, $generated_styles, 'productTitle', $offered_product->get_name(), "revx-product-title",'div',['product_url'=>get_permalink($offered_product->get_id())]),revenue()->get_allowed_tag());

