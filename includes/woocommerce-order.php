<?php
/**
 * Create WooCommerce orders from venue bookings (no WC products).
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/security.php';

/**
 * Whether a hold booking is still active (not expired).
 */
function venuestack_core_is_hold_active( int $booking_id ): bool {
	$post = get_post( $booking_id );

	if ( ! $post instanceof WP_Post || 'venue_booking' !== $post->post_type ) {
		return false;
	}

	if ( 'hold' !== (string) get_post_meta( $booking_id, 'status', true ) ) {
		return false;
	}

	$created = strtotime( $post->post_date_gmt . ' UTC' );

	return $created && ( time() - $created ) < VENUESTACK_HOLD_TTL;
}

/**
 * Atomically claim wc_order_id on a booking (only if still 0/empty).
 */
function venuestack_core_claim_booking_order_id( int $booking_id, int $order_id ): bool {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$updated = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->postmeta}
			SET meta_value = %s
			WHERE post_id = %d
				AND meta_key = 'wc_order_id'
				AND meta_value IN ( '0', '' )",
			(string) $order_id,
			$booking_id
		)
	);

	if ( 1 === (int) $updated ) {
		clean_post_cache( $booking_id );
		return true;
	}

	$current = get_post_meta( $booking_id, 'wc_order_id', true );
	if ( '' === $current || false === $current ) {
		return (bool) add_post_meta( $booking_id, 'wc_order_id', $order_id, true );
	}

	return false;
}

/**
 * Delete a WC order created during a failed checkout attempt.
 */
function venuestack_core_delete_orphan_order( $order ): void {
	if ( $order instanceof WC_Order ) {
		$order->delete( true );
	}
}

/**
 * Build a WC order with custom fee line items from server-side pricing.
 *
 * Ignores any client-supplied total. Rates come from DB via
 * venuestack_core_calculate_price().
 *
 * @param int                  $booking_id Active hold booking ID.
 * @param int                  $headcount  Guest count.
 * @param int                  $package_id Optional event_package ID.
 * @param array<string,string> $billing    Optional billing fields.
 * @param string               $hold_token Signed hold token from /holds.
 * @return array{order_id:int,booking_id:int,total:float,currency:string,payment_method:string,pricing:array}|\WP_Error
 */
function venuestack_core_create_order_for_booking(
	int $booking_id,
	int $headcount = 0,
	int $package_id = 0,
	array $billing = array(),
	string $hold_token = ''
) {
	if ( ! function_exists( 'wc_create_order' ) ) {
		return new WP_Error(
			'venuestack_wc_missing',
			__( 'WooCommerce is required to create booking orders.', 'venuestack-core' ),
			array( 'status' => 503 )
		);
	}

	if ( ! venuestack_core_verify_hold_token( $booking_id, $hold_token ) ) {
		return new WP_Error(
			'venuestack_invalid_token',
			__( 'Invalid or expired hold token.', 'venuestack-core' ),
			array( 'status' => 403 )
		);
	}

	if ( ! venuestack_core_acquire_booking_lock( $booking_id ) ) {
		return new WP_Error(
			'venuestack_locked',
			__( 'This booking is busy processing checkout. Try again.', 'venuestack-core' ),
			array( 'status' => 423 )
		);
	}

	try {
		return venuestack_core_create_order_for_booking_locked(
			$booking_id,
			$headcount,
			$package_id,
			$billing
		);
	} finally {
		venuestack_core_release_booking_lock( $booking_id );
	}
}

/**
 * Create order while holding the booking mutex.
 *
 * @param array<string,string> $billing Billing fields.
 * @return array{order_id:int,booking_id:int,total:float,currency:string,payment_method:string,pricing:array}|\WP_Error
 */
