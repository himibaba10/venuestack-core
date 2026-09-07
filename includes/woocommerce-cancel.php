<?php
/**
 * Cancel venue bookings when WC orders are cancelled / refunded / failed.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Flip linked venue_booking to cancelled when the Woo order is cancelled.
 *
 * Does not push status back to Woo (avoids loops). Idempotent.
 *
 * @param int $order_id WC order ID.
 * @return bool True when the booking was cancelled (or already cancelled).
 */
function venuestack_core_cancel_booking_for_order( int $order_id ): bool {
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

	$linked_order = (int) get_post_meta( $booking_id, 'wc_order_id', true );
	if ( $linked_order > 0 && $linked_order !== $order_id ) {
		return false;
	}

	$result = venuestack_core_cancel_booking(
		$booking_id,
		array(
			'sync_order' => false,
			'send_email' => true,
		)
	);

	return ! is_wp_error( $result );
}

/**
 * @param int $order_id WC order ID.
 */
function venuestack_core_on_order_status_cancelled( int $order_id ): void {
	venuestack_core_cancel_booking_for_order( $order_id );
}
add_action( 'woocommerce_order_status_cancelled', 'venuestack_core_on_order_status_cancelled' );

/**
 * @param int $order_id WC order ID.
 */
function venuestack_core_on_order_status_refunded( int $order_id ): void {
	venuestack_core_cancel_booking_for_order( $order_id );
}
add_action( 'woocommerce_order_status_refunded', 'venuestack_core_on_order_status_refunded' );

/**
 * @param int $order_id WC order ID.
 */
function venuestack_core_on_order_status_failed( int $order_id ): void {
	venuestack_core_cancel_booking_for_order( $order_id );
}
add_action( 'woocommerce_order_status_failed', 'venuestack_core_on_order_status_failed' );
