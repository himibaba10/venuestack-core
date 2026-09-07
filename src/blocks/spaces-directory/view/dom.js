/**
 * DOM helpers for spaces directory cards.
 */

/**
 * @param {HTMLElement} li Post template list item.
 * @return {number} Post ID from class, or 0.
 */
export function getPostId( li ) {
	const match = ( li.className || '' ).match( /\bpost-(\d+)\b/ );
	return match ? Number( match[ 1 ] ) : 0;
}

/**
 * @param {HTMLElement} li Post template list item.
 * @return {{types:string[],amenities:string[]}} Card filter context.
 */
export function getCardContext( li ) {
	const raw = li.getAttribute( 'data-wp-context' );
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
