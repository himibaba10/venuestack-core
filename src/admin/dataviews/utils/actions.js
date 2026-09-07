/**
 * Shared Data Views action factories.
 */
import { __ } from '@wordpress/i18n';
import { external, pencil } from '@wordpress/icons';
import { getEditUrl, getOrderUrl } from './urls';

/**
 * @param {string} label Action label.
 * @return {Object} Edit post action.
 */
export function createEditAction( label = __( 'Edit', 'venuestack-core' ) ) {
	return {
		id: 'edit',
		label,
		icon: pencil,
		isPrimary: true,
		callback: ( items ) => {
			const item = items?.[ 0 ];
			if ( item?.id ) {
				window.location.assign( getEditUrl( item.id ) );
			}
		},
	};
}

/**
 * @return {Object} Open public permalink action.
 */
export function createViewPermalinkAction() {
	return {
		id: 'view',
		label: __( 'View', 'venuestack-core' ),
		icon: external,
		isEligible: ( item ) => Boolean( item?.link ),
		callback: ( items ) => {
			const url = items?.[ 0 ]?.link;
			if ( url ) {
				window.open( url, '_blank', 'noopener,noreferrer' );
			}
		},
	};
}

/**
 * @return {Object} Open WooCommerce order action.
 */
export function createViewOrderAction() {
	return {
		id: 'order',
		label: __( 'View order', 'venuestack-core' ),
		icon: external,
		isEligible: ( item ) => Number( item?.meta?.wc_order_id ) > 0,
		callback: ( items ) => {
			const orderId = Number( items?.[ 0 ]?.meta?.wc_order_id );
			if ( orderId > 0 ) {
				window.location.assign( getOrderUrl( orderId ) );
			}
		},
	};
}
