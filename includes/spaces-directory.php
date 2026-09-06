<?php
/**
 * Spaces directory blocks — Interactivity filters + card context.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the current request is the venue_space archive directory.
 */
function venuestack_is_spaces_directory(): bool {
	if ( is_post_type_archive( 'venue_space' ) ) {
		return true;
	}

	global $wp_query;
	if ( $wp_query instanceof WP_Query && ! empty( $wp_query->is_post_type_archive ) ) {
		$obj = $wp_query->queried_object ?? null;
		if ( $obj instanceof WP_Post_Type && 'venue_space' === $obj->name ) {
			return true;
		}
		if ( is_array( $wp_query->query ) && ( $wp_query->query['post_type'] ?? '' ) === 'venue_space' && ! empty( $wp_query->is_archive ) ) {
			return true;
		}
	}

	$qo = get_queried_object();
	return $qo instanceof WP_Post_Type && 'venue_space' === $qo->name;
}

/**
 * Attach Interactivity attrs to the directory shell and space cards.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block.
 */
function venuestack_core_directory_space_card_attrs( string $block_content, array $block ): string {
	if ( ( $block['blockName'] ?? '' ) !== 'core/group' ) {
		return $block_content;
	}

	if ( ! venuestack_is_spaces_directory() ) {
		return $block_content;
	}

	$class = $block['attrs']['className'] ?? '';
	if ( ! is_string( $class ) ) {
		$class = '';
	}

	$classes = preg_split( '/\s+/', trim( $class ) ) ?: array();

	$is_directory_root = in_array( 'venuestack-directory', $classes, true );
	if ( ! $is_directory_root && preg_match( '/^<main\b[^>]*\bvenuestack-directory\b/i', ltrim( $block_content ) ) ) {
		$is_directory_root = true;
	}

	if ( $is_directory_root ) {
		foreach ( $classes as $token ) {
			if ( str_starts_with( $token, 'venuestack-directory-' ) ) {
				$is_directory_root = false;
				break;
			}
		}
	}

	if ( $is_directory_root && ! str_contains( $block_content, 'data-wp-interactive=' ) ) {
		$updated = preg_replace(
			'/^<(main|div)\b/i',
			'<$1 data-wp-interactive="venuestack/spaces-directory" data-wp-init="callbacks.initGrid"',
			ltrim( $block_content ),
			1
		);
		return is_string( $updated ) ? $updated : $block_content;
	}

	$is_space_card = in_array( 'venuestack-home-space-card', $classes, true );
	if ( ! $is_space_card && preg_match( '/^<div\b[^>]*\bvenuestack-home-space-card\b/i', ltrim( $block_content ) ) ) {
		$is_space_card = true;
	}

	if ( ! $is_space_card || str_contains( $block_content, 'data-wp-context' ) ) {
		return $block_content;
	}

	$post_id = (int) get_the_ID();
	if ( $post_id < 1 ) {
		return $block_content;
	}

	$types     = wp_get_post_terms( $post_id, 'space_type', array( 'fields' => 'slugs' ) );
	$amenities = wp_get_post_terms( $post_id, 'space_amenity', array( 'fields' => 'slugs' ) );

	if ( is_wp_error( $types ) ) {
		$types = array();
	}
	if ( is_wp_error( $amenities ) ) {
		$amenities = array();
	}

	$context_attr = function_exists( 'wp_interactivity_data_wp_context' )
		? wp_interactivity_data_wp_context(
			array(
				'types'     => array_values( array_map( 'strval', $types ) ),
				'amenities' => array_values( array_map( 'strval', $amenities ) ),
			)
		)
		: '';

	if ( '' === $context_attr ) {
		return $block_content;
	}

	$updated = preg_replace( '/^<div\b/i', '<div ' . trim( $context_attr ), ltrim( $block_content ), 1 );

	return is_string( $updated ) ? $updated : $block_content;
}
add_filter( 'render_block', 'venuestack_core_directory_space_card_attrs', 20, 2 );

/**
 * Space type terms for filter chips.
 *
 * @return array<int, array{slug:string,name:string}>
 */
