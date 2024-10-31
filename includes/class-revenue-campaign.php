<?php
namespace Revenue;

use WC_Shipping_Free_Shipping;
use DateTime;

/**
 * Revenue Campaign
 *
 * @hooked on init
 */
class Revenue_Campaign  {
    /**
     * placement
     *
     */

	 public $campaigns = [];

     public $stock_scacities = [];

     public $campaign_additional_data = [];

     public $countdown_data = [];

     public $animated_button_data = [];

	 public $is_free_shipping = false;

	 public $spending_goals = [];

	 public $is_enqueue_data_already = false;

     public $is_already_render = 0;

     public $renderd_campaigns= [];
	 /**
	 * Flag to avoid infinite loops when removing a bundle parent via a child.
	 *
	 * @var string
	 */
	protected $removing_container_key = null;

    public function __construct() {

		add_action('revenue_item_added_to_cart',[$this,'after_add_to_cart'],10,3);

		// add_action('woocommerce_remove_cart_item',[$this,'after_remove_cart_item'],10,2);

		add_action('woocommerce_cart_item_restored',[$this,'after_cart_item_restored'],10,2);

		add_action('woocommerce_cart_emptied',[$this,'after_cart_emptied']);


		add_action('woocommerce_add_to_cart',[$this,'woocommerce_after_add_to_cart'],10,6);


		add_action('woocommerce_before_calculate_totals',[$this,'woocommerce_before_calculate_totals']);
		// add_action('woocommerce_cart_calculate_fees',[$this,'woocommerce_cart_calculate_fees']);

        add_action( 'woocommerce_check_cart_items', [$this,'woocommerce_check_cart_items'] );
		add_action('woocommerce_cart_item_remove_link',[$this,'woocommerce_cart_item_remove_link'],10,2);
		add_action('woocommerce_cart_item_quantity',[$this,'woocommerce_cart_item_quantity'],10,2);
		add_action('woocommerce_cart_item_class',[$this,'woocommerce_cart_item_class'],10,2);
		add_action('woocommerce_cart_item_subtotal',[$this,'woocommerce_cart_item_subtotal'],10,2);
		// add_action('woocommerce_checkout_item_subtotal',[$this,'woocommerce_checkout_item_subtotal'],10,2);
		add_action('woocommerce_cart_item_price',[$this,'woocommerce_cart_item_price'],10,2);
		add_action('woocommerce_get_item_data',[$this,'woocommerce_get_item_data'],10,2);

		add_action('woocommerce_cart_item_name',[$this,'woocommerce_cart_item_name'],10,2);

        add_action( 'woocommerce_after_cart_item_quantity_update', [$this,'woocommerce_after_cart_item_quantity_update'],10,2 );

		add_action('woocommerce_store_api_product_quantity_minimum',[$this,'woocommerce_store_api_product_quantity_minimum'],10,3);
		add_action('woocommerce_store_api_product_quantity_maximum',[$this,'woocommerce_store_api_product_quantity_maximum'],10,3);

		add_action('woocommerce_checkout_order_processed',[$this,'woocommerce_checkout_create_order'],10);
		add_action('woocommerce_store_api_checkout_order_processed',[$this,'woocommerce_checkout_create_order'],10);

		// add_action('woocommerce_store_api_checkout_update_order_meta',[$this,'woocommerce_checkout_create_order'],10);

		add_action('woocommerce_checkout_create_order_line_item',[$this,'woocommerce_checkout_create_order_line_item'],10,4);

		add_action('woocommerce_hidden_order_itemmeta',[$this,'woocommerce_hidden_order_itemmeta']);


		add_shortcode(revenue()->get_campaign_shortcode_tag(),[$this, 'render_campaign_view_shortcode']);

		add_filter('woocommerce_package_rates',[$this, 'handle_free_shipping'],10,2);

		add_action('wp',[$this,'run_all_page_campaigns']);


		add_action('wp_print_scripts',[$this,'localize_script']);



        // add_action( 'woocommerce_cart_updated', [$this,'force_recalculate_shipping'] );
        // add_action( 'woocommerce_checkout_update_order_review', [$this,'force_recalculate_shipping'] );

		add_action('revenue_campaign_before_header',[$this,'add_edit_campaign_link']);

    }



	/**
	 * Add Edit Campaign link
	 *
	 * @param string $campaign_id Campaign Id.
	 * @return void
	 */
	public function add_edit_campaign_link($campaign_id) {
		if(!current_user_can('manage_options')) {
			return;
		}
		?>
			<a class="revx-admin-edit" target="_blank" href="<?php echo esc_url(revenue()->get_edit_campaign_url($campaign_id)) ?>"><?php echo esc_html__('Edit Campaign','revenue'); ?></a>
		<?php
	}

    public function force_recalculate_shipping() {
        // Force WooCommerce to recalculate the cart shipping
        WC()->cart->calculate_shipping();

    }

	public function localize_script() {

		$campaign_localize_data = [
            'ajax'=>admin_url( 'admin-ajax.php'),
            'nonce'=>wp_create_nonce('revenue-add-to-cart'),
            'user'=>get_current_user_id(),
            'data' => $this->campaign_additional_data,
            'currency_format_num_decimals' => wc_get_price_decimals(),
            'currency_format_symbol' => get_woocommerce_currency_symbol(),
            'currency_format_decimal_sep' => wc_get_price_decimal_separator(),
            'currency_format_thousand_sep' => wc_get_price_thousand_separator(),
            'currency_format' => get_woocommerce_price_format(),
			'checkout_page_url' => wc_get_checkout_url(),
        ];

		if(!empty($this->campaign_additional_data)) {
			$this->is_enqueue_data_already = true;
		}
        wp_localize_script('revenue-campaign','revenue_campaign',$campaign_localize_data);

	}


