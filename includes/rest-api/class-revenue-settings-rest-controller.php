<?php

namespace Revenue;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;
use Exception;
use WC_DateTime;
use DateTimeZone;
use DateTime;

class Revenue_Setttings_REST_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $namespace = 'revenue/v1';

	/**
	 * Route name
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $base = 'settings';


	
	/**
	 * Register all routes related with stores
	 *
	 * @return void
	 */
	public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->base,
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_settings' ),
                'permission_callback' => array( $this, 'get_settings_permission_check' ), // Provide a permission callback or remove if not needed
            )
        );
    
        register_rest_route(
            $this->namespace,
            '/' . $this->base,
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'update_setting' ),
                'permission_callback' => array( $this, 'get_update_setting_permission_check' ),
            )
        );
    }
    
    public function get_settings_permission_check() {
        // Define the permission check for getting settings, if needed
        return current_user_can( 'read' );
    }
    
    public function get_update_setting_permission_check() {
        return current_user_can( 'manage_options' );
    }
    

    // Fetch settings from the WordPress database
    public function get_settings() {
        // Fetch settings using get_option
        return rest_ensure_response(revenue()->get_setting());
    }

    // Update a specific setting in the WordPress database
    public function update_setting($request) {
        $key = $request->get_param('key');
        $value = $request->get_param('value');

        if (empty($key)) {
            return new WP_Error('invalid_key', 'Invalid setting key', array('status' => 400));
        }

        // Update the setting using update_option
        $updated = revenue()->set_setting($key, $value);

        if ($updated) {
            return rest_ensure_response(array('success' => true, 'message' => 'Setting updated successfully'));
        } else {
            return new WP_Error('update_failed', 'Failed to update setting', array('status' => 500));
        }
    }

}
