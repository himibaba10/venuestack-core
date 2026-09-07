/**
 * Admin URL helpers for inventory screens.
 */
import { getDataViewsConfig } from './config';

/**
 * @param {number} id Post ID.
 * @return {string} Edit URL.
 */
export function getEditUrl( id ) {
	const config = getDataViewsConfig();
	const template =
		typeof config.editPostUrl === 'string'
			? config.editPostUrl
			: 'post.php?post=%d&action=edit';
	return template.replace( '%d', String( id ) );
}

/**
 * @param {number} orderId WooCommerce order ID.
 * @return {string} Order edit URL.
 */
export function getOrderUrl( orderId ) {
	const config = getDataViewsConfig();
	const base =
		typeof config.orderEditUrl === 'string'
			? config.orderEditUrl
			: 'admin.php?page=wc-orders&action=edit&id=';
	return `${ base }${ orderId }`;
}

/**
 * @param {number} bookingId Booking post ID.
 * @return {string} Admin cancel URL.
 */
export function getCancelBookingUrl( bookingId ) {
	const config = getDataViewsConfig();
	const base =
		typeof config.cancelBookingUrl === 'string'
			? config.cancelBookingUrl
			: 'admin-post.php';
	const nonce =
		typeof config.cancelBookingNonce === 'string'
			? config.cancelBookingNonce
			: '';
	const redirect =
		typeof config.bookingsInventoryUrl === 'string'
			? config.bookingsInventoryUrl
			: '';

	const url = new URL( base, window.location.origin );
	url.searchParams.set( 'action', 'venuestack_cancel_booking' );
	url.searchParams.set( 'booking_id', String( bookingId ) );
	if ( nonce ) {
		url.searchParams.set( '_wpnonce', nonce );
	}
	if ( redirect ) {
		url.searchParams.set( 'redirect', redirect );
	}
	return url.toString();
}
