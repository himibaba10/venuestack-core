/**
 * Parse a text-control value into a finite number for post meta.
 *
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
