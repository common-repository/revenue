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
$stickyStyle = revenue()->get_style($generated_styles, 'stickyOffer');
if(empty($message)) {
	return;
}
?>
<div class="revx-builder-sticky-offer">
	<span style="<?php echo esc_attr($stickyStyle); ?>" class="revx-builder-sticky-offer__content"><?php echo wp_kses_post( $message ); ?></span>
</div>
