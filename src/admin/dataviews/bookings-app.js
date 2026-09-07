/**
 * Bookings inventory DataViews app.
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
 * @param {number} orderId WooCommerce order ID.
 * @return {string} Order edit URL.
 */
function orderUrl( orderId ) {
	const base =
		typeof config.orderEditUrl === 'string'
			? config.orderEditUrl
			: 'admin.php?page=wc-orders&action=edit&id=';
	return `${ base }${ orderId }`;
}

/**
 * @param {number} utc Unix timestamp (UTC seconds).
 * @return {string} Localized datetime label.
 */
function formatUtc( utc ) {
	const ts = Number( utc );
	if ( ! Number.isFinite( ts ) || ts < 1 ) {
		return '—';
	}
	try {
		return new Intl.DateTimeFormat( undefined, {
			dateStyle: 'medium',
			timeStyle: 'short',
			timeZone: config.timezone || undefined,
		} ).format( new Date( ts * 1000 ) );
	} catch ( e ) {
		return new Date( ts * 1000 ).toLocaleString();
	}
}

const defaultLayouts = {
	table: {},
};

/**
 * @return {JSX.Element} Bookings DataViews screen.
 */
export default function BookingsApp() {
	const spaceElements = useMemo( () => {
		const list = Array.isArray( config.spaces ) ? config.spaces : [];
		return list.map( ( space ) => ( {
			value: String( space.value ),
			label: space.label,
		} ) );
	}, [] );

	const spaceLabelById = useMemo( () => {
		const map = {};
		spaceElements.forEach( ( space ) => {
			map[ space.value ] = space.label;
		} );
		return map;
	}, [ spaceElements ] );

	const [ view, setView ] = useState( {
		type: 'table',
		perPage: 20,
		page: 1,
		search: '',
		filters: [],
		sort: { field: 'start_datetime', direction: 'desc' },
		titleField: 'title',
		fields: [
			'space_id',
			'start_datetime',
			'end_datetime',
			'status',
			'wc_order_id',
		],
		layout: {},
	} );

	const { records, isResolving } = useEntityRecords(
		'postType',
		'venue_booking',
		{
			per_page: 100,
			status: 'publish,draft,private,pending',
			context: 'edit',
		}
	);

	const fields = useMemo(
		() => [
			{
				id: 'title',
				label: __( 'Booking', 'venuestack-core' ),
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
				getValue: ( { item } ) =>
					Number( item?.meta?.start_datetime ) || 0,
				render: ( { item } ) => formatUtc( item?.meta?.start_datetime ),
			},
			{
				id: 'end_datetime',
				label: __( 'Ends', 'venuestack-core' ),
				type: 'integer',
				getValue: ( { item } ) =>
					Number( item?.meta?.end_datetime ) || 0,
				render: ( { item } ) => formatUtc( item?.meta?.end_datetime ),
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
				getValue: ( { item } ) =>
					Number( item?.meta?.wc_order_id ) || 0,
				render: ( { item } ) => {
					const orderId = Number( item?.meta?.wc_order_id ) || 0;
					if ( orderId < 1 ) {
						return '—';
					}
					return <a href={ orderUrl( orderId ) }>#{ orderId }</a>;
				},
			},
		],
		[ spaceElements, spaceLabelById ]
	);

	const { data, paginationInfo } = useMemo( () => {
		return filterSortAndPaginate( records || [], view, fields );
	}, [ records, view, fields ] );

	const actions = useMemo(
		() => [
			{
				id: 'edit',
				label: __( 'Edit booking', 'venuestack-core' ),
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
				id: 'order',
				label: __( 'View order', 'venuestack-core' ),
				icon: external,
				isEligible: ( item ) => Number( item?.meta?.wc_order_id ) > 0,
				callback: ( items ) => {
					const orderId = Number( items?.[ 0 ]?.meta?.wc_order_id );
					if ( orderId > 0 ) {
						window.location.assign( orderUrl( orderId ) );
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
