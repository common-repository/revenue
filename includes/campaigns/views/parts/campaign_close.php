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

if(!$generated_styles) {
    return;
}
$style = revenue()->get_style($generated_styles,'CampaignClose');
?>

<div class="revx-campaign-close-sticky">
    <div class="revx-campaign-close revx-align-center" style="<?php echo esc_attr($style); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20"><rect width="20" height="20" rx="10"></rect><path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.2" d="m14 6-8 8M6 6l8 8"></path></svg>
    </div>
</div>
