/**
 * REST helpers for the booking panel.
 */

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
