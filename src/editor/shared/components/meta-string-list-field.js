/**
 * Multiline string-list meta field (one item per line).
 */
import { TextareaControl } from '@wordpress/components';
import { parseStringListInput, stringListToInput } from '../utils/meta';
import FieldLabel from './field-label';

/**
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field definition.
 * @param {unknown}  props.value    Current meta value.
 * @param {Function} props.onChange Change handler (key, type, value).
 * @return {JSX.Element} Textarea control.
 */
export default function MetaStringListField( { field, value, onChange } ) {
	return (
		<TextareaControl
			label={ <FieldLabel Icon={ field.Icon } label={ field.label } /> }
			help={ field.help }
			rows={ 5 }
			value={ stringListToInput( value ) }
			onChange={ ( next ) =>
				onChange( field.key, field.type, parseStringListInput( next ) )
			}
			__nextHasNoMarginBottom
		/>
	);
}
