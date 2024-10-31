<?php
namespace Revenue;

/**
 * Display the Checkbox
 *
 * This template is used to render the Checkbox.
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

$checkbox_selected_style = revenue()->get_style($generated_styles,'checkboxSelected');
$checkbox_default_style = revenue()->get_style($generated_styles,'checkboxDefault');
$checkbox_required_style = revenue()->get_style($generated_styles,'checkboxRequired');
$currentStyle = $checkbox_default_style;
if($required) {
    $currentStyle = $checkbox_required_style;
} else if($selected) {
    $currentStyle = $checkbox_selected_style;
}

?>

<div data-default-style="<?php echo esc_attr( $checkbox_default_style ); ?>" data-selected-style="<?php echo esc_attr($checkbox_selected_style); ?>" class="revx-builder-checkbox revx-justify-center" style="<?php echo esc_attr($currentStyle); ?>">
    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 12 12"><path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9.75 3.75 5 8.5 2.625 6.125"></path></svg>
</div>
