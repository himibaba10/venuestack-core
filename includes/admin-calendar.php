<?php
/**
 * Admin bookings calendar (FullCalendar) + REST event feed.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register Calendar submenu under Bookings.
 */
function venuestack_core_register_calendar_menu(): void {
	add_submenu_page(
		'edit.php?post_type=venue_booking',
		__( 'Bookings Calendar', 'venuestack-core' ),
		__( 'Calendar', 'venuestack-core' ),
		'edit_posts',
		'venuestack-bookings-calendar',
		'venuestack_core_render_calendar_page'
	);
}
add_action( 'admin_menu', 'venuestack_core_register_calendar_menu' );

/**
 * Render the calendar admin page shell.
 */
function venuestack_core_render_calendar_page(): void {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to view bookings.', 'venuestack-core' ) );
	}
	?>
	<div class="wrap venuestack-admin-calendar">
		<h1><?php echo esc_html__( 'Bookings Calendar', 'venuestack-core' ); ?></h1>
		<p class="description">
			<?php echo esc_html__( 'Confirmed bookings and active soft-holds across venue spaces. Times shown in the site timezone.', 'venuestack-core' ); ?>
		</p>
		<div class="venuestack-admin-calendar__toolbar">
			<label for="venuestack-calendar-space">
				<?php echo esc_html__( 'Space', 'venuestack-core' ); ?>
			</label>
			<select id="venuestack-calendar-space" class="venuestack-admin-calendar__space">
				<option value="0"><?php echo esc_html__( 'All spaces', 'venuestack-core' ); ?></option>
			</select>
			<ul class="venuestack-admin-calendar__legend" aria-label="<?php echo esc_attr__( 'Status legend', 'venuestack-core' ); ?>">
				<li><span class="is-confirmed"></span><?php echo esc_html__( 'Confirmed', 'venuestack-core' ); ?></li>
				<li><span class="is-hold"></span><?php echo esc_html__( 'Hold', 'venuestack-core' ); ?></li>
				<li><span class="is-pending"></span><?php echo esc_html__( 'Pending', 'venuestack-core' ); ?></li>
				<li><span class="is-cancelled"></span><?php echo esc_html__( 'Cancelled', 'venuestack-core' ); ?></li>
			</ul>
		</div>
		<div id="venuestack-bookings-calendar" class="venuestack-admin-calendar__mount"></div>
	</div>
	<?php
}

/**
 * Enqueue FullCalendar assets on the calendar admin screen only.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function venuestack_core_enqueue_calendar_assets( string $hook_suffix ): void {
	if ( 'venue_booking_page_venuestack-bookings-calendar' !== $hook_suffix ) {
		return;
	}

	$script = VENUESTACK_CORE_PATH . 'build/bookings-calendar.js';
	$asset  = VENUESTACK_CORE_PATH . 'build/bookings-calendar.asset.php';

	if ( ! file_exists( $script ) || ! file_exists( $asset ) ) {
		return;
	}

	$meta = include $asset;

	$style = VENUESTACK_CORE_PATH . 'build/bookings-calendar.css';
	if ( file_exists( $style ) ) {
		wp_enqueue_style(
			'venuestack-bookings-calendar',
			VENUESTACK_CORE_URL . 'build/bookings-calendar.css',
			array(),
			$meta['version'] ?? VENUESTACK_CORE_VERSION
		);
	}

	wp_enqueue_script(
		'venuestack-bookings-calendar',
		VENUESTACK_CORE_URL . 'build/bookings-calendar.js',
		$meta['dependencies'] ?? array(),
		$meta['version'] ?? VENUESTACK_CORE_VERSION,
		true
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
			'id'   => (int) $space->ID,
			'name' => get_the_title( $space ),
		);
	}

	wp_localize_script(
		'venuestack-bookings-calendar',
		'venuestackBookingsCalendar',
		array(
			'restUrl'  => esc_url_raw( rest_url( 'venuestack/admin/calendar' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'timezone' => wp_timezone_string(),
			'spaces'   => $space_options,
			'i18n'     => array(
				'loading' => __( 'Loading bookings…', 'venuestack-core' ),
				'error'   => __( 'Could not load bookings.', 'venuestack-core' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'venuestack_core_enqueue_calendar_assets' );

/**
 * Register admin calendar REST route.
 */
