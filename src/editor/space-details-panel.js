/**
 * Document sidebar: venue_space capacity, size, rate, minimum hours.
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { Users, Ruler, CircleDollarSign, Clock } from 'lucide-react';

const SPACE_FIELDS = [
	{
		key: 'max_capacity',
		label: __( 'Max capacity', 'venuestack-core' ),
		help: __( 'Guest capacity for this space.', 'venuestack-core' ),
		type: 'integer',
		Icon: Users,
	},
	{
		key: 'square_footage',
		label: __( 'Square footage', 'venuestack-core' ),
		help: __( 'Interior size in square feet.', 'venuestack-core' ),
		type: 'integer',
		Icon: Ruler,
	},
	{
		key: 'hourly_rate',
		label: __( 'Hourly rate', 'venuestack-core' ),
		help: __( 'Base rate charged per hour.', 'venuestack-core' ),
		type: 'number',
		Icon: CircleDollarSign,
	},
	{
		key: 'minimum_booking_hours',
		label: __( 'Minimum booking hours', 'venuestack-core' ),
		help: __( 'Shortest bookable duration.', 'venuestack-core' ),
		type: 'integer',
		Icon: Clock,
	},
];

function SpaceDetailsPanel() {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	if ( postType !== 'venue_space' ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="venuestack-space-details"
			title={ __( 'Space details', 'venuestack-core' ) }
			className="venuestack-space-details-panel"
		>
			{ SPACE_FIELDS.map( ( field ) => {
				const Icon = field.Icon;
				return (
					<TextControl
						key={ field.key }
						label={
							<span
								style={ {
									display: 'inline-flex',
									alignItems: 'center',
									gap: '0.4rem',
								} }
							>
								<Icon
									size={ 14 }
									strokeWidth={ 1.75 }
									aria-hidden
								/>
								{ field.label }
							</span>
						}
						help={ field.help }
						type="number"
						min={ 0 }
						step={ field.type === 'number' ? '0.01' : '1' }
						value={
							meta?.[ field.key ] === undefined ||
							meta?.[ field.key ] === null
								? ''
								: String( meta[ field.key ] )
						}
						onChange={ ( value ) => {
							const parsed =
								field.type === 'number'
									? parseFloat( value )
									: parseInt( value, 10 );

							setMeta( {
								...meta,
								[ field.key ]: Number.isFinite( parsed )
									? parsed
									: 0,
							} );
						} }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				);
			} ) }
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'venuestack-space-details', {
	render: SpaceDetailsPanel,
} );
