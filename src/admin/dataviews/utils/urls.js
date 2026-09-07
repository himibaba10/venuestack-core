/**
 * Admin URL helpers for inventory screens.
 */
import { getDataViewsConfig } from './config';

/**
 * @param {number} id Post ID.
 * @return {string} Edit URL.
 */
export function getEditUrl( id ) {
	const config = getDataViewsConfig();
	const template =
		typeof config.editPostUrl === 'string'
			? config.editPostUrl
			: 'post.php?post=%d&action=edit';
	return template.replace( '%d', String( id ) );
}

/**
 * @param {number} orderId WooCommerce order ID.
 * @return {string} Order edit URL.
 */
export function getOrderUrl( orderId ) {
	const config = getDataViewsConfig();
	const base =
		typeof config.orderEditUrl === 'string'
			? config.orderEditUrl
			: 'admin.php?page=wc-orders&action=edit&id=';
	return `${ base }${ orderId }`;
}
