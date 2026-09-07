/**
 * Air Datepicker wiring for schedule date + start time.
 * Disables days/times that conflict with busy ranges for the selected duration.
 */
import AirDatepicker from 'air-datepicker';
import localeEn from 'air-datepicker/locale/en';
import { getElement } from '@wordpress/interactivity';
import {
	availableStartsForDay,
	dateToYmd,
	isDayAvailable,
	isStartAvailable,
	normalizeBusyRanges,
} from './availability';
import { formatHm, formatYmd, parseHm, parseYmd } from './datetime';

/** @type {AirDatepicker|null} */
let datePickerInstance = null;
/** @type {AirDatepicker|null} */
let timePickerInstance = null;

const BOOKABLE = {
	dayStartHour: 8,
	dayEndHour: 23,
	stepMinutes: 15,
};

/**
 * @param {Object} state Interactivity state.
 * @return {Array<{ start: number, end: number }>} Normalized busy ranges.
 */
function busyFromState( state ) {
	return normalizeBusyRanges( state.busyRanges );
}

/**
 * @param {Object} state   Interactivity state.
 * @param {string} dateStr Y-m-d
 * @return {string[]} Available HH:MM starts.
 */
function startsFor( state, dateStr ) {
	return availableStartsForDay(
		dateStr,
		Number( state.hours ) || Number( state.minHours ) || 1,
		busyFromState( state ),
		BOOKABLE
	);
}

/**
 * Keep selected time valid for date + duration; prefer nearest available.
 *
 * @param {Object} state
 */
function ensureValidTime( state ) {
	if ( ! state.date ) {
		return;
	}
	const hours = Number( state.hours ) || Number( state.minHours ) || 1;
	const busy = busyFromState( state );
	if (
		state.time &&
		isStartAvailable( state.date, state.time, hours, busy )
	) {
		return;
	}
	const starts = startsFor( state, state.date );
	state.time = starts[ 0 ] || '';
	if ( timePickerInstance && state.time ) {
		const parsed = parseHm( state.time );
		if ( parsed ) {
			timePickerInstance.selectDate( parsed, { silent: true } );
		}
	}
}

/**
 * @param {Object} state
 */
function ensureValidDate( state ) {
	const hours = Number( state.hours ) || Number( state.minHours ) || 1;
	const busy = busyFromState( state );
	if ( state.date && isDayAvailable( state.date, hours, busy, BOOKABLE ) ) {
		ensureValidTime( state );
		return;
	}

	const cursor = parseYmd( state.date ) || new Date();
	cursor.setHours( 0, 0, 0, 0 );
	for ( let i = 0; i < 120; i++ ) {
		const ymd = formatYmd( cursor );
		if ( isDayAvailable( ymd, hours, busy, BOOKABLE ) ) {
			state.date = ymd;
			if ( datePickerInstance ) {
				datePickerInstance.selectDate( cursor, { silent: true } );
			}
			ensureValidTime( state );
			return;
		}
		cursor.setDate( cursor.getDate() + 1 );
	}
	state.date = '';
	state.time = '';
}

/**
 * @param {HTMLElement} root
 * @param {Object}      state
 */
export function initDatepicker( root, state ) {
	const input = root.querySelector( '.venuestack-booking-panel__datepicker' );
	if ( ! input ) {
		return;
	}
	if ( datePickerInstance ) {
		datePickerInstance.destroy();
		datePickerInstance = null;
		delete input.dataset.datepickerReady;
	}
	input.dataset.datepickerReady = '1';

	const selected = parseYmd( state.date );
	const minDate = new Date();
	minDate.setHours( 0, 0, 0, 0 );

	datePickerInstance = new AirDatepicker( input, {
		locale: localeEn,
		autoClose: true,
		minDate,
		selectedDates: selected ? [ selected ] : [],
		dateFormat: 'MMMM dd, yyyy',
		buttons: [ 'today' ],
		onRenderCell( { date, cellType } ) {
			if ( cellType !== 'day' ) {
				return;
			}
			const ymd = dateToYmd( date );
			const hours =
				Number( state.hours ) || Number( state.minHours ) || 1;
			if (
				! isDayAvailable( ymd, hours, busyFromState( state ), BOOKABLE )
			) {
				return { disabled: true };
			}
		},
		onBeforeSelect( { date } ) {
			const ymd = dateToYmd( date );
			const hours =
				Number( state.hours ) || Number( state.minHours ) || 1;
			return isDayAvailable(
				ymd,
				hours,
				busyFromState( state ),
				BOOKABLE
			);
		},
		onSelect( { date } ) {
			const picked = Array.isArray( date ) ? date[ 0 ] : date;
			if ( ! picked ) {
				state.date = '';
				state.error = '';
				return;
			}
			state.date = formatYmd( picked );
			state.error = '';
			ensureValidTime( state );
			if ( ! state.time ) {
				state.error =
					'No open start times for this date and duration. Try another day.';
			}
		},
	} );
}

/**
 * @param {HTMLElement} root
 * @param {Object}      state
 */
export function initTimepicker( root, state ) {
	const input = root.querySelector( '.venuestack-booking-panel__timepicker' );
	if ( ! input ) {
		return;
	}
	if ( timePickerInstance ) {
		timePickerInstance.destroy();
		timePickerInstance = null;
		delete input.dataset.timepickerReady;
	}
	input.dataset.timepickerReady = '1';

	const selected = parseHm( state.time ) || parseHm( '10:00' );

	timePickerInstance = new AirDatepicker( input, {
		locale: localeEn,
		timepicker: true,
		onlyTimepicker: true,
		autoClose: false,
		selectedDates: selected ? [ selected ] : [],
		timeFormat: 'h:mm AA',
		minutesStep: BOOKABLE.stepMinutes,
		hoursStep: 1,
		minHours: BOOKABLE.dayStartHour,
		maxHours: BOOKABLE.dayEndHour,
		onBeforeSelect( { date } ) {
			if ( ! state.date ) {
				return false;
			}
			const timeStr = formatHm( date );
			const hours =
				Number( state.hours ) || Number( state.minHours ) || 1;
			return isStartAvailable(
				state.date,
				timeStr,
				hours,
				busyFromState( state )
			);
		},
		onSelect( { date } ) {
			const picked = Array.isArray( date ) ? date[ 0 ] : date;
			if ( ! picked ) {
				state.time = '';
				state.error = '';
				return;
			}
			const timeStr = formatHm( picked );
			const hours =
				Number( state.hours ) || Number( state.minHours ) || 1;
			if (
				state.date &&
				! isStartAvailable(
					state.date,
					timeStr,
					hours,
					busyFromState( state )
				)
			) {
				state.error =
					'That start time overlaps an existing booking. Pick another time.';
				ensureValidTime( state );
				return;
			}
			state.time = timeStr;
			state.error = '';
		},
	} );
}

/**
 * Re-apply disabled days after duration changes.
 *
 * @param {Object} state
 */
export function refreshAvailability( state ) {
	ensureValidDate( state );
	if ( datePickerInstance ) {
		datePickerInstance.update( {} );
	}
	if ( timePickerInstance && state.time ) {
		const parsed = parseHm( state.time );
		if ( parsed ) {
			timePickerInstance.selectDate( parsed, { silent: true } );
		}
	}
}

/**
 * @param {Object} state Interactivity store state.
 */
export function initPickers( state ) {
	const { ref } = getElement();
	if ( ! ref ) {
		return;
	}
	state.busyRanges = normalizeBusyRanges( state.busyRanges );
	ensureValidDate( state );
	initDatepicker( ref, state );
	initTimepicker( ref, state );
}
