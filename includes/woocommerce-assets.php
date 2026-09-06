<?php
/**
 * Keep WooCommerce off VenueStack marketing/booking pages.
 *
 * WC is a headless payment processor here — classic jQuery storefront
 * scripts (and unused mini-cart chrome) must not load site-wide.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the current request needs WooCommerce storefront assets.
 */
function venuestack_core_needs_woocommerce_storefront_assets(): bool {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return true;
	}

	if ( ! function_exists( 'is_cart' ) ) {
		return false;
	}

	return is_cart()
		|| is_checkout()
		|| is_account_page()
		|| is_wc_endpoint_url()
		|| is_singular( 'product' )
		|| is_post_type_archive( 'product' )
		|| is_product_taxonomy();
}

/**
 * Drop classic WC scripts that pull jQuery on non-commerce pages.
 */
function venuestack_core_dequeue_woocommerce_storefront_assets(): void {
	if ( venuestack_core_needs_woocommerce_storefront_assets() ) {
		return;
	}

	$scripts = array(
		'woocommerce',
		'wc-add-to-cart',
		'wc-cart-fragments',
		'wc-checkout',
		'wc-single-product',
		'wc-jquery-blockui',
		'wc-js-cookie',
		'wc-password-strength-meter',
		'wc-order-attribution',
		'sourcebuster-js',
		'jquery-blockui',
		'js-cookie',
	);

	foreach ( $scripts as $handle ) {
		wp_dequeue_script( $handle );
		wp_deregister_script( $handle );
	}

	venuestack_core_dequeue_orphan_jquery();

	$styles = array(
		'woocommerce-general',
		'woocommerce-layout',
		'woocommerce-smallscreen',
		'woocommerce-inline',
		'wc-blocks-style',
		'wc-blocks-packages-style',
		'woocommerce-general-css',
		'woocommerce-blocktheme',
		'wp-woocommerce-blocktheme',
		'woocommerce-mini-cart-style',
		'woocommerce-customer-account-style',
	);

	foreach ( $styles as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'venuestack_core_dequeue_woocommerce_storefront_assets', 100 );

/**
 * Remove jQuery only when no remaining enqueued script depends on it.
 *
 * Keeps the WP admin bar (and any other legit dependents) working.
 */
function venuestack_core_dequeue_orphan_jquery(): void {
	if ( is_admin_bar_showing() ) {
		return;
	}

	global $wp_scripts;
	if ( ! $wp_scripts instanceof WP_Scripts ) {
		return;
	}

	foreach ( (array) $wp_scripts->queue as $handle ) {
		$obj = $wp_scripts->registered[ $handle ] ?? null;
		if ( ! $obj || empty( $obj->deps ) || ! is_array( $obj->deps ) ) {
			continue;
		}

		if (
			in_array( 'jquery', $obj->deps, true )
			|| in_array( 'jquery-core', $obj->deps, true )
			|| in_array( 'jquery-migrate', $obj->deps, true )
		) {
			return;
		}
	}

	wp_dequeue_script( 'jquery' );
	wp_dequeue_script( 'jquery-core' );
	wp_dequeue_script( 'jquery-migrate' );
}

/**
 * Skip WC's default stylesheet pack on non-commerce pages.
 *
 * @param array<string, mixed> $styles Style handles.
 * @return array<string, mixed>
 */
function venuestack_core_filter_woocommerce_enqueue_styles( array $styles ): array {
	if ( venuestack_core_needs_woocommerce_storefront_assets() ) {
		return $styles;
	}

	return array();
}
add_filter( 'woocommerce_enqueue_styles', 'venuestack_core_filter_woocommerce_enqueue_styles' );

/**
 * Order attribution loads Sourcebuster + extra JS on every page.
 */
add_filter( 'woocommerce_enable_order_attribution', '__return_false' );

/**
 * Stop WC from auto-injecting mini-cart / customer-account into the header.
 *
 * @param string[]                          $hooked_blocks Hooked block names.
 * @param string                            $position      Hook position.
 * @param string                            $anchor_block  Anchor block name.
 * @param array|\WP_Post|\WP_Block_Template $context       Template context.
 * @return string[]
 */
function venuestack_core_filter_hooked_block_types( $hooked_blocks, $position, $anchor_block, $context ) {
	unset( $position, $anchor_block, $context );

	if ( ! is_array( $hooked_blocks ) ) {
		return $hooked_blocks;
	}

	return array_values(
		array_filter(
			$hooked_blocks,
			static function ( string $block ): bool {
				return ! in_array(
					$block,
					array(
						'woocommerce/mini-cart',
						'woocommerce/customer-account',
					),
					true
				);
			}
		)
	);
}
add_filter( 'hooked_block_types', 'venuestack_core_filter_hooked_block_types', 20, 4 );
