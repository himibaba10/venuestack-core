<?php
/**
 * Registers VenueStack custom post types.
 *
 * @package VenuestackCore
 */

defined('ABSPATH') || exit;

/**
 * Register all VenueStack CPTs.
 */
function venuestack_core_register_post_types(): void
{
	register_post_type(
		'venue_space',
		array(
			'labels' => array(
				'name' => __('Spaces', 'venuestack-core'),
				'singular_name' => __('Space', 'venuestack-core'),
			),
			'public' => true,
			'show_in_rest' => true,
			'rest_base' => 'venue-spaces',
			'menu_position' => 20,
			'menu_icon' => 'dashicons-building',
			'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields'),
			'has_archive' => true,
			'rewrite' => array(
				'slug' => 'spaces',
				'with_front' => false,
			),
		)
	);

	register_post_type(
		'event_package',
		array(
			'labels' => array(
				'name' => __('Packages', 'venuestack-core'),
				'singular_name' => __('Package', 'venuestack-core'),
			),
			'public' => true,
			'show_in_rest' => true,
			'rest_base' => 'event-packages',
			'menu_position' => 21,
			'menu_icon' => 'dashicons-carrot',
			'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
			'has_archive' => true,
			'rewrite' => array(
				'slug' => 'packages',
				'with_front' => false,
			),
		)
	);

	register_post_type(
		'venue_booking',
		array(
			'labels' => array(
				'name' => __('Bookings', 'venuestack-core'),
				'singular_name' => __('Booking', 'venuestack-core'),
			),
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => true,
			'rest_base' => 'venue-bookings',
			'menu_position' => 22,
			'menu_icon' => 'dashicons-calendar-alt',
			'supports' => array('title', 'author'),
			'rewrite' => false,
		)
	);
}
add_action('init', 'venuestack_core_register_post_types');
