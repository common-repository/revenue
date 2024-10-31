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
if(!$generated_styles || !$regular_price || !$current_campaign) {
	return;
}

if(!$message || !$offered_price) {

    $in_percentage = round(revenue()->calculate_discount_percentage($regular_price, $offered_price));

    if(!$in_percentage) {
        return;
    }

    $message = $in_percentage . '% OFF';

	if($offered_price==0) {
		$message = 'Free';
	}
}

$in_percentage = round(revenue()->calculate_discount_percentage($regular_price, $offered_price));

if(!$in_percentage) {
	return;
}
if(floatval($regular_price)>=floatval($offered_price)) {
    echo wp_kses(revenue()->tag_wrapper($current_campaign,$generated_styles,'discountAmount', $message, "revx-builder-savings-tag"),revenue()->get_allowed_tag()); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
