<?php
/**
 * Space availability: busy ranges for the booking panel.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/booking.php';

/**
 * Active busy ranges for a space that overlap a UTC window.
 *
 * Same rules as overlap checks: confirmed always; holds only while fresh.
 *
 * @param int $space_id         Venue space ID.
 * @param int $window_start_utc Window start (UTC unix).
 * @param int $window_end_utc   Window end (UTC unix).
 * @return array<int, array{start:int,end:int,start_local:string,end_local:string}>
 */
function venuestack_core_get_space_busy_ranges( int $space_id, int $window_start_utc, int $window_end_utc ): array {
	global $wpdb;

	if ( $space_id < 1 || $window_end_utc <= $window_start_utc ) {
		return array();
	}

	$hold_cutoff = gmdate( 'Y-m-d H:i:s', time() - VENUESTACK_HOLD_TTL );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT
				CAST( start_m.meta_value AS UNSIGNED ) AS start_utc,
				CAST( end_m.meta_value AS UNSIGNED ) AS end_utc
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
			ORDER BY start_utc ASC",
			(string) $space_id,
			$window_end_utc,
			$window_start_utc,
			$hold_cutoff
		),
		ARRAY_A
	);

	if ( ! is_array( $rows ) || array() === $rows ) {
		return array();
	}

	$tz     = wp_timezone();
	$ranges = array();

	foreach ( $rows as $row ) {
		$start = (int) ( $row['start_utc'] ?? 0 );
		$end   = (int) ( $row['end_utc'] ?? 0 );
		if ( $start < 1 || $end <= $start ) {
			continue;
		}

		$start_dt = ( new DateTimeImmutable( '@' . $start ) )->setTimezone( $tz );
		$end_dt   = ( new DateTimeImmutable( '@' . $end ) )->setTimezone( $tz );

		$ranges[] = array(
			'start'       => $start,
			'end'         => $end,
			'start_local' => $start_dt->format( 'Y-m-d H:i:s' ),
			'end_local'   => $end_dt->format( 'Y-m-d H:i:s' ),
		);
	}

	return $ranges;
}

/**
 * Busy ranges for the booking panel (next N days from today in site TZ).
 *
 * @param int $space_id Venue space ID.
 * @param int $days     Horizon in days.
 * @return array<int, array{start:int,end:int,start_local:string,end_local:string}>
 */
function venuestack_core_get_space_busy_ranges_for_panel( int $space_id, int $days = 120 ): array {
	$days = max( 1, min( 366, $days ) );
	$tz   = wp_timezone();
	$from = new DateTimeImmutable( 'today', $tz );
	$to   = $from->modify( '+' . $days . ' days' )->setTime( 23, 59, 59 );

	return venuestack_core_get_space_busy_ranges(
		$space_id,
		$from->setTimezone( new DateTimeZone( 'UTC' ) )->getTimestamp(),
		$to->setTimezone( new DateTimeZone( 'UTC' ) )->getTimestamp()
	);
}

/**
 * Register availability REST route.
 */
function venuestack_core_register_availability_routes(): void {
	register_rest_route(
		'venuestack',
		'/availability',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'venuestack_core_rest_availability',
			'permission_callback' => '__return_true',
			'args'                => array(
				'space_id' => array(
					'required'          => true,
					'type'              => 'integer',
					'minimum'           => 1,
					'sanitize_callback' => 'absint',
				),
				'days'     => array(
					'required'          => false,
					'type'              => 'integer',
					'default'           => 120,
					'minimum'           => 1,
					'maximum'           => 366,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'venuestack_core_register_availability_routes' );

/**
 * GET /venuestack/availability?space_id=&days=
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function venuestack_core_rest_availability( WP_REST_Request $request ) {
	$space_id = (int) $request['space_id'];
	if ( ! venuestack_core_space_exists( $space_id ) ) {
		return new WP_Error(
			'venuestack_invalid_space',
			__( 'Venue space not found.', 'venuestack-core' ),
			array( 'status' => 404 )
		);
	}

	$days   = (int) ( $request['days'] ?? 120 );
	$ranges = venuestack_core_get_space_busy_ranges_for_panel( $space_id, $days );

	return new WP_REST_Response(
		array(
			'space_id'    => $space_id,
			'timezone'    => wp_timezone_string(),
			'busy_ranges' => $ranges,
		),
		200
	);
}
