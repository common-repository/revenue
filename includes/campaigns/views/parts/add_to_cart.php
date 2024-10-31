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

global $product;

if(!$generated_styles || !$current_campaign || !$offered_product) {
	return;
}

$className = '';
$product_id = $offered_product->get_id();
switch ($current_campaign['campaign_type']) {
    case 'bundle_discount':
        $className =  "revenue-campaign-add-bundle-to-cart";
        # code...
        break;
    case 'frequently_bought_together':
        $product_id = $product->get_id();
        $className = "revx-campaign-add-to-cart-btn";

        break;

    default:
        $className = "revx-campaign-add-to-cart-btn";
        break;
}


$is_animated_atc_enabled = isset($current_campaign['animated_add_to_cart_enabled']) && 'yes'==$current_campaign['animated_add_to_cart_enabled'];
$animated_atc_trigger_type = isset($current_campaign['add_to_cart_animation_trigger_type']) ? sanitize_text_field($current_campaign['add_to_cart_animation_trigger_type']):'';
$animated_type = isset($current_campaign['add_to_cart_animation_type'])? sanitize_text_field( $current_campaign['add_to_cart_animation_type'] ):'';
$animation_delay = isset($current_campaign['add_to_cart_animation_start_delay'])? sanitize_text_field( $current_campaign['add_to_cart_animation_start_delay'] ): '0.8s';
$animation_base_class = $is_animated_atc_enabled? "revx-btn-animation ": '';
$animation_class = "";

switch ($animated_type) {
    case 'wobble':
        $animation_class = 'revx-btn-wobble';
        break;
    case 'shake':
        $animation_class = 'revx-btn-shake';
        break;
    case 'zoom':
        $animation_class=   'revx-btn-zoomIn';
        break;
    case 'pulse':
        $animation_class=   'revx-btn-pulse';
        break;

    default:
        # code...
        break;
}

$animation_base_class = $animated_atc_trigger_type=='loop'?"$animation_base_class $animation_class": $animation_base_class;

if($is_animated_atc_enabled) {
	wp_enqueue_script('revenue-animated-add-to-cart');
    wp_enqueue_style('revenue-animated-add-to-cart');
}

$which_page = '';
if(is_product()) {
	$which_page = 'product_page';
}
else if(is_cart()) {
	$which_page = 'cart_page';
} else if(is_shop()) {
	$which_page = 'shop_page';
} else if(is_checkout()) {
	$which_page = 'checkout_page';
}


if(isset($current_campaign['skip_add_to_cart']) && 'yes' == $current_campaign['skip_add_to_cart']) {
	echo wp_kses(revenue()->tag_wrapper($current_campaign,$generated_styles,'addToCartButton', "Checkout", "$className $class $animation_base_class revx-builder-atc-btn revx-builder-atc-skip revx-cursor-pointer" ,"button",array_merge($data,["product-id"=>$product_id,'campaign-id'=>$current_campaign['id'],'campaign-type'=>$current_campaign['campaign_type'],'animation-class'=>$animation_class,'animation-delay'=>$animation_delay,'animation-trigger-type'=>$animated_atc_trigger_type])), revenue()->get_allowed_tag());
} else {
	echo wp_kses(revenue()->tag_wrapper($current_campaign,$generated_styles,'addToCartButton', "Add to Cart", "$className $class $animation_base_class revx-builder-atc-btn revx-cursor-pointer" ,"button",array_merge($data,["product-id"=>$product_id,'campaign-id'=>$current_campaign['id'],'campaign-type'=>$current_campaign['campaign_type'],'animation-class'=>$animation_class,'animation-delay'=>$animation_delay,'animation-trigger-type'=>$animated_atc_trigger_type,'campaign_source_page' => $which_page ])),revenue()->get_allowed_tag());
}
