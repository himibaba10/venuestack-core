/**
 * Create and mount the VenueStack bookings FullCalendar.
 */
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';
import timeGridPlugin from '@fullcalendar/timegrid';
import { getCalendarConfig } from './config';
import { fetchEvents } from './fetch-events';
import { bindSpaceFilterChange, populateSpaceFilter } from './space-filter';
import { bindStatusFilterChange } from './status-filter';

/**
 * @param {HTMLElement} mount Calendar mount node.
 * @return {Calendar|null} Calendar instance.
 */
export function createBookingsCalendar( mount ) {
	const config = getCalendarConfig();
	if ( ! mount || ! config.restUrl ) {
		return null;
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

	const refetch = () => {
		calendar.refetchEvents();
	};
	bindSpaceFilterChange( refetch );
	bindStatusFilterChange( refetch );

	return calendar;
}
