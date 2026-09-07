<?php
/**
 * Booking panel block registration.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the booking-panel block from the plugin build folder.
 */
function venuestack_core_register_booking_panel_block(): void {
	$path = VENUESTACK_CORE_PATH . 'build/blocks/booking-panel';
	if ( file_exists( $path . '/block.json' ) ) {
		register_block_type( $path );
	}
}
add_action( 'init', 'venuestack_core_register_booking_panel_block' );
