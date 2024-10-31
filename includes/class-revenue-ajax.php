<?php
namespace Revenue;

/**
 * Revenue Campaign
 *
 * @hooked on init
 */
class Revenue_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_revenue_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_revenue_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wp_ajax_revenue_add_bundle_to_cart', array( $this, 'add_bundle_to_cart' ) );
		add_action( 'wp_ajax_nopriv_revenue_add_bundle_to_cart', array( $this, 'add_bundle_to_cart' ) );
		add_action( 'wp_ajax_revenue_close_popup', array( $this, 'close_popup' ) );
		add_action( 'wp_ajax_nopriv_revenue_close_popup', array( $this, 'close_popup' ) );
		add_action( 'wp_ajax_revenue_count_impression', array( $this, 'count_impression' ) );
		add_action( 'wp_ajax_nopriv_revenue_count_impression', array( $this, 'count_impression' ) );

		add_action( 'template_redirect', array( $this, 'collect_data_if_not_collected' ) );

		add_filter( 'revenue_rest_before_prepare_campaign', array( $this, 'modify_campaign_rest_response' ) );

		add_action( 'wp_ajax_revenue_get_product_price', array( $this, 'get_product_price' ) );

		add_action( 'wp_ajax_revx_get_next_campaign_id', array( $this, 'get_next_campaign_id' ) );

		add_action( 'wp_ajax_revx_get_campaign_limits', array( $this, 'get_campaign_limits' ) );

		add_action( 'wp_ajax_revx_activate_woocommerce', array( $this, 'activate_woocommerce' ) );
		add_action( 'wp_ajax_revx_install_woocommerce', array( $this, 'install_woocommerce' ) );
        add_action('wp_ajax_revenue_get_search_suggestion',[$this,'get_search_suggestion']);

	}

	public function get_next_campaign_id() {
		$nonce = '';
		if ( isset( $_POST['security'] ) ) {
			$nonce = sanitize_key( $_POST['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
        if(!wp_verify_nonce( $nonce, 'revenue-dashboard' )) {
            die();
        }

		global $wpdb;
		$res = $wpdb->get_row( "SELECT COALESCE(MAX(id), 0) + 1 AS next_campaign_id FROM {$wpdb->prefix}revenue_campaigns;" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return wp_send_json_success( array( 'next_campaign_id' => $res->next_campaign_id ) );

	}
	public function get_product_price() {
		check_ajax_referer( 'revenue-get-product-price', false );

		$product_id = isset( $_GET['product_id'] ) ? sanitize_text_field( wp_unslash($_GET['product_id']) ) : '';

		$product = wc_get_product( $product_id );
		$data    = array();
		if ( $product ) {
			$data['sale_price']    = $product->get_sale_price();
			$data['regular_price'] = $product->get_regular_price();
		}

		return wp_send_json_success( $data );
	}

	public function modify_campaign_rest_response( $data ) {

		// revenue()->clear_campaign_runtime_cache($data['id']);
		if ( empty( $data['campaign_display_style'] ) ) {
			$data['campaign_display_style'] = 'inpage';
		}
		if ( empty( $data['campaign_builder_view'] ) ) {
			$data['campaign_builder_view'] = 'list';
		}
		if ( is_null( $data['offers'] ) ) {
			$data['offers'] = array();
		}
		if ( isset( $data['offers'] ) ) {

			foreach ( $data['offers'] as $idx => $offer ) {

				$products_data = array();
				foreach ( $offer['products'] as $product_id ) {

					$product = wc_get_product( $product_id );
					if ( $product ) {
						$products_data[] = array(
							'item_id'       => $product_id,
							'item_name'     => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
							'thumbnail'     => wp_get_attachment_url( $product->get_image_id() ),
							'regular_price' => $product->get_regular_price(),
							'url'       	=> get_permalink( $product_id ),
						);
					}
				}

				$data['offers'][ $idx ]['products'] = $products_data;
			}
		}

		if ( is_null( $data['campaign_start_date_time'] ) ) {
			$data['campaign_start_date'] = gmdate( 'Y-m-d', time() );
			$data['campaign_start_time'] = gmdate( 'H:00', time() );
		} else {
			$timestamp                   = strtotime( $data['campaign_start_date_time'] );
			$data['campaign_start_date'] = gmdate( 'Y-m-d', $timestamp );
			$data['campaign_start_time'] = gmdate( 'H:i', $timestamp );
		}

		if ( isset( $data['schedule_end_time_enabled'] ) && 'yes' === $data['schedule_end_time_enabled'] ) {
			if ( is_null( $data['campaign_end_date_time'] ) ) {
				$data['campaign_end_date'] = gmdate( 'Y-m-d', time() );
				$data['campaign_end_time'] = gmdate( 'H:00', time() );
			} else {
				$timestamp                 = strtotime( $data['campaign_end_date_time'] );
				$data['campaign_end_date'] = gmdate( 'Y-m-d', $timestamp );
				$data['campaign_end_time'] = gmdate( 'H:i', $timestamp );
			}
		}


		if ( is_null( $data['stock_scarcity_actions'] ) ) {
			$data['stock_scarcity_actions'] =
			 array(
				 array(
					 'id'            => 1,
					 'action'        => 'yes',
					 'stock_status'  => 'In Stock',
					 'stock_message' => '{stock_number} items in stock',
					 'stock_value'   => '-',
				 ),
				 array(
					 'id'            => 2,
					 'action'        => 'yes',
					 'stock_status'  => 'Low Stock',
					 'stock_message' => 'only {stock_number} items left in stock!',
					 'stock_value'   => '',
				 ),
				 array(
					 'id'            => 3,
					 'action'        => 'yes',
					 'stock_status'  => 'Urgent Stock',
					 'stock_message' => 'Hurry! Almost sold out. Hurry! only {stock_number} items left in stock!',
					 'stock_value'   => '',
				 ),
			 );
		}

		if ( is_null( $data['builder'] ) ) {
			unset( $data['builder'] );
		}

		if ( is_null( $data['builderdata'] ) ) {
			unset( $data['builderdata'] );
		}


		if(isset($data['campaign_type']) && 'mix_match' == $data['campaign_type']) {
			$data['campaign_trigger_relation'] = 'and';
		} else {
			if(empty($data['campaign_trigger_relation'])) {
				$data['campaign_trigger_relation'] = 'or';
			}
		}
		if(empty($data['campaign_placement'])) {
			$data['campaign_placement'] = 'multiple';
		}
		// if(empty($data['campaign_inpage_position'])) {
		// 	$data['campaign_inpage_position'] = 'multiple';
		// }
		// if(empty($data['campaign_display_style'])) {
		// 	$data['campaign_display_style'] = 'multiple';
		// }

		if(empty($data['campaign_trigger_type'])) {
			$data['campaign_trigger_type'] = 'products';
		}
		if(empty($data['offered_product_click_action'])) {
			$data['offered_product_click_action'] = 'go_to_product';
		}

		if(empty($data['add_to_cart_animation_trigger_type'])) {
			$data['add_to_cart_animation_trigger_type'] = 'on_hover';
		}
		if(empty($data['countdown_start_time_status'])) {
			$data['countdown_start_time_status'] = 'right_now';
		}

		if(isset($data['campaign_placement']) && 'multiple' != $data['campaign_placement']) {
			$data['placement_settings'] = [
				$data['campaign_placement'] => [
					'page' => $data['campaign_placement'],
					'status' => 'yes',
					'display_style' => $data['campaign_display_style'] ?? 'inpage',
					'builder_view' => $data['campaign_builder_view'],
					'inpage_position' => $data['campaign_inpage_position']?$data['campaign_inpage_position']:'before_add_to_cart_form',
					'popup_animation' => $data['campaign_popup_animation'],
					'popup_animation_delay' => $data['campaign_popup_animation_delay'],
					'floating_position' => $data['campaign_floating_position'],
					'floating_animation_delay' => $data['campaign_floating_animation_delay']
				]
			];

			$data['placement_settings'] = $data['placement_settings'];
			$data['campaign_placement'] = 'multiple';
			$data['campaign_display_style'] = 'multiple';
			$data['campaign_inpage_position'] = 'multiple';
		}

		if(empty($data['offered_product_on_cart_action'])) {
			if('normal_discount' == $data['campaign_type']) {
				$data['offered_product_on_cart_action'] = 'hide_products';
			} else {
				$data['offered_product_on_cart_action'] = 'hide_campaign';
			}
		}
		if (empty($data['active_page']) && !empty($data['placement_settings'])) {
			$placementSettings = (array) $data['placement_settings'];
			$data['active_page'] = !empty($placementSettings) ? array_keys($placementSettings)[0] : 'product_page';
		}
		return $data;
	}


	/**
	 * Reveneux Add to cart
	 *
	 * @return void
	 */
	public function add_to_cart() {

		check_ajax_referer( 'revenue-add-to-cart', false );

		$product_id  = isset($_POST['productId'])?sanitize_text_field(wp_unslash($_POST['productId'])): '';
		$campaign_id = isset($_POST['campaignId'])? sanitize_text_field( wp_unslash($_POST['campaignId'] ) ):'';
		$quantity    = isset($_POST['quantity'])? sanitize_text_field( wp_unslash( $_POST['quantity'] ) ):'';
		$has_free_shipping_enabled = revenue()->get_campaign_meta($campaign_id,'free_shipping_enabled',true) ?? 'no';

		$campaign = (array) revenue()->get_campaign_data( $campaign_id, 'package' );

		$offers = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
		$status = false;

        $cart_item_data = [
            'rev_is_free_shipping'       => $has_free_shipping_enabled,
            'revx_campaign_id'           => $campaign_id,
			'revx_campaign_type'         => $campaign['campaign_type'],
        ];

		if ( 'buy_x_get_y' == $campaign['campaign_type'] ) {

			$bxgy_data = isset( $_POST['bxgy_data'] ) ? array_map( 'sanitize_text_field', wp_unslash($_POST['bxgy_data']) ) : array();

			$trigger_product_relation = isset( $campaign['campaign_trigger_relation'] ) ? $campaign['campaign_trigger_relation'] : 'or';

            if(empty($trigger_product_relation)) {
                $trigger_product_relation = 'or';
            }

			$trigger_items = revenue()->getTriggerProductsData($campaign['campaign_trigger_items'],$trigger_product_relation,$product_id);
			$trigger_product_ids = [];
            $trigger_product_qty = [];
			foreach ($trigger_items as $titem) {
				$trigger_product_ids[$titem['item_id']]=$bxgy_data[$titem['item_id']] ?? 1;
                unset($bxgy_data[$titem['item_id']]);

                $trigger_product_qty[$titem['item_id']] = $titem['quantity'];
			}

			$parent_keys    = array();
			$cart_item_data = array_merge($cart_item_data,array(
				'revx_bxgy_trigger_products' => $bxgy_data,
				'revx_bxgy_items'            => array(),
				'revx_offer_data'            => $offers,
				'revx_offer_products'        => $bxgy_data,
				'revx_bxgy_all_triggers_key' => array(),
				'revx_required_qty'          => 1,
			) );


			$all_passed = true;
			$i          = 0;
			foreach ( $trigger_product_ids as $id => $qty ) {
                // $cart_item_data['revx_required_qty'] = $bxgy_data['']
                $i++;
                $cart_item_data['revx_required_qty'] = $trigger_product_qty[$id];

				if ( $i == count( $trigger_product_ids ) ) {
					// Last Product
					$cart_item_data['revx_bxgy_last_trigger']     = true;
					$cart_item_data['revx_bxgy_all_triggers_key'] = $parent_keys;
					$status                                       = WC()->cart->add_to_cart( $id, $qty, 0, array(), $cart_item_data );

				} else {
					$status = WC()->cart->add_to_cart( $id, $qty, 0, array(), $cart_item_data );
				}
				if ( $status ) {
					$parent_keys[] = $status;

					if ( $status ) {
						do_action( 'revenue_item_added_to_cart', $status, $id, $campaign_id );
					}
				} else {
					$all_passed = false;
				}
			}

			if ( $all_passed ) {
				do_action( 'revenue_campaign_buy_x_get_y_after_added_trigger_products', $parent_keys, $cart_item_data, $trigger_product_ids );

			} else {
				$status = false;
			}

			revenue()->increment_campaign_add_to_cart_count( $campaign_id );

		} elseif ( 'mix_match' == $campaign['campaign_type'] ) {
			$required_products          = revenue()->get_campaign_meta( $campaign['id'], 'mix_match_required_products', true ) ?? array();
			$mix_match_trigger_products = revenue()->get_item_ids_from_triggers( $campaign );
			$mix_match_data             = isset( $_POST['mix_match_data'] ) ? array_map( 'sanitize_text_field', wp_unslash($_POST['mix_match_data']) ) : array();

            $cart_item_data = array_merge($cart_item_data,array(
                'revx_campaign_id'        => $campaign_id,
                'revx_campaign_type'      => $campaign['campaign_type'],
                'revx_required_products'  => $required_products,
                'revx_mix_match_products' => array_keys($mix_match_data),
                'revx_offer_data'         => $offers,
                'rev_is_free_shipping'	  => $has_free_shipping_enabled
            ) );


			foreach ( $mix_match_data as $pid=> $qty ) {
				$status = WC()->cart->add_to_cart(
					$pid,
					$qty,
					0,
					array(),
					$cart_item_data
				);
				revenue()->increment_campaign_add_to_cart_count( $campaign_id, $pid );

				if ( $status ) {
					do_action( 'revenue_item_added_to_cart', $status, $pid, $campaign_id );
				}
			}
		} elseif ( 'frequently_bought_together' == $campaign['campaign_type'] ) {
			$required_product = isset($_POST['requiredProduct'])? sanitize_text_field( wp_unslash( $_POST['requiredProduct'] ) ):'';
            $ftb_data = isset($_POST['fbt_data'])? array_map('sanitize_text_field',wp_unslash($_POST['fbt_data'])): [];

			$is_required_trigger_product = revenue()->get_campaign_meta($campaign_id,'fbt_is_trigger_product_required',true);

			if('yes' == $is_required_trigger_product) {
				if(!isset($ftb_data[$required_product])) {
					return wp_send_json_success(  );
				}
			}


            $cart_item_data = array_merge($cart_item_data, array(
                'revx_campaign_id'          => $campaign_id,
                'revx_campaign_type'        => $campaign['campaign_type'],
                'revx_fbt_required_product' => $required_product,
                'revx_fbt_data' => $ftb_data,
                'revx_offer_data'           => $offers,
                'rev_is_free_shipping'		=> $has_free_shipping_enabled
            ));

			foreach ( $ftb_data as $pid => $qty ) {

                $status = WC()->cart->add_to_cart(
                    $pid,
                    $qty,
                    0,
                    array(),
                    $cart_item_data
                );
                if ( $status ) {
                    do_action( 'revenue_item_added_to_cart', $status, $pid, $campaign_id );
                }
            }

			revenue()->increment_campaign_add_to_cart_count( $campaign_id );

		} elseif ( 'spending_goal' == $campaign['campaign_type'] ) {
			$discount_config = revenue()->get_var( $campaign['spending_goal_upsell_discount_configuration'] );
			$discount_type   = revenue()->get_var( $discount_config['type'] ) ?? 1;
			$min_qty         = revenue()->get_var( $discount_config['quantity'] ) ?? 1;
			$offer_value     = revenue()->get_var( $discount_config['value'] ) ?? 0;

			foreach ( $discount_config['products'] as $idx => $item_data ) {
				if ( $product_id == $item_data['item_id'] ) {
					$item = wc_get_product( $item_data['item_id'] );
					if ( ! $item ) {
						continue;
					}
					$regular_price = $item->get_regular_price( 'edit' );
					$sale_price    = revenue()->calculate_campaign_offered_price( $discount_type, $offer_value, $regular_price );

					$status = WC()->cart->add_to_cart(
						$product_id,
						$min_qty,
						0,
						array(),
						array(
							'revx_campaign_id'     => $campaign_id,
							'revx_campaign_type'   => $campaign['campaign_type'],
							'rev_is_free_shipping' => $has_free_shipping_enabled,

						)
					);
					revenue()->increment_campaign_add_to_cart_count( $campaign_id );
				}
			}

			if ( $status ) {
				do_action( 'revenue_item_added_to_cart', $status, $product_id, $campaign_id );
			}
		} else {

			$offer_qty = '';
			if ( is_array( $offers ) ) {
				foreach ( $offers as $offer ) {

					$offered_product_ids = $offer['products'];
					$offer_qty           = $offer['quantity'];

					if ( 'volume_discount' == $campaign['campaign_type'] ) {
						$offered_product_ids   = array();
						$offered_product_ids[] = $product_id;
					}

					foreach ( $offered_product_ids as $offer_product_id ) {
						$offered_product = wc_get_product( $offer_product_id );
						if ( ! $offered_product ) {
							continue;
						}
						if ( $offer_product_id === $product_id ) {
							if ( 'yes' === revenue()->get_campaign_meta( $campaign['id'], 'quantity_selector_enabled', true ) ) {
								$offer_qty = max( $quantity, $offer_qty );
							}

							if ( 'volume_discount' == $campaign['campaign_type'] ) {
								$offer_qty = max( $quantity, $offer_qty );
							}

                            if(!( 'volume_discount' == $campaign['campaign_type'] )) {
                                $status = WC()->cart->add_to_cart(
                                    $product_id,
                                    $offer_qty,
                                    0,
                                    array(),
                                    $cart_item_data
                                );
                                revenue()->increment_campaign_add_to_cart_count( $campaign_id );
                            }



						}
					}
				}
			}

            if(( 'volume_discount' == $campaign['campaign_type'] )) {
                $status = WC()->cart->add_to_cart(
                    $product_id,
                    $quantity,
                    0,
                    array(),
                    $cart_item_data
                );
                revenue()->increment_campaign_add_to_cart_count( $campaign_id );
            }
			if ( $status ) {
				do_action( 'revenue_item_added_to_cart', $status, $product_id, $campaign_id );
			}
		}



		$on_cart_action = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_on_cart_action', true );

		$campaign_source_page = isset($_POST['campaignSourcePage'])?sanitize_text_field($_POST['campaignSourcePage']):'';

		$response_data = array(
			'add_to_cart'    => $status,
			'on_cart_action' => $on_cart_action,
		);
		switch ($campaign_source_page) {
			case 'cart_page':
					$response_data['is_reload'] = true;
				break;
			case 'checkout_page':
					$response_data['is_reload'] = true;
				break;

			default:
				# code...
				break;
		}
		wp_send_json_success($response_data);
	}
	/**
	 * Reveneux Add Bundle to cart
	 *
	 * @return void
	 */
	public function add_bundle_to_cart() {

		check_ajax_referer( 'revenue-add-to-cart', false );

		$campaign_id  = isset( $_POST['campaignId'])? sanitize_text_field( wp_unslash( $_POST['campaignId'] ) ):'';
		$quantity     = isset($_POST['quantity'])? sanitize_text_field( wp_unslash( $_POST['quantity'] ) ):'';

		$bundle_product_id = get_option( 'revenue_bundle_parent_product_id', false );

		if ( ! $bundle_product_id ) {
			wp_send_json_error();
		}

		$campaign                  = (array) revenue()->get_campaign_data( $campaign_id );
		$has_free_shipping_enabled = revenue()->get_campaign_meta( $campaign_id, 'free_shipping_enabled', true ) ?? 'no';

		$offers         = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
		$is_qty_enabled = revenue()->get_campaign_meta( $campaign['id'], 'quantity_selector_enabled', true );

		if ( 'yes' != $is_qty_enabled ) {
			$quantity = 1;
		}

		$bundle_id = $campaign['id'] . '_' . wp_rand( 1, 9999999 );
		if ( 'yes' == $campaign['bundle_with_trigger_products_enabled'] ) {

		}
		$bundle_data = array(
			'revx_campaign_id'   => $campaign_id,
			'revx_bundle_id'     => $bundle_id,
			'revx_bundle_data'   => $offers,
			'revx_bundle_type'   => 'trigger',
			'revx_bundled_items' => array(),
			'revx_campaign_type' => $campaign['campaign_type'],
			'rev_is_free_shipping' => $has_free_shipping_enabled,
		);

		if('yes' == $campaign['bundle_with_trigger_products_enabled']) {
			$trigger_product_id = isset( $_POST['trigger_product_id'])?  sanitize_text_field( wp_unslash( $_POST['trigger_product_id'] ) ):'';
			$bundle_data['revx_bundle_with_trigger'] = 'yes';
			$bundle_data['revx_trigger_product_id'] = $trigger_product_id;
			$bundle_data['revx_min_qty'] = 1;
		}

		$status = WC()->cart->add_to_cart( $bundle_product_id, $quantity, 0, array(), $bundle_data );

		if($status) {
			revenue()->increment_campaign_add_to_cart_count( $campaign_id );
		}

		$on_cart_action = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_on_cart_action', true );

		$campaign_source_page = isset($_POST['campaignSrcPage'])?sanitize_text_field($_POST['campaignSrcPage']):'';

		$response_data = array(
			'add_to_cart'    => $status,
			'on_cart_action' => $on_cart_action,
		);
		switch ($campaign_source_page) {
			case 'cart_page':
					$response_data['is_reload'] = true;
				break;
			case 'checkout_page':
					$response_data['is_reload'] = true;
				break;

			default:
				# code...
				break;
		}
		wp_send_json_success($response_data);
	}


	public function close_popup() {
        check_ajax_referer( 'revenue-nonce', false ); // Add this nonce on js and also localize this

		$campaign_id = isset($_POST['campaignId'])? sanitize_text_field( wp_unslash( $_POST['campaignId'] ) ):'';

		$cart_data = WC()->session->get( 'revenue_cart_data' );

		if ( ! ( is_array( $cart_data ) && isset( $cart_data[ $campaign_id ] ) ) ) {
			revenue()->increment_campaign_rejection_count( $campaign_id );
		}

		wp_send_json_success( array( 'rejection_updated' => true ) );

	}
	public function count_impression() {
        check_ajax_referer( 'revenue-nonce', false ); // Add this nonce on js and also localize this

		$campaign_id = isset($_POST['campaignId'])? sanitize_text_field( wp_unslash( $_POST['campaignId'] ) ):'';

		revenue()->update_campaign_impression( $campaign_id );

		wp_send_json_success( array( 'impression_count_updated' => true ) );

	}


	public function collect_data_if_not_collected() {
	}


	public function get_campaign_limits() {
		$nonce = '';
		if ( isset( $_POST['security'] ) ) {
			$nonce = sanitize_key( $_POST['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
        if(!wp_verify_nonce( $nonce, 'revenue-dashboard' )) {
            die();
        }

		global $wpdb;
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$res = $wpdb->get_row(
			"SELECT
                COUNT(*) AS total_campaigns,
                SUM(CASE WHEN campaign_type = 'normal_discount' THEN 1 ELSE 0 END) AS normal_discount,
                SUM(CASE WHEN campaign_type = 'volume_discount' THEN 1 ELSE 0 END) AS volume_discount,
                SUM(CASE WHEN campaign_type = 'bundle_discount' THEN 1 ELSE 0 END) AS bundle_discount,
                SUM(CASE WHEN campaign_type = 'buy_x_get_y' THEN 1 ELSE 0 END) AS buy_x_get_y
            FROM {$wpdb->prefix}revenue_campaigns;"
		); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return wp_send_json_success( $res );
	}



	public function activate_woocommerce() {

		$nonce = '';
		if ( isset( $_POST['security'] ) ) {
			$nonce = sanitize_key( $_POST['security'] );
		}
        if(!wp_verify_nonce( $nonce, 'revenue-dashboard' )) {
            die();
        }
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions to activate plugins.', 'revenue' ) );
		}
		$result = activate_plugin( 'woocommerce/woocommerce.php' );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		wp_send_json_success();
	}


	public function install_woocommerce() {

		$nonce = '';
		if ( isset( $_POST['security'] ) ) {
			$nonce = sanitize_key( $_POST['security'] );
		}
        if(!wp_verify_nonce( $nonce, 'revenue-dashboard' )) {
            die();
        }
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions to install plugins.', 'revenue' ) );
		}

		if ( ! class_exists( 'WP_Upgrader' ) ) {
			include ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! function_exists( 'plugins_api' ) ) {
			include ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		$plugin_slug = 'woocommerce';
		$api         = plugins_api(
			'plugin_information',
			array(
				'slug'   => $plugin_slug,
				'fields' => array(
					'sections' => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			wp_send_json_error( $api->get_error_message() );
		}
		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		if ( ! $result ) {
			wp_send_json_error( __( 'Plugin installation failed.', 'revenue' ) );
		}
		wp_send_json_success();
	}


    public function get_search_suggestion() {

		$nonce = '';
		if ( isset( $_GET['security'] ) ) {
			$nonce = sanitize_key( $_GET['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
        if(!wp_verify_nonce( $nonce, 'revenue-dashboard' )) {
            die();
        }

		$type = isset($_GET['type'])? sanitize_text_field($_GET['type']): '';

		$data = array();

		if('products' == $type) {

			$args = array(
				'limit' => 5, // Limit to 5 products
				'orderby' => 'date', // Order by date
				'order' => 'ASC', // Ascending order
			);

			$products = wc_get_products($args);

			$source = isset($_GET['source'])?sanitize_text_field( $_GET['source'] ):'';

			$campaign_type = isset($_GET['campaign_type'])?sanitize_text_field($_GET['campaign_type']):'';
			foreach ( $products as $product ) {
				if ( $product ) {

					$chilren    = $product->get_children();
					$child_data = array();
					$product_link = get_permalink($product);
					if ( is_array( $chilren ) ) {
						foreach ( $chilren as $child_id ) {
							$child        = wc_get_product( $child_id );
							$child_data[] = array(
								'item_id'       => $child_id,
								'item_name'     => rawurldecode( wp_strip_all_tags( $child->get_name() ) ),
								// 'product_title_with_sku' => rawurldecode( wp_strip_all_tags($with_sku? $child->get_formatted_name(): $child->get_title())),
								'thumbnail'     => wp_get_attachment_url( $child->get_image_id() ),
								'regular_price' => $child->get_regular_price(),
								'parent'        => $product->get_id(),
								'url'			=> $product_link,
							);
						}
					}

                    if($source=='trigger' && $campaign_type!='mix_match') {
                        $data[] = array(
                            'item_id'       => $product->get_id(),
                            'url'			=> get_permalink($product),
                            'item_name'     => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
                            'thumbnail'     => wp_get_attachment_url( $product->get_image_id() ),
                            'regular_price' => $product->get_regular_price(),
                            'children'      =>  [],
                        );

                    } else {
                        if(!empty($child_data)) {
                            $data = array_merge($data, $child_data);
                        } else {

                            $data[] = array(
                                'item_id'       => $product->get_id(),
                                'url'			=> get_permalink($product),
                                'item_name'     => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
                                'thumbnail'     => wp_get_attachment_url( $product->get_image_id() ),
                                'regular_price' => $product->get_regular_price(),
                                'children'      =>  [],
                            );
                        }
                    }

				}
			}


		} else if('category' == $type) {
			$category_args = array(
				'taxonomy' => 'product_cat', // Taxonomy for WooCommerce product categories
				'number' => 5, // Limit to 5 categories
				'orderby' => 'name', // Order by name
				'order' => 'ASC', // Ascending order
			);

			$categories = get_terms($category_args);

			foreach ($categories as $category) {
				if (!is_wp_error($category)) {
					$data[] = array(
						'item_id' => $category->term_id,
						'item_name' => $category->name,
						'url' => get_term_link($category), // Get the category link
						'thumbnail' => get_term_meta($category->term_id, 'thumbnail_id', true) ? wp_get_attachment_url(get_term_meta($category->term_id, 'thumbnail_id', true)) : wc_placeholder_img_src(), // Get category thumbnail
					);
				}
			}
		}

		wp_send_json_success($data);
	}

}
