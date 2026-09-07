/**
 * Status filter helpers for the bookings calendar.
 */

const ALLOWED_STATUSES = [ 'confirmed', 'hold', 'pending', 'cancelled' ];

/**
 * @return {string} Selected status filter ('' = all).
 */
export function getStatusFilter() {
	const select = document.getElementById( 'venuestack-calendar-status' );
	if ( ! select ) {
		return '';
	}
	const value = String( select.value || '' );
	return ALLOWED_STATUSES.includes( value ) ? value : '';
}

/**
 * @param {Function} onChange Change handler.
 */
export function bindStatusFilterChange( onChange ) {
	const select = document.getElementById( 'venuestack-calendar-status' );
	if ( ! select ) {
		return;
	}
	select.addEventListener( 'change', onChange );
}
