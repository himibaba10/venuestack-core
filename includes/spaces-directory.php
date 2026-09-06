<?php
/**
 * Spaces directory — helpers + block registration.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Published venue spaces for the directory (title ASC).
 *
 * @return WP_Post[]
 */
function venuestack_core_get_directory_spaces(): array {
	$posts = get_posts(
		array(
			'post_type'              => 'venue_space',
			'post_status'            => 'publish',
			'posts_per_page'         => 100,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		)
	);

	return is_array( $posts ) ? $posts : array();
}

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
 * Taxonomy slugs for a space (filter context).
 *
 * @param int $post_id Post ID.
 * @return array{types: string[], amenities: string[]}
 */
function venuestack_core_get_space_filter_terms( int $post_id ): array {
	$types     = wp_get_post_terms( $post_id, 'space_type', array( 'fields' => 'slugs' ) );
	$amenities = wp_get_post_terms( $post_id, 'space_amenity', array( 'fields' => 'slugs' ) );

	return array(
		'types'     => is_wp_error( $types ) ? array() : array_values( array_map( 'strval', $types ) ),
		'amenities' => is_wp_error( $amenities ) ? array() : array_values( array_map( 'strval', $amenities ) ),
	);
}

/**
 * Seed Interactivity state for the spaces directory block.
 *
 * @param WP_Post[] $posts Spaces included in the grid.
 */
function venuestack_core_seed_spaces_directory_state( array $posts ): void {
	if ( ! function_exists( 'wp_interactivity_state' ) ) {
		return;
	}

	static $seeded = false;
	if ( $seeded ) {
		return;
	}
	$seeded = true;

	$cards = array();
	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		$terms   = venuestack_core_get_space_filter_terms( (int) $post->ID );
		$cards[] = array(
			'id'        => (int) $post->ID,
			'types'     => $terms['types'],
			'amenities' => $terms['amenities'],
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

/**
 * HTML for one directory space card (matches theme space-card pattern classes).
 *
 * @param WP_Post $post Space post.
 */
function venuestack_core_render_directory_space_card( WP_Post $post ): string {
	$post_id = (int) $post->ID;
	$permalink = get_permalink( $post );
	$title     = get_the_title( $post );
	$terms     = venuestack_core_get_space_filter_terms( $post_id );

	$context_attr = function_exists( 'wp_interactivity_data_wp_context' )
		? wp_interactivity_data_wp_context(
			array(
				'types'     => $terms['types'],
				'amenities' => $terms['amenities'],
			)
		)
		: '';

	$type_names = wp_get_post_terms( $post_id, 'space_type', array( 'fields' => 'names' ) );
	$type_label = ( ! is_wp_error( $type_names ) && ! empty( $type_names[0] ) )
		? (string) $type_names[0]
		: '';

	$capacity  = venuestack_core_format_space_field( $post_id, 'max_capacity' );
	$footprint = venuestack_core_format_space_field( $post_id, 'square_footage' );
	$rate      = venuestack_core_format_space_field( $post_id, 'hourly_rate' );

	$thumbnail = get_the_post_thumbnail(
		$post,
		'large',
		array(
			'style'   => 'aspect-ratio:4/3;object-fit:cover;width:100%;height:auto;',
			'loading' => 'lazy',
			'alt'     => $title,
		)
	);

	ob_start();
	?>
	<li class="<?php echo esc_attr( implode( ' ', get_post_class( 'wp-block-post', $post ) ) ); ?>">
		<div
			class="wp-block-group venuestack-home-space-card venuestack-home-reveal has-plaster-background-color has-background"
			style="margin-top:0;margin-bottom:0"
			<?php echo $context_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
			<div class="wp-block-group venuestack-home-space-card__media" style="margin-top:0;margin-bottom:0">
				<?php if ( $type_label ) : ?>
					<div class="taxonomy-space_type venuestack-home-space-type has-plaster-color has-brass-background-color has-text-color has-background" style="margin:0">
						<?php echo esc_html( $type_label ); ?>
					</div>
				<?php endif; ?>
				<figure class="wp-block-post-featured-image" style="margin:0">
					<?php if ( $permalink && $thumbnail ) : ?>
						<a href="<?php echo esc_url( $permalink ); ?>"><?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<?php elseif ( $thumbnail ) : ?>
						<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				</figure>
			</div>
			<div
				class="wp-block-group venuestack-home-space-card__body"
				style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"
			>
				<h3 class="wp-block-post-title is-style-card has-ink-color has-text-color" style="margin-top:0;margin-bottom:0">
					<?php if ( $permalink ) : ?>
						<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $title ); ?>
					<?php endif; ?>
				</h3>
				<div class="wp-block-group venuestack-home-space-meta" style="margin-top:var(--wp--preset--spacing--20);display:flex;flex-direction:column;gap:0.15rem">
					<?php if ( $capacity ) : ?>
						<div class="wp-block-group" style="display:flex;flex-wrap:nowrap;justify-content:space-between;gap:1rem">
							<p class="is-style-body has-muted-color has-text-color" style="margin:0"><?php echo esc_html__( 'Capacity', 'venuestack-core' ); ?></p>
							<p class="has-text-align-right has-ink-color has-text-color has-small-font-size" style="margin:0"><?php echo esc_html( $capacity ); ?></p>
						</div>
					<?php endif; ?>
					<?php if ( $footprint ) : ?>
						<div class="wp-block-group" style="display:flex;flex-wrap:nowrap;justify-content:space-between;gap:1rem">
							<p class="is-style-body has-muted-color has-text-color" style="margin:0"><?php echo esc_html__( 'Footprint', 'venuestack-core' ); ?></p>
							<p class="has-text-align-right has-ink-color has-text-color has-small-font-size" style="margin:0"><?php echo esc_html( $footprint ); ?></p>
						</div>
					<?php endif; ?>
					<?php if ( $rate ) : ?>
						<div class="wp-block-group" style="display:flex;flex-wrap:nowrap;justify-content:space-between;gap:1rem">
							<p class="is-style-body has-muted-color has-text-color" style="margin:0"><?php echo esc_html__( 'From', 'venuestack-core' ); ?></p>
							<p class="has-text-align-right is-rate has-brass-color has-text-color has-small-font-size" style="margin:0"><?php echo esc_html( $rate ); ?></p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</li>
	<?php
	return (string) ob_get_clean();
}

/**
 * Register the spaces-directory block from the plugin build folder.
 */
function venuestack_core_register_directory_blocks(): void {
	$directory = VENUESTACK_CORE_PATH . 'build/blocks/spaces-directory';
	if ( file_exists( $directory . '/block.json' ) ) {
		register_block_type( $directory );
	}
}
add_action( 'init', 'venuestack_core_register_directory_blocks' );

require_once VENUESTACK_CORE_PATH . 'includes/spaces-directory/partials/filters.php';
require_once VENUESTACK_CORE_PATH . 'includes/spaces-directory/partials/results.php';
