/**
 * Shared field factories for inventory Data Views.
 */
import { __ } from '@wordpress/i18n';
import { getEditUrl } from '../utils/urls';
import { getRecordTitle, getRecordTitleOrId } from '../utils/records';

/**
 * @param {string} label Title column label.
 * @return {Object} Title field definition.
 */
export function createTitleField( label ) {
	return {
		id: 'title',
		label,
		enableHiding: false,
		enableSorting: true,
		getValue: ( { item } ) => getRecordTitle( item ),
		render: ( { item } ) => (
			<a href={ getEditUrl( item.id ) }>
				<strong>{ getRecordTitleOrId( item ) }</strong>
			</a>
		),
	};
}

/**
 * @return {Object} WP post status field.
 */
export function createPostStatusField() {
	return {
		id: 'status',
		label: __( 'Status', 'venuestack-core' ),
		elements: [
			{
				value: 'publish',
				label: __( 'Published', 'venuestack-core' ),
			},
			{
				value: 'draft',
				label: __( 'Draft', 'venuestack-core' ),
			},
			{
				value: 'pending',
				label: __( 'Pending', 'venuestack-core' ),
			},
			{
				value: 'private',
				label: __( 'Private', 'venuestack-core' ),
			},
		],
		filterBy: { operators: [ 'isAny' ] },
		getValue: ( { item } ) => item?.status || '',
	};
}
