<?php
/**
 * Registers VenueStack custom post types.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build CPT labels with Booking/Space wording (not generic "Post").
 *
 * @param string $singular Singular name.
 * @param string $plural   Plural name.
 * @return array<string, string>
 */
function venuestack_core_cpt_labels( string $singular, string $plural ): array {
	return array(
		'name'                     => $plural,
		'singular_name'            => $singular,
		'add_new'                  => sprintf(
			/* translators: %s: singular CPT name */
			__( 'Add %s', 'venuestack-core' ),
			$singular
		),
		'add_new_item'             => sprintf(
			/* translators: %s: singular CPT name */
			__( 'Add %s', 'venuestack-core' ),
			$singular
		),
		'edit_item'                => sprintf(
			/* translators: %s: singular CPT name */
			__( 'Edit %s', 'venuestack-core' ),
			$singular
		),
		'new_item'                 => sprintf(
			/* translators: %s: singular CPT name */
			__( 'New %s', 'venuestack-core' ),
			$singular
		),
		'view_item'                => sprintf(
			/* translators: %s: singular CPT name */
			__( 'View %s', 'venuestack-core' ),
			$singular
		),
		'view_items'               => sprintf(
			/* translators: %s: plural CPT name */
			__( 'View %s', 'venuestack-core' ),
			$plural
		),
		'search_items'             => sprintf(
			/* translators: %s: plural CPT name */
			__( 'Search %s', 'venuestack-core' ),
			$plural
		),
		'not_found'                => sprintf(
			/* translators: %s: plural CPT name */
			__( 'No %s found.', 'venuestack-core' ),
			strtolower( $plural )
		),
		'not_found_in_trash'       => sprintf(
			/* translators: %s: plural CPT name */
			__( 'No %s found in Trash.', 'venuestack-core' ),
			strtolower( $plural )
		),
		'all_items'                => $plural,
		'archives'                 => sprintf(
			/* translators: %s: singular CPT name */
			__( '%s Archives', 'venuestack-core' ),
			$singular
		),
		'attributes'               => sprintf(
			/* translators: %s: singular CPT name */
			__( '%s Attributes', 'venuestack-core' ),
			$singular
		),
		'insert_into_item'         => sprintf(
			/* translators: %s: singular CPT name */
			__( 'Insert into %s', 'venuestack-core' ),
			strtolower( $singular )
		),
		'uploaded_to_this_item'    => sprintf(
			/* translators: %s: singular CPT name */
			__( 'Uploaded to this %s', 'venuestack-core' ),
			strtolower( $singular )
		),
		'featured_image'           => __( 'Featured image', 'venuestack-core' ),
		'set_featured_image'       => __( 'Set featured image', 'venuestack-core' ),
		'remove_featured_image'    => __( 'Remove featured image', 'venuestack-core' ),
		'use_featured_image'       => __( 'Use as featured image', 'venuestack-core' ),
		'filter_items_list'        => sprintf(
			/* translators: %s: plural CPT name */
			__( 'Filter %s list', 'venuestack-core' ),
			strtolower( $plural )
		),
		'filter_by_date'           => __( 'Filter by date', 'venuestack-core' ),
		'items_list_navigation'    => sprintf(
			/* translators: %s: plural CPT name */
			__( '%s list navigation', 'venuestack-core' ),
			$plural
		),
		'items_list'               => sprintf(
			/* translators: %s: plural CPT name */
			__( '%s list', 'venuestack-core' ),
			$plural
		),
		'item_published'           => sprintf(
			/* translators: %s: singular CPT name */
			__( '%s published.', 'venuestack-core' ),
			$singular
		),
		'item_published_privately' => sprintf(
			/* translators: %s: singular CPT name */
			__( '%s published privately.', 'venuestack-core' ),
			$singular
		),
		'item_reverted_to_draft'   => sprintf(
			/* translators: %s: singular CPT name */
			__( '%s reverted to draft.', 'venuestack-core' ),
			$singular
		),
		'item_scheduled'           => sprintf(
			/* translators: %s: singular CPT name */
			__( '%s scheduled.', 'venuestack-core' ),
			$singular
		),
		'item_updated'             => sprintf(
			/* translators: %s: singular CPT name */
			__( '%s updated.', 'venuestack-core' ),
			$singular
		),
		'menu_name'                => $plural,
		'name_admin_bar'           => $singular,
	);
}

/**
 * Register all VenueStack CPTs.
 */
function venuestack_core_register_post_types(): void {
	register_post_type(
		'venue_space',
		array(
			'labels'        => venuestack_core_cpt_labels(
				__( 'Space', 'venuestack-core' ),
				__( 'Spaces', 'venuestack-core' )
			),
			'public'        => true,
			'show_in_rest'  => true,
			'rest_base'     => 'venue-spaces',
			'menu_position' => 20,
			'menu_icon'     => 'dashicons-building',
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
			'has_archive'   => true,
			'rewrite'       => array(
				'slug'       => 'spaces',
				'with_front' => false,
			),
		)
	);

	register_post_type(
		'event_package',
		array(
			'labels'        => venuestack_core_cpt_labels(
				__( 'Package', 'venuestack-core' ),
				__( 'Packages', 'venuestack-core' )
			),
			'public'        => true,
			'show_in_rest'  => true,
			'rest_base'     => 'event-packages',
			'menu_position' => 21,
			'menu_icon'     => 'dashicons-carrot',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
			'has_archive'   => true,
			'rewrite'       => array(
				'slug'       => 'packages',
				'with_front' => false,
			),
		)
	);

	register_post_type(
		'venue_booking',
		array(
			'labels'        => venuestack_core_cpt_labels(
				__( 'Booking', 'venuestack-core' ),
				__( 'Bookings', 'venuestack-core' )
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_rest'  => true,
			'rest_base'     => 'venue-bookings',
			'menu_position' => 22,
			'menu_icon'     => 'dashicons-calendar-alt',
			'supports'      => array( 'title', 'author', 'custom-fields' ),
			'rewrite'       => false,
		)
	);
}
add_action( 'init', 'venuestack_core_register_post_types' );
