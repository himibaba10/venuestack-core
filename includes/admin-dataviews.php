<?php
/**
 * Admin Data Views inventory screens for Spaces, Bookings, and Packages.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Map inventory page slugs to screen keys.
 *
 * @return array<string, string>
 */
function venuestack_core_dataviews_screens(): array {
	return array(
		'venuestack-spaces-inventory'   => 'spaces',
		'venuestack-bookings-inventory' => 'bookings',
		'venuestack-packages-inventory' => 'packages',
	);
}

/**
 * Register Data Views as the primary CPT list screens.
 */
function venuestack_core_register_dataviews_menus(): void {
	add_submenu_page(
		'edit.php?post_type=venue_space',
		__( 'Spaces', 'venuestack-core' ),
		__( 'Spaces', 'venuestack-core' ),
		'edit_posts',
		'venuestack-spaces-inventory',
		'venuestack_core_render_dataviews_page'
	);

	add_submenu_page(
		'edit.php?post_type=venue_booking',
		__( 'Bookings', 'venuestack-core' ),
		__( 'Bookings', 'venuestack-core' ),
		'edit_posts',
		'venuestack-bookings-inventory',
		'venuestack_core_render_dataviews_page'
	);

	add_submenu_page(
		'edit.php?post_type=event_package',
		__( 'Packages', 'venuestack-core' ),
		__( 'Packages', 'venuestack-core' ),
		'edit_posts',
		'venuestack-packages-inventory',
		'venuestack_core_render_dataviews_page'
	);
}
add_action( 'admin_menu', 'venuestack_core_register_dataviews_menus' );

/**
 * Hide classic CPT list tables and promote Data Views to the first submenu.
 */
function venuestack_core_promote_dataviews_menus(): void {
	global $submenu;

	$parents = array(
		'edit.php?post_type=venue_space'    => 'venuestack-spaces-inventory',
		'edit.php?post_type=venue_booking'  => 'venuestack-bookings-inventory',
		'edit.php?post_type=event_package'  => 'venuestack-packages-inventory',
	);

	foreach ( $parents as $parent => $slug ) {
		remove_submenu_page( $parent, $parent );

		if ( empty( $submenu[ $parent ] ) || ! is_array( $submenu[ $parent ] ) ) {
			continue;
		}

		$promoted = null;
		foreach ( $submenu[ $parent ] as $index => $item ) {
			if ( ( $item[2] ?? '' ) === $slug ) {
				$promoted = $item;
				unset( $submenu[ $parent ][ $index ] );
				break;
			}
		}

		if ( null === $promoted ) {
			continue;
		}

		array_unshift( $submenu[ $parent ], $promoted );
		$submenu[ $parent ] = array_values( $submenu[ $parent ] );
	}
}
add_action( 'admin_menu', 'venuestack_core_promote_dataviews_menus', 999 );

/**
 * Send top-level CPT menu clicks to Data Views instead of the classic list.
 */
function venuestack_core_redirect_classic_cpt_lists(): void {
	if ( ! is_admin() || ! isset( $_GET['post_type'] ) || isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$post_type = sanitize_key( wp_unslash( $_GET['post_type'] ) );
	$map       = array(
		'venue_space'    => 'venuestack-spaces-inventory',
		'venue_booking'  => 'venuestack-bookings-inventory',
		'event_package'  => 'venuestack-packages-inventory',
	);

	if ( ! isset( $map[ $post_type ] ) ) {
		return;
	}

	global $pagenow;
	if ( 'edit.php' !== $pagenow ) {
		return;
	}

	wp_safe_redirect(
		admin_url(
			'edit.php?post_type=' . rawurlencode( $post_type ) . '&page=' . rawurlencode( $map[ $post_type ] )
		)
	);
	exit;
}
add_action( 'load-edit.php', 'venuestack_core_redirect_classic_cpt_lists' );

/**
 * Render the Data Views mount point.
 */
function venuestack_core_render_dataviews_page(): void {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to view this screen.', 'venuestack-core' ) );
	}

	$page    = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$screens = venuestack_core_dataviews_screens();
	$screen  = $screens[ $page ] ?? 'spaces';

	$titles = array(
		'spaces'   => __( 'Spaces', 'venuestack-core' ),
		'bookings' => __( 'Bookings', 'venuestack-core' ),
		'packages' => __( 'Packages', 'venuestack-core' ),
	);
	$title  = $titles[ $screen ] ?? $titles['spaces'];

	$new_links = array(
		'spaces'   => array(
			'url'   => admin_url( 'post-new.php?post_type=venue_space' ),
			'label' => __( 'Add Space', 'venuestack-core' ),
		),
		'bookings' => array(
			'url'   => admin_url( 'post-new.php?post_type=venue_booking' ),
			'label' => __( 'Add Booking', 'venuestack-core' ),
		),
		'packages' => array(
			'url'   => admin_url( 'post-new.php?post_type=event_package' ),
			'label' => __( 'Add Package', 'venuestack-core' ),
		),
	);
	$new_link  = $new_links[ $screen ] ?? $new_links['spaces'];
	?>
	<div class="wrap venuestack-dataviews-wrap">
		<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>
		<a href="<?php echo esc_url( $new_link['url'] ); ?>" class="page-title-action">
			<?php echo esc_html( $new_link['label'] ); ?>
		</a>
		<hr class="wp-header-end" />
		<div
			id="venuestack-dataviews-root"
			class="venuestack-dataviews-root"
			data-screen="<?php echo esc_attr( $screen ); ?>"
		></div>
	</div>
	<?php
}

