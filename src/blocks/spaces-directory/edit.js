import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';

export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'venuestack-spaces-directory-editor',
	} );

	return (
		<div { ...blockProps }>
			<Disabled>
				<ServerSideRender
					block={ metadata.name }
					EmptyResponsePlaceholder={ () => (
						<p>
							{ __(
								'Spaces directory will appear here on the front end.',
								'venuestack-core'
							) }
						</p>
					) }
					ErrorResponsePlaceholder={ () => (
						<p>
							{ __(
								'Could not preview spaces directory.',
								'venuestack-core'
							) }
						</p>
					) }
				/>
			</Disabled>
		</div>
	);
}
