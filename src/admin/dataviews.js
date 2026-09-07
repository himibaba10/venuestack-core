/**
 * Entry: VenueStack admin Data Views (Spaces / Bookings / Packages).
 *
 * Filename must not be index.js — wp-scripts would collide with src/index.js.
 */
import { createRoot } from '@wordpress/element';
import BookingsApp from './dataviews/bookings-app';
import PackagesApp from './dataviews/packages-app';
import SpacesApp from './dataviews/spaces-app';
import './dataviews/style.css';

const rootEl = document.getElementById( 'venuestack-dataviews-root' );

if ( rootEl ) {
	const screen =
		window.venuestackDataViews?.screen ||
		rootEl.getAttribute( 'data-screen' ) ||
		'spaces';

	let app = <SpacesApp />;
	if ( screen === 'bookings' ) {
		app = <BookingsApp />;
	} else if ( screen === 'packages' ) {
		app = <PackagesApp />;
	}

	createRoot( rootEl ).render( app );
}
