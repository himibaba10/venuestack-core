/**
 * Availability helpers for the booking panel pickers.
 */

/**
 * Venue-wall timestamp for ordering/overlap (components as UTC numbers).
 *
 * @param {string} dateStr Y-m-d
 * @param {string} timeStr HH:MM or HH:MM:SS
 * @return {number} Milliseconds wall stamp.
 */
export function wallStamp( dateStr, timeStr ) {
	const [ y, mo, d ] = dateStr.split( '-' ).map( Number );
	const timeParts = String( timeStr ).split( ':' ).map( Number );
	const h = timeParts[ 0 ] || 0;
	const mi = timeParts[ 1 ] || 0;
	const s = timeParts[ 2 ] || 0;
	return Date.UTC( y, mo - 1, d, h, mi, s );
}

/**
 * @param {string} local "Y-m-d H:i:s"
 * @return {number} Milliseconds wall stamp.
 */
export function localBusyStamp( local ) {
	if ( ! local || typeof local !== 'string' ) {
		return 0;
	}
	const [ dateStr, timeStr = '00:00:00' ] = local.trim().split( /\s+/ );
	return wallStamp( dateStr, timeStr );
}

/**
 * @param {number} aStart Range A start ms.
 * @param {number} aEnd   Range A end ms.
 * @param {number} bStart Range B start ms.
 * @param {number} bEnd   Range B end ms.
 * @return {boolean} True when the ranges overlap.
 */
export function rangesOverlap( aStart, aEnd, bStart, bEnd ) {
	return aStart < bEnd && aEnd > bStart;
}

/**
 * Normalize busy ranges from PHP / REST.
 *
 * @param {unknown} raw Raw busy range payload.
 * @return {Array<{ start: number, end: number }>} Normalized ranges in wall ms.
 */
export function normalizeBusyRanges( raw ) {
	if ( ! Array.isArray( raw ) ) {
		return [];
	}
	return raw
		.map( ( row ) => {
			if ( ! row || typeof row !== 'object' ) {
				return null;
			}
			const start =
				typeof row.start_local === 'string'
					? localBusyStamp( row.start_local )
					: Number( row.start ) * 1000;
			const end =
				typeof row.end_local === 'string'
					? localBusyStamp( row.end_local )
					: Number( row.end ) * 1000;
			if (
				! Number.isFinite( start ) ||
				! Number.isFinite( end ) ||
				end <= start
			) {
				return null;
			}
			return { start, end };
		} )
		.filter( Boolean );
}

/**
 * @param {string}                                dateStr    Y-m-d
 * @param {string}                                timeStr    HH:MM
 * @param {number}                                hours      Duration
 * @param {Array<{ start: number, end: number }>} busyRanges Busy ranges
 * @return {boolean} True when the proposed start is free.
 */
export function isStartAvailable( dateStr, timeStr, hours, busyRanges ) {
	const duration = Number( hours );
	if (
		! dateStr ||
		! timeStr ||
		! Number.isFinite( duration ) ||
		duration <= 0
	) {
		return false;
	}
	const start = wallStamp( dateStr, timeStr );
	const end = start + duration * 60 * 60 * 1000;
	return ! busyRanges.some( ( range ) =>
		rangesOverlap( start, end, range.start, range.end )
	);
}

/**
 * Bookable start times for a day (15-minute grid).
 *
 * @param {string}                                                               dateStr    Y-m-d
 * @param {number}                                                               hours      Duration
 * @param {Array<{ start: number, end: number }>}                                busyRanges Busy ranges
 * @param {{ dayStartHour?: number, dayEndHour?: number, stepMinutes?: number }} [opts]     Window
 * @return {string[]} Available HH:MM start times.
 */
export function availableStartsForDay( dateStr, hours, busyRanges, opts = {} ) {
	const dayStartHour = opts.dayStartHour ?? 8;
	const dayEndHour = opts.dayEndHour ?? 23;
	const stepMinutes = opts.stepMinutes ?? 15;
	const duration = Number( hours ) || 1;
	const starts = [];

	const lastStartMinutes = dayEndHour * 60 - Math.round( duration * 60 );
	if ( lastStartMinutes < dayStartHour * 60 ) {
		return starts;
	}

	for (
		let mins = dayStartHour * 60;
		mins <= lastStartMinutes;
		mins += stepMinutes
	) {
		const h = Math.floor( mins / 60 );
		const m = mins % 60;
		const timeStr = `${ String( h ).padStart( 2, '0' ) }:${ String(
			m
		).padStart( 2, '0' ) }`;
		if ( isStartAvailable( dateStr, timeStr, duration, busyRanges ) ) {
			starts.push( timeStr );
		}
	}

	return starts;
}

/**
 * @param {string}                                dateStr    Y-m-d
 * @param {number}                                hours      Duration
 * @param {Array<{ start: number, end: number }>} busyRanges Busy ranges
 * @param {Object}                                [opts]     Bookable window options
 * @return {boolean} True when at least one start time is free.
 */
export function isDayAvailable( dateStr, hours, busyRanges, opts = {} ) {
	return availableStartsForDay( dateStr, hours, busyRanges, opts ).length > 0;
}

/**
 * @param {Date} date
 * @return {string} Y-m-d
 */
export function dateToYmd( date ) {
	const pad = ( n ) => String( n ).padStart( 2, '0' );
	return `${ date.getFullYear() }-${ pad( date.getMonth() + 1 ) }-${ pad(
		date.getDate()
	) }`;
}
