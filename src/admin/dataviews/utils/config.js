/**
 * Localized admin config from `wp_localize_script`.
 *
 * @return {Object} VenueStack Data Views config.
 */
export function getDataViewsConfig() {
	return window.venuestackDataViews || {};
}
