/**
 * Icon + text label used by meta field controls.
 *
 * @param {Object}                        props
 * @param {import('react').ComponentType} props.Icon  Lucide icon.
 * @param {string}                        props.label Field label.
 * @return {JSX.Element} Label content.
 */
export default function FieldLabel( { Icon, label } ) {
	return (
		<span
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				gap: '0.4rem',
			} }
		>
			<Icon size={ 14 } strokeWidth={ 1.75 } aria-hidden />
			{ label }
		</span>
	);
}
