<?php
/**
 * Render callback: Spaces directory (filters + results grid).
 *
 * @package VenuestackCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner HTML (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

unset( $attributes, $content, $block );

$spaces    = venuestack_core_get_directory_spaces();
$types     = venuestack_get_directory_space_types();
$amenities = venuestack_get_directory_amenities();

venuestack_core_seed_spaces_directory_state( $spaces );

$wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'alignfull venuestack-directory-browser',
		'data-wp-interactive' => 'venuestack/spaces-directory',
		'data-wp-init'        => 'callbacks.initGrid',
	)
);
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php
	venuestack_core_print_directory_filters( $types, $amenities );
	venuestack_core_print_directory_results( $spaces );
	?>
</div>
