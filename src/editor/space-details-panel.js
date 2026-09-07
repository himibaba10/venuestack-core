/**
 * Register Space details document sidebar plugin.
 */
import { registerPlugin } from '@wordpress/plugins';
import SpaceDetailsPanel from './space-details/components/space-details-panel';

registerPlugin( 'venuestack-space-details', {
	render: SpaceDetailsPanel,
} );
