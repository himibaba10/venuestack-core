<?php
/**
 * Confirm venue bookings when WC payment completes.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Flip linked venue_booking from hold → confirmed after payment.
 *
 * @param int $order_id WC order ID.
 */
function venuestack_core_on_payment_complete( int $order_id ): void {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$booking_id = (int) $order->get_meta( '_venuestack_booking_id' );
	if ( $booking_id <= 0 ) {
		return;
	}

	$booking = get_post( $booking_id );
	if ( ! $booking instanceof WP_Post || 'venue_booking' !== $booking->post_type ) {
		return;
	}

	if ( 'hold' !== (string) get_post_meta( $booking_id, 'status', true ) ) {
		return;
	}

	$linked_order = (int) get_post_meta( $booking_id, 'wc_order_id', true );
	if ( $linked_order > 0 && $linked_order !== $order_id ) {
		return;
	}

	update_post_meta( $booking_id, 'status', 'confirmed' );
}
add_action( 'woocommerce_payment_complete', 'venuestack_core_on_payment_complete' );
