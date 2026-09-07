/**
 * Shared record helpers for Data Views fields.
 */

/**
 * @param {Object} item REST entity record.
 * @return {string} Plain title text.
 */
export function getRecordTitle( item ) {
	return (
		item?.title?.raw ||
		item?.title?.rendered?.replace( /<[^>]+>/g, '' ) ||
		''
	);
}

/**
 * @param {Object} item REST entity record.
 * @return {string} Title or fallback id label.
 */
export function getRecordTitleOrId( item ) {
	return getRecordTitle( item ) || `#${ item?.id }`;
}

/**
 * @param {unknown} items Menu items meta.
 * @return {string[]} Clean string list.
 */
export function normalizeMenuItems( items ) {
	if ( ! Array.isArray( items ) ) {
		return [];
	}

	return items
		.map( ( item ) => String( item || '' ).trim() )
		.filter( Boolean );
}