/**
 * Enqueue Data Views assets on inventory screens only.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function venuestack_core_enqueue_dataviews_assets( string $hook_suffix ): void {
	$allowed = array(
		'venue_space_page_venuestack-spaces-inventory',
		'venue_booking_page_venuestack-bookings-inventory',
		'event_package_page_venuestack-packages-inventory',
	);

	if ( ! in_array( $hook_suffix, $allowed, true ) ) {
		return;
	}

	$script = VENUESTACK_CORE_PATH . 'build/dataviews.js';
	$asset  = VENUESTACK_CORE_PATH . 'build/dataviews.asset.php';

	if ( ! file_exists( $script ) || ! file_exists( $asset ) ) {
		return;
	}

	$meta = include $asset;
	$deps = array_values(
		array_filter(
			$meta['dependencies'] ?? array(),
			static function ( $dep ): bool {
				return is_string( $dep ) && ! str_contains( $dep, '.css' );
			}
		)
	);

	$styles = array(
		array(
			'handle' => 'venuestack-dataviews-package',
			'file'   => 'assets/admin/dataviews-package.css',
		),
		array(
			'handle' => 'venuestack-dataviews',
			'file'   => 'build/dataviews.css',
		),
		array(
			'handle' => 'venuestack-dataviews-chrome',
			'file'   => 'build/style-dataviews.css',
		),
	);

	$style_deps = array( 'wp-components' );
	foreach ( $styles as $style ) {
		$path = VENUESTACK_CORE_PATH . $style['file'];
		if ( ! file_exists( $path ) ) {
			continue;
		}

		wp_enqueue_style(
			$style['handle'],
			VENUESTACK_CORE_URL . $style['file'],
			$style_deps,
			$meta['version'] ?? VENUESTACK_CORE_VERSION
		);
		$style_deps = array( $style['handle'] );
	}

	wp_enqueue_script(
		'venuestack-dataviews',
		VENUESTACK_CORE_URL . 'build/dataviews.js',
		$deps,
		$meta['version'] ?? VENUESTACK_CORE_VERSION,
		array( 'in_footer' => true )
	);

	$spaces = get_posts(
		array(
			'post_type'              => 'venue_space',
			'post_status'            => 'publish',
			'posts_per_page'         => 100,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$space_options = array();
	foreach ( $spaces as $space ) {
		$space_options[] = array(
			'value' => (string) $space->ID,
			'label' => get_the_title( $space ),
		);
	}

	$screen = 'spaces';
	if ( false !== strpos( $hook_suffix, 'bookings' ) ) {
		$screen = 'bookings';
	} elseif ( false !== strpos( $hook_suffix, 'packages' ) ) {
		$screen = 'packages';
	}

	wp_localize_script(
		'venuestack-dataviews',
		'venuestackDataViews',
		array(
			'screen'       => $screen,
			'timezone'     => wp_timezone_string(),
			'adminUrl'     => esc_url_raw( admin_url() ),
			'newSpaceUrl'  => esc_url_raw( admin_url( 'post-new.php?post_type=venue_space' ) ),
			'editPostUrl'  => esc_url_raw( admin_url( 'post.php?post=%d&action=edit' ) ),
			'orderEditUrl' => esc_url_raw( admin_url( 'admin.php?page=wc-orders&action=edit&id=' ) ),
			'spaces'       => $space_options,
		)
	);
}
add_action( 'admin_enqueue_scripts', 'venuestack_core_enqueue_dataviews_assets' );
