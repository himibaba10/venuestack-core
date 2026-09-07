/**
 * Meta field definitions for the Package details sidebar.
 */
import { __ } from '@wordpress/i18n';
import { CircleDollarSign, Clock, UtensilsCrossed } from 'lucide-react';

export const PACKAGE_DETAIL_FIELDS = [
	{
		key: 'price_per_head',
		label: __( 'Price per guest', 'venuestack-core' ),
		help: __(
			'Catering / package fee charged per guest.',
			'venuestack-core'
		),
		type: 'number',
		Icon: CircleDollarSign,
		control: 'number',
	},
	{
		key: 'requires_advance_notice',
		label: __( 'Advance notice (days)', 'venuestack-core' ),
		help: __(
			'Minimum days before the event this package can be booked.',
			'venuestack-core'
		),
		type: 'integer',
		Icon: Clock,
		control: 'number',
	},
	{
		key: 'menu_items_included',
		label: __( 'Menu items included', 'venuestack-core' ),
		help: __( 'One menu item per line.', 'venuestack-core' ),
		type: 'string_list',
		Icon: UtensilsCrossed,
		control: 'string_list',
	},
];
