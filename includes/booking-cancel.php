<?php
/**
 * Cancel a venue booking and optionally sync the linked WooCommerce order.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether a booking can be cancelled from its current status.
 *
 * @param string $status Booking status slug.
 */
function venuestack_core_booking_can_cancel( string $status ): bool {
	return in_array( $status, array( 'hold', 'confirmed', 'pending' ), true );
}

/**
 * Cancel a booking by ID.
 *
 * @param int                  $booking_id Booking post ID.
 * @param array<string, mixed> $args {
 *     Optional. Arguments.
 *     @type bool   $sync_order   Cancel linked WC order when possible. Default true.
 *     @type bool   $send_email   Send cancelled email. Default true.
 *     @type string $order_note   Note written on the WC order. Default ''.
 * }
 * @return true|\WP_Error
 */
function venuestack_core_cancel_booking( int $booking_id, array $args = array() ) {
	static $in_progress = array();

	if ( $booking_id < 1 ) {
		return new WP_Error( 'venuestack_cancel_id', __( 'Invalid booking.', 'venuestack-core' ) );
	}

	if ( isset( $in_progress[ $booking_id ] ) ) {
		return true;
	}

	$booking = get_post( $booking_id );
	if ( ! $booking instanceof WP_Post || 'venue_booking' !== $booking->post_type ) {
		return new WP_Error( 'venuestack_cancel_missing', __( 'Booking not found.', 'venuestack-core' ) );
	}

	$status = (string) get_post_meta( $booking_id, 'status', true );
	if ( 'cancelled' === $status ) {
		return true;
	}

	if ( ! venuestack_core_booking_can_cancel( $status ) ) {
		return new WP_Error(
			'venuestack_cancel_status',
			__( 'This booking cannot be cancelled from its current status.', 'venuestack-core' )
		);
	}

	$sync_order = array_key_exists( 'sync_order', $args ) ? (bool) $args['sync_order'] : true;
	$send_email = array_key_exists( 'send_email', $args ) ? (bool) $args['send_email'] : true;
	$order_note = isset( $args['order_note'] ) && is_string( $args['order_note'] )
		? $args['order_note']
		: __( 'Booking cancelled in VenueStack admin.', 'venuestack-core' );

	$in_progress[ $booking_id ] = true;

	update_post_meta( $booking_id, 'status', 'cancelled' );
	venuestack_core_sync_booking_title( $booking_id );

	if ( $send_email ) {
		venuestack_core_send_booking_cancelled_email( $booking_id );
	}

	$order_id = (int) get_post_meta( $booking_id, 'wc_order_id', true );

	if ( $sync_order && $order_id > 0 && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( $order_id );
		if ( $order instanceof WC_Order && ! $order->has_status( array( 'cancelled', 'refunded' ) ) ) {
			$order->update_status( 'cancelled', $order_note );
		}
	}

	/**
	 * Fires after a booking is cancelled.
	 *
	 * @param int $booking_id Booking ID.
	 * @param int $order_id   Linked order ID (0 if none).
	 */
	do_action( 'venuestack_booking_cancelled', $booking_id, $order_id );

	unset( $in_progress[ $booking_id ] );

	return true;
}