	public function run_all_page_campaigns() {

		$which_page = '';
		if(is_product()) {
			$which_page = 'product_page';
		}else if(is_cart()) {
			$which_page = 'cart_page';
		} else if(is_checkout()) {
			$which_page = 'checkout_page';
		}


		if(!empty($which_page)) {

            $positions = revenue()->get_campaign_inpage_positions();

			$inpage_positions = isset($positions[$which_page])? array_keys($positions[$which_page]): [];

			foreach ($inpage_positions as $position) {
				if(method_exists($this,$position)) {
					add_action('woocommerce_'.$position,[$this,$position],1);
				}
			}

			if('checkout_page' == $which_page) {
				add_action('woocommerce_before_thankyou',[$this,'before_thankyou']);
				add_action('woocommerce_thankyou',[$this,'thankyou']);
			}

		}


		/**
		 * Filters the post content.
		 *
		 * @param string $content Content of the current post.
		 * @return string Content of the current post.
		 */
		add_filter('the_content',[$this,'run_cart_checkout_block_campaigns'] );

	}

	public function run_cart_checkout_block_campaigns($content) {
		global $product;
		$before_extra = '';
		$after_extra = '';
		$showed_product_id = [];
		ob_start();
		if(is_cart() && has_block('woocommerce/cart', get_the_ID())) {

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product   = $cart_item['data'];
				$product_id = $cart_item['product_id'];
				if(!isset($showed_product_id[$product_id])) {
					$showed_product_id[$product_id] = true;
					$campaigns = revenue()->get_available_campaigns($product_id,'cart_page','inpage','before_content');
					$this->run_campaigns($campaigns,'inpage','cart_page','before_content');
				}
			}
		}
		if(is_checkout() && has_block('woocommerce/checkout', get_the_ID())) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product   = $cart_item['data'];
				$product_id = $cart_item['product_id'];

