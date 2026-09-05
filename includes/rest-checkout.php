<?php
/**
 * REST: checkout — create WC order from an active hold.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register checkout routes.
 */
function venuestack_core_register_checkout_routes(): void {
	register_rest_route(
		'venuestack/v1',
		'/checkout',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'venuestack_core_rest_checkout',
			'permission_callback' => '__return_true',
			'args'                => array(
				'booking_id' => array(
					'required'          => true,
					'type'              => 'integer',
					'minimum'           => 1,
					'sanitize_callback' => 'absint',
				),
				'headcount'  => array(
					'required'          => false,
					'type'              => 'integer',
					'default'           => 0,
					'minimum'           => 0,
					'sanitize_callback' => 'absint',
				),
				'package_id' => array(
					'required'          => false,
					'type'              => 'integer',
					'default'           => 0,
					'minimum'           => 0,
					'sanitize_callback' => 'absint',
				),
				'billing'    => array(
					'required' => false,
					'type'     => 'object',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'venuestack_core_register_checkout_routes' );

/**
 * POST /venuestack/v1/checkout
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function venuestack_core_rest_checkout( WP_REST_Request $request ) {
	$billing_raw = $request['billing'];
	$billing     = array();

	if ( is_array( $billing_raw ) ) {
		foreach ( array( 'first_name', 'last_name', 'email', 'phone' ) as $key ) {
			if ( isset( $billing_raw[ $key ] ) ) {
				$billing[ $key ] = sanitize_text_field( (string) $billing_raw[ $key ] );
			}
		}
		if ( ! empty( $billing['email'] ) ) {
			$billing['email'] = sanitize_email( $billing['email'] );
		}
	}

	$result = venuestack_core_create_order_for_booking(
		(int) $request['booking_id'],
		(int) $request['headcount'],
		(int) $request['package_id'],
		$billing
	);

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return new WP_REST_Response( $result, 201 );
}
