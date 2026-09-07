/**
 * Bookings inventory field definitions.
 */
import { __ } from '@wordpress/i18n';
import { formatUtcTimestamp } from '../utils/format';
import { getOrderUrl } from '../utils/urls';
import { createTitleField } from './common';

/**
 * @param {Array<{value:string,label:string}>} spaceElements  Filter options.
 * @param {Object<string,string>}              spaceLabelById Space id → name.
 * @return {Array<Object>} Data Views fields.
 */
export function getBookingsFields( spaceElements, spaceLabelById ) {
	return [
		createTitleField( __( 'Booking', 'venuestack-core' ) ),
		{
			id: 'space_id',
			label: __( 'Space', 'venuestack-core' ),
			elements: spaceElements,
			filterBy: { operators: [ 'isAny' ] },
			getValue: ( { item } ) => String( item?.meta?.space_id || '' ),
			render: ( { item } ) => {
				const id = String( item?.meta?.space_id || '' );
				return spaceLabelById[ id ] || ( id ? `#${ id }` : '—' );
			},
		},
		{
			id: 'start_datetime',
			label: __( 'Starts', 'venuestack-core' ),
			type: 'integer',
			getValue: ( { item } ) => Number( item?.meta?.start_datetime ) || 0,
			render: ( { item } ) =>
				formatUtcTimestamp( item?.meta?.start_datetime ),
		},
		{
			id: 'end_datetime',
			label: __( 'Ends', 'venuestack-core' ),
			type: 'integer',
			getValue: ( { item } ) => Number( item?.meta?.end_datetime ) || 0,
			render: ( { item } ) =>
				formatUtcTimestamp( item?.meta?.end_datetime ),
		},
		{
			id: 'status',
			label: __( 'Status', 'venuestack-core' ),
			elements: [
				{
					value: 'confirmed',
					label: __( 'Confirmed', 'venuestack-core' ),
				},
				{
					value: 'hold',
					label: __( 'Hold', 'venuestack-core' ),
				},
				{
					value: 'pending',
					label: __( 'Pending', 'venuestack-core' ),
				},
				{
					value: 'cancelled',
					label: __( 'Cancelled', 'venuestack-core' ),
				},
			],
			filterBy: { operators: [ 'isAny' ] },
			getValue: ( { item } ) => item?.meta?.status || 'pending',
		},
		{
			id: 'wc_order_id',
			label: __( 'Order', 'venuestack-core' ),
			type: 'integer',
			getValue: ( { item } ) => Number( item?.meta?.wc_order_id ) || 0,
			render: ( { item } ) => {
				const orderId = Number( item?.meta?.wc_order_id ) || 0;
				if ( orderId < 1 ) {
					return '—';
				}
				return <a href={ getOrderUrl( orderId ) }>#{ orderId }</a>;
			},
		},
	];
}
