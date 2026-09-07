/**
 * Editor preview for Booking Panel.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import { usePreviewSpaceId } from './hooks/use-preview-space-id';

/**
 * @param {Object} props
 * @param {Object} [props.context] Block context (postId from singular template).
 * @return {JSX.Element} Editor preview.
 */
export default function Edit( { context } ) {
	const blockProps = useBlockProps( {
		className: 'venuestack-booking-panel-editor',
	} );
	const postId = usePreviewSpaceId( context?.postId );
	const urlQueryArgs = postId > 0 ? { post_id: postId } : {};

	return (
		<div { ...blockProps }>
			<Disabled>
				<ServerSideRender
					block={ metadata.name }
					httpMethod="GET"
					urlQueryArgs={ urlQueryArgs }
					EmptyResponsePlaceholder={ () => (
						<p>
							{ __(
								'Open a venue space (or preview this template with a space) to see the booking panel.',
								'venuestack-core'
							) }
						</p>
					) }
					ErrorResponsePlaceholder={ () => (
						<p>
							{ __(
								'Could not preview booking panel.',
								'venuestack-core'
							) }
						</p>
					) }
				/>
			</Disabled>
		</div>
	);
}