function venuestack_core_register_calendar_routes(): void {
	register_rest_route(
		'venuestack',
		'/admin/calendar',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'venuestack_core_rest_admin_calendar',
			'permission_callback' => static function (): bool {
				return current_user_can( 'edit_posts' );
			},
			'args'                => array(
				'start'    => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'end'      => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'space_id' => array(
					'type'              => 'integer',
					'required'          => false,
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'venuestack_core_register_calendar_routes' );

/**
 * Map booking status to calendar colors (VenueStack tokens).
 *
 * @param string $status Booking status.
 * @return array{backgroundColor:string,borderColor:string,textColor:string}
 */
function venuestack_core_calendar_status_colors( string $status ): array {
	switch ( $status ) {
		case 'confirmed':
			return array(
				'backgroundColor' => '#2F3B34',
				'borderColor'     => '#2F3B34',
				'textColor'       => '#F2ECE3',
			);
		case 'hold':
			return array(
				'backgroundColor' => '#A6763D',
				'borderColor'     => '#A6763D',
				'textColor'       => '#F2ECE3',
			);
		case 'cancelled':
			return array(
				'backgroundColor' => '#E4DACB',
				'borderColor'     => '#C4B5A0',
				'textColor'       => '#6B635A',
			);
		case 'pending':
		default:
			return array(
				'backgroundColor' => '#C9A06B',
				'borderColor'     => '#A6763D',
				'textColor'       => '#211D1B',
			);
	}
}

/**
 * REST: FullCalendar event feed for admin.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function venuestack_core_rest_admin_calendar( WP_REST_Request $request ) {
	$range_start = venuestack_core_parse_to_utc_timestamp( (string) $request['start'] );
	$range_end   = venuestack_core_parse_to_utc_timestamp( (string) $request['end'] );

	if ( is_wp_error( $range_start ) ) {
		return $range_start;
	}
	if ( is_wp_error( $range_end ) ) {
		return $range_end;
	}
	if ( $range_end <= $range_start ) {
		return new WP_Error(
			'venuestack_invalid_range',
			__( 'Calendar end must be after start.', 'venuestack-core' ),
			array( 'status' => 400 )
		);
	}

	$space_id = (int) $request['space_id'];

	$meta_query = array(
		'relation' => 'AND',
		array(
			'key'     => 'start_datetime',
			'value'   => $range_end,
			'compare' => '<',
			'type'    => 'NUMERIC',
		),
		array(
			'key'     => 'end_datetime',
			'value'   => $range_start,
			'compare' => '>',
			'type'    => 'NUMERIC',
		),
	);

	if ( $space_id > 0 ) {
		$meta_query[] = array(
			'key'     => 'space_id',
			'value'   => $space_id,
			'compare' => '=',
			'type'    => 'NUMERIC',
		);
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'venue_booking',
			'post_status'            => 'publish',
			'posts_per_page'         => 500,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
			'meta_query'             => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		)
	);

	$hold_cutoff = time() - VENUESTACK_HOLD_TTL;
	$events      = array();
	$space_cache = array();

	foreach ( $query->posts as $booking ) {
		if ( ! $booking instanceof WP_Post ) {
			continue;
		}

		$status = (string) get_post_meta( $booking->ID, 'status', true );
		if ( ! in_array( $status, array( 'confirmed', 'hold', 'pending', 'cancelled' ), true ) ) {
			$status = 'pending';
		}

		if ( 'hold' === $status ) {
			$created = strtotime( $booking->post_date_gmt . ' UTC' );
			if ( ! $created || $created < $hold_cutoff ) {
				continue;
			}
		}

		$start_ts = (int) get_post_meta( $booking->ID, 'start_datetime', true );
		$end_ts   = (int) get_post_meta( $booking->ID, 'end_datetime', true );
		if ( $start_ts < 1 || $end_ts <= $start_ts ) {
			continue;
		}

		$booking_space = (int) get_post_meta( $booking->ID, 'space_id', true );
		if ( ! isset( $space_cache[ $booking_space ] ) ) {
			$space_cache[ $booking_space ] = $booking_space > 0
				? get_the_title( $booking_space )
				: __( 'Unknown space', 'venuestack-core' );
		}
		$space_name = (string) $space_cache[ $booking_space ];

		$order_id = (int) get_post_meta( $booking->ID, 'wc_order_id', true );
		$colors   = venuestack_core_calendar_status_colors( $status );

		$title = sprintf(
			/* translators: 1: space name, 2: booking status label */
			__( '%1$s — %2$s', 'venuestack-core' ),
			$space_name,
			ucfirst( $status )
		);

		$events[] = array_merge(
			array(
				'id'            => (string) $booking->ID,
				'title'         => $title,
				'start'         => gmdate( 'c', $start_ts ),
				'end'           => gmdate( 'c', $end_ts ),
				'url'           => get_edit_post_link( $booking->ID, 'raw' ),
				'extendedProps' => array(
					'status'  => $status,
					'spaceId' => $booking_space,
					'orderId' => $order_id,
				),
			),
			$colors
		);
	}

	return rest_ensure_response( $events );
}
