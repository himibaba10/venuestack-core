<?php
/**
 * WP-Cron garbage collection for expired soft holds.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/** Cron hook name. */
const VENUESTACK_HOLD_GC_HOOK = 'venuestack_core_gc_expired_holds';

/**
 * Register a 5-minute cron schedule.
 *
 * @param array<string, array{interval:int, display:string}> $schedules Schedules.
 * @return array<string, array{interval:int, display:string}>
 */
function venuestack_core_cron_schedules( array $schedules ): array {
	$schedules['venuestack_five_minutes'] = array(
		'interval' => 5 * MINUTE_IN_SECONDS,
		'display'  => __( 'Every five minutes', 'venuestack-core' ),
	);

	return $schedules;
}
add_filter( 'cron_schedules', 'venuestack_core_cron_schedules' );

/**
 * Ensure the hold GC event is scheduled.
 */
function venuestack_core_schedule_hold_gc(): void {
	if ( ! wp_next_scheduled( VENUESTACK_HOLD_GC_HOOK ) ) {
		wp_schedule_event( time(), 'venuestack_five_minutes', VENUESTACK_HOLD_GC_HOOK );
	}
}
add_action( 'init', 'venuestack_core_schedule_hold_gc' );

/**
 * Clear scheduled hold GC events.
 */
function venuestack_core_unschedule_hold_gc(): void {
	wp_clear_scheduled_hook( VENUESTACK_HOLD_GC_HOOK );
}

/**
 * Permanently delete hold bookings older than VENUESTACK_HOLD_TTL.
 *
 * @return int Number of bookings deleted.
 */
function venuestack_core_gc_expired_holds(): int {
	$cutoff = gmdate( 'Y-m-d H:i:s', time() - VENUESTACK_HOLD_TTL );

	$query = new WP_Query(
		array(
			'post_type'              => 'venue_booking',
			'post_status'            => 'publish',
			'posts_per_page'         => 100,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				array(
					'key'   => 'status',
					'value' => 'hold',
				),
			),
			'date_query'             => array(
				array(
					'column'    => 'post_date_gmt',
					'before'    => $cutoff,
					'inclusive' => false,
				),
			),
		)
	);

	$deleted = 0;

	foreach ( $query->posts as $booking_id ) {
		$result = wp_delete_post( (int) $booking_id, true );

		if ( $result ) {
			++$deleted;
		}
	}

	return $deleted;
}
add_action( VENUESTACK_HOLD_GC_HOOK, 'venuestack_core_gc_expired_holds' );
