/**
 * Spaces inventory field definitions.
 */
import { __ } from '@wordpress/i18n';
import { formatHourlyRate } from '../utils/format';
import { createPostStatusField, createTitleField } from './common';

/**
 * @return {Array<Object>} Data Views fields.
 */
export function getSpacesFields() {
	return [
		createTitleField( __( 'Space', 'venuestack-core' ) ),
		{
			id: 'max_capacity',
			label: __( 'Capacity', 'venuestack-core' ),
			type: 'integer',
			getValue: ( { item } ) => Number( item?.meta?.max_capacity ) || 0,
		},
		{
			id: 'square_footage',
			label: __( 'Sq ft', 'venuestack-core' ),
			type: 'integer',
			getValue: ( { item } ) => Number( item?.meta?.square_footage ) || 0,
		},
		{
			id: 'hourly_rate',
			label: __( 'Hourly rate', 'venuestack-core' ),
			type: 'number',
			getValue: ( { item } ) => Number( item?.meta?.hourly_rate ) || 0,
			render: ( { item } ) => formatHourlyRate( item?.meta?.hourly_rate ),
		},
		{
			id: 'minimum_booking_hours',
			label: __( 'Min hours', 'venuestack-core' ),
			type: 'integer',
			getValue: ( { item } ) =>
				Number( item?.meta?.minimum_booking_hours ) || 0,
		},
		createPostStatusField(),
	];
}
