<?php
namespace Revenue;

/**
 * Display the product
 *
 * This template is used to render the product image markup.
 */

defined('ABSPATH') || exit;

/**
 * Variables used in this file.
 *
 * @var array $generated_styles Array of styles data.
 * @var WC_Product $offered_product Product object.
 */

if (!$offered_product || !$generated_styles) {
    return;
}

// Get the product image
$image = wp_get_attachment_image_src(get_post_thumbnail_id($offered_product->get_id()), 'single-post-thumbnail') ?: [wc_placeholder_img_src()];
$product_title = esc_attr($offered_product->get_title());
$img_style = esc_attr(revenue()->get_style($generated_styles, 'productImage'));

// Determine the click action
$click_action = 'go_to_product'; // Default action
if ($current_campaign) {
    $click_action = revenue()->get_campaign_meta($current_campaign['id'], 'offered_product_click_action', true) ?: $click_action;
}

// Render the image markup
?>
<div class="revx-campaign-item__image" style="<?php echo esc_attr($img_style); ?>">
    <?php if ('go_to_product' === $click_action): ?>
        <a target="_blank" href="<?php echo esc_url(get_permalink($offered_product->get_id())); ?>">
            <img src="<?php echo esc_url($image[0]); ?>" alt="<?php echo esc_html($product_title); ?>" />
        </a>
    <?php else: ?>
        <img src="<?php echo esc_url($image[0]); ?>" alt="<?php echo esc_html($product_title); ?>" />
    <?php endif; ?>
</div>