function venuestack_get_directory_space_types(): array {
	if ( ! taxonomy_exists( 'space_type' ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'space_type',
			'hide_empty' => true,
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	$out = array();
	foreach ( $terms as $term ) {
		$out[] = array(
			'slug' => (string) $term->slug,
			'name' => (string) $term->name,
		);
	}

	return $out;
}

/**
 * Amenity terms for filter chips.
 *
 * @return array<int, array{slug:string,name:string}>
 */
function venuestack_get_directory_amenities(): array {
	if ( ! taxonomy_exists( 'space_amenity' ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'space_amenity',
			'hide_empty' => true,
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	$out = array();
	foreach ( $terms as $term ) {
		$out[] = array(
			'slug' => (string) $term->slug,
			'name' => (string) $term->name,
		);
	}

	return $out;
}

/**
 * Seed Interactivity state for the spaces directory.
 */
function venuestack_core_register_spaces_directory_interactivity(): void {
	if ( ! venuestack_is_spaces_directory() || ! function_exists( 'wp_interactivity_state' ) ) {
		return;
	}

	$posts = get_posts(
		array(
			'post_type'              => 'venue_space',
			'post_status'            => 'publish',
			'posts_per_page'         => 100,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
		)
	);

	$cards = array();
	foreach ( $posts as $post ) {
		$types     = wp_get_post_terms( $post->ID, 'space_type', array( 'fields' => 'slugs' ) );
		$amenities = wp_get_post_terms( $post->ID, 'space_amenity', array( 'fields' => 'slugs' ) );

		$cards[] = array(
			'id'        => (int) $post->ID,
			'types'     => is_wp_error( $types ) ? array() : array_values( array_map( 'strval', $types ) ),
			'amenities' => is_wp_error( $amenities ) ? array() : array_values( array_map( 'strval', $amenities ) ),
		);
	}

	wp_interactivity_state(
		'venuestack/spaces-directory',
		array(
			'type'         => '',
			'amenity'      => '',
			'cards'        => $cards,
			'total'        => count( $cards ),
			'emptyLabel'   => __( 'No spaces match these filters.', 'venuestack-core' ),
			'resultsLabel' => __( 'Showing', 'venuestack-core' ),
			'ofLabel'      => __( 'of', 'venuestack-core' ),
		)
	);
}
add_action( 'wp', 'venuestack_core_register_spaces_directory_interactivity' );

/**
 * Register directory-related blocks from the plugin build folder.
 */
function venuestack_core_register_directory_blocks(): void {
	$directory = VENUESTACK_CORE_PATH . 'build/blocks/spaces-directory';
	if ( file_exists( $directory . '/block.json' ) ) {
		register_block_type( $directory );
	}

	$filters = VENUESTACK_CORE_PATH . 'build/blocks/spaces-filters';
	if ( file_exists( $filters . '/block.json' ) ) {
		register_block_type( $filters );
	}
}
add_action( 'init', 'venuestack_core_register_directory_blocks' );

/**
 * Enqueue directory Interactivity module on the spaces archive.
 */
function venuestack_core_enqueue_spaces_directory_assets(): void {
	if ( ! venuestack_is_spaces_directory() ) {
		return;
	}

	$asset_file = VENUESTACK_CORE_PATH . 'build/blocks/spaces-directory/view.asset.php';
	$module_js  = VENUESTACK_CORE_PATH . 'build/blocks/spaces-directory/view.js';

	if ( ! file_exists( $module_js ) ) {
		return;
	}

	$asset = file_exists( $asset_file ) ? include $asset_file : array();
	$deps  = array( '@wordpress/interactivity' );

	if ( ! empty( $asset['dependencies'] ) && is_array( $asset['dependencies'] ) ) {
		foreach ( $asset['dependencies'] as $dep ) {
			if ( is_string( $dep ) && str_starts_with( $dep, '@' ) ) {
				$deps[] = $dep;
			}
		}
	}

	wp_enqueue_script_module(
		'@venuestack/spaces-directory',
		VENUESTACK_CORE_URL . 'build/blocks/spaces-directory/view.js',
		array_values( array_unique( $deps ) ),
		$asset['version'] ?? VENUESTACK_CORE_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'venuestack_core_enqueue_spaces_directory_assets', 20 );
