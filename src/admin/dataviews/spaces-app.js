/**
 * Spaces inventory DataViews app.
 */
import { useEntityRecords } from '@wordpress/core-data';
import { DataViews, filterSortAndPaginate } from '@wordpress/dataviews/wp';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { external, pencil } from '@wordpress/icons';

const config = window.venuestackDataViews || {};

/**
 * @param {number} id Post ID.
 * @return {string} Edit URL.
 */
function editUrl( id ) {
	const template =
		typeof config.editPostUrl === 'string'
			? config.editPostUrl
			: 'post.php?post=%d&action=edit';
	return template.replace( '%d', String( id ) );
}

/**
 * @param {number|string|undefined} value Rate.
 * @return {string} Formatted money.
 */
function formatRate( value ) {
	const n = Number( value );
	if ( ! Number.isFinite( n ) || n <= 0 ) {
		return '—';
	}
	return `$${ n.toLocaleString( undefined, {
		minimumFractionDigits: 0,
		maximumFractionDigits: 2,
	} ) }/hr`;
}

const defaultLayouts = {
	table: {},
	grid: {},
};

/**
 * @return {JSX.Element} Spaces DataViews screen.
 */
export default function SpacesApp() {
	const [ view, setView ] = useState( {
		type: 'table',
		perPage: 20,
		page: 1,
		search: '',
		filters: [],
		sort: { field: 'title', direction: 'asc' },
		titleField: 'title',
		fields: [
			'title',
			'max_capacity',
			'square_footage',
			'hourly_rate',
			'minimum_booking_hours',
			'status',
		],
		layout: {},
	} );

	const { records, isResolving } = useEntityRecords(
		'postType',
		'venue_space',
		{
			per_page: 100,
			status: 'publish,draft,private,pending',
			context: 'edit',
			orderby: 'title',
			order: 'asc',
		}
	);

	const fields = useMemo(
		() => [
			{
				id: 'title',
				label: __( 'Space', 'venuestack-core' ),
				enableHiding: false,
				enableSorting: true,
				getValue: ( { item } ) =>
					item?.title?.raw ||
					item?.title?.rendered?.replace( /<[^>]+>/g, '' ) ||
					'',
				render: ( { item } ) => {
					const title =
						item?.title?.raw ||
						item?.title?.rendered?.replace( /<[^>]+>/g, '' ) ||
						`#${ item.id }`;
					return (
						<a href={ editUrl( item.id ) }>
							<strong>{ title }</strong>
						</a>
					);
				},
			},
			{
				id: 'max_capacity',
				label: __( 'Capacity', 'venuestack-core' ),
				type: 'integer',
				getValue: ( { item } ) =>
					Number( item?.meta?.max_capacity ) || 0,
			},
			{
				id: 'square_footage',
				label: __( 'Sq ft', 'venuestack-core' ),
				type: 'integer',
				getValue: ( { item } ) =>
					Number( item?.meta?.square_footage ) || 0,
			},
			{
				id: 'hourly_rate',
				label: __( 'Hourly rate', 'venuestack-core' ),
				type: 'number',
				getValue: ( { item } ) =>
					Number( item?.meta?.hourly_rate ) || 0,
				render: ( { item } ) => formatRate( item?.meta?.hourly_rate ),
			},
			{
				id: 'minimum_booking_hours',
				label: __( 'Min hours', 'venuestack-core' ),
				type: 'integer',
				getValue: ( { item } ) =>
					Number( item?.meta?.minimum_booking_hours ) || 0,
			},
			{
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
			},
		],
		[]
	);

	const { data, paginationInfo } = useMemo( () => {
		return filterSortAndPaginate( records || [], view, fields );
	}, [ records, view, fields ] );

	const actions = useMemo(
		() => [
			{
				id: 'edit',
				label: __( 'Edit', 'venuestack-core' ),
				icon: pencil,
				isPrimary: true,
				callback: ( items ) => {
					const item = items?.[ 0 ];
					if ( item?.id ) {
						window.location.assign( editUrl( item.id ) );
					}
				},
			},
			{
				id: 'view',
				label: __( 'View', 'venuestack-core' ),
				icon: external,
				callback: ( items ) => {
					const item = items?.[ 0 ];
					const url = item?.link;
					if ( url ) {
						window.open( url, '_blank', 'noopener,noreferrer' );
					}
				},
			},
		],
		[]
	);

	return (
		<DataViews
			data={ data }
			fields={ fields }
			view={ view }
			onChangeView={ setView }
			actions={ actions }
			paginationInfo={ paginationInfo }
			defaultLayouts={ defaultLayouts }
			isLoading={ isResolving }
			getItemId={ ( item ) => String( item.id ) }
		/>
	);
}