function venuestack_core_create_order_for_booking_locked(
	int $booking_id,
	int $headcount,
	int $package_id,
	array $billing
) {
	if ( ! venuestack_core_is_hold_active( $booking_id ) ) {
		return new WP_Error(
			'venuestack_hold_inactive',
			__( 'Booking hold is missing or expired.', 'venuestack-core' ),
			array( 'status' => 409 )
		);
	}

	$existing_order = (int) get_post_meta( $booking_id, 'wc_order_id', true );
	if ( $existing_order > 0 ) {
		return new WP_Error(
			'venuestack_order_exists',
			__( 'An order already exists for this booking.', 'venuestack-core' ),
			array( 'status' => 409 )
		);
	}

	$space_id  = (int) get_post_meta( $booking_id, 'space_id', true );
	$start_utc = (int) get_post_meta( $booking_id, 'start_datetime', true );
	$end_utc   = (int) get_post_meta( $booking_id, 'end_datetime', true );

	$pricing = venuestack_core_calculate_price( $space_id, $start_utc, $end_utc, $headcount, $package_id );
	if ( is_wp_error( $pricing ) ) {
		return $pricing;
	}

	$order = wc_create_order(
		array(
			'status'      => 'pending',
			'customer_id' => get_current_user_id(),
		)
	);

	if ( is_wp_error( $order ) ) {
		return $order;
	}

	if ( ! $order instanceof WC_Order ) {
		return new WP_Error(
			'venuestack_order_failed',
			__( 'Could not create WooCommerce order.', 'venuestack-core' ),
			array( 'status' => 500 )
		);
	}

	$space_title = get_the_title( $space_id );
	$range_label = sprintf(
		'%s – %s UTC',
		gmdate( 'Y-m-d H:i', $start_utc ),
		gmdate( 'Y-m-d H:i', $end_utc )
	);

	$space_item = new WC_Order_Item_Fee();
	$space_item->set_name(
		sprintf(
			/* translators: 1: space title, 2: UTC datetime range */
			__( 'Space: %1$s (%2$s)', 'venuestack-core' ),
			$space_title,
			$range_label
		)
	);
	$space_item->set_amount( $pricing['space_subtotal'] );
	$space_item->set_total( $pricing['space_subtotal'] );
	$space_item->set_tax_status( 'none' );
	$order->add_item( $space_item );

	if ( $package_id > 0 && $pricing['package_subtotal'] > 0 ) {
		$package_item = new WC_Order_Item_Fee();
		$package_item->set_name(
			sprintf(
				/* translators: 1: package title, 2: headcount */
				__( 'Package: %1$s × %2$d guests', 'venuestack-core' ),
				get_the_title( $package_id ),
				$headcount
			)
		);
		$package_item->set_amount( $pricing['package_subtotal'] );
		$package_item->set_total( $pricing['package_subtotal'] );
		$package_item->set_tax_status( 'none' );
		$order->add_item( $package_item );
	}

	$billing_defaults = array(
		'first_name' => '',
		'last_name'  => '',
		'email'      => '',
		'phone'      => '',
	);
	$billing = array_merge( $billing_defaults, array_intersect_key( $billing, $billing_defaults ) );

	if ( $billing['email'] || $billing['first_name'] || $billing['last_name'] ) {
		$order->set_address( $billing, 'billing' );
	}

	$order->set_currency( get_woocommerce_currency() );
	$order->set_payment_method( 'cod' );
	$order->set_payment_method_title( __( 'Cash on Delivery', 'venuestack-core' ) );

	$order->update_meta_data( '_venuestack_booking_id', $booking_id );
	$order->update_meta_data( '_venuestack_space_id', $space_id );
	$order->update_meta_data( '_venuestack_package_id', $package_id );
	$order->update_meta_data( '_venuestack_headcount', $headcount );
	$order->update_meta_data( '_venuestack_start_datetime', $start_utc );
	$order->update_meta_data( '_venuestack_end_datetime', $end_utc );

	// Fee lines have no product stock — mark reduced so WC skips inventory paths.
	$order->set_order_stock_reduced( true );

	$order->calculate_totals( false );
	$order->save();

	$order_id = (int) $order->get_id();
	if ( $order_id < 1 ) {
		venuestack_core_delete_orphan_order( $order );
		return new WP_Error(
			'venuestack_order_failed',
			__( 'Could not create WooCommerce order.', 'venuestack-core' ),
			array( 'status' => 500 )
		);
	}

	if ( ! venuestack_core_claim_booking_order_id( $booking_id, $order_id ) ) {
		venuestack_core_delete_orphan_order( $order );
		return new WP_Error(
			'venuestack_order_exists',
			__( 'An order already exists for this booking.', 'venuestack-core' ),
			array( 'status' => 409 )
		);
	}

	/*
	 * Match stock COD checkout: move pending → processing so
	 * woocommerce_order_status_processing confirms the hold.
	 */
	$order->update_status(
		'processing',
		__( 'VenueStack booking checkout completed (COD).', 'venuestack-core' )
	);

	return array(
		'order_id'       => $order_id,
		'booking_id'     => $booking_id,
		'total'          => (float) $order->get_total(),
		'currency'       => $order->get_currency(),
		'payment_method' => 'cod',
		'pricing'        => $pricing,
		'received_url'   => $order->get_checkout_order_received_url(),
	);
}
