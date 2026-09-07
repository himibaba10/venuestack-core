/**
 * Post meta helpers for VenueStack document panels.
 */
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { parseMetaNumber } from '../utils/meta';

/**
 * @param {string} expectedPostType CPT that owns this panel.
 * @return {{isMatch:boolean, meta:Object, updateField:Function}} Panel state.
 */
export function usePostTypeMeta( expectedPostType ) {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const updateField = useCallback(
		( key, type, value ) => {
			let next = value;
			if ( type === 'integer' || type === 'number' ) {
				next = parseMetaNumber( value, type );
			}

			setMeta( {
				...meta,
				[ key ]: next,
			} );
		},
		[ meta, setMeta ]
	);

	return {
		isMatch: postType === expectedPostType,
		meta: meta || {},
		updateField,
	};
}
