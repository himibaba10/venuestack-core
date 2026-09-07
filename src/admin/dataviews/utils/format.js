/**
 * Display formatters for inventory tables.
 */
import { getDataViewsConfig } from './config';

/**
 * @param {number|string|undefined} value  Money amount.
 * @param {string}                  suffix Unit label (e.g. "/hr").
 * @return {string} Formatted money or em dash.
 */
export function formatMoney( value, suffix = '' ) {
	const n = Number( value );
	if ( ! Number.isFinite( n ) || n <= 0 ) {
		return '—';
	}
	const amount = n.toLocaleString( undefined, {
		minimumFractionDigits: 0,
		maximumFractionDigits: 2,
	} );
	return `$${ amount }${ suffix }`;
}

/**
 * @param {number|string|undefined} value Hourly rate.
 * @return {string} Formatted rate.
 */
export function formatHourlyRate( value ) {
	return formatMoney( value, '/hr' );
}

/**
 * @param {number|string|undefined} value Per-guest price.
 * @return {string} Formatted price.
 */
export function formatPerGuest( value ) {
	return formatMoney( value, '/guest' );
}

/**
 * @param {number} utc Unix timestamp (UTC seconds).
 * @return {string} Localized datetime label.
 */
export function formatUtcTimestamp( utc ) {
	const ts = Number( utc );
	if ( ! Number.isFinite( ts ) || ts < 1 ) {
		return '—';
	}

	const config = getDataViewsConfig();

	try {
		return new Intl.DateTimeFormat( undefined, {
			dateStyle: 'medium',
			timeStyle: 'short',
			timeZone: config.timezone || undefined,
		} ).format( new Date( ts * 1000 ) );
	} catch ( e ) {
		return new Date( ts * 1000 ).toLocaleString();
	}
}
