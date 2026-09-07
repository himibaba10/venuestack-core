/**
 * Bookings inventory screen.
 */
import InventoryDataViews from './components/inventory-data-views';
import { useBookingsInventory } from './hooks/use-bookings-inventory';

/**
 * @return {JSX.Element} Bookings Data Views screen.
 */
export default function BookingsApp() {
	const inventory = useBookingsInventory();
	return <InventoryDataViews { ...inventory } />;
}
