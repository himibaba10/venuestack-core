/**
 * Space filter helpers for the bookings calendar.
 */
import { getCalendarConfig } from './config';

/**
 * @return {number} Selected space filter (0 = all).
 */
export function getSpaceFilter() {
	const select = document.getElementById( 'venuestack-calendar-space' );
	if ( ! select ) {
		return 0;
	}
	const value = Number( select.value );
	return Number.isFinite( value ) && value > 0 ? value : 0;
}

/**
 * Populate space filter options from localized data.
 */
export function populateSpaceFilter() {
	const select = document.getElementById( 'venuestack-calendar-space' );
	const config = getCalendarConfig();
	if ( ! select || ! Array.isArray( config.spaces ) ) {
		return;
	}

	config.spaces.forEach( ( space ) => {
		const option = document.createElement( 'option' );
		option.value = String( space.id );
		option.textContent = space.name;
		select.appendChild( option );
	} );
}

/**
 * @param {Function} onChange Change handler.
 */
export function bindSpaceFilterChange( onChange ) {
	const select = document.getElementById( 'venuestack-calendar-space' );
	if ( ! select ) {
		return;
	}
	select.addEventListener( 'change', onChange );
}
