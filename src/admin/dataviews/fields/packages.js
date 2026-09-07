/**
 * Packages inventory field definitions.
 */
import { __ } from '@wordpress/i18n';
import { formatPerGuest } from '../utils/format';
import { normalizeMenuItems } from '../utils/records';
import { createPostStatusField, createTitleField } from './common';

/**
 * @return {Array<Object>} Data Views fields.
 */
export function getPackagesFields() {
	return [
		createTitleField( __( 'Package', 'venuestack-core' ) ),
		{
			id: 'price_per_head',
			label: __( 'Per guest', 'venuestack-core' ),
			type: 'number',
			getValue: ( { item } ) => Number( item?.meta?.price_per_head ) || 0,
			render: ( { item } ) =>
				formatPerGuest( item?.meta?.price_per_head ),
		},
		{
			id: 'requires_advance_notice',
			label: __( 'Notice (days)', 'venuestack-core' ),
			type: 'integer',
			getValue: ( { item } ) =>
				Number( item?.meta?.requires_advance_notice ) || 0,
			render: ( { item } ) => {
				const days = Number( item?.meta?.requires_advance_notice ) || 0;
				return days > 0 ? String( days ) : '—';
			},
		},
		{
			id: 'menu_items_included',
			label: __( 'Menu items', 'venuestack-core' ),
			enableSorting: false,
			getValue: ( { item } ) =>
				normalizeMenuItems( item?.meta?.menu_items_included ).join(
					', '
				),
			render: ( { item } ) => {
				const list = normalizeMenuItems(
					item?.meta?.menu_items_included
				);
				if ( ! list.length ) {
					return '—';
				}
				return (
					<span title={ list.join( ', ' ) }>
						{ list.length === 1
							? list[ 0 ]
							: `${ list.length } items` }
					</span>
				);
			},
		},
		createPostStatusField(),
	];
}
