/**
 * Packages inventory Data Views hook.
 */
import { useMemo } from '@wordpress/element';
import { createEditAction, createViewPermalinkAction } from '../utils/actions';
import { getPackagesFields } from '../fields/packages';
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
		'price_per_head',
		'requires_advance_notice',
		'menu_items_included',
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
export function usePackagesInventory() {
	const fields = useMemo( () => getPackagesFields(), [] );
	const actions = useMemo(
		() => [ createEditAction(), createViewPermalinkAction() ],
		[]
	);

	return useInventoryDataViews( {
		postType: 'event_package',
		query: QUERY,
		initialView: INITIAL_VIEW,
		fields,
		actions,
		defaultLayouts: DEFAULT_LAYOUTS,
	} );
}
