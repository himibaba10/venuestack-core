/**
 * Date / time helpers for the booking panel.
 */

/**
 * @param {string} dateStr Y-m-d
 * @return {Date|null} Local calendar date, or null if invalid.
 */
export function parseYmd( dateStr ) {
	if ( ! dateStr || typeof dateStr !== 'string' ) {
		return null;
	}
	const parts = dateStr.split( '-' ).map( Number );
	if ( parts.length !== 3 || parts.some( ( n ) => ! Number.isFinite( n ) ) ) {
		return null;
	}
	const [ y, mo, d ] = parts;
	return new Date( y, mo - 1, d );
}

/**
 * @param {Date} date
 * @return {string} Y-m-d
 */
export function formatYmd( date ) {
	const pad = ( n ) => String( n ).padStart( 2, '0' );
	return `${ date.getFullYear() }-${ pad( date.getMonth() + 1 ) }-${ pad(
		date.getDate()
	) }`;
}

/**
 * @param {string} timeStr HH:MM
 * @return {Date|null} Today with that local time, or null if invalid.
 */
export function parseHm( timeStr ) {
	if ( ! timeStr || typeof timeStr !== 'string' ) {
		return null;
	}
	const parts = timeStr.split( ':' ).map( Number );
	if ( parts.length < 2 || parts.some( ( n ) => ! Number.isFinite( n ) ) ) {
		return null;
	}
	const [ h, mi ] = parts;
	if ( h < 0 || h > 23 || mi < 0 || mi > 59 ) {
		return null;
	}
	const date = new Date();
	date.setHours( h, mi, 0, 0 );
	return date;
}

/**
 * @param {Date} date
 * @return {string} HH:MM
 */
export function formatHm( date ) {
	const pad = ( n ) => String( n ).padStart( 2, '0' );
	return `${ pad( date.getHours() ) }:${ pad( date.getMinutes() ) }`;
}

/**
 * @param {string} dateStr Y-m-d
 * @param {string} timeStr HH:MM
 * @param {number} hours
 * @return {string} End datetime as Y-m-d H:i:s (UTC wall).
 */
export function buildEndDateTime( dateStr, timeStr, hours ) {
	const [ y, mo, d ] = dateStr.split( '-' ).map( Number );
	const [ h, mi ] = timeStr.split( ':' ).map( Number );
	const ms = Date.UTC( y, mo - 1, d, h, mi, 0 ) + hours * 60 * 60 * 1000;
	const dt = new Date( ms );
	const pad = ( n ) => String( n ).padStart( 2, '0' );
	return `${ dt.getUTCFullYear() }-${ pad( dt.getUTCMonth() + 1 ) }-${ pad(
		dt.getUTCDate()
	) } ${ pad( dt.getUTCHours() ) }:${ pad( dt.getUTCMinutes() ) }:00`;
}
