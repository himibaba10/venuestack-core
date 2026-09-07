/**
 * Display helpers for the booking panel.
 */

/**
 * @param {number} amount Currency amount.
 * @return {string} Formatted money label.
 */
export function formatMoney( amount ) {
	return `$${ Number( amount ).toLocaleString( undefined, {
		minimumFractionDigits: 0,
		maximumFractionDigits: 2,
	} ) }`;
}

/**
 * @param {Object} body REST error body.
 * @return {string} Human-readable error message.
 */
export function errorMessage( body ) {
	if ( body && typeof body.message === 'string' && body.message ) {
		return body.message;
	}
	return 'Something went wrong. Please try again.';
}
