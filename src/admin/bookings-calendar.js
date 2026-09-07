/**
 * Admin bookings calendar entry.
 */
import { createBookingsCalendar } from './bookings-calendar/create-calendar';
import './bookings-calendar.css';

document.addEventListener( 'DOMContentLoaded', () => {
	createBookingsCalendar(
		document.getElementById( 'venuestack-bookings-calendar' )
	);
} );
