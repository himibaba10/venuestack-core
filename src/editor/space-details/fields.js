/**
 * Meta field definitions for the Space details sidebar.
 */
import { __ } from '@wordpress/i18n';
import { CircleDollarSign, Clock, Ruler, Users } from 'lucide-react';

export const SPACE_DETAIL_FIELDS = [
	{
		key: 'max_capacity',
		label: __( 'Max capacity', 'venuestack-core' ),
		help: __( 'Guest capacity for this space.', 'venuestack-core' ),
		type: 'integer',
		Icon: Users,
	},
	{
		key: 'square_footage',
		label: __( 'Square footage', 'venuestack-core' ),
		help: __( 'Interior size in square feet.', 'venuestack-core' ),
		type: 'integer',
		Icon: Ruler,
	},
	{
		key: 'hourly_rate',
		label: __( 'Hourly rate', 'venuestack-core' ),
		help: __( 'Base rate charged per hour.', 'venuestack-core' ),
		type: 'number',
		Icon: CircleDollarSign,
	},
	{
		key: 'minimum_booking_hours',
		label: __( 'Minimum booking hours', 'venuestack-core' ),
		help: __( 'Shortest bookable duration.', 'venuestack-core' ),
		type: 'integer',
		Icon: Clock,
	},
];
