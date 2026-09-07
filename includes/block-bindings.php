<?php
/**
 * Block Bindings sources for venue_space / event_package meta display.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register VenueStack binding sources and package inclusions block.
 */
function venuestack_core_register_block_bindings(): void {
	if ( function_exists( 'register_block_bindings_source' ) ) {
		register_block_bindings_source(
			'venuestack/space-field',
			array(
				'label'              => __( 'Venue space field', 'venuestack-core' ),
				'get_value_callback' => 'venuestack_core_get_space_field_binding',
				'uses_context'       => array( 'postId', 'postType' ),
			)
		);

		register_block_bindings_source(
			'venuestack/package-field',
			array(
				'label'              => __( 'Event package field', 'venuestack-core' ),
				'get_value_callback' => 'venuestack_core_get_package_field_binding',
				'uses_context'       => array( 'postId', 'postType' ),
			)
		);
	}

	register_block_type(
		'venuestack/package-inclusions',
		array(
			'api_version'     => 3,
			'title'           => __( 'Package inclusions', 'venuestack-core' ),
			'category'        => 'widgets',
			'uses_context'    => array( 'postId', 'postType' ),
			'render_callback' => 'venuestack_core_render_package_inclusions',
		)
	);
}
add_action( 'init', 'venuestack_core_register_block_bindings' );

/**
 * Formatted venue_space meta for bound core blocks.
 *
 * @param array    $source_args    Binding args (key).
 * @param WP_Block $block_instance Block instance.
 * @param string   $attribute_name Bound attribute.
 */
function venuestack_core_get_space_field_binding( array $source_args, WP_Block $block_instance, string $attribute_name ): string {
	unset( $attribute_name );

	$post_id = isset( $block_instance->context['postId'] )
		? (int) $block_instance->context['postId']
		: (int) get_the_ID();

	if ( $post_id < 1 ) {
		return '';
	}

	$post_type = $block_instance->context['postType'] ?? get_post_type( $post_id );
	if ( 'venue_space' !== $post_type ) {
		return '';
	}

	$key = isset( $source_args['key'] ) ? (string) $source_args['key'] : '';

	return venuestack_core_format_space_field( $post_id, $key );
}

/**
 * Format a venue_space meta field for display.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 */
function venuestack_core_format_space_field( int $post_id, string $key ): string {
	if ( $post_id < 1 || '' === $key ) {
		return '';
	}

	switch ( $key ) {
		case 'max_capacity':
			$value = (int) get_post_meta( $post_id, 'max_capacity', true );
			return $value > 0
				? sprintf(
					/* translators: %d: guest capacity */
					__( '%d guests', 'venuestack-core' ),
					$value
				)
				: '';

		case 'square_footage':
			$value = (int) get_post_meta( $post_id, 'square_footage', true );
			return $value > 0
				? sprintf(
					/* translators: %s: square footage number */
					__( '%s sq ft', 'venuestack-core' ),
					number_format_i18n( $value )
				)
				: '';

		case 'hourly_rate':
			$value = (float) get_post_meta( $post_id, 'hourly_rate', true );
			if ( $value <= 0 ) {
				return '';
			}
			$decimals  = ( floor( $value ) === $value ) ? 0 : 2;
			$formatted = '$' . number_format_i18n( $value, $decimals );
			return sprintf(
				/* translators: %s: formatted hourly rate like $400 */
				__( '%s/hr', 'venuestack-core' ),
				$formatted
			);

		case 'minimum_booking_hours':
			$value = (int) get_post_meta( $post_id, 'minimum_booking_hours', true );
			return $value > 0
				? sprintf(
					/* translators: %d: minimum hours */
					_n( '%d hr min', '%d hr min', $value, 'venuestack-core' ),
					$value
				)
				: '';

		default:
			return '';
	}
}

/**
 * Formatted event_package meta for bound core blocks.
 *
 * @param array    $source_args    Binding args (key).
 * @param WP_Block $block_instance Block instance.
 * @param string   $attribute_name Bound attribute.
 */
function venuestack_core_get_package_field_binding( array $source_args, WP_Block $block_instance, string $attribute_name ): string {
	unset( $attribute_name );

	$post_id = isset( $block_instance->context['postId'] )
		? (int) $block_instance->context['postId']
		: (int) get_the_ID();

	if ( $post_id < 1 ) {
		return '';
	}

	$post_type = $block_instance->context['postType'] ?? get_post_type( $post_id );
	if ( 'event_package' !== $post_type ) {
		return '';
	}

	$key = isset( $source_args['key'] ) ? (string) $source_args['key'] : '';

	return venuestack_core_format_package_field( $post_id, $key );
}

/**
 * Format an event_package meta field for display.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 */
function venuestack_core_format_package_field( int $post_id, string $key ): string {
	if ( $post_id < 1 || '' === $key ) {
		return '';
	}

	switch ( $key ) {
		case 'price_per_head':
			$value = (float) get_post_meta( $post_id, 'price_per_head', true );
			if ( $value <= 0 ) {
				return '';
			}
			$decimals  = ( floor( $value ) === $value ) ? 0 : 2;
			$formatted = '$' . number_format_i18n( $value, $decimals );
			return sprintf(
				/* translators: %s: formatted per-guest price like $48 */
				__( '%s/guest', 'venuestack-core' ),
				$formatted
			);

		case 'requires_advance_notice':
			$value = (int) get_post_meta( $post_id, 'requires_advance_notice', true );
			return $value > 0
				? sprintf(
					/* translators: %d: days of advance notice */
					_n( '%d day notice', '%d days notice', $value, 'venuestack-core' ),
					$value
				)
				: '';

		case 'menu_items_count':
			$items = get_post_meta( $post_id, 'menu_items_included', true );
			$count = is_array( $items ) ? count( array_filter( array_map( 'strval', $items ) ) ) : 0;
			return $count > 0
				? sprintf(
					/* translators: %d: number of menu items included */
					_n( '%d item included', '%d items included', $count, 'venuestack-core' ),
					$count
				)
				: '';

		default:
			return '';
	}
}

/**
 * Render menu items included for the current event_package.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 */
function venuestack_core_render_package_inclusions( array $attributes, string $content, WP_Block $block ): string {
	unset( $attributes, $content );

	$post_id = isset( $block->context['postId'] )
		? (int) $block->context['postId']
		: (int) get_the_ID();

	if ( $post_id < 1 ) {
		return '';
	}

	if ( 'event_package' !== get_post_type( $post_id ) ) {
		return '';
	}

	$items = get_post_meta( $post_id, 'menu_items_included', true );
	if ( ! is_array( $items ) || array() === $items ) {
		return '';
	}

	$lis = '';
	foreach ( $items as $item ) {
		$label = sanitize_text_field( (string) $item );
		if ( '' === $label ) {
			continue;
		}
		$lis .= '<li class="venuestack-package-inclusions__item">' . esc_html( $label ) . '</li>';
	}

	if ( '' === $lis ) {
		return '';
	}

	return '<ul class="venuestack-package-inclusions">' . $lis . '</ul>';
}
