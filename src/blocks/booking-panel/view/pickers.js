/**
 * Air Datepicker wiring for schedule date + start time.
 */
import AirDatepicker from 'air-datepicker';
import localeEn from 'air-datepicker/locale/en';
import { getElement } from '@wordpress/interactivity';
import { formatHm, formatYmd, parseHm, parseYmd } from './datetime';

/**
 * @param {HTMLElement} root  Booking panel root.
 * @param {Object}      state Interactivity store state.
 */
export function initDatepicker( root, state ) {
	const input = root.querySelector( '.venuestack-booking-panel__datepicker' );
	if ( ! input || input.dataset.datepickerReady === '1' ) {
		return;
	}
	input.dataset.datepickerReady = '1';

	const selected = parseYmd( state.date );
	const minDate = selected || new Date();
	minDate.setHours( 0, 0, 0, 0 );

	new AirDatepicker( input, {
		locale: localeEn,
		autoClose: true,
		minDate,
		selectedDates: selected ? [ selected ] : [],
		dateFormat: 'MMMM dd, yyyy',
		buttons: [ 'today' ],
		onSelect( { date } ) {
			const picked = Array.isArray( date ) ? date[ 0 ] : date;
			if ( ! picked ) {
				state.date = '';
				state.error = '';
				return;
			}
			state.date = formatYmd( picked );
			state.error = '';
		},
	} );
}

/**
 * @param {HTMLElement} root  Booking panel root.
 * @param {Object}      state Interactivity store state.
 */
export function initTimepicker( root, state ) {
	const input = root.querySelector( '.venuestack-booking-panel__timepicker' );
	if ( ! input || input.dataset.timepickerReady === '1' ) {
		return;
	}
	input.dataset.timepickerReady = '1';

	const selected = parseHm( state.time ) || parseHm( '10:00' );

	new AirDatepicker( input, {
		locale: localeEn,
		timepicker: true,
		onlyTimepicker: true,
		autoClose: false,
		selectedDates: selected ? [ selected ] : [],
		timeFormat: 'h:mm AA',
		minutesStep: 15,
		hoursStep: 1,
		onSelect( { date } ) {
			const picked = Array.isArray( date ) ? date[ 0 ] : date;
			if ( ! picked ) {
				state.time = '';
				state.error = '';
				return;
			}
			state.time = formatHm( picked );
			state.error = '';
		},
	} );
}

/**
 * @param {Object} state Interactivity store state.
 */
export function initPickers( state ) {
	const { ref } = getElement();
	if ( ! ref ) {
		return;
	}
	initDatepicker( ref, state );
	initTimepicker( ref, state );
}
