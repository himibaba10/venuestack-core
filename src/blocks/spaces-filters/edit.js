/**
 * Editor view for Spaces Filters — ServerSideRender of the PHP markup.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * @return {JSX.Element} Editor preview for the spaces filters block.
 */
export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'venuestack-spaces-filters-editor',
	} );

	return (
		<div { ...blockProps }>
			<Disabled>
				<ServerSideRender
					block={ metadata.name }
					EmptyResponsePlaceholder={ () => (
						<p>
							{ __(
								'Spaces filters will appear here on the front end.',
								'venuestack-core'
							) }
						</p>
					) }
					ErrorResponsePlaceholder={ () => (
						<p>
							{ __(
								'Could not preview spaces filters.',
								'venuestack-core'
							) }
						</p>
					) }
				/>
			</Disabled>
		</div>
	);
}
