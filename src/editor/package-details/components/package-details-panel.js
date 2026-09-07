/**
 * Package details document setting panel UI.
 */
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import MetaNumberField from '../../shared/components/meta-number-field';
import MetaStringListField from '../../shared/components/meta-string-list-field';
import { usePostTypeMeta } from '../../shared/hooks/use-post-type-meta';
import { PACKAGE_DETAIL_FIELDS } from '../fields';

/**
 * @return {JSX.Element|null} Panel, or null when not editing a package.
 */
export default function PackageDetailsPanel() {
	const { isMatch, meta, updateField } = usePostTypeMeta( 'event_package' );

	if ( ! isMatch ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="venuestack-package-details"
			title={ __( 'Package details', 'venuestack-core' ) }
			className="venuestack-package-details-panel"
		>
			{ PACKAGE_DETAIL_FIELDS.map( ( field ) => {
				if ( field.control === 'string_list' ) {
					return (
						<MetaStringListField
							key={ field.key }
							field={ field }
							value={ meta[ field.key ] }
							onChange={ updateField }
						/>
					);
				}

				return (
					<MetaNumberField
						key={ field.key }
						field={ field }
						value={ meta[ field.key ] }
						onChange={ updateField }
					/>
				);
			} ) }
		</PluginDocumentSettingPanel>
	);
}
