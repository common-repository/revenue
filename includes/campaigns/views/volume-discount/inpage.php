<?php
namespace Revenue;

/**
 * The Template for displaying revenue view
 *
 * @package Revenue
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;
global $product;

$offered_product = false;
$regular_price   = false;
$offered_price   = false;
$output_content  = '';

$view_mode = 'list';
if ( 'list' == $view_mode ) {
	$buider_data = revenue()->get_campaign_meta( $campaign['id'], 'builderdata', true )['inpage']['list'];
} else {
	$buider_data = revenue()->get_campaign_meta( $campaign['id'], 'builderdata', true )['inpage']['grid'];
}

$generated_styles         = revenue()->campaign_style_generator( 'inpage', $campaign, $placement );
$offers                   = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
$on_cart_action           = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_on_cart_action', true );
$on_offered_product_click = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_click_action', true );
$offer_data               = array();

if ( $view_mode == 'list' ) {
	if ( is_array( $offers ) ) {
		$offer_product_id = $product->get_id();
		$offer_length     = count( $offers );

		foreach ( $offers as $offer_index => $offer ) {
			// settings
			$is_selected     = $offer_length - 1 == $offer_index;
			$offered_product = $product;
			$offer_type      = $offer['type'];
			$offer_value     = $offer['value'];
			$offer_qty       = $offer['quantity'];
			$regular_price   = $offered_product->get_regular_price( 'edit' );
			$save_data       = '';
			if ( 'percentage' == $offer_type ) {
				$save_data = "$offer_value%";
			} elseif ( 'amount' == $offer_type ) {
				$save_data = wc_price( intval( $offer_qty ) * floatval( $offer_value ) );
			}

			// Translators: %s is the placeholder for the data being saved.
			$save_data               = sprintf( __( 'Save %s', 'revenue' ), $save_data );
			$in_percentage           = revenue()->calculate_discount_percentage( $regular_price, $offered_price );
			$image                   = wp_get_attachment_image_src( get_post_thumbnail_id( $offer_product_id ), 'single-post-thumbnail' );
			$price_data              = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price, true );
			$offered_price           = $price_data['price'];
			$is_qty_selector_enabled = revenue()->get_campaign_meta( $campaign['id'], 'quantity_selector_enabled', true );
			// Style
			$product_style              = revenue()->get_style( $generated_styles, 'product' );
			$product_attr_wrapper_style = revenue()->get_style( $generated_styles, 'productAttrWrapper' );
			$checkbox_selected_style    = revenue()->get_style( $generated_styles, 'choseOfferIcon' );
			$checkbox_default_style     = revenue()->get_style( $generated_styles, 'choseOfferDefaultIcon' );
			$attribute_field_style      = revenue()->get_style( $generated_styles, 'productAttrField' );

			// Translators: %s is the placeholder for the offer quanity.
			$product_title = sprintf( __( 'Buy %s Quantities', 'revenue' ), $offer_qty );

			if ( ! $product->is_type( 'variable' ) ) {
				if ( ! isset( $offer_data[ $offer_product_id ]['regular_price'] ) ) {
					$offer_data[ $offer_product_id ]['regular_price'] = $regular_price;
				}
				if ( ! isset( $offer_data[ $offer_product_id ]['offer'] ) ) {
					$offer_data[ $offer_product_id ]['offer'] = array();
				}
				$offer_data[ $offer_product_id ]['offer'][] = array(
					'qty'   => $offer_qty,
					'type'  => $offer_type,
					'value' => $offer_value,
				);
			}

			$item_class = 'revx-campaign-item';

			if ( isset( $offer['isEnableTag'] ) && 'yes' == $offer['isEnableTag'] ) {
				$product_style = revenue()->get_style( $generated_styles, 'taggedProduct' );
				$item_class    = 'revx-campaign-item revx-campaign-item--popular';
			}

			ob_start();
			?>

			<div data-quantity="<?php echo esc_attr( $offer_qty ); ?>" data-revx-selected="<?php echo esc_attr( $is_selected?'true':'false' ); ?>" id="revenue-campaign-item-<?php echo esc_attr( $offer_product_id . '-' . $campaign['id'] ); ?>" class="<?php echo esc_attr( $item_class ); ?>"
				data-product-id="<?php echo esc_attr( ! $product->is_type( 'variable' ) ? $offer_product_id : '' ); ?>" data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>" style="<?php echo esc_attr( $product_style ); ?>">
					<div class="revx-justify-space" style="gap: inherit;">
						<div class="revx-align-center">
							<div class="revx-volume-discount__tag" data-default-style="<?php echo esc_attr( $checkbox_default_style ); ?>" data-selected-style="<?php echo esc_attr( $checkbox_selected_style ); ?>"  style="<?php echo esc_attr( $is_selected?$checkbox_selected_style: $checkbox_default_style ); ?>"></div>
							<div class="revx-align-center revx-volume-discount__text">
                                <?php
                                    echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'productTitle', $product_title, 'revx-volume-title' ), revenue()->get_allowed_tag() );
                                    echo wp_kses(
                                        revenue()->get_template_part(
                                            'save',
                                            array(
                                                'quantity' => $offer_qty,
                                                'message'  => $price_data['message'],
												'generated_styles' => $generated_styles,
												'regular_price' => $regular_price,
												'offered_price' => $offered_price,
												'current_campaign'=>$campaign
                                            )
                                        ),
                                        revenue()->get_allowed_tag()
                                    );
                                ?>
                            </div>
						</div>
						<div>
							<?php
							echo wp_kses(
								revenue()->get_template_part(
									'price_container',
									array(
										'campaign_type'    => 'volume_discount',
										'quantity'         => $offer_qty,
										'offered_product'  => $offered_product,
										'generated_styles' => $generated_styles,
										'regular_price'    => $regular_price,
										'offered_price'    => $offered_price,
										'current_campaign' => $campaign,
									)
								),
								revenue()->get_allowed_tag()
							);
							?>
						</div>
					</div>


					<?php
					if ( isset( $offer['isEnableTag'] ) && 'yes' == $offer['isEnableTag'] ) {
						echo wp_kses(
							revenue()->get_template_part(
								'badge_tag',
								array(
									'generated_styles' => $generated_styles,
									'current_campaign' => $campaign,
								)
							),
							revenue()->get_allowed_tag()
						);
					}
					if ( $product->is_type( 'variable' ) ) {
						$available_variations = $product->get_available_variations();
						$attributes           = $product->get_variation_attributes();
						$selected_attributes  = $product->get_default_attributes();

						foreach ( $available_variations as $variations ) {

							$offer_product_id = $variations['variation_id'];

							$offered_product = wc_get_product( $offer_product_id );

							if ( ! isset( $offer_data[ $offer_product_id ]['regular_price'] ) ) {
								$offer_data[ $offer_product_id ]['regular_price'] = $offered_product->get_regular_price();
							}
							if ( ! isset( $offer_data[ $offer_product_id ]['offer'] ) ) {
								$offer_data[ $offer_product_id ]['offer'] = array();
							}
							$offer_data[ $offer_product_id ]['offer'][] = array(
								'qty'   => $offer_qty,
								'type'  => $offer_type,
								'value' => $offer_value,
							);
						}

						?>
						<div class="revx-productAttr-wrapper revx-justify-space" >
							<div class="revx-full-width revx-align-center" style="<?php echo esc_attr( $product_attr_wrapper_style ); ?>">
								<?php
								foreach ( $attributes as  $attribute_name => $options ) {
									?>
											<div class="revx-full-width" >
											<?php
												echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'productAttrLabel', wc_attribute_label( $attribute_name ), 'revx-productAttr-wrapper__label', 'h5' ), revenue()->get_allowed_tag() );
												revenue()->dropdown_variation_attribute_options(
													$generated_styles,
													array(
														'options'   => $options,
														'attribute' => $attribute_name,
														'product'   => $product,
													)
												);
											?>
											</div>
										<?php
								}
								?>
							</div>
						</div>
						<?php
					}

					if ( 'yes' == revenue()->get_campaign_meta( $campaign['id'], 'quantity_selector_enabled', true ) ) {
						echo wp_kses( revenue()->get_template_part( 'product_separator', array( 'generated_styles' => $generated_styles ) ), revenue()->get_allowed_tag() );
						?>
						<div class="revx-justify-space">
						<?php
							echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'quantityLabel', 'Quantity', 'revx-quantity-label', 'h4' ), revenue()->get_allowed_tag() );
							echo wp_kses(
								revenue()->get_template_part(
									'quantity_selector',
									array(
										'quantity'         => $offer_qty,
										'min_quantity'     => $offer_qty,
										'value'            => $offer_qty,
										'generated_styles' => $generated_styles,
										'current_campaign' => $campaign,
										'offered_product'  => $offered_product,
									)
								),
								revenue()->get_allowed_tag()
							);
						?>
					</div>
						<?php
					}
					?>

			</div>
			<?php
			$output_content .= ob_get_clean();
		}
	}
}

if ( ! $output_content ) {
	return;
}
$container_style = revenue()->get_style( $generated_styles, 'container' );
$wrapper_style   = revenue()->get_style( $generated_styles, 'wrapper' );
$heading_text    = isset( $campaign['banner_heading'] ) ? $campaign['banner_heading'] : '';

$containerClass = 'revx-campaign-list revx-volume-discount revx-volume-discount-list';

ob_start();
?>

	<div class="revx-campaign-container__wrapper revx-campaign-text-content" style="<?php echo esc_attr( $wrapper_style ); ?>">
		<?php
				echo wp_kses( $output_content, revenue()->get_allowed_tag() );
		?>
		<?php
		echo wp_kses(
			revenue()->get_template_part(
				'add_to_cart',
				array(
                    'generated_styles' => $generated_styles,
                    'current_campaign' => $campaign,
                    'offered_product'  => $offered_product,
				)
			),
			revenue()->get_allowed_tag()
		);
		?>
	</div>

	<input type="hidden" name="<?php echo esc_attr( 'revx-offer-data-' . $campaign['id'] ); ?>" value="<?php echo esc_html( htmlspecialchars( wp_json_encode( $offer_data ) ) ); ?>" />

<?php
$output_content = ob_get_clean();

revenue()->inpage_container( $campaign, $generated_styles, $output_content, $containerClass, $placement );
