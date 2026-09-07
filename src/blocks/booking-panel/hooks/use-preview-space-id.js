/**
 * Resolve a venue_space ID for Booking Panel editor preview.
 */
import { useSelect } from '@wordpress/data';

/**
 * @param {number|undefined} contextPostId Block context postId.
 * @return {number} Space ID for ServerSideRender, or 0.
 */
export function usePreviewSpaceId( contextPostId ) {
	return useSelect(
		( select ) => {
			const fromContext = contextPostId ? Number( contextPostId ) : 0;
			if ( fromContext > 0 ) {
				return fromContext;
			}

			const editor = select( 'core/editor' );
			const currentId = editor?.getCurrentPostId?.();
			if ( currentId ) {
				return Number( currentId );
			}

			const records = select( 'core' ).getEntityRecords(
				'postType',
				'venue_space',
				{ per_page: 1, status: 'publish' }
			);
			return records?.[ 0 ]?.id ? Number( records[ 0 ].id ) : 0;
		},
		[ contextPostId ]
	);
}
