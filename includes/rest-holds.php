<?php
/**
 * REST: soft-hold check-and-insert.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register hold routes.
 */
function venuestack_core_register_hold_routes(): void {
	register_rest_route(
		'venuestack/v1',
		'/holds',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'venuestack_core_rest_create_hold',
			'permission_callback' => '__return_true',
			'args'                => array(
				'space_id' => array(
					'required'          => true,
					'type'              => 'integer',
					'minimum'           => 1,
					'sanitize_callback' => 'absint',
				),
				'start'    => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'end'      => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'timezone' => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'venuestack_core_register_hold_routes' );

/**
 * POST /venuestack/v1/holds — mutex → overlap check → insert hold.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function venuestack_core_rest_create_hold( WP_REST_Request $request ) {
	$space_id = (int) $request['space_id'];
	$timezone = $request['timezone'] ?: null;

	$start_utc = venuestack_core_parse_to_utc_timestamp( (string) $request['start'], $timezone );
	if ( is_wp_error( $start_utc ) ) {
		return $start_utc;
	}

	$end_utc = venuestack_core_parse_to_utc_timestamp( (string) $request['end'], $timezone );
	if ( is_wp_error( $end_utc ) ) {
		return $end_utc;
	}

	if ( ! venuestack_core_acquire_space_lock( $space_id ) ) {
		return new WP_Error(
			'venuestack_locked',
			__( 'This space is busy processing another booking. Try again.', 'venuestack-core' ),
			array( 'status' => 423 )
		);
	}

	try {
		$result = venuestack_core_create_hold( $space_id, $start_utc, $end_utc );
	} finally {
		venuestack_core_release_space_lock( $space_id );
	}

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return new WP_REST_Response( $result, 201 );
}
