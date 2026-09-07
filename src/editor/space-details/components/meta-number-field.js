/**
 * Number meta field for document setting panels.
 */
import { TextControl } from '@wordpress/components';
import { metaValueToInput } from '../utils/meta';

/**
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field definition.
 * @param {unknown}  props.value    Current meta value.
 * @param {Function} props.onChange Change handler (key, type, value).
 * @return {JSX.Element} Text control.
 */
export default function MetaNumberField( { field, value, onChange } ) {
	const Icon = field.Icon;

	return (
		<TextControl
			label={
				<span
					style={ {
						display: 'inline-flex',
						alignItems: 'center',
						gap: '0.4rem',
					} }
				>
					<Icon size={ 14 } strokeWidth={ 1.75 } aria-hidden />
					{ field.label }
				</span>
			}
			help={ field.help }
			type="number"
			min={ 0 }
			step={ field.type === 'number' ? '0.01' : '1' }
			value={ metaValueToInput( value ) }
			onChange={ ( next ) => onChange( field.key, field.type, next ) }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
}
