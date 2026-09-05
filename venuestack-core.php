<?php
/**
 * Plugin Name:       VenueStack Core
 * Description:       Companion blocks and booking engine for the VenueStack theme.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      8.0
 * Author:            VenueStack
 * License:           GPL-2.0-or-later
 * Text Domain:       venuestack-core
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

define( 'VENUESTACK_CORE_VERSION', '0.1.0' );
define( 'VENUESTACK_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'VENUESTACK_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once VENUESTACK_CORE_PATH . 'includes/post-types.php';
require_once VENUESTACK_CORE_PATH . 'includes/taxonomies.php';
require_once VENUESTACK_CORE_PATH . 'includes/meta.php';

/**
 * Flush rewrite rules once after CPT/taxonomy registration changes.
 */
function venuestack_core_activate(): void {
	venuestack_core_register_post_types();
	venuestack_core_register_taxonomies();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'venuestack_core_activate' );

/**
 * Flush rewrite rules on deactivate.
 */
function venuestack_core_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'venuestack_core_deactivate' );

/**
 * Enqueue built editor/script assets when present.
 */
function venuestack_core_enqueue_assets(): void {
	$asset_file = VENUESTACK_CORE_PATH . 'build/index.asset.php';

	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = include $asset_file;

	wp_enqueue_script(
		'venuestack-core-editor',
		VENUESTACK_CORE_URL . 'build/index.js',
		$asset['dependencies'] ?? array(),
		$asset['version'] ?? VENUESTACK_CORE_VERSION,
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'venuestack_core_enqueue_assets' );
