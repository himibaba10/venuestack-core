/**
 * Register Package details document sidebar plugin.
 */
import { registerPlugin } from '@wordpress/plugins';
import PackageDetailsPanel from './components/package-details-panel';

registerPlugin( 'venuestack-package-details', {
	render: PackageDetailsPanel,
} );
