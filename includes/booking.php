<?php
/**
 * Soft-hold booking helpers: UTC parsing, mutex, overlap, insert.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/security.php';

/** Soft-hold lifetime in seconds (read-time expiry window). */
const VENUESTACK_HOLD_TTL = 15 * MINUTE_IN_SECONDS;

/**
 * Build a venue_booking post title from status + space + start (UTC).
 *
 * @param string $status   Booking status (hold|confirmed|pending|cancelled).
 * @param int    $space_id Venue space post ID.
 * @param int    $start_utc Start Unix timestamp (UTC).
 */
function venuestack_core_format_booking_title( string $status, int $space_id, int $start_utc ): string {
	$space_title = get_the_title( $space_id );
	if ( '' === $space_title ) {
		$space_title = __( 'Space', 'venuestack-core' );
	}

	$when = gmdate( 'Y-m-d H:i', $start_utc ) . ' UTC';

	switch ( $status ) {
		case 'confirmed':
			$prefix = __( 'Booking', 'venuestack-core' );
			break;
		case 'cancelled':
			$prefix = __( 'Cancelled', 'venuestack-core' );
			break;
		case 'pending':
			$prefix = __( 'Pending', 'venuestack-core' );
			break;
		case 'hold':
		default:
			$prefix = __( 'Hold', 'venuestack-core' );
			break;
	}

	return sprintf(
		/* translators: 1: status label (Hold/Booking/…), 2: space title, 3: UTC datetime */
		__( '%1$s — %2$s — %3$s', 'venuestack-core' ),
		$prefix,
		$space_title,
		$when
	);
}

/**
 * Refresh a booking post title from its current meta.
 *
 * @param int $booking_id Booking post ID.
 * @return bool True when the title was updated.
 */
function venuestack_core_sync_booking_title( int $booking_id ): bool {
	$booking = get_post( $booking_id );
	if ( ! $booking instanceof WP_Post || 'venue_booking' !== $booking->post_type ) {
		return false;
	}

	$status   = (string) get_post_meta( $booking_id, 'status', true );
	$space_id = (int) get_post_meta( $booking_id, 'space_id', true );
	$start    = (int) get_post_meta( $booking_id, 'start_datetime', true );

	if ( $space_id < 1 || $start < 1 ) {
		return false;
	}

	$title = venuestack_core_format_booking_title( $status ?: 'hold', $space_id, $start );
	if ( $title === $booking->post_title ) {
		return false;
	}

	$result = wp_update_post(
		array(
			'ID'         => $booking_id,
			'post_title' => $title,
		),
		true
	);

	return ! is_wp_error( $result );
}

/**
 * Parse a datetime string to a UTC Unix timestamp.
 *
 * Timezone-aware strings (Z / ±offset) are respected.
 * Naive strings use $timezone, or the site timezone when omitted.
 *
 * @param string      $datetime Datetime string.
 * @param string|null $timezone IANA timezone for naive datetimes.
 * @return int|\WP_Error
 */
function venuestack_core_parse_to_utc_timestamp( string $datetime, ?string $timezone = null ) {
	$datetime = trim( $datetime );

	if ( '' === $datetime ) {
		return new WP_Error(
			'venuestack_invalid_datetime',
			__( 'Datetime is required.', 'venuestack-core' ),
			array( 'status' => 400 )
		);
	}

	try {
		if ( preg_match( '/(Z|[+-]\d{2}:?\d{2})$/', $datetime ) ) {
			$dt = new DateTimeImmutable( $datetime );
		} else {
			$tz_string = $timezone ?: wp_timezone_string();
			$tz        = new DateTimeZone( $tz_string );
			$dt        = new DateTimeImmutable( $datetime, $tz );
		}

		return $dt->setTimezone( new DateTimeZone( 'UTC' ) )->getTimestamp();
	} catch ( Exception $e ) {
		return new WP_Error(
			'venuestack_invalid_datetime',
			__( 'Could not parse datetime.', 'venuestack-core' ),
			array( 'status' => 400 )
		);
	}
}

/**
 * Whether a published venue_space exists.
 */
function venuestack_core_space_exists( int $space_id ): bool {
	$post = get_post( $space_id );

	return $post instanceof WP_Post
		&& 'venue_space' === $post->post_type
		&& 'publish' === $post->post_status;
}

