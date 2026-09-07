/**
 * Spaces directory Interactivity store.
 */
import autoAnimate from '@formkit/auto-animate';
import { getContext, getElement, store } from '@wordpress/interactivity';
import { getPostId } from './dom';
import { setListElement, setOrder, syncGrid } from './sync-grid';

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
			queueMicrotask( () => syncGrid( state ) );
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
			queueMicrotask( () => syncGrid( state ) );
		},
		selectAmenity() {
			const ctx = getContext() || {};
			const slug =
				typeof ctx.filterAmenity === 'string' ? ctx.filterAmenity : '';
			state.amenity = state.amenity === slug ? '' : slug;
			queueMicrotask( () => syncGrid( state ) );
		},
	},
	callbacks: {
		initGrid() {
			const { ref } = getElement();
			if ( ! ref ) {
				return;
			}

			const listEl = ref.querySelector( '.wp-block-post-template' );
			if ( ! listEl ) {
				return;
			}

			setListElement( listEl );

			const nextOrder = [];
			listEl
				.querySelectorAll( ':scope > .wp-block-post' )
				.forEach( ( li ) => {
					const id = getPostId( li );
					if ( id ) {
						nextOrder.push( id );
					}
				} );
			setOrder( nextOrder );

			if (
				! window.matchMedia( '(prefers-reduced-motion: reduce)' )
					.matches
			) {
				autoAnimate( listEl, { duration: 280, easing: 'ease-out' } );
			}

			syncGrid( state );
		},
	},
} );
