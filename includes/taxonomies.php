<?php
/**
 * Registers VenueStack taxonomies.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register taxonomies attached to venue_space.
 */
function venuestack_core_register_taxonomies(): void {
	register_taxonomy(
		'space_type',
		array( 'venue_space' ),
		array(
			'labels'            => array(
				'name'          => __( 'Space Types', 'venuestack-core' ),
				'singular_name' => __( 'Space Type', 'venuestack-core' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rest_base'         => 'space-types',
			'rewrite'           => array(
				'slug'         => 'space-type',
				'with_front'   => false,
				'hierarchical' => true,
			),
		)
	);

	register_taxonomy(
		'space_amenity',
		array( 'venue_space' ),
		array(
			'labels'            => array(
				'name'          => __( 'Amenities', 'venuestack-core' ),
				'singular_name' => __( 'Amenity', 'venuestack-core' ),
			),
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rest_base'         => 'space-amenities',
			'rewrite'           => array(
				'slug'       => 'amenity',
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'venuestack_core_register_taxonomies' );
