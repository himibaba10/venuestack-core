/**
 * Spaces inventory Data Views hook.
 */
import { useMemo } from '@wordpress/element';
import { createEditAction, createViewPermalinkAction } from '../utils/actions';
import { getSpacesFields } from '../fields/spaces';
import { useInventoryDataViews } from './use-inventory-data-views';

const INITIAL_VIEW = {
	type: 'table',
	perPage: 20,
	page: 1,
	search: '',
	filters: [],
	sort: { field: 'title', direction: 'asc' },
	titleField: 'title',
	fields: [
		'max_capacity',
		'square_footage',
		'hourly_rate',
		'minimum_booking_hours',
		'status',
	],
	layout: {},
};

const DEFAULT_LAYOUTS = {
	table: {},
	grid: {},
};

const QUERY = {
	per_page: 100,
	status: 'publish,draft,private,pending',
	context: 'edit',
	orderby: 'title',
	order: 'asc',
};

/**
 * @return {Object} Props for InventoryDataViews.
 */
export function useSpacesInventory() {
	const fields = useMemo( () => getSpacesFields(), [] );
	const actions = useMemo(
		() => [ createEditAction(), createViewPermalinkAction() ],
		[]
	);

	return useInventoryDataViews( {
		postType: 'venue_space',
		query: QUERY,
		initialView: INITIAL_VIEW,
		fields,
		actions,
		defaultLayouts: DEFAULT_LAYOUTS,
	} );
}
