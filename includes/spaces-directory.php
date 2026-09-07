<?php
/**
 * Spaces directory — helpers + block registration.
 *
 * @package VenuestackCore
 */

defined('ABSPATH') || exit;

/**
 * Published venue spaces for the directory (title ASC).
 *
 * @return WP_Post[]
 */
function venuestack_core_get_directory_spaces(): array
{
	$posts = get_posts(
		array(
			'post_type' => 'venue_space',
			'post_status' => 'publish',
			'posts_per_page' => 100,
			'orderby' => 'title',
			'order' => 'ASC',
			'no_found_rows' => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		)
	);

	return is_array($posts) ? $posts : array();
}

/**
 * Space type terms for filter chips.
 *
 * @return array<int, array{slug:string,name:string}>
 */
function venuestack_get_directory_space_types(): array
{
	if (!taxonomy_exists('space_type')) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy' => 'space_type',
			'hide_empty' => true,
		)
	);

	if (is_wp_error($terms) || empty($terms)) {
		return array();
	}

	$out = array();
	foreach ($terms as $term) {
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
function venuestack_get_directory_amenities(): array
{
	if (!taxonomy_exists('space_amenity')) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy' => 'space_amenity',
			'hide_empty' => true,
		)
	);

	if (is_wp_error($terms) || empty($terms)) {
		return array();
	}

	$out = array();
	foreach ($terms as $term) {
		$out[] = array(
			'slug' => (string) $term->slug,
			'name' => (string) $term->name,
		);
	}

	return $out;
}

/**
 * Taxonomy slugs for a space (filter context).
 *
 * @param int $post_id Post ID.
 * @return array{types: string[], amenities: string[]}
 */
function venuestack_core_get_space_filter_terms(int $post_id): array
{
	$types = wp_get_post_terms($post_id, 'space_type', array('fields' => 'slugs'));
	$amenities = wp_get_post_terms($post_id, 'space_amenity', array('fields' => 'slugs'));

	return array(
		'types' => is_wp_error($types) ? array() : array_values(array_map('strval', $types)),
		'amenities' => is_wp_error($amenities) ? array() : array_values(array_map('strval', $amenities)),
	);
}

/**
 * Seed Interactivity state for the spaces directory block.
 *
 * @param WP_Post[] $posts Spaces included in the grid.
 */
function venuestack_core_seed_spaces_directory_state(array $posts): void
{
	if (!function_exists('wp_interactivity_state')) {
		return;
	}

	static $seeded = false;
	if ($seeded) {
		return;
	}
	$seeded = true;

	$cards = array();
	foreach ($posts as $post) {
		if (!$post instanceof WP_Post) {
			continue;
		}
		$terms = venuestack_core_get_space_filter_terms((int) $post->ID);
		$cards[] = array(
			'id' => (int) $post->ID,
			'types' => $terms['types'],
			'amenities' => $terms['amenities'],
		);
	}

	wp_interactivity_state(
		'venuestack/spaces-directory',
		array(
			'type' => '',
			'amenity' => '',
			'cards' => $cards,
			'total' => count($cards),
			'emptyLabel' => __('No spaces match these filters.', 'venuestack-core'),
			'resultsLabel' => __('Showing', 'venuestack-core'),
			'ofLabel' => __('of', 'venuestack-core'),
		)
	);
}

/**
 * HTML for one directory space card via the synced `venuestack/space-card` pattern.
 *
 * Filter taxonomy context lives on the list item so Interactivity can hide/show
 * cards without mutating the pattern markup.
 *
 * @param WP_Post $space Space post.
 */
function venuestack_core_render_directory_space_card(WP_Post $space): string
{
	$terms = venuestack_core_get_space_filter_terms((int) $space->ID);

	$context_attr = function_exists('wp_interactivity_data_wp_context')
		? wp_interactivity_data_wp_context(
			array(
				'types' => $terms['types'],
				'amenities' => $terms['amenities'],
			)
		)
		: '';

	$pattern_id = function_exists('venuestack_get_synced_pattern_id')
		? (int) venuestack_get_synced_pattern_id('venuestack/space-card')
		: 0;

	$card_html = '';
	if ($pattern_id > 0) {
		global $post;
		$previous_post = (isset($post) && $post instanceof WP_Post) ? $post : null;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- post-* blocks + bindings need loop context.
		$post = $space;
		setup_postdata($space);

		$card_html = do_blocks(
			sprintf(
				'<!-- wp:block {"ref":%d,"metadata":{"name":"venuestack/space-card"}} /-->',
				$pattern_id
			)
		);

		if ($previous_post instanceof WP_Post) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$post = $previous_post;
			setup_postdata($previous_post);
		} else {
			wp_reset_postdata();
		}
	}

	ob_start();
	?>
	<li class="<?php echo esc_attr(implode(' ', get_post_class('wp-block-post', $space))); ?>" <?php echo $context_attr; ?>>
		<?php echo $card_html; ?>
	</li>
	<?php
	return (string) ob_get_clean();
}

/**
 * Register the spaces-directory block from the plugin build folder.
 */
function venuestack_core_register_directory_blocks(): void
{
	$directory = VENUESTACK_CORE_PATH . 'build/blocks/spaces-directory';
	if (file_exists($directory . '/block.json')) {
		register_block_type($directory);
	}
}
add_action('init', 'venuestack_core_register_directory_blocks');

require_once VENUESTACK_CORE_PATH . 'includes/spaces-directory/partials/filters.php';
require_once VENUESTACK_CORE_PATH . 'includes/spaces-directory/partials/results.php';
