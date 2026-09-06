<?php
/**
 * Block Bindings sources for venue_space meta display.
 *
 * @package VenuestackCore
 */

defined('ABSPATH') || exit;

/**
 * Register VenueStack binding sources.
 */
function venuestack_core_register_block_bindings(): void
{
	if (!function_exists('register_block_bindings_source')) {
		return;
	}

	register_block_bindings_source(
		'venuestack/space-field',
		[
			'label' => __('Venue space field', 'venuestack-core'),
			'get_value_callback' => 'venuestack_core_get_space_field_binding',
			'uses_context' => ['postId', 'postType'],
		]
	);
}
add_action('init', 'venuestack_core_register_block_bindings');

/**
 * Formatted venue_space meta for bound core blocks.
 *
 * @param array    $source_args     Binding args (key).
 * @param WP_Block $block_instance  Block instance.
 * @param string   $attribute_name  Bound attribute.
 */
function venuestack_core_get_space_field_binding(array $source_args, WP_Block $block_instance, string $attribute_name): string
{
	unset($attribute_name);

	$post_id = isset($block_instance->context['postId'])
		? (int) $block_instance->context['postId']
		: (int) get_the_ID();

	if ($post_id < 1) {
		return '';
	}

	$post_type = $block_instance->context['postType'] ?? get_post_type($post_id);
	if ('venue_space' !== $post_type) {
		return '';
	}

	$key = isset($source_args['key']) ? (string) $source_args['key'] : '';

	switch ($key) {
		case 'max_capacity':
			$value = (int) get_post_meta($post_id, 'max_capacity', true);
			return $value > 0
				? sprintf(
					/* translators: %d: guest capacity */
					__('%d guests', 'venuestack-core'),
					$value
				)
				: '';

		case 'square_footage':
			$value = (int) get_post_meta($post_id, 'square_footage', true);
			return $value > 0
				? sprintf(
					/* translators: %s: square footage number */
					__('%s sq ft', 'venuestack-core'),
					number_format_i18n($value)
				)
				: '';

		case 'hourly_rate':
			$value = (float) get_post_meta($post_id, 'hourly_rate', true);
			if ($value <= 0) {
				return '';
			}
			$decimals  = (floor($value) === $value) ? 0 : 2;
			$formatted = '$' . number_format_i18n($value, $decimals);
			return sprintf(
				/* translators: %s: formatted hourly rate like $400 */
				__('%s/hr', 'venuestack-core'),
				$formatted
			);

		case 'minimum_booking_hours':
			$value = (int) get_post_meta($post_id, 'minimum_booking_hours', true);
			return $value > 0
				? sprintf(
					/* translators: %d: minimum hours */
					_n('%d hr min', '%d hr min', $value, 'venuestack-core'),
					$value
				)
				: '';

		default:
			return '';
	}
}
