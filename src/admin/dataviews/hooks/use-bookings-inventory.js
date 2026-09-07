/**
 * Bookings inventory Data Views hook.
 */
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getDataViewsConfig } from '../utils/config';
import { createEditAction, createViewOrderAction } from '../utils/actions';
import { getBookingsFields } from '../fields/bookings';
import { useInventoryDataViews } from './use-inventory-data-views';

const INITIAL_VIEW = {
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
};

const DEFAULT_LAYOUTS = {
	table: {},
};

const QUERY = {
	per_page: 100,
	status: 'publish,draft,private,pending',
	context: 'edit',
};

/**
 * @return {Object} Props for InventoryDataViews.
 */
export function useBookingsInventory() {
	const config = getDataViewsConfig();

	const spaceElements = useMemo( () => {
		const list = Array.isArray( config.spaces ) ? config.spaces : [];
		return list.map( ( space ) => ( {
			value: String( space.value ),
			label: space.label,
		} ) );
	}, [ config.spaces ] );

	const spaceLabelById = useMemo( () => {
		const map = {};
		spaceElements.forEach( ( space ) => {
			map[ space.value ] = space.label;
		} );
		return map;
	}, [ spaceElements ] );

	const fields = useMemo(
		() => getBookingsFields( spaceElements, spaceLabelById ),
		[ spaceElements, spaceLabelById ]
	);

	const actions = useMemo(
		() => [
			createEditAction( __( 'Edit booking', 'venuestack-core' ) ),
			createViewOrderAction(),
		],
		[]
	);

	return useInventoryDataViews( {
		postType: 'venue_booking',
		query: QUERY,
		initialView: INITIAL_VIEW,
		fields,
		actions,
		defaultLayouts: DEFAULT_LAYOUTS,
	} );
}
