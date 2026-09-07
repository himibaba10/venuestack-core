/**
 * Space details document panel state.
 */
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { parseMetaNumber } from '../utils/meta';

/**
 * @return {{isSpace:boolean, meta:Object, updateField:Function}} Panel state.
 */
export function useSpaceDetails() {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const updateField = useCallback(
		( key, type, value ) => {
			setMeta( {
				...meta,
				[ key ]: parseMetaNumber( value, type ),
			} );
		},
		[ meta, setMeta ]
	);

	return {
		isSpace: postType === 'venue_space',
		meta: meta || {},
		updateField,
	};
}