/**
 * Whether the range overlaps an active booking for the space.
 *
 * Confirmed bookings always conflict. Holds conflict only when
 * post_date_gmt is within the last VENUESTACK_HOLD_TTL seconds.
 * Cancelled (and other) statuses are ignored.
 */
function venuestack_core_has_booking_overlap( int $space_id, int $start_utc, int $end_utc ): bool {
	global $wpdb;

	$hold_cutoff = gmdate( 'Y-m-d H:i:s', time() - VENUESTACK_HOLD_TTL );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$found = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT p.ID
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} space_m
				ON ( p.ID = space_m.post_id AND space_m.meta_key = 'space_id' AND space_m.meta_value = %s )
			INNER JOIN {$wpdb->postmeta} start_m
				ON ( p.ID = start_m.post_id AND start_m.meta_key = 'start_datetime' )
			INNER JOIN {$wpdb->postmeta} end_m
				ON ( p.ID = end_m.post_id AND end_m.meta_key = 'end_datetime' )
			INNER JOIN {$wpdb->postmeta} status_m
				ON ( p.ID = status_m.post_id AND status_m.meta_key = 'status' )
			WHERE p.post_type = 'venue_booking'
				AND p.post_status = 'publish'
				AND CAST( start_m.meta_value AS UNSIGNED ) < %d
				AND CAST( end_m.meta_value AS UNSIGNED ) > %d
				AND (
					status_m.meta_value = 'confirmed'
					OR (
						status_m.meta_value = 'hold'
						AND p.post_date_gmt >= %s
					)
				)
			LIMIT 1",
			(string) $space_id,
			$end_utc,
			$start_utc,
			$hold_cutoff
		)
	);

	return null !== $found;
}

/**
 * Insert a soft-hold booking after validation (caller must hold the mutex).
 *
 * @return array{booking_id:int,status:string,space_id:int,start_datetime:int,end_datetime:int,expires_at:int,hold_token:string}|\WP_Error
 */
function venuestack_core_create_hold( int $space_id, int $start_utc, int $end_utc ) {
	if ( $end_utc <= $start_utc ) {
		return new WP_Error(
			'venuestack_invalid_range',
			__( 'End must be after start.', 'venuestack-core' ),
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

	$min_hours = (int) get_post_meta( $space_id, 'minimum_booking_hours', true );
	if ( $min_hours > 0 ) {
		$hours = ( $end_utc - $start_utc ) / HOUR_IN_SECONDS;
		if ( $hours < $min_hours ) {
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
	}

	if ( venuestack_core_has_booking_overlap( $space_id, $start_utc, $end_utc ) ) {
		return new WP_Error(
			'venuestack_overlap',
			__( 'That time range is not available for this space.', 'venuestack-core' ),
			array( 'status' => 409 )
		);
	}

	$title = venuestack_core_format_booking_title( 'hold', $space_id, $start_utc );

	$author = get_current_user_id();
	if ( $author < 1 ) {
		$author = 1;
	}

	$booking_id = wp_insert_post(
		array(
			'post_type'   => 'venue_booking',
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_author' => $author,
		),
		true
	);

	if ( is_wp_error( $booking_id ) ) {
		return $booking_id;
	}

	update_post_meta( $booking_id, 'space_id', $space_id );
	update_post_meta( $booking_id, 'start_datetime', $start_utc );
	update_post_meta( $booking_id, 'end_datetime', $end_utc );
	update_post_meta( $booking_id, 'status', 'hold' );
	update_post_meta( $booking_id, 'wc_order_id', 0 );

	$created_gmt = get_post_field( 'post_date_gmt', $booking_id );
	$created_ts  = $created_gmt ? strtotime( $created_gmt . ' UTC' ) : time();
	$expires_at  = $created_ts + VENUESTACK_HOLD_TTL;

	return array(
		'booking_id'     => (int) $booking_id,
		'status'         => 'hold',
		'space_id'       => $space_id,
		'start_datetime' => $start_utc,
		'end_datetime'   => $end_utc,
		'expires_at'     => $expires_at,
		'hold_token'     => venuestack_core_create_hold_token( (int) $booking_id, $expires_at ),
	);
}
