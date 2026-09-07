/**
 * Shared inventory Data Views state: records → filter/sort/paginate.
 */
import { useEntityRecords } from '@wordpress/core-data';
import { filterSortAndPaginate } from '@wordpress/dataviews/wp';
import { useMemo, useState } from '@wordpress/element';

/**
 * @param {Object}        options                Hook options.
 * @param {string}        options.postType       CPT name.
 * @param {Object}        options.query          Entity records query.
 * @param {Object}        options.initialView    Initial Data Views view state.
 * @param {Array<Object>} options.fields         Field definitions.
 * @param {Array<Object>} options.actions        Row actions.
 * @param {Object}        options.defaultLayouts Layout presets.
 * @return {Object} Props for InventoryDataViews.
 */
export function useInventoryDataViews( {
	postType,
	query,
	initialView,
	fields,
	actions,
	defaultLayouts,
} ) {
	const [ view, setView ] = useState( initialView );

	const { records, isResolving } = useEntityRecords(
		'postType',
		postType,
		query
	);

	const { data, paginationInfo } = useMemo( () => {
		return filterSortAndPaginate( records || [], view, fields );
	}, [ records, view, fields ] );

	return {
		data,
		fields,
		view,
		onChangeView: setView,
		actions,
		paginationInfo,
		defaultLayouts,
		isLoading: isResolving,
		getItemId: ( item ) => String( item.id ),
	};
}
