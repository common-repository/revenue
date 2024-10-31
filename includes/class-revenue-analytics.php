<?php
namespace Revenue;

/**
 * Revenue Analytics
 *
 * @hooked on init
 */
class Revenue_Analytics {
	use SingletonTrait;

    public function init() {
        add_action('template_redirect', [$this, 'track_checkout_page_view']);
        add_action('revenue_campaign_order_created', [$this, 'track_order_create'], 10, 2);
    }

    /**
     * Track checkout page view and update campaign checkout counts.
     */
    public function track_checkout_page_view() {
        if ((is_checkout() && !is_order_received_page()) || \WC_Blocks_Utils::has_block_in_page(get_the_ID(), 'woocommerce/checkout')) {
            $cart_hash = isset($_COOKIE['woocommerce_cart_hash']) ? sanitize_text_field(wp_unslash($_COOKIE['woocommerce_cart_hash'])) : '';
            $revx_hash = isset($_COOKIE['revenue_cart_hash']) ? sanitize_text_field(wp_unslash($_COOKIE['revenue_cart_hash'])) : '';

            if ($cart_hash != $revx_hash) {
                // Update campaign checkout counts
                $data = [];

                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                    if (isset($cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'])) {
                        $item_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
                        $this->increment_campaign_checkout_count($cart_item['revx_campaign_id'], $item_id);
                        $data[] = "{$cart_item['revx_campaign_id']}:{$item_id}";
                    }
                }

                // Update cookies
                if (!headers_sent()) {
                    setcookie('revenue_cart_hash', $cart_hash, time() + (86400 * 30), "/");
                    setcookie('revenue_cart_stats', implode(',', $data), time() + (86400 * 30), "/");
                }
            } else {
                // Already checked out, update campaign checkout counts from cookies
                $revx_stats = isset($_COOKIE['revenue_cart_stats']) ? sanitize_text_field(wp_unslash($_COOKIE['revenue_cart_stats'])) : '';
                if ($revx_stats) {
                    $data = explode(',', $revx_stats);
                    foreach ($data as $value) {
                        $cd = explode(':', $value);
                        if (isset($cd[0], $cd[1])) {
                            $campaign_id = intval($cd[0]);
                            $item_id = intval($cd[1]);
                            $this->increment_campaign_checkout_count($campaign_id, $item_id);
                        }
                    }
                }
            }
        }
    }

    /**
     * Track order creation and update campaign order counts.
     *
     * @param array $stats Array containing campaign_id and item_id for each stat.
     * @param \WC_Order $order The WooCommerce order object.
     */
    public function track_order_create($stats, $order) {
        foreach ($stats as $data) {
            if (isset($data['campaign_id'], $data['item_id'])) {
                $this->increment_campaign_order_count($data['campaign_id'], $data['item_id']);
                // $this->store_order_id_for_campaign($data['campaign_id'], $order->get_id());
            }
        }
    }

    /**
     * Increment the checkout count for a campaign and item.
     *
     * @param int $campaign_id Campaign ID.
     * @param int $item_id Item ID.
     */
    public function increment_campaign_checkout_count($campaign_id, $item_id) {
        $this->update_campaign_stat($campaign_id, 'checkout_count');
    }

    /**
     * Increment the order count for a campaign and item.
     *
     * @param int $campaign_id Campaign ID.
     * @param int $item_id Item ID.
     */
    public function increment_campaign_order_count($campaign_id, $item_id) {
        $this->update_campaign_stat($campaign_id, 'order_count');
    }

    /**
     * Update a specific campaign statistic for a campaign on the current date.
     *
     * @param int $campaign_id Campaign ID.
     * @param string $stat_type Statistic type (e.g., order_count, checkout_count).
     * @param int|null $count Optional. Count to update (default is increment by 1).
     * @param string|null $date Optional. Date to update (default is current date).
     */
    public function update_campaign_stat($campaign_id, $stat_type, $count = null, $date = null) {
        global $wpdb;

        if (is_null($date)) {
            $date = current_time('Y-m-d');
        }

        $allowed_columns = [
            'add_to_cart_count',
            'checkout_count',
            'order_count',
            'impression_count',
            'rejection_count'
        ];

        if (!in_array($stat_type, $allowed_columns)) {
            return;
        }
        $stat_type = esc_sql($stat_type);

        $cache_key = "campaign_stat_{$campaign_id}_{$date}";
        $cached_stat = wp_cache_get($cache_key, 'campaign_analytics');

        if ($cached_stat === false) {
            $existing_record = $wpdb->get_row( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->prepare(
                    "SELECT id, `$stat_type` FROM `{$wpdb->prefix}revenue_campaign_analytics` WHERE campaign_id = %d AND date = %s", //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $campaign_id, $date
                ), ARRAY_A
            );

            wp_cache_set($cache_key, $existing_record, 'campaign_analytics');
        } else {
            $existing_record = $cached_stat;
        }

        if ($existing_record) {
            $new_count = isset($existing_record[$stat_type]) ? $existing_record[$stat_type] + (is_null($count) ? 1 : $count) : $count;

            $wpdb->update( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                "{$wpdb->prefix}revenue_campaign_analytics",
                [$stat_type => $new_count],
                ['id' => $existing_record['id']],
                ['%d'],
                ['%d']
            );
        } else {
            $new_count = is_null($count) ? 1 : $count;

            $wpdb->insert( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                "{$wpdb->prefix}revenue_campaign_analytics",
                [
                    'campaign_id' => $campaign_id,
                    'date' => $date,
                    $stat_type => $new_count
                ],
                ['%d', '%s', '%d']
            );
        }

        wp_cache_delete($cache_key, 'campaign_analytics');
    }

}
