/**
 * Editor preview for My Bookings.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';

/**
 * @return {JSX.Element} Editor preview.
 */
export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'venuestack-my-bookings-editor',
	} );

	return (
		<div { ...blockProps }>
			<Disabled>
				<ServerSideRender
					block={ metadata.name }
					EmptyResponsePlaceholder={ () => (
						<p>
							{ __(
								'My bookings list appears here for logged-in customers.',
								'venuestack-core'
							) }
						</p>
					) }
				/>
			</Disabled>
		</div>
	);
}
