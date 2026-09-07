/**
 * Space details document setting panel UI.
 */
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import MetaNumberField from '../../shared/components/meta-number-field';
import { usePostTypeMeta } from '../../shared/hooks/use-post-type-meta';
import { SPACE_DETAIL_FIELDS } from '../fields';

/**
 * @return {JSX.Element|null} Panel, or null when not editing a space.
 */
export default function SpaceDetailsPanel() {
	const { isMatch, meta, updateField } = usePostTypeMeta( 'venue_space' );

	if ( ! isMatch ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="venuestack-space-details"
			title={ __( 'Space details', 'venuestack-core' ) }
			className="venuestack-space-details-panel"
		>
			{ SPACE_DETAIL_FIELDS.map( ( field ) => (
				<MetaNumberField
					key={ field.key }
					field={ field }
					value={ meta[ field.key ] }
					onChange={ updateField }
				/>
			) ) }
		</PluginDocumentSettingPanel>
	);
}
