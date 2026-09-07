/**
 * Filter + reorder the spaces directory grid.
 */
import { getCardContext, getPostId } from './dom';

/** @type {HTMLElement|null} */
let listEl = null;

/** @type {Map<number, HTMLElement>} */
const parked = new Map();

/** @type {number[]} */
let order = [];

/**
 * @param {Object}      state Directory interactivity state.
 * @param {HTMLElement} li    Card list item.
 * @return {boolean} Whether the card matches active filters.
 */
function liMatchesFilters( state, li ) {
	const { types, amenities } = getCardContext( li );

	if ( state.type && ! types.includes( state.type ) ) {
		return false;
	}
	if ( state.amenity && ! amenities.includes( state.amenity ) ) {
		return false;
	}
	return true;
}

/**
 * @param {HTMLElement|null} el Grid list element.
 */
export function setListElement( el ) {
	listEl = el;
}

/**
 * @param {number[]} nextOrder Initial card order by post ID.
 */
export function setOrder( nextOrder ) {
	order = nextOrder;
}

/**
 * @param {Object} state Directory interactivity state.
 */
export function syncGrid( state ) {
	if ( ! listEl ) {
		listEl = document.querySelector(
			'.venuestack-directory-query .wp-block-post-template'
		);
	}
	if ( ! listEl ) {
		return;
	}

	/** @type {Map<number, HTMLElement>} */
	const byId = new Map( parked );
	listEl.querySelectorAll( ':scope > .wp-block-post' ).forEach( ( li ) => {
		const id = getPostId( li );
		if ( id ) {
			byId.set( id, li );
		}
	} );

	if ( ! order.length ) {
		order = [ ...byId.keys() ];
	}

	order.forEach( ( id ) => {
		const li = byId.get( id );
		if ( ! li ) {
			return;
		}

		if ( liMatchesFilters( state, li ) ) {
			parked.delete( id );
			if ( li.parentElement !== listEl ) {
				listEl.appendChild( li );
			}
		} else if ( li.parentElement === listEl ) {
			parked.set( id, li );
			li.remove();
		} else {
			parked.set( id, li );
		}
	} );

	order.forEach( ( id ) => {
		const li = byId.get( id );
		if ( li && li.parentElement === listEl ) {
			listEl.appendChild( li );
		}
	} );
}
