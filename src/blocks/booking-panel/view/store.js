/**
 * Booking panel Interactivity store (hold → checkout).
 */
import { store } from '@wordpress/interactivity';
import { readJson } from './api';
import { isStartAvailable, normalizeBusyRanges } from './availability';
import { buildEndDateTime } from './datetime';
import { errorMessage, formatMoney } from './format';
import { initPickers, refreshAvailability } from './pickers';

/** @type {ReturnType<typeof setInterval>|null} */
let countdownTimer = null;

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
		get isLoggedOut() {
			return ! state.isLoggedIn;
		},
		get hideLoginGate() {
			return Boolean( state.isLoggedIn );
		},
		get isFormDisabled() {
			return ! state.isLoggedIn;
		},
		get isHoldDisabled() {
			return ! state.isLoggedIn || Boolean( state.busy );
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
			if ( ! state.isLoggedIn ) {
				return;
			}
			state.hours = Number( event.target.value ) || state.minHours;
			state.error = '';
			refreshAvailability( state );
		},
		setHeadcount( event ) {
			if ( ! state.isLoggedIn ) {
				return;
			}
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
			if ( ! state.isLoggedIn ) {
				return;
			}
			state.packageId = Number( event.target.value ) || 0;
			state.error = '';
		},
		setFirstName( event ) {
			if ( ! state.isLoggedIn ) {
				return;
			}
			state.firstName = event.target.value;
		},
		setLastName( event ) {
			if ( ! state.isLoggedIn ) {
				return;
			}
			state.lastName = event.target.value;
		},
		setEmail( event ) {
			if ( ! state.isLoggedIn ) {
				return;
			}
			state.email = event.target.value;
		},
		setPhone( event ) {
			if ( ! state.isLoggedIn ) {
				return;
			}
			state.phone = event.target.value;
		},
		backToSchedule() {
			if ( ! state.isLoggedIn ) {
				return;
			}
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
			if ( ! state.isLoggedIn ) {
				state.error = 'Log in to hold this space.';
				return;
			}
			if ( ! state.date || ! state.time ) {
				state.error = 'Choose a date and start time.';
				return;
			}

			const hours = Number( state.hours ) || Number( state.minHours );
			const busy = normalizeBusyRanges( state.busyRanges );
			if ( ! isStartAvailable( state.date, state.time, hours, busy ) ) {
				state.error =
					'That time overlaps an existing booking. Pick another slot.';
				refreshAvailability( state );
				return;
			}

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
			if ( ! state.isLoggedIn ) {
				state.error = 'Log in to confirm this booking.';
				return;
			}
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
			if ( ! state.isLoggedIn ) {
				return;
			}
			initPickers( state );
		},
	},
} );
