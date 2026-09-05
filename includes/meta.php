<?php
/**
 * Registers VenueStack post meta for Block Bindings and REST.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register all custom post meta fields.
 */
function venuestack_core_register_meta(): void {
	$auth = 'venuestack_core_meta_auth_callback';

	$int_schema = array(
		'type'    => 'integer',
		'minimum' => 0,
	);

	$number_schema = array(
		'type'    => 'number',
		'minimum' => 0,
	);

	foreach ( array( 'max_capacity', 'square_footage', 'minimum_booking_hours' ) as $key ) {
		register_post_meta(
			'venue_space',
			$key,
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => array( 'schema' => $int_schema ),
				'auth_callback'     => $auth,
				'sanitize_callback' => 'absint',
			)
		);
	}

	register_post_meta(
		'venue_space',
		'hourly_rate',
		array(
			'type'              => 'number',
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => array( 'schema' => $number_schema ),
			'auth_callback'     => $auth,
			'sanitize_callback' => 'venuestack_core_sanitize_float',
		)
	);

	register_post_meta(
		'event_package',
		'price_per_head',
		array(
			'type'              => 'number',
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => array( 'schema' => $number_schema ),
			'auth_callback'     => $auth,
			'sanitize_callback' => 'venuestack_core_sanitize_float',
		)
	);

	register_post_meta(
		'event_package',
		'requires_advance_notice',
		array(
			'type'              => 'integer',
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => array( 'schema' => $int_schema ),
			'auth_callback'     => $auth,
			'sanitize_callback' => 'absint',
		)
	);

	register_post_meta(
		'event_package',
		'menu_items_included',
		array(
			'type'              => 'array',
			'single'            => true,
			'default'           => array(),
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
			'auth_callback'     => $auth,
			'sanitize_callback' => 'venuestack_core_sanitize_string_array',
		)
	);

	foreach ( array( 'space_id', 'start_datetime', 'end_datetime', 'wc_order_id' ) as $key ) {
		register_post_meta(
			'venue_booking',
			$key,
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => array( 'schema' => $int_schema ),
				'auth_callback'     => $auth,
				'sanitize_callback' => 'absint',
			)
		);
	}

	register_post_meta(
		'venue_booking',
		'status',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => 'pending',
			'show_in_rest'      => array(
				'schema' => array(
					'type' => 'string',
					'enum' => array( 'pending', 'hold', 'confirmed', 'cancelled' ),
				),
			),
			'auth_callback'     => $auth,
			'sanitize_callback' => 'venuestack_core_sanitize_booking_status',
		)
	);
}
add_action( 'init', 'venuestack_core_register_meta' );

/**
 * @param mixed $allowed   Unused.
 * @param mixed $meta_key  Unused.
 * @param mixed $object_id Post ID.
 */
function venuestack_core_meta_auth_callback( $allowed, $meta_key, $object_id ): bool {
	return current_user_can( 'edit_post', (int) $object_id );
}

/**
 * @param mixed $value Raw value.
 */
function venuestack_core_sanitize_float( $value ): float {
	return max( 0, (float) $value );
}

/**
 * @param mixed $value Raw value.
 * @return array<int, string>
 */
function venuestack_core_sanitize_string_array( $value ): array {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$clean = array();

	foreach ( $value as $item ) {
		if ( ! is_scalar( $item ) ) {
			continue;
		}

		$item = sanitize_text_field( (string) $item );

		if ( '' !== $item ) {
			$clean[] = $item;
		}
	}

	return array_values( $clean );
}

/**
 * @param mixed $value Raw value.
 */
function venuestack_core_sanitize_booking_status( $value ): string {
	$allowed = array( 'pending', 'hold', 'confirmed', 'cancelled' );
	$value   = is_string( $value ) ? strtolower( $value ) : '';

	return in_array( $value, $allowed, true ) ? $value : 'pending';
}
