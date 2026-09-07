/**
 * Thin Data Views table wrapper for inventory screens.
 */
import { DataViews } from '@wordpress/dataviews/wp';

/**
 * @param {Object} props Inventory Data Views props from a domain hook.
 * @return {JSX.Element} DataViews UI.
 */
export default function InventoryDataViews( props ) {
	return <DataViews { ...props } />;
}
