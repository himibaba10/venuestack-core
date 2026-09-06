/**
 * Spaces directory Interactivity store (filter chips + animated grid).
 *
 * Chip active state uses getContext() (not getElement()) so each button
 * evaluates against its own filter slug.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import autoAnimate from '@formkit/auto-animate';

/** @type {HTMLElement|null} */
let listEl = null;

/** @type {Map<number, HTMLElement>} */
const parked = new Map();

/** @type {number[]} */
let order = [];

/**
 * @param {HTMLElement} li
 * @return {number} Post ID from the list item class, or 0.
 */
function getPostId( li ) {
	const match = ( li.className || '' ).match( /\bpost-(\d+)\b/ );
	return match ? Number( match[ 1 ] ) : 0;
}

/**
 * @param {HTMLElement} li
 * @return {{ types: string[], amenities: string[] }} Filter taxonomies for the card.
 */
function getCardContext( li ) {
	const card = li.querySelector( '.venuestack-home-space-card' );
	if ( ! card ) {
		return { types: [], amenities: [] };
	}

	const raw = card.getAttribute( 'data-wp-context' );
	if ( ! raw ) {
		return { types: [], amenities: [] };
	}

	try {
		const parsed = JSON.parse( raw );
		return {
			types: Array.isArray( parsed.types ) ? parsed.types : [],
			amenities: Array.isArray( parsed.amenities )
				? parsed.amenities
				: [],
		};
	} catch ( e ) {
		return { types: [], amenities: [] };
	}
}

/**
 * @param {HTMLElement} li
 * @return {boolean} Whether the list item matches the current filters.
 */
function liMatchesFilters( li ) {
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
 * Park non-matching posts and restore matches so auto-animate can run.
 */
function syncGrid() {
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

		if ( liMatchesFilters( li ) ) {
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

const { state } = store( 'venuestack/spaces-directory', {
	state: {
		get hasFilters() {
			return Boolean( state.type || state.amenity );
		},
		get hideClear() {
			return ! state.type && ! state.amenity;
		},
		get visible() {
			const cards = Array.isArray( state.cards ) ? state.cards : [];
			return cards.filter( ( card ) => {
				const types = card.types || [];
				const amenities = card.amenities || [];
				if ( state.type && ! types.includes( state.type ) ) {
					return false;
				}
				if ( state.amenity && ! amenities.includes( state.amenity ) ) {
					return false;
				}
				return true;
			} ).length;
		},
		get isEmpty() {
			return state.visible === 0;
		},
		get hideEmpty() {
			return state.visible > 0;
		},
		get isAllTypesActive() {
			return state.type === '';
		},
		get isTypeActive() {
			const ctx = getContext() || {};
			const slug = ctx.filterType;
			if ( typeof slug !== 'string' || slug === '' ) {
				return false;
			}
			return state.type === slug;
		},
		get isAmenityActive() {
			const ctx = getContext() || {};
			const slug = ctx.filterAmenity;
			if ( typeof slug !== 'string' || slug === '' ) {
				return false;
			}
			return state.amenity === slug;
		},
	},
	actions: {
		clearFilters() {
			state.type = '';
			state.amenity = '';
			queueMicrotask( syncGrid );
		},
		selectType() {
			const ctx = getContext() || {};
			const slug =
				typeof ctx.filterType === 'string' ? ctx.filterType : '';
			if ( slug === '' ) {
				state.type = '';
			} else {
				state.type = state.type === slug ? '' : slug;
			}
			queueMicrotask( syncGrid );
		},
		selectAmenity() {
			const ctx = getContext() || {};
			const slug =
				typeof ctx.filterAmenity === 'string' ? ctx.filterAmenity : '';
			state.amenity = state.amenity === slug ? '' : slug;
			queueMicrotask( syncGrid );
		},
	},
	callbacks: {
		initGrid() {
			const { ref } = getElement();
			if ( ! ref ) {
				return;
			}

			listEl = ref.querySelector( '.wp-block-post-template' );
			if ( ! listEl ) {
				return;
			}

			order = [];
			listEl
				.querySelectorAll( ':scope > .wp-block-post' )
				.forEach( ( li ) => {
					const id = getPostId( li );
					if ( id ) {
						order.push( id );
					}
				} );

			if (
				! window.matchMedia( '(prefers-reduced-motion: reduce)' )
					.matches
			) {
				autoAnimate( listEl, { duration: 280, easing: 'ease-out' } );
			}

			syncGrid();
		},
	},
} );
