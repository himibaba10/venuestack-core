<?php
/**
 * Customer My Bookings: query helpers, page seed, login redirect, block register.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Permalink for the My bookings page (falls back to /my-bookings/).
 */
function venuestack_core_get_my_bookings_url(): string {
	$page = get_page_by_path( 'my-bookings' );
	if ( $page instanceof WP_Post ) {
		$url = get_permalink( $page );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	return home_url( '/my-bookings/' );
}

/**
 * Bookings owned by a user via Woo order customer_id.
 *
 * @param int $user_id WP user ID.
 * @return array<int, array<string, mixed>>
 */
function venuestack_core_get_bookings_for_user( int $user_id ): array {
	if ( $user_id < 1 || ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}

	$orders = wc_get_orders(
		array(
			'customer_id' => $user_id,
			'limit'       => 50,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'return'      => 'objects',
			'status'      => array_keys( wc_get_order_statuses() ),
		)
	);

	if ( ! is_array( $orders ) || array() === $orders ) {
		return array();
	}

	$rows = array();

	foreach ( $orders as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}

		$booking_id = (int) $order->get_meta( '_venuestack_booking_id' );
		if ( $booking_id < 1 ) {
			continue;
		}

		$booking = get_post( $booking_id );
		if ( ! $booking instanceof WP_Post || 'venue_booking' !== $booking->post_type ) {
			continue;
		}

		$linked_order = (int) get_post_meta( $booking_id, 'wc_order_id', true );
		if ( $linked_order > 0 && $linked_order !== (int) $order->get_id() ) {
			continue;
		}

		$space_id    = (int) get_post_meta( $booking_id, 'space_id', true );
		$start_utc   = (int) get_post_meta( $booking_id, 'start_datetime', true );
		$end_utc     = (int) get_post_meta( $booking_id, 'end_datetime', true );
		$status      = (string) get_post_meta( $booking_id, 'status', true );
		$space_title = $space_id > 0 ? get_the_title( $space_id ) : '';
		if ( '' === $space_title ) {
			$space_title = __( 'Space', 'venuestack-core' );
		}

		$space_url = $space_id > 0 ? get_permalink( $space_id ) : '';
		$order_url = $order->get_checkout_order_received_url();

		$rows[] = array(
			'booking_id'   => $booking_id,
			'status'       => $status ?: 'pending',
			'status_label' => venuestack_core_booking_status_label( $status ?: 'pending' ),
			'space_id'     => $space_id,
			'space_title'  => $space_title,
			'space_url'    => is_string( $space_url ) ? $space_url : '',
			'start_label'  => venuestack_core_format_booking_email_datetime( $start_utc ),
			'end_label'    => venuestack_core_format_booking_email_datetime( $end_utc ),
			'order_id'     => (int) $order->get_id(),
			'order_number' => $order->get_order_number(),
			'order_url'    => is_string( $order_url ) ? $order_url : '',
		);
	}

	return $rows;
}

/**
 * Ensure a published My bookings page exists.
 */
function venuestack_core_ensure_my_bookings_page(): void {
	$existing = get_page_by_path( 'my-bookings' );
	if ( $existing instanceof WP_Post ) {
		return;
	}

	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => __( 'My bookings', 'venuestack-core' ),
			'post_name'    => 'my-bookings',
			'post_content' => '',
		),
		true
	);
}
add_action( 'init', 'venuestack_core_ensure_my_bookings_page', 30 );

/**
 * Guests visiting My bookings are sent to login (return here after).
 */
function venuestack_core_my_bookings_require_login(): void {
	if ( ! is_page( 'my-bookings' ) || is_user_logged_in() ) {
		return;
	}

	wp_safe_redirect( wp_login_url( venuestack_core_get_my_bookings_url() ) );
	exit;
}
add_action( 'template_redirect', 'venuestack_core_my_bookings_require_login' );

/**
 * Register the my-bookings block.
 */
function venuestack_core_register_my_bookings_block(): void {
	$path = VENUESTACK_CORE_PATH . 'build/blocks/my-bookings';
	if ( file_exists( $path . '/block.json' ) ) {
		register_block_type( $path );
	}
}
add_action( 'init', 'venuestack_core_register_my_bookings_block' );
