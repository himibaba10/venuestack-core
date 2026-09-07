/**
 * Space details document setting panel UI.
 */
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { SPACE_DETAIL_FIELDS } from '../fields';
import { useSpaceDetails } from '../hooks/use-space-details';
import MetaNumberField from './meta-number-field';

/**
 * @return {JSX.Element|null} Panel, or null when not editing a space.
 */
export default function SpaceDetailsPanel() {
	const { isSpace, meta, updateField } = useSpaceDetails();

	if ( ! isSpace ) {
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
