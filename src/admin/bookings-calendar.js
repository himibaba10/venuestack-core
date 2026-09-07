/**
 * Admin bookings calendar (FullCalendar).
 */
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';
import timeGridPlugin from '@fullcalendar/timegrid';
import './bookings-calendar.css';

const config = window.venuestackBookingsCalendar || {};

/**
 * @return {number} Selected space filter (0 = all).
 */
function getSpaceFilter() {
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
function populateSpaceFilter() {
	const select = document.getElementById( 'venuestack-calendar-space' );
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
 * Fetch FullCalendar events for the visible range.
 *
 * @param {Object}   fetchInfo FullCalendar fetch info.
 * @param {Function} success   Success callback.
 * @param {Function} failure   Failure callback.
 */
function fetchEvents( fetchInfo, success, failure ) {
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

document.addEventListener( 'DOMContentLoaded', () => {
	const mount = document.getElementById( 'venuestack-bookings-calendar' );
	if ( ! mount || ! config.restUrl ) {
		return;
	}

	populateSpaceFilter();

	const calendar = new Calendar( mount, {
		plugins: [ dayGridPlugin, timeGridPlugin, listPlugin ],
		initialView: 'timeGridWeek',
		headerToolbar: {
			left: 'prev,next today',
			center: 'title',
			right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
		},
		height: 'auto',
		expandRows: true,
		nowIndicator: true,
		navLinks: true,
		dayMaxEvents: true,
		timeZone: config.timezone || 'local',
		events: fetchEvents,
		eventDisplay: 'block',
		eventTimeFormat: {
			hour: 'numeric',
			minute: '2-digit',
			meridiem: 'short',
		},
	} );

	calendar.render();

	const select = document.getElementById( 'venuestack-calendar-space' );
	if ( select ) {
		select.addEventListener( 'change', () => {
			calendar.refetchEvents();
		} );
	}
} );
