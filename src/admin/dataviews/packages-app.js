/**
 * Packages inventory screen.
 */
import InventoryDataViews from './components/inventory-data-views';
import { usePackagesInventory } from './hooks/use-packages-inventory';

/**
 * @return {JSX.Element} Packages Data Views screen.
 */
export default function PackagesApp() {
	const inventory = usePackagesInventory();
	return <InventoryDataViews { ...inventory } />;
}
