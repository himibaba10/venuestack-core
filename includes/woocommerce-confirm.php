<?php
/**
 * Confirm venue bookings when WC payment completes.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Flip linked venue_booking from hold → confirmed.
 *
 * Idempotent: only acts when status is still hold.
 *
 * @param int $order_id WC order ID.
 * @return bool True when status was flipped to confirmed.
 */
function venuestack_core_confirm_booking_for_order( int $order_id ): bool {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return false;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return false;
	}

	$booking_id = (int) $order->get_meta( '_venuestack_booking_id' );
	if ( $booking_id <= 0 ) {
		return false;
	}

	$booking = get_post( $booking_id );
	if ( ! $booking instanceof WP_Post || 'venue_booking' !== $booking->post_type ) {
		return false;
	}

	if ( 'hold' !== (string) get_post_meta( $booking_id, 'status', true ) ) {
		return false;
	}

	$linked_order = (int) get_post_meta( $booking_id, 'wc_order_id', true );
	if ( $linked_order > 0 && $linked_order !== $order_id ) {
		return false;
	}

	update_post_meta( $booking_id, 'status', 'confirmed' );
	venuestack_core_sync_booking_title( $booking_id );
	venuestack_core_send_booking_confirmed_email( $booking_id );

	/**
	 * Fires after a booking is confirmed from a WooCommerce order.
	 *
	 * @param int $booking_id Booking ID.
	 * @param int $order_id   Order ID.
	 */
	do_action( 'venuestack_booking_confirmed', $booking_id, $order_id );

	return true;
}

/**
 * @param int $order_id WC order ID.
 */
function venuestack_core_on_payment_complete( int $order_id ): void {
	venuestack_core_confirm_booking_for_order( $order_id );
}
add_action( 'woocommerce_payment_complete', 'venuestack_core_on_payment_complete' );

/**
 * COD / manual gateways often land on processing without payment_complete.
 *
 * @param int $order_id WC order ID.
 */
function venuestack_core_on_order_status_processing( int $order_id ): void {
	venuestack_core_confirm_booking_for_order( $order_id );
}
add_action( 'woocommerce_order_status_processing', 'venuestack_core_on_order_status_processing' );

/**
 * @param int $order_id WC order ID.
 */
function venuestack_core_on_order_status_completed( int $order_id ): void {
	venuestack_core_confirm_booking_for_order( $order_id );
}
add_action( 'woocommerce_order_status_completed', 'venuestack_core_on_order_status_completed' );

/**
 * One-shot: rename confirmed bookings that still use a Hold — title.
 */
function venuestack_core_maybe_repair_confirmed_booking_titles(): void {
	if ( get_option( 'venuestack_repaired_booking_titles' ) ) {
		return;
	}

	// Migrate pre-rename flag if present.
	if ( get_option( 'venuestack_repaired_booking_titles_v1' ) ) {
		update_option( 'venuestack_repaired_booking_titles', 1, false );
		delete_option( 'venuestack_repaired_booking_titles_v1' );
		return;
	}

	$bookings = get_posts(
		array(
			'post_type'              => 'venue_booking',
			'post_status'            => 'publish',
			'posts_per_page'         => 200,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'meta_key'               => 'status',
			'meta_value'             => 'confirmed',
		)
	);

	foreach ( $bookings as $booking ) {
		if ( ! $booking instanceof WP_Post ) {
			continue;
		}
		if ( ! str_starts_with( $booking->post_title, 'Hold' ) ) {
			continue;
		}
		venuestack_core_sync_booking_title( (int) $booking->ID );
	}

	update_option( 'venuestack_repaired_booking_titles', 1, false );
}
add_action( 'admin_init', 'venuestack_core_maybe_repair_confirmed_booking_titles' );
