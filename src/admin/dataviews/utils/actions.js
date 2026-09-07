/**
 * Shared Data Views action factories.
 */
import { __ } from '@wordpress/i18n';
import { cancelCircleFilled, external, pencil } from '@wordpress/icons';
import { getCancelBookingUrl, getEditUrl, getOrderUrl } from './urls';

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

/**
 * @return {Object} Cancel booking (+ linked Woo order) action.
 */
export function createCancelBookingAction() {
	return {
		id: 'cancel',
		label: __( 'Cancel booking', 'venuestack-core' ),
		icon: cancelCircleFilled,
		isDestructive: true,
		isEligible: ( item ) => {
			const status = item?.meta?.status || 'pending';
			return [ 'hold', 'confirmed', 'pending' ].includes( status );
		},
		callback: ( items ) => {
			const item = items?.[ 0 ];
			if ( ! item?.id ) {
				return;
			}
			// Admin destructive action — browser confirm is intentional.
			// eslint-disable-next-line no-alert
			const ok = window.confirm(
				__(
					'Cancel this booking and the linked WooCommerce order? The customer will be emailed.',
					'venuestack-core'
				)
			);
			if ( ! ok ) {
				return;
			}
			window.location.assign( getCancelBookingUrl( item.id ) );
		},
	};
}
