/**
 * FullCalendar event source for VenueStack bookings.
 */
import { getCalendarConfig } from './config';
import { getSpaceFilter } from './space-filter';

/**
 * Fetch FullCalendar events for the visible range.
 *
 * @param {Object}   fetchInfo FullCalendar fetch info.
 * @param {Function} success   Success callback.
 * @param {Function} failure   Failure callback.
 */
export function fetchEvents( fetchInfo, success, failure ) {
	const config = getCalendarConfig();
	const url = new URL( config.restUrl, window.location.origin );
	url.searchParams.set( 'start', fetchInfo.startStr );
	url.searchParams.set( 'end', fetchInfo.endStr );

	const spaceId = getSpaceFilter();
	if ( spaceId > 0 ) {
		url.searchParams.set( 'space_id', String( spaceId ) );
	}

	fetch( url.toString(), {
		credentials: 'same-origin',
		headers: {
			Accept: 'application/json',
			'X-WP-Nonce': config.nonce || '',
		},
	} )
		.then( ( response ) => {
			if ( ! response.ok ) {
				throw new Error( config.i18n?.error || 'Error' );
			}
			return response.json();
		} )
		.then( ( data ) => {
			success( Array.isArray( data ) ? data : [] );
		} )
		.catch( () => {
			failure( new Error( config.i18n?.error || 'Error' ) );
		} );
}
