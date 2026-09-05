<?php
/**
 * Server-side booking pricing (DB rates only — never trust the client total).
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Calculate booking price from immutable space/package meta.
 *
 * Formula: (hourly_rate × hours) + (price_per_head × headcount)
 *
 * @param int $space_id   venue_space post ID.
 * @param int $start_utc  Range start (UTC Unix timestamp).
 * @param int $end_utc    Range end (UTC Unix timestamp).
 * @param int $headcount  Guest count.
 * @param int $package_id Optional event_package post ID (0 = space only).
 * @return array{
 *     space_id:int,
 *     package_id:int,
 *     hours:float,
 *     headcount:int,
 *     hourly_rate:float,
 *     price_per_head:float,
 *     space_subtotal:float,
 *     package_subtotal:float,
 *     total:float
 * }|\WP_Error
 */
function venuestack_core_calculate_price(
	int $space_id,
	int $start_utc,
	int $end_utc,
	int $headcount = 0,
	int $package_id = 0
) {
	if ( $end_utc <= $start_utc ) {
		return new WP_Error(
			'venuestack_invalid_range',
			__( 'End must be after start.', 'venuestack-core' ),
			array( 'status' => 400 )
		);
	}

	if ( $headcount < 0 ) {
		return new WP_Error(
			'venuestack_invalid_headcount',
			__( 'Headcount cannot be negative.', 'venuestack-core' ),
			array( 'status' => 400 )
		);
	}

	if ( ! venuestack_core_space_exists( $space_id ) ) {
		return new WP_Error(
			'venuestack_invalid_space',
			__( 'Venue space not found.', 'venuestack-core' ),
			array( 'status' => 404 )
		);
	}

	$hours = ( $end_utc - $start_utc ) / (float) HOUR_IN_SECONDS;

	$min_hours = (int) get_post_meta( $space_id, 'minimum_booking_hours', true );
	if ( $min_hours > 0 && $hours < $min_hours ) {
		return new WP_Error(
			'venuestack_below_minimum',
			sprintf(
				/* translators: %d: minimum hours */
				__( 'Booking must be at least %d hours.', 'venuestack-core' ),
				$min_hours
			),
			array( 'status' => 400 )
		);
	}

	$hourly_rate    = (float) get_post_meta( $space_id, 'hourly_rate', true );
	$price_per_head = 0.0;

	if ( $package_id > 0 ) {
		$package = get_post( $package_id );

		if (
			! $package instanceof WP_Post
			|| 'event_package' !== $package->post_type
			|| 'publish' !== $package->post_status
		) {
			return new WP_Error(
				'venuestack_invalid_package',
				__( 'Event package not found.', 'venuestack-core' ),
				array( 'status' => 404 )
			);
		}

		$price_per_head = (float) get_post_meta( $package_id, 'price_per_head', true );
	}

	$space_subtotal   = round( $hourly_rate * $hours, 2 );
	$package_subtotal = round( $price_per_head * $headcount, 2 );
	$total            = round( $space_subtotal + $package_subtotal, 2 );

	return array(
		'space_id'         => $space_id,
		'package_id'       => $package_id,
		'hours'            => round( $hours, 4 ),
		'headcount'        => $headcount,
		'hourly_rate'      => $hourly_rate,
		'price_per_head'   => $price_per_head,
		'space_subtotal'   => $space_subtotal,
		'package_subtotal' => $package_subtotal,
		'total'            => $total,
	);
}
