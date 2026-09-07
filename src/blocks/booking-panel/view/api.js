/**
 * REST helpers for the booking panel.
 */

/**
 * @param {Object} state Booking panel interactivity state.
 * @return {Record<string, string>} Headers for authenticated REST calls.
 */
export function restHeaders( state ) {
	const headers = {
		'Content-Type': 'application/json',
		Accept: 'application/json',
	};
	if ( state.restNonce ) {
		headers[ 'X-WP-Nonce' ] = String( state.restNonce );
	}
	return headers;
}

/**
 * @param {Response} response Fetch response.
 * @return {Promise<Object>} Parsed JSON body or empty object.
 */
export async function readJson( response ) {
	try {
		return await response.json();
	} catch ( e ) {
		return {};
	}
}