				if(!isset($showed_product_id[$product_id])) {
					$showed_product_id[$product_id] = true;
					$campaigns = revenue()->get_available_campaigns($product_id,'checkout_page','inpage','before_content');
					$this->run_campaigns($campaigns,'inpage','cart_page','before_content');
				}
			}
		}
		$before_extra=ob_get_clean();
		ob_start();
		if(is_cart() && has_block('woocommerce/cart', get_the_ID())) {

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product   = $cart_item['data'];
				$product_id = $cart_item['product_id'];
				if(!isset($showed_product_id[$product_id])) {
					$showed_product_id[$product_id] = true;
					$campaigns = revenue()->get_available_campaigns($product_id,'cart_page','inpage','after_content');
					$this->run_campaigns($campaigns,'inpage','cart_page','after_content');
				}

			}
		}
		if(is_checkout() && has_block('woocommerce/checkout', get_the_ID())) {

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product   = $cart_item['data'];
				$product_id = $cart_item['product_id'];
				if(!isset($showed_product_id[$product_id])) {
					$showed_product_id[$product_id] = true;
					$campaigns = revenue()->get_available_campaigns($product_id,'checkout_page','inpage','after_content');
					$this->run_campaigns($campaigns,'inpage','cart_page','after_content');
				}
			}
		}

		$after_extra=ob_get_clean();


		return $before_extra.$content.$after_extra;
	}

    public function run_campaigns($campaigns,$display_type='inpage', $placement='',$position='') {
        $typewise_campaigns = [];


        wp_enqueue_style('revenue-campaign');
		wp_enqueue_style('revenue-utility');
		wp_enqueue_style('revenue-responsive');
		wp_enqueue_script('revenue-campaign');

        foreach ($campaigns as $campaign) {
			$campaign = (array) $campaign;

			if(revenue()->is_hide_campaign($campaign['id'])) {
				return;
			}

			if(!isset($campaign['campaign_status'])) {
				continue;
			}

			if('publish' != $campaign['campaign_status']) {
				continue;
			}

			if(!(isset(($campaign['campaign_type'])))) {
				continue;
			}

            $typewise_campaigns[$campaign['campaign_type']][] = $campaign;

            if(isset($this->renderd_campaigns[$campaign['id']])) {
                continue;
            }

            if(!isset($this->renderd_campaigns[$campaign['id']])) {
                $this->renderd_campaigns[$campaign['id']] = true;
            }


            $this->campaign_additional_data[$campaign['id']]['offered_product_click_action'] = $campaign['offered_product_click_action'];
            $this->campaign_additional_data[$campaign['id']]['offered_product_on_cart_action'] = $campaign['offered_product_on_cart_action'];
            $this->campaign_additional_data[$campaign['id']]['animated_add_to_cart_enabled'] = $campaign['animated_add_to_cart_enabled'];
            $this->campaign_additional_data[$campaign['id']]['add_to_cart_animation_trigger_type'] = $campaign['add_to_cart_animation_trigger_type'];
            $this->campaign_additional_data[$campaign['id']]['add_to_cart_animation_type'] = $campaign['add_to_cart_animation_type'];
            $this->campaign_additional_data[$campaign['id']]['add_to_cart_animation_start_delay'] = $campaign['add_to_cart_animation_start_delay'];
            $this->campaign_additional_data[$campaign['id']]['free_shipping_enabled'] = $campaign['free_shipping_enabled'];
			if(isset($campaign['countdown_timer_enabled']) && 'yes' == $campaign['countdown_timer_enabled']) {

				$countdown_data = [];

				$end_date = revenue()->get_campaign_meta($campaign['id'],'countdown_end_date',true);
                $end_time = revenue()->get_campaign_meta($campaign['id'],'countdown_end_time',true);

                $end_date_time = $end_date.' '.$end_time;


                $have_start_date_time = ("schedule_to_later"==revenue()->get_campaign_meta($campaign['id'],'countdown_start_time_status',true));

                $start_date_time = '';
                if($have_start_date_time) {
                    $start_date = revenue()->get_campaign_meta($campaign['id'],'countdown_start_date',true);
                    $start_time = revenue()->get_campaign_meta($campaign['id'],'countdown_start_time',true);

                    $start_date_time = $start_date.' '.$start_time;
                }

				// If start_date_time is empty, set it to current date and time
				if (empty($start_date_time)) {
					$start_date_time = current_time('mysql');
				}

				$current_date_time = new DateTime( current_time( 'mysql' ) );


				$end_date_time_obj = new DateTime( $end_date_time );
                if($end_date_time_obj >= $current_date_time) {
                    $countdown_data= ['end_time'=>$end_date_time,'start_time'=>$start_date_time];
                }


				$this->campaign_additional_data[$campaign['id']]['countdown_data'] = $countdown_data;
			}


			if(isset($campaign['campaign_placement']) && 'multiple' != $campaign['campaign_placement']) {
				$campaign['placement_settings'] = [
					$campaign['campaign_placement'] => [
						'page' => $campaign['campaign_placement'],
						'status' => 'yes',
						'display_style' => $campaign['campaign_display_style'],
						'builder_view' => $campaign['campaign_builder_view'],
						'inpage_position' => $campaign['campaign_inpage_position'],
						'popup_animation' => $campaign['campaign_popup_animation'],
						'popup_animation_delay' => $campaign['campaign_popup_animation_delay'],
						'floating_position' => $campaign['campaign_floating_position'],
						'floating_animation_delay' => $campaign['campaign_floating_animation_delay']
					]
				];

				$campaign['placement_settings'] =  $campaign['placement_settings'];
			}


			$placement_settings = $campaign['placement_settings'][$placement];


			if('popup' == $placement_settings['display_style']) {
				$this->campaign_additional_data[$campaign['id']]['campaign_popup_animation'] = $placement_settings['popup_animation']??'';
				$this->campaign_additional_data[$campaign['id']]['campaign_popup_animation_delay'] = $placement_settings['popup_animation_delay']??'';
			}


            if(isset($campaign['animated_add_to_cart_enabled']) && 'yes' == $campaign['animated_add_to_cart_enabled']) {

                $trigger_when = revenue()->get_campaign_meta($campaign['id'],'add_to_cart_animation_trigger_type',true);
                $animation_type = revenue()->get_campaign_meta($campaign['id'],'add_to_cart_animation_type',true);
                $animation_delay = 0;

                if('loop' === $trigger_when && empty($loop_animation)) {
                    $animation_delay = revenue()->get_campaign_meta($campaign['id'],'add_to_cart_animation_start_delay',true) ?? 0;
                    $loop_animation = ['type'=> $animation_type, 'delay'=> $animation_delay];
                } else if('on_hover' === $trigger_when && empty($hover_animation)) {
                    $hover_animation = $animation_type;
                }
                $this->animated_button_data[$campaign['id']]=['loop_animation'=>$animation_type,'delay'=> $animation_delay,'hover_animation'=>$animation_type];
            }
        }
		$campaign_localize_data = [
            'ajax'=>admin_url( 'admin-ajax.php'),
            'nonce'=>wp_create_nonce('revenue-add-to-cart'),
            'user'=>get_current_user_id(),
            'data' => $this->campaign_additional_data,
            'currency_format_num_decimals' => wc_get_price_decimals(),
            'currency_format_symbol' => get_woocommerce_currency_symbol(),
            'currency_format_decimal_sep' => wc_get_price_decimal_separator(),
            'currency_format_thousand_sep' => wc_get_price_thousand_separator(),
            'currency_format' => get_woocommerce_price_format(),
			'checkout_page_url' => wc_get_checkout_url(),
        ];

		if(!$this->is_enqueue_data_already) {
			wp_localize_script('revenue-campaign','revenue_campaign',$campaign_localize_data);
		}

		$display_type_methods = [
			'inpage' => 'output_inpage_views',
			'floating' => 'output_floating_views',
			'popup' => 'output_popup_views',
		];

		$class = false;

		foreach ($typewise_campaigns as $type => $_campaigns) {
			// Check if the display type and campaign type are valid
			if (isset($display_type_methods[$display_type])) {
				$method = $display_type_methods[$display_type];

				// Determine the appropriate class based on the campaign type
				switch ($type) {
					case 'normal_discount':
						$class = Revenue_Normal_Discount::instance();
						break;
					case 'bundle_discount':
						$class = Revenue_Bundle_Discount::instance();
						break;
					case 'volume_discount':
						$class = Revenue_Volume_Discount::instance();
						break;
					case 'buy_x_get_y':
						$class = Revenue_Buy_X_Get_Y::instance();
						break;
					default:
						$class = false;
						break;
				}
				$class = apply_filters('revenue_campaign_instance',$class, $type);

				if($class) {
					do_action("revenue_campaign_{$type}_{$display_type}_before_render_content");
					// @TODO Backward Compatibility should be added for pro
					$class->$method($_campaigns,['display_type'=> $display_type, 'position' => $position, 'placement' => $placement]);
					do_action("revenue_campaign_{$type}_{$display_type}_after_render_content");
				}

			}
		}
    }

	// Product Page
	public function before_add_to_cart_button() {
		global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns($product_id,'product_page','inpage',__FUNCTION__);

        $this->run_campaigns($campaigns,'inpage','product_page',__FUNCTION__);

	}

	public function after_add_to_cart_button() {
        global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns($product_id,'product_page','inpage',__FUNCTION__);

        $this->run_campaigns($campaigns,'inpage','product_page',__FUNCTION__);

	}
	public function after_add_to_cart_quantity() {
        global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns($product_id,'product_page','inpage',__FUNCTION__);
        $this->run_campaigns($campaigns,'inpage','product_page',__FUNCTION__);

	}
	public function before_add_to_cart_quantity() {
        global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns($product_id,'product_page','inpage',__FUNCTION__);
        $this->run_campaigns($campaigns,'inpage','product_page',__FUNCTION__);

	}
	public function before_add_to_cart_form() {
        global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns($product_id,'product_page','inpage',__FUNCTION__);
        $this->run_campaigns($campaigns,'inpage','product_page',__FUNCTION__);

	}

	public function after_add_to_cart_form() {
        global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns($product_id,'product_page','inpage',__FUNCTION__);
        $this->run_campaigns($campaigns,'inpage','product_page',__FUNCTION__);

	}

	public function before_single_product_summary() {
        global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns($product_id,'product_page','inpage',__FUNCTION__);
        $this->run_campaigns($campaigns,'inpage','product_page',__FUNCTION__);

	}

	public function after_single_product_summary() {
        global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns($product_id,'product_page','inpage',__FUNCTION__);

        $this->run_campaigns($campaigns,'inpage','product_page',__FUNCTION__);


        // Floating

        // $floating_campaigns = revenue()->get_available_campaigns($product_id,'product_page','floating',__FUNCTION__);
        // $this->run_campaigns($floating_campaigns,'floating'); // unnecessarily gendered multiple time on frontend


	}

	public function after_single_product() {
        global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns($product_id,'product_page','inpage',__FUNCTION__);

        $this->run_campaigns($campaigns,'inpage','product_page',__FUNCTION__);

        // Popup

        $campaigns = revenue()->get_available_campaigns($product_id,'product_page','popup');
        $this->run_campaigns($campaigns,'popup','product_page',__FUNCTION__);

        $campaigns = revenue()->get_available_campaigns($product_id,'product_page','floating');
        $this->run_campaigns($campaigns,'floating','product_page',__FUNCTION__);

	}

	public function before_single_product() {
        global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns($product_id,'product_page','inpage',__FUNCTION__);
        $this->run_campaigns($campaigns,'inpage','product_page',__FUNCTION__);




	}

	// Shop Page

	// Cart Page
	public function after_cart_item_name($cart_item) {
        global $product;
        $product = $cart_item['data'];

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns($product_id,'cart_page','inpage',__FUNCTION__);
        $this->run_campaigns($campaigns,'inpage','cart_page',__FUNCTION__);

	}
	public function before_cart_contents() {

        global $product;
        $already_running = [];
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
           $product = $cart_item['data'];
            $product_id = $product->get_id();

            $data = revenue()->get_available_campaigns($product_id,'cart_page','inpage',__FUNCTION__, true);
            $campaigns = $data['campaigns'];
            $already_running = array_merge($already_running, $data['ids']);
            $this->run_campaigns($campaigns,'inpage','cart_page',__FUNCTION__);
        }

	}
	public function before_cart_table() {
        global $product;

		$product_id = $product->get_id();

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'cart_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','cart_page',__FUNCTION__);
            // Popup
            // $campaigns = revenue()->get_available_campaigns($product_id,'cart_page','popup',__FUNCTION__);
			// $this->run_campaigns($campaigns,'popup','cart_page',__FUNCTION__);
            // // Floating
            // $campaigns = revenue()->get_available_campaigns($product_id,'cart_page','floating',__FUNCTION__);
			// $this->run_campaigns($campaigns,'floating','cart_page',__FUNCTION__);
		}
	}
	public function before_cart() {
        global $product;

		$is_found = false;
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'cart_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','cart_page',__FUNCTION__);


			if(!$is_found) {
				 // Popup
				 $campaigns = revenue()->get_available_campaigns($product_id,'cart_page','popup',__FUNCTION__);
				 $this->run_campaigns($campaigns,'popup','cart_page',__FUNCTION__);

				 if(!empty($campaigns)) {
					$is_found = true;
				 }
				 // Floating
				 $campaigns = revenue()->get_available_campaigns($product_id,'cart_page','floating',__FUNCTION__);
				 $this->run_campaigns($campaigns,'floating','cart_page',__FUNCTION__);

				 if(!empty($campaigns)) {
					$is_found = true;
				 }

			}

		}

	}
	public function after_cart_contents() {
        // global $product;

		// $product_id = $product->get_id();

		// $campaigns = revenue()->get_available_campaigns($product_id,'cart_page','inpage',__FUNCTION__);
        // $this->run_campaigns($campaigns);

	}
	public function after_cart_table() {
        global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'cart_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','cart_page',__FUNCTION__);
		}

	}
	public function after_cart() {
        global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'cart_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','cart_page',__FUNCTION__);
		}

	}
	public function before_cart_totals() {
        global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'cart_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','cart_page',__FUNCTION__);
		}
	}
	public function after_cart_totals() {
        global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'cart_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','cart_page',__FUNCTION__);
		}
	}
	public function proceed_to_checkout() {
        global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'cart_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','cart_page',__FUNCTION__);
		}
	}

	// Checkout
	public function before_checkout_form() {
        global $product;

		$is_found = false;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'checkout_page','inpage',__FUNCTION__);
		    $this->run_campaigns($campaigns,'inpage','checkout_page',__FUNCTION__);

			if(!$is_found) {
				// Floating
				$campaigns = revenue()->get_available_campaigns($product_id,'checkout_page','floating',__FUNCTION__);
				$this->run_campaigns($campaigns,'floating','checkout_page',__FUNCTION__);

				if(!empty($campaigns)) {
					$is_found = true;
				 }
				// Popup
				$campaigns = revenue()->get_available_campaigns($product_id,'checkout_page','popup',__FUNCTION__);
				$this->run_campaigns($campaigns,'popup','checkout_page',__FUNCTION__);

				if(!empty($campaigns)) {
					$is_found = true;
				 }
			}

		}
	}

	public function before_checkout_billing_form() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'checkout_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','checkout_page',__FUNCTION__);
		}

	}
	public function after_checkout_billing_form() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'checkout_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','checkout_page',__FUNCTION__);
		}

	}
	public function checkout_after_order_review() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'checkout_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','checkout_page',__FUNCTION__);
		}

	}

	public function checkout_before_order_review() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'checkout_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','checkout_page',__FUNCTION__);
		}

	}

	public function review_order_before_order_total() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'checkout_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','checkout_page',__FUNCTION__);
		}

	}
	public function review_order_after_order_total() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'checkout_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','checkout_page',__FUNCTION__);
		}

	}
	public function review_order_before_payment() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'checkout_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','checkout_page',__FUNCTION__);
		}

	}
	public function review_order_after_payment() {
        global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'checkout_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','checkout_page',__FUNCTION__);
		}

	}
	public function after_checkout_form() {
        global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product   = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns = revenue()->get_available_campaigns($product_id,'checkout_page','inpage',__FUNCTION__);
			$this->run_campaigns($campaigns,'inpage','checkout_page',__FUNCTION__);
		}

	}

    // Thankyou page
    public function before_thankyou($order_id) {
        global $product;
        $_order = wc_get_order($order_id);
		$is_found = false;
        // Loop through order items
        foreach ( $_order->get_items() as $item ) {
            // Get the product ID from the order item
            $product_id = $item->get_product_id();

            $product= wc_get_product($product_id);

            $campaigns = revenue()->get_available_campaigns($product_id,'thankyou_page','inpage',__FUNCTION__);

            $this->run_campaigns($campaigns,'inpage','thankyou_page',__FUNCTION__);

			if(!$is_found) {
				// Popup
				$campaigns = revenue()->get_available_campaigns($product_id,'thankyou_page','popup',__FUNCTION__);

				$this->run_campaigns($campaigns,'popup','thankyou_page',__FUNCTION__);

				if(!empty($campaigns)) {
					$is_found = true;
				 }
				// Floating
				$campaigns = revenue()->get_available_campaigns($product_id,'thankyou_page','floating',__FUNCTION__);

				$this->run_campaigns($campaigns,'floating','thankyou_page',__FUNCTION__);

				if(!empty($campaigns)) {
					$is_found = true;
				 }
			}

        }
    }
    public function thankyou($order_id) {
        global $product;

        $_order = wc_get_order($order_id);
            // Loop through order items
            foreach ( $_order->get_items() as $item ) {
            // Get the product ID from the order item
            $product_id = $item->get_product_id();

            $product= wc_get_product($product_id);

            $campaigns = revenue()->get_available_campaigns($product_id,'thankyou_page','inpage',__FUNCTION__);
            $this->run_campaigns($campaigns,'inpage','thankyou_page',__FUNCTION__);

        }
    }


	public function handle_free_shipping($package_rates, $package) {

		$is_free_shipping = apply_filters('revenue_free_shipping',false);

        $is_free_shipping = true;
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            if(isset($cart_item['rev_is_free_shipping']) && 'yes'== $cart_item['rev_is_free_shipping']) {
			} else {
                $is_free_shipping = false;
                break;
            }

            if(isset($cart_item['revx_campaign_type']) && 'buy_x_get_y' == $cart_item['revx_campaign_type']) {
                if(!Revenue_Buy_X_Get_Y::instance()->is_eligible_for_discount($cart_item)) {
                    $is_free_shipping = false;
                    break;
                }
            }

        }


		if($is_free_shipping) {
			$free_shipping = new WC_Shipping_Free_Shipping( 'revenue_free_shipping' );
			$free_shipping->title = apply_filters('revenue_free_shipping_title',__('WoW Revenue Free Shipping','revenue')); // Add Global Settings
			$free_shipping->calculate_shipping( $package );
			return $free_shipping->rates;
		}

		return $package_rates;
	}



    public function handle_countdown() {
        wp_enqueue_script( 'revenue-countdown' );
        wp_enqueue_style( 'revenue-countdown' );
        // wp_localize_script('revenue-countdown','revenue_countdown',['data'=>$this->countdown_data]);

        foreach ($this->countdown_data as $id => $data) {
            ?>
                <div id="revx-campaign-countdown-<?php echo esc_attr( $id ); ?>" class="revx-campaign-countdown revx-d-none">
					<div class="revx-campaign-countdown__header">
						<div class="revx-campaign-countdown__header-heading">🔥 Hurry up! Sale ends in</div>
					</div>
					<div class="revx-campaign-countdown__content">
						<div class="revx-campaign-countdown-timer revx-campaign-countdown-day-container">
							<div class="revx-campaign-countdown-timer__remaing-time revx-day">00</div>
							<div class="revx-campaign-countdown-timer__content">Days</div>
						</div>
						<div class="revx-campaign-countdown-timer revx-campaign-countdown-hour-container">
							<div class="revx-campaign-countdown-timer__remaing-time revx-hour">00</div>
							<div class="revx-campaign-countdown-timer__content">Hours</div>
						</div>
						<div class="revx-campaign-countdown-timer revx-campaign-countdown-minute-container">
							<div class="revx-campaign-countdown-timer__remaing-time revx-minute">00</div>
							<div class="revx-campaign-countdown-timer__content">Minutes</div>
						</div>
						<div class="revx-campaign-countdown-timer revx-campaign-countdown-second-cotainer">
							<div class="revx-campaign-countdown-timer__remaing-time revx-second">00</div>
							<div class="revx-campaign-countdown-timer__content">Seconds</div>
						</div>
					</div>
				</div>
            <?php
        }
        ?>

        <?php
    }

    public function handle_animated_add_to_cart() {
        wp_enqueue_script('revenue-animated-add-to-cart');
        wp_enqueue_style('revenue-animated-add-to-cart');
        wp_localize_script('revenue-animated-add-to-cart','revenue_animated_atc',['data'=>$this->animated_button_data]);
    }

    public function woocommerce_check_cart_items() {
        $cart_hash = isset($_COOKIE['woocommerce_cart_hash'])?sanitize_text_field(wp_unslash($_COOKIE['woocommerce_cart_hash'])):'';
        $revx_hash = isset($_COOKIE['revenue_cart_checked_hash'])?sanitize_text_field(wp_unslash($_COOKIE['revenue_cart_checked_hash'])):'';

        if($cart_hash != $revx_hash) {
            // Not Checked
            $cart = WC()->cart;

            do_action( 'revenue_check_cart_items', $cart );

            if(!headers_sent()) {
                setcookie('revenue_cart_checked_hash', $cart_hash, time() + (86400 * 30), "/");
            }

        }


    }

	/**
	 * After Add to cart from revenue campaign
	 *
	 * Set Campaign and Product Id on session to check if the product already on cart or not
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Cart item hash.
	 * @param int|string $product_id Product Id.
	 * @param int|string $campaign_id Campaign id.
	 * @return void
	 */
	public function after_add_to_cart($key,$product_id,$campaign_id) {
		$cart_data = WC()->session->get( 'revenue_cart_data'  );
		if(!is_array($cart_data)) {
			$cart_data = [];
		}
		if(!isset($cart_data[$campaign_id][$product_id] )) {
			$cart_data[$campaign_id][$product_id] = $key;
			WC()->session->set( 'revenue_cart_data',$cart_data  );
		}
	}

	/**
	 * Actions After remove cart item
	 *
	 * Perform campaign wise action after remove a item from cart
	 *
	 * @param string $key Cart Item Hash Key.
	 * @param WC_Cart $cart Cart.
	 * @return void
	 */
	public function after_remove_cart_item($key,$cart) {
		$cart_item = $cart->removed_cart_contents[ $key ];
		if(isset($cart_item['revx_campaign_id'],$cart_item['revx_campaign_type']) ) {
			$campaign_id = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];
			$item = WC()->cart->get_cart()[$key];
			$cart_data = WC()->session->get( 'revenue_cart_data'  );
			if(!is_array($cart_data)) {
				$cart_data = [];
			}
			if(isset($cart_data[$item['revx_campaign_id']][$item['product_id']] )) {
				unset($cart_data[$item['revx_campaign_id']][$item['product_id']]);
				if(empty($cart_data[$item['revx_campaign_id']])) {
					unset($cart_data[$item['revx_campaign_id']]);
				}
				WC()->session->set( 'revenue_cart_data',$cart_data  );
			}


			/**
			 * @hook for update price on cart from several campaigns
			 * Valid Campaign Type:
			 * normal_discount
			 * bundle_discount
			 * volume_discount
			 * buy_x_get_y
			 * mix_match
			 * frequently_bought_together
			 * spending_goal
			 */
			do_action("revenue_campaign_{$campaign_type}_remove_cart_item",$key,$cart_item, $campaign_id);

		}
	}

	/**
	 * After cart item restored
	 *
	 * Set Campaign and Product Id on session to check if the product already on cart or not
	 *
	 * @param string $key Restored item cart hash key.
	 * @return void
	 */
	public function after_cart_item_restored($key,$cart) {
		$cart_item = $cart->removed_cart_contents[ $key ];

		if(isset($cart_item['revx_campaign_id'],$cart_item['revx_campaign_type']) ) {
			$campaign_id = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$cart_data = WC()->session->get( 'revenue_cart_data'  );
			if(!is_array($cart_data)) {
				$cart_data = [];
			}
			if(!isset($cart_data[$campaign_id ][$cart_item['product_id']])) {
				$cart_data[$campaign_id ][$cart_item['product_id']] = $key;
				WC()->session->set( 'revenue_cart_data',$cart_data  );
			}



			/**
			 * @hook for update price on cart from several campaigns
			 * Valid Campaign Type:
			 * normal_discount
			 * bundle_discount
			 * volume_discount
			 * buy_x_get_y
			 * mix_match
			 * frequently_bought_together
			 * spending_goal
			 */
			do_action("revenue_campaign_{$campaign_type}_restore_cart_item",$key,$cart_item, $campaign_id);
		}
	}

	/**
	 * Set Revenue cart data Null after cart empty
	 *
	 * @return void
	 */
	public function after_cart_emptied() {
		WC()->session->set( 'revenue_cart_data', null );
	}



	/**
	 * WooCommerce After Add to cart an item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $cart_item_key Cart Item Key.
	 * @param int|string $product_id Product Id.
	 * @param int $quantity Added Quantity.
	 * @param int $variation_id Variation Id.
	 * @param array $variation Variation Data.
	 * @param array $cart_item_data Cart Item Data.
	 * @return void
	 */
	public function woocommerce_after_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {

		if(!did_action( 'woocommerce_cart_loaded_from_session' )) {
			return;
		}

		if(isset($cart_item_data['revx_campaign_id'])) {
			$campaign_type = $cart_item_data['revx_campaign_type'];
			/**
			 * @hook for update price on cart from several campaigns
			 * Valid Campaign Type:
			 * normal_discount
			 * bundle_discount
			 * volume_discount
			 * buy_x_get_y
			 * mix_match
			 * frequently_bought_together
			 * spending_goal
			 */
			do_action("revenue_campaign_{$campaign_type}_added_to_cart",$cart_item_key,$cart_item_data, $product_id,$quantity);
		}

	}


	/**
	 * WooCommerce Before Calculate Total
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Cart $cart Cart.
	 * @return void
	 */
	public function woocommerce_before_calculate_totals($cart) {
		if (is_admin()) {
            return;
        }

		wp_cache_delete('revx_cart_product_ids');
		foreach ( $cart->get_cart() as $cart_item ) {
			// Revenue Cart Item : Added through revenue
			if(isset($cart_item['revx_campaign_id'],$cart_item['revx_campaign_type'])) {
				$campaign_type = $cart_item['revx_campaign_type'];
				$campaign_id = $cart_item['revx_campaign_id'];

				/**
				 * @hook for update price on cart from several campaigns
				 * Valid Campaign Type:
				 * normal_discount
				 * bundle_discount
				 * volume_discount
				 * buy_x_get_y
				 * mix_match
				 * frequently_bought_together
				 * spending_goal
				 */
				do_action("revenue_campaign_{$campaign_type}_before_calculate_cart_totals",$cart_item,$campaign_id,$cart);
			}
		}
	}
	/**
	 * WooCommerce Before Calculate Total
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Cart $cart Cart.
	 * @return void
	 */
	public function woocommerce_cart_calculate_fees($cart) {
		if (is_admin()) {
            return;
        }

		foreach ( $cart->get_cart() as $cart_item ) {
			// Revenue Cart Item : Added through revenue
			if(isset($cart_item['revx_campaign_id'],$cart_item['revx_campaign_type'])) {
				$campaign_type = $cart_item['revx_campaign_type'];
				$campaign_id = $cart_item['revx_campaign_id'];

				/**
				 * @hook for update price on cart from several campaigns
				 * Valid Campaign Type:
				 * normal_discount
				 * bundle_discount
				 * volume_discount
				 * buy_x_get_y
				 * mix_match
				 * frequently_bought_together
				 * spending_goal
				 */
				do_action("revenue_campaign_{$campaign_type}_cart_calculate_fees",$cart_item,$campaign_id,$cart);
			}
		}
	}


	/**
	 * WooCommerce Cart Item Remove Link
	 *
	 * Used for any modification on remove link based on campaign type
	 *
	 * @since 1.0.0
	 *
	 * @param string $link Remove Link.
	 * @param string $cart_item_key Cart Hash Key.
	 * @return string
	 */
	public function woocommerce_cart_item_remove_link($link, $cart_item_key) {
		$cart_item = WC()->cart->cart_contents[ $cart_item_key ];
		if(isset($cart_item['revx_campaign_id'])) {
			$campaign_id = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$link = apply_filters("revenue_campaign_{$campaign_type}_cart_item_remove_link",$link,$cart_item,$campaign_id);
		}

		return $link;
	}


	/**
	 * WooCommerce Cart Item Quantity
	 *
	 * Used for any modification on cart item quantity based on campaign type
	 *
	 * @since 1.0.0
	 *
	 * @param string $quantity Item Quantity
	 * @param string $cart_item_key Cart Hash Key.
	 * @return string
	 */
	public function woocommerce_cart_item_quantity($quantity, $cart_item_key) {
		$cart_item = WC()->cart->cart_contents[ $cart_item_key ];
		if(isset($cart_item['revx_campaign_id'])) {
			$campaign_id = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$quantity = apply_filters("revenue_campaign_{$campaign_type}_cart_item_quantity",$quantity,$cart_item,$campaign_id);
		}
		return $quantity;
	}

	/**
	 * Add Custom class name on cart item based on campaign type
	 *
	 * @since 1.0.0
	 *
	 * @param string $classname Class Name.
	 * @param array $cart_item Cart Item
	 * @return string
	 */
	public function woocommerce_cart_item_class($classname, $cart_item) {

		if(isset($cart_item['revx_campaign_id'])) {
			$campaign_id = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];
			$classname = apply_filters("revenue_campaign_{$campaign_type}_cart_item_class",$classname,$cart_item,$campaign_id);
		}
		return $classname;
	}

	/**
	 * Change Cart Item Subtotal based on campaign
	 *
	 * @param string $subtotal Item Subtotal.
	 * @param array $cart_item Cart Item
	 * @return string|float
	 */
	public function woocommerce_cart_item_subtotal($subtotal, $cart_item) {

		if(isset($cart_item['revx_campaign_id'])) {
			$campaign_id = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$subtotal = apply_filters("revenue_campaign_{$campaign_type}_cart_item_subtotal",$subtotal,$cart_item,$campaign_id);
		}
		return $subtotal;
	}

	/**
	 * Change Cart Item Name based on campaign
	 *
	 * @param string $item_name Item Name.
	 * @param array $cart_item Cart Item
	 * @return string|float
	 */
	public function woocommerce_cart_item_name($item_name, $cart_item) {

		if(isset($cart_item['revx_campaign_id'])) {
			$campaign_id = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$item_name = apply_filters("revenue_campaign_{$campaign_type}_cart_item_name",$item_name,$cart_item,$campaign_id);
		}
		return $item_name;
	}

	/**
	 * Change Minimum Product quantity on Cart Block based on campaign
	 *
	 * @param string $value Minimum Quantity Value.
	 * @param WC_Product $product Product Object.
	 * @param array $cart_item Cart Item
	 * @return string|float
	 */
	public function woocommerce_store_api_product_quantity_minimum($value, $product, $cart_item) {

		if(isset($cart_item['revx_campaign_id'])) {
			$campaign_id = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$value = apply_filters("revenue_campaign_{$campaign_type}_store_api_product_quantity_minimum",$value,$cart_item,$campaign_id);
		}
		return $value;
	}
	/**
	 * Change maximum Product quantity on Cart Block based on campaign
	 *
	 * @param string $value Maximum Quantity Value.
	 * @param WC_Product $product Product Object.
	 * @param array $cart_item Cart Item
	 * @return string|float
	 */
	public function woocommerce_store_api_product_quantity_maximum($value, $product, $cart_item) {

		if(isset($cart_item['revx_campaign_id'])) {
			$campaign_id = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$value = apply_filters("revenue_campaign_{$campaign_type}_store_api_product_quantity_maximum",$value,$cart_item,$campaign_id);
		}
		return $value;
	}



	/**
	 * Change Cart Item Price based on campaign
	 *
	 * @since 1.0.0
	 *
	 * @param string $price Item Price.
	 * @param array $cart_item Cart Item
	 * @return string|float
	 */
	public function woocommerce_cart_item_price($price, $cart_item) {

		if(isset($cart_item['revx_campaign_id'])) {
			$campaign_id = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$price = apply_filters("revenue_campaign_{$campaign_type}_cart_item_price",$price,$cart_item,$campaign_id);

		}
		return $price;
	}

	/**
	 * Get Cart item Data
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Item Data.
	 * @param array $cart_item Cart Item
	 * @return array
	 */
	public function woocommerce_get_item_data($data, $cart_item) {
		if(isset($cart_item['revx_campaign_id'],$cart_item['revx_campaign_type'])) {
			$campaign_id = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$data = apply_filters("revenue_campaign_{$campaign_type}_cart_item_data",$data,$cart_item,$campaign_id);

		}
		return $data;
	}


	public function woocommerce_checkout_create_order_line_item($item, $cart_item_key, $cart_item, $order) {

		// Item order through reveneux
		if(isset($cart_item['revx_campaign_id'],$cart_item['revx_campaign_type'])) {
			$campaign_id = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$item->add_meta_data( '_revx_campaign_id', $campaign_id, true );
			$item->add_meta_data( '_revx_campaign_type', $campaign_type, true );
			/**
			 * @hook for add item meta data
			 * Valid Campaign Type:
			 * normal_discount
			 * bundle_discount
			 * volume_discount
			 * buy_x_get_y
			 * mix_match
			 * frequently_bought_together
			 * spending_goal
			 */
			do_action("revenue_campaign_{$campaign_type}_create_order_line_item",$item,$cart_item_key,$cart_item,$campaign_id,$order);
		}
	}


	public function woocommerce_hidden_order_itemmeta($hidden_meta) {
		$hidden_meta[]='_revx_campaign_id';
		$hidden_meta[]='_revx_campaign_type';

		$hidden_meta  = apply_filters('revenue_hidden_order_item_meta',$hidden_meta);

		return $hidden_meta;
	}


	/**
	 * Perform action after create order on WooCommerce
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public function woocommerce_checkout_create_order($order)
	{

        $order = wc_get_order($order);

        if(!$order) {
            return;
        }

		// Get all items from the order
		$items = $order->get_items();


		$data = [];

		// Loop through each item in the order
		foreach ($items as $item) {
			// Get the campaign ID from the item's meta data
			$campaign_id = $item->get_meta('_revx_campaign_id');

			if ($campaign_id) {
				// Get the product associated with the item
				$order->update_meta_data('_revx_campaign_id', $campaign_id);
				$product = $item->get_product();
				// Add the product ID and campaign ID to the data array
				$data[] = ['item_id' => $product->get_id(), 'campaign_id' => $campaign_id];
			}
		}


		// If data array is not empty, trigger the custom action
		if (!empty($data)) {
			$order->save();
			do_action('revenue_campaign_order_created', $data, $order);
		}
	}



	public function render_campaign_view_shortcode($atts) {

        if (!isset($atts['id'])) {
            return false;
        }

        $id = (int) $atts['id'];

        $campaign = revenue()->get_campaign_data($id);

        if (!$campaign) {
            return false;
        }

        $class = false;

        switch ($campaign['campaign_type']) {
            case 'normal_discount':
                $class = Revenue_Normal_Discount::instance();
                break;
            case 'bundle_discount':
                $class = Revenue_Bundle_Discount::instance();
                break;
            case 'volume_discount':
                $class = Revenue_Volume_Discount::instance();
                break;
            case 'buy_x_get_y':
                $class = Revenue_Buy_X_Get_Y::instance();
                break;
            default:
                $class = false;
                break;
        }

        $class = apply_filters('revenue_campaign_instance', $class, $campaign['campaign_type']);

		do_action("revenue_campaign_before_render_shortcode", $campaign);
		$this->render_shortcode($campaign);
		do_action("revenue_campaign_after_render_shortcode", $campaign);


    }

    /**
     * Renders and outputs a shortcode view for a single campaign.
     *
     * This method generates HTML output for a campaign view by including the
     * in-page view PHP file. It also updates the campaign impression count based on
     * whether a product is available.
     *
     * @param array $campaign The campaign data to be rendered.
     *
     * @return void
     */
    public function render_shortcode($campaign,$data=[]) {
        global $product;


		if(is_product()) {

			if($product && $product instanceof \WC_Product && is_array($campaign)) {
				revenue()->update_campaign_impression($campaign['id'], $product->get_id());
			} else {
				return;
			}

			$this->run_shortcode($campaign,['display_type'=> 'inpage', 'position' => '', 'placement' => 'product_page'] );
		}
		else if(is_cart()) {
			$which_page = 'cart_page';

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product   = $cart_item['data'];
				$product_id = $cart_item['product_id'];

				$this->run_shortcode($campaign,['display_type'=> 'inpage', 'position' => '', 'placement' => 'cart_page']);

			}
		} else if(is_shop()) {
			$which_page = 'shop_page';
		} else if(is_checkout()) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product   = $cart_item['data'];
				$product_id = $cart_item['product_id'];

				$this->run_shortcode($campaign,['display_type'=> 'inpage', 'position' => '', 'placement' => 'checkout_page']);
			}
		}


    }

	public function run_shortcode($campaign, $data=[]) {
		wp_enqueue_style('revenue-campaign');
		wp_enqueue_style('revenue-utility');
		wp_enqueue_style('revenue-responsive');
		wp_enqueue_script('revenue-campaign');
		$file_path_prefix = apply_filters('revenue_campaign_file_path', REVENUE_PATH, $campaign['campaign_type'], $campaign);

        // Replace underscores with hyphens in the campaign type
        $campaign_type = isset($campaign['campaign_type']) ? str_replace('_', '-', $campaign['campaign_type']) : 'normal-discount';

        $file_path = false;
        if(isset($campaign['campaign_display_style'])) {
            switch ($campaign['campaign_display_style']) {
                case 'inpage':
                    $file_path = $file_path_prefix . "includes/campaigns/views/{$campaign_type}/inpage.php";
                    break;
                case 'popup':
                    $file_path = $file_path_prefix . "includes/campaigns/views/{$campaign_type}/popup.php";
                    break;
                case 'floating':
                    $file_path = $file_path_prefix . "includes/campaigns/views/{$campaign_type}/floating.php";
                    break;
                default:
                    $file_path = $file_path_prefix . "includes/campaigns/views/{$campaign_type}/inpage.php";
                    break;
            }
        }

        ob_start();
        if(file_exists($file_path)) {
			extract($data);
            include $file_path;
        }

        $output = ob_get_clean();

        $request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

        $rest_prefix         = trailingslashit( rest_get_url_prefix() );
        $is_rest_api_request = ( false !== strpos( $request_uri, $rest_prefix ) );

        if($is_rest_api_request) {
            return $output;
        } else {
            echo wp_kses($output, revenue()->get_allowed_tag()); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
	}


    public function woocommerce_after_cart_item_quantity_update($cart_item_key, $quantity=0) {
        $cart_item = WC()->cart->cart_contents[ $cart_item_key ];
        if(isset($cart_item['revx_campaign_id'],$cart_item['revx_campaign_type'])) {
            $campaign_id = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];


			/**
			 * @hook for add item meta data
			 * Valid Campaign Type:
			 * normal_discount
			 * bundle_discount
			 * volume_discount
			 * buy_x_get_y
			 * mix_match
			 * frequently_bought_together
			 * spending_goal
			 */
			do_action("revenue_campaign_{$campaign_type}_after_item_quantity_updated",$cart_item,$cart_item_key,$quantity);
        }
    }
}
