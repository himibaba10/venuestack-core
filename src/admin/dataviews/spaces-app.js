/**
 * Spaces inventory screen.
 */
import InventoryDataViews from './components/inventory-data-views';
import { useSpacesInventory } from './hooks/use-spaces-inventory';

/**
 * @return {JSX.Element} Spaces Data Views screen.
 */
export default function SpacesApp() {
	const inventory = useSpacesInventory();
	return <InventoryDataViews { ...inventory } />;
}
