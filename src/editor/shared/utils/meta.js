/**
 * Shared meta helpers for document setting panels.
 */

/**
 * @param {string}             value Raw input.
 * @param {'integer'|'number'} type  Field numeric type.
 * @return {number} Parsed value or 0.
 */
export function parseMetaNumber( value, type ) {
	const parsed =
		type === 'number' ? parseFloat( value ) : parseInt( value, 10 );
	return Number.isFinite( parsed ) ? parsed : 0;
}

/**
 * @param {unknown} value Meta value.
 * @return {string} Safe string for a controlled number input.
 */
export function metaValueToInput( value ) {
	if ( value === undefined || value === null ) {
		return '';
	}
	return String( value );
}

/**
 * @param {unknown} value Array meta.
 * @return {string[]} Clean string list.
 */
export function normalizeStringList( value ) {
	if ( ! Array.isArray( value ) ) {
		return [];
	}
	return value
		.map( ( item ) => String( item || '' ).trim() )
		.filter( Boolean );
}

/**
 * @param {string} value Textarea value (one item per line).
 * @return {string[]} Parsed list.
 */
export function parseStringListInput( value ) {
	return String( value || '' )
		.split( /\r?\n/ )
		.map( ( line ) => line.trim() )
		.filter( Boolean );
}

/**
 * @param {unknown} value Array meta.
 * @return {string} Textarea value.
 */
export function stringListToInput( value ) {
	return normalizeStringList( value ).join( '\n' );
}
