/**
 * Single-space booking panel (hold → checkout) Interactivity store.
 */
import AirDatepicker from 'air-datepicker';
import localeEn from 'air-datepicker/locale/en';
import { getElement, store } from '@wordpress/interactivity';
import 'air-datepicker/air-datepicker.css';
import './view.css';

/** @type {ReturnType<typeof setInterval>|null} */
let countdownTimer = null;

/**
 * @param {string} dateStr Y-m-d
 * @return {Date|null} Local calendar date, or null if invalid.
 */
function parseYmd( dateStr ) {
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
function formatYmd( date ) {
	const pad = ( n ) => String( n ).padStart( 2, '0' );
	return `${ date.getFullYear() }-${ pad( date.getMonth() + 1 ) }-${ pad(
		date.getDate()
	) }`;
}

/**
 * @param {string} timeStr HH:MM
 * @return {Date|null} Today with that local time, or null if invalid.
 */
function parseHm( timeStr ) {
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
function formatHm( date ) {
	const pad = ( n ) => String( n ).padStart( 2, '0' );
	return `${ pad( date.getHours() ) }:${ pad( date.getMinutes() ) }`;
}

/**
 * @param {string} dateStr Y-m-d
 * @param {string} timeStr HH:MM
 * @param {number} hours
 * @return {string} End datetime as Y-m-d H:i:s.
 */
function buildEndDateTime( dateStr, timeStr, hours ) {
	const [ y, mo, d ] = dateStr.split( '-' ).map( Number );
	const [ h, mi ] = timeStr.split( ':' ).map( Number );
	const ms = Date.UTC( y, mo - 1, d, h, mi, 0 ) + hours * 60 * 60 * 1000;
	const dt = new Date( ms );
	const pad = ( n ) => String( n ).padStart( 2, '0' );
	return `${ dt.getUTCFullYear() }-${ pad( dt.getUTCMonth() + 1 ) }-${ pad(
		dt.getUTCDate()
	) } ${ pad( dt.getUTCHours() ) }:${ pad( dt.getUTCMinutes() ) }:00`;
}

/**
 * @param {number} amount Currency amount.
 * @return {string} Formatted money label.
 */
function formatMoney( amount ) {
	return `$${ Number( amount ).toLocaleString( undefined, {
		minimumFractionDigits: 0,
		maximumFractionDigits: 2,
	} ) }`;
}

/**
 * @param {Response} response Fetch response.
 * @return {Promise<Object>} Parsed JSON body or empty object.
 */
async function readJson( response ) {
	try {
		return await response.json();
	} catch ( e ) {
		return {};
	}
}

/**
 * @param {Object} body REST error body.
 * @return {string} Human-readable error message.
 */
function errorMessage( body ) {
	if ( body && typeof body.message === 'string' && body.message ) {
		return body.message;
	}
	return 'Something went wrong. Please try again.';
}

/**
 * Attach Air Datepicker to the schedule date field.
 *
 * @param {HTMLElement} root Booking panel root.
 */
function initDatepicker( root ) {
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
 * Attach Air Datepicker time-only control to the start time field.
 *
 * @param {HTMLElement} root Booking panel root.
 */
function initTimepicker( root ) {
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
 * Initialize date + time pickers on the panel.
 */
function initPickers() {
	const { ref } = getElement();
	if ( ! ref ) {
		return;
	}
	initDatepicker( ref );
	initTimepicker( ref );
}

const { state } = store( 'venuestack/booking-panel', {
	state: {
		get hasError() {
			return Boolean( state.error );
		},
		get hideError() {
			return ! state.error;
		},
		get hideSchedule() {
			return state.step !== 'schedule';
		},
		get hideCheckout() {
			return state.step !== 'checkout';
		},
		get hideBusy() {
			return ! state.busy;
		},
		get hideIdle() {
			return state.busy;
		},
		get isSchedule() {
			return state.step === 'schedule';
		},
		get isCheckout() {
			return state.step === 'checkout';
		},
		get estimate() {
			const hours = Number( state.hours ) || 0;
			const headcount = Number( state.headcount ) || 0;
			const rate = Number( state.hourlyRate ) || 0;
			const packageId = Number( state.packageId ) || 0;
			let perHead = 0;
			const packages = Array.isArray( state.packages )
				? state.packages
				: [];
			const match = packages.find(
				( p ) => Number( p.id ) === packageId
			);
			if ( match ) {
				perHead = Number( match.price_per_head ) || 0;
			}
			return rate * hours + perHead * headcount;
		},
		get estimateLabel() {
			return formatMoney( state.estimate );
		},
		get countdownLabel() {
			const sec = Math.max( 0, Number( state.secondsLeft ) || 0 );
			const m = Math.floor( sec / 60 );
			const s = sec % 60;
			return `${ m }:${ String( s ).padStart( 2, '0' ) } remaining`;
		},
	},
	actions: {
		setHours( event ) {
			state.hours = Number( event.target.value ) || state.minHours;
			state.error = '';
		},
		setHeadcount( event ) {
			let n = Number( event.target.value ) || 0;
			const max = Number( state.maxCapacity ) || 0;
			if ( max > 0 && n > max ) {
				n = max;
			}
			if ( n < 1 ) {
				n = 1;
			}
			state.headcount = n;
			state.error = '';
		},
		setPackage( event ) {
			state.packageId = Number( event.target.value ) || 0;
			state.error = '';
		},
		setFirstName( event ) {
			state.firstName = event.target.value;
		},
		setLastName( event ) {
			state.lastName = event.target.value;
		},
		setEmail( event ) {
			state.email = event.target.value;
		},
		setPhone( event ) {
			state.phone = event.target.value;
		},
		backToSchedule() {
			if ( countdownTimer ) {
				clearInterval( countdownTimer );
				countdownTimer = null;
			}
			state.step = 'schedule';
			state.bookingId = 0;
			state.holdToken = '';
			state.expiresAt = 0;
			state.secondsLeft = 0;
			state.error = '';
		},
		*createHold() {
			state.error = '';
			if ( ! state.date || ! state.time ) {
				state.error = 'Choose a date and start time.';
				return;
			}

			const hours = Number( state.hours ) || Number( state.minHours );
			const start = `${ state.date } ${ state.time }:00`;
			const end = buildEndDateTime( state.date, state.time, hours );

			state.busy = true;
			try {
				const response = yield fetch( `${ state.restUrl }/holds`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					credentials: 'same-origin',
					body: JSON.stringify( {
						space_id: state.spaceId,
						start,
						end,
						timezone: state.timezone,
					} ),
				} );
				const body = yield readJson( response );
				if ( ! response.ok ) {
					state.error = errorMessage( body );
					return;
				}
				state.bookingId = Number( body.booking_id ) || 0;
				state.holdToken = String( body.hold_token || '' );
				state.expiresAt = Number( body.expires_at ) || 0;
				state.secondsLeft = Math.max(
					0,
					state.expiresAt - Math.floor( Date.now() / 1000 )
				);
				state.step = 'checkout';
				if ( countdownTimer ) {
					clearInterval( countdownTimer );
				}
				countdownTimer = setInterval( () => {
					const left = Math.max(
						0,
						state.expiresAt - Math.floor( Date.now() / 1000 )
					);
					state.secondsLeft = left;
					if ( left <= 0 && countdownTimer ) {
						clearInterval( countdownTimer );
						countdownTimer = null;
						state.error =
							'Your hold expired. Pick a new time to continue.';
						state.step = 'schedule';
						state.bookingId = 0;
						state.holdToken = '';
					}
				}, 1000 );
			} catch ( e ) {
				state.error = 'Network error creating hold.';
			} finally {
				state.busy = false;
			}
		},
		*checkout() {
			state.error = '';
			if ( ! state.firstName || ! state.lastName || ! state.email ) {
				state.error = 'Enter your name and email to confirm.';
				return;
			}
			if ( ! state.bookingId || ! state.holdToken ) {
				state.error = 'Hold missing. Start again from the schedule.';
				state.step = 'schedule';
				return;
			}

			state.busy = true;
			try {
				const response = yield fetch( `${ state.restUrl }/checkout`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					credentials: 'same-origin',
					body: JSON.stringify( {
						booking_id: state.bookingId,
						hold_token: state.holdToken,
						headcount: Number( state.headcount ) || 0,
						package_id: Number( state.packageId ) || 0,
						billing: {
							first_name: state.firstName,
							last_name: state.lastName,
							email: state.email,
							phone: state.phone,
						},
					} ),
				} );
				const body = yield readJson( response );
				if ( ! response.ok ) {
					state.error = errorMessage( body );
					return;
				}
				if ( countdownTimer ) {
					clearInterval( countdownTimer );
					countdownTimer = null;
				}
				const url =
					typeof body.received_url === 'string' && body.received_url
						? body.received_url
						: `/checkout/order-received/${ body.order_id }/`;
				window.location.assign( url );
			} catch ( e ) {
				state.error = 'Network error during checkout.';
			} finally {
				state.busy = false;
			}
		},
	},
	callbacks: {
		init() {
			state.error = '';
			initPickers();
		},
	},
} );
