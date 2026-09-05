<?php
/**
 * Disable WC stock/inventory hooks for headless booking orders.
 *
 * Fee line items are not WC products — stock reduce/restore and stock
 * notification emails must not run against them.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether a WC order was created by VenueStack checkout.
 *
 * @param WC_Order|int|false|null $order Order object or ID.
 */
function venuestack_core_is_booking_order( $order ): bool {
	if ( ! $order instanceof WC_Order ) {
		if ( ! $order || ! function_exists( 'wc_get_order' ) ) {
			return false;
		}
		$order = wc_get_order( $order );
	}

	return $order instanceof WC_Order
		&& (int) $order->get_meta( '_venuestack_booking_id' ) > 0;
}

/**
 * Never reduce inventory for booking fee orders.
 *
 * @param bool     $reduce Whether to reduce stock.
 * @param WC_Order $order  Order.
 */
function venuestack_core_filter_can_reduce_order_stock( bool $reduce, $order ): bool {
	return venuestack_core_is_booking_order( $order ) ? false : $reduce;
}
add_filter( 'woocommerce_can_reduce_order_stock', 'venuestack_core_filter_can_reduce_order_stock', 10, 2 );

/**
 * Never restore inventory for booking fee orders.
 *
 * @param bool     $restore Whether to restore stock.
 * @param WC_Order $order   Order.
 */
function venuestack_core_filter_can_restore_order_stock( bool $restore, $order ): bool {
	return venuestack_core_is_booking_order( $order ) ? false : $restore;
}
add_filter( 'woocommerce_can_restore_order_stock', 'venuestack_core_filter_can_restore_order_stock', 10, 2 );

/**
 * Skip payment-complete stock reduction for booking orders.
 *
 * @param bool $reduce   Whether to reduce stock on payment complete.
 * @param int  $order_id Order ID.
 */
function venuestack_core_filter_payment_complete_reduce_stock( bool $reduce, int $order_id ): bool {
	return venuestack_core_is_booking_order( $order_id ) ? false : $reduce;
}
add_filter( 'woocommerce_payment_complete_reduce_order_stock', 'venuestack_core_filter_payment_complete_reduce_stock', 10, 2 );

/**
 * Block admin/manual line-item stock adjustments on booking orders.
 *
 * @param bool                  $prevent Whether to prevent adjustment.
 * @param WC_Order_Item_Product $item    Line item.
 */
function venuestack_core_filter_prevent_line_item_stock( bool $prevent, $item ): bool {
	if ( ! $item instanceof WC_Order_Item || ! method_exists( $item, 'get_order' ) ) {
		return $prevent;
	}

	$order = $item->get_order();

	return venuestack_core_is_booking_order( $order ) ? true : $prevent;
}
add_filter( 'woocommerce_prevent_adjust_line_item_product_stock', 'venuestack_core_filter_prevent_line_item_stock', 10, 2 );

/**
 * Mute WC stock transactional emails site-wide (no WC products in use).
 */
function venuestack_core_disable_stock_emails(): void {
	add_filter( 'woocommerce_should_send_low_stock_notification', '__return_false' );
	add_filter( 'woocommerce_should_send_no_stock_notification', '__return_false' );
	add_filter( 'woocommerce_should_send_backorder_notification', '__return_false' );
}
add_action( 'woocommerce_init', 'venuestack_core_disable_stock_emails' );
