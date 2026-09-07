<?php
/**
 * Admin UI for venue_booking: detail metabox + list columns.
 *
 * Guest/package/money stay on the Woo order; this screen joins via wc_order_id.
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the Booking details metabox.
 */
function venuestack_core_register_booking_metaboxes(): void {
	add_meta_box(
		'venuestack_booking_details',
		__( 'Booking details', 'venuestack-core' ),
		'venuestack_core_render_booking_details_metabox',
		'venue_booking',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_venue_booking', 'venuestack_core_register_booking_metaboxes' );

/**
 * Format a UTC Unix timestamp for admin display (site TZ + UTC footnote).
 *
 * @param int $utc Unix timestamp (UTC).
 */
function venuestack_core_format_booking_datetime_admin( int $utc ): string {
	if ( $utc < 1 ) {
		return '—';
	}

	$local = wp_date( 'M j, Y g:i a', $utc );
	$utc_s = gmdate( 'Y-m-d H:i', $utc ) . ' UTC';

	return sprintf( '%s <span class="description">(%s)</span>', esc_html( $local ), esc_html( $utc_s ) );
}

/**
 * Human label for booking status.
 *
 * @param string $status Status slug.
 */
function venuestack_core_booking_status_label( string $status ): string {
	$labels = array(
		'hold'      => __( 'Hold', 'venuestack-core' ),
		'confirmed' => __( 'Confirmed', 'venuestack-core' ),
		'pending'   => __( 'Pending', 'venuestack-core' ),
		'cancelled' => __( 'Cancelled', 'venuestack-core' ),
	);

	return $labels[ $status ] ?? $status;
}

/**
 * Collect display rows for a booking (booking meta + optional WC order).
 *
 * @param int $booking_id Booking post ID.
 * @return array<int, array{label:string,value:string,html?:bool}>
 */
function venuestack_core_get_booking_admin_rows( int $booking_id ): array {
	$status    = (string) get_post_meta( $booking_id, 'status', true );
	$space_id  = (int) get_post_meta( $booking_id, 'space_id', true );
	$start_utc = (int) get_post_meta( $booking_id, 'start_datetime', true );
	$end_utc   = (int) get_post_meta( $booking_id, 'end_datetime', true );
	$order_id  = (int) get_post_meta( $booking_id, 'wc_order_id', true );

	$space_html = '—';
	if ( $space_id > 0 ) {
		$title = get_the_title( $space_id );
		$link  = get_edit_post_link( $space_id, 'raw' );
		if ( $link ) {
			$space_html = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $link ),
				esc_html( $title ?: (string) $space_id )
			);
		} else {
			$space_html = esc_html( $title ?: (string) $space_id );
		}
	}

	$rows = array(
		array(
			'label' => __( 'Status', 'venuestack-core' ),
			'value' => esc_html( venuestack_core_booking_status_label( $status ?: 'pending' ) ),
			'html'  => true,
		),
		array(
			'label' => __( 'Space', 'venuestack-core' ),
			'value' => $space_html,
			'html'  => true,
		),
		array(
			'label' => __( 'Starts', 'venuestack-core' ),
			'value' => venuestack_core_format_booking_datetime_admin( $start_utc ),
			'html'  => true,
		),
		array(
			'label' => __( 'Ends', 'venuestack-core' ),
			'value' => venuestack_core_format_booking_datetime_admin( $end_utc ),
			'html'  => true,
		),
	);

	$order = null;
	if ( $order_id > 0 && function_exists( 'wc_get_order' ) ) {
		$maybe = wc_get_order( $order_id );
		if ( $maybe instanceof WC_Order ) {
			$order = $maybe;
		}
	}

	if ( ! $order instanceof WC_Order ) {
		$rows[] = array(
			'label' => __( 'Order', 'venuestack-core' ),
			'value' => $order_id > 0
				? sprintf(
					/* translators: %d: missing WooCommerce order ID */
					esc_html__( 'Order #%d (missing)', 'venuestack-core' ),
					$order_id
				)
				: esc_html__( 'No order yet (active hold or abandoned checkout)', 'venuestack-core' ),
			'html'  => true,
		);
		return $rows;
	}

	$package_id = (int) $order->get_meta( '_venuestack_package_id' );
	$headcount  = (int) $order->get_meta( '_venuestack_headcount' );

	$package_html = '—';
	if ( $package_id > 0 ) {
		$pkg_title = get_the_title( $package_id );
		$pkg_link  = get_edit_post_link( $package_id, 'raw' );
		if ( $pkg_link ) {
			$package_html = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $pkg_link ),
				esc_html( $pkg_title ?: (string) $package_id )
			);
		} else {
			$package_html = esc_html( $pkg_title ?: (string) $package_id );
		}
	}

	$name = trim( $order->get_formatted_billing_full_name() );
	if ( '' === $name ) {
		$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
	}
	$email = $order->get_billing_email();

	$customer = '—';
	if ( $name || $email ) {
		$customer = esc_html( $name ?: __( 'Guest', 'venuestack-core' ) );
		if ( $email ) {
			$customer .= sprintf(
				'<br><a href="mailto:%1$s">%2$s</a>',
				esc_attr( $email ),
				esc_html( $email )
			);
		}
	}

	$order_link = $order->get_edit_order_url();
	$order_html = sprintf(
		'<a href="%1$s">%2$s</a> <span class="description">(%3$s)</span>',
		esc_url( $order_link ),
		esc_html(
			sprintf(
				/* translators: %d: WooCommerce order ID */
				__( 'Order #%d', 'venuestack-core' ),
				$order->get_id()
			)
		),
		esc_html( wc_get_order_status_name( $order->get_status() ) )
	);

	$rows[] = array(
		'label' => __( 'Package', 'venuestack-core' ),
		'value' => $package_html,
		'html'  => true,
	);
	$rows[] = array(
		'label' => __( 'Guests', 'venuestack-core' ),
		'value' => $headcount > 0 ? esc_html( (string) $headcount ) : '—',
		'html'  => true,
	);
	$rows[] = array(
		'label' => __( 'Customer', 'venuestack-core' ),
		'value' => $customer,
		'html'  => true,
	);
	$rows[] = array(
		'label' => __( 'Total', 'venuestack-core' ),
		'value' => wp_kses_post( $order->get_formatted_order_total() ),
		'html'  => true,
	);
	$rows[] = array(
		'label' => __( 'Payment', 'venuestack-core' ),
		'value' => esc_html( $order->get_payment_method_title() ?: '—' ),
		'html'  => true,
	);
	$rows[] = array(
		'label' => __( 'Order', 'venuestack-core' ),
		'value' => $order_html,
		'html'  => true,
	);

	return $rows;
}

/**
 * Build a nonce-protected cancel URL for a booking.
 *
 * @param int         $booking_id Booking ID.
 * @param string|null $redirect   Optional redirect URL after cancel.
 */
function venuestack_core_get_cancel_booking_url( int $booking_id, ?string $redirect = null ): string {
	$args = array(
		'action'     => 'venuestack_cancel_booking',
		'booking_id' => $booking_id,
	);

	if ( is_string( $redirect ) && '' !== $redirect ) {
		$args['redirect'] = $redirect;
	}

	return wp_nonce_url(
		add_query_arg( $args, admin_url( 'admin-post.php' ) ),
		'venuestack_cancel_booking'
	);
}

/**
 * Handle admin cancel booking requests.
 */
function venuestack_core_handle_cancel_booking_request(): void {
	$booking_id = isset( $_REQUEST['booking_id'] ) ? absint( wp_unslash( $_REQUEST['booking_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	check_admin_referer( 'venuestack_cancel_booking' );

	if ( $booking_id < 1 || ! current_user_can( 'edit_post', $booking_id ) ) {
		wp_die( esc_html__( 'You do not have permission to cancel this booking.', 'venuestack-core' ) );
	}

	$result = venuestack_core_cancel_booking(
		$booking_id,
		array(
			'sync_order' => true,
			'send_email' => true,
			'order_note' => __( 'Booking cancelled from VenueStack admin.', 'venuestack-core' ),
		)
	);

	$redirect = isset( $_GET['redirect'] ) ? esc_url_raw( wp_unslash( $_GET['redirect'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( '' === $redirect ) {
		$redirect = get_edit_post_link( $booking_id, 'raw' ) ?: admin_url( 'edit.php?post_type=venue_booking&page=venuestack-bookings-inventory' );
	}

	$redirect = add_query_arg(
		array(
			'venuestack_cancelled' => is_wp_error( $result ) ? '0' : '1',
			'venuestack_cancel_msg' => is_wp_error( $result ) ? rawurlencode( $result->get_error_message() ) : '',
		),
		$redirect
	);

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_venuestack_cancel_booking', 'venuestack_core_handle_cancel_booking_request' );

/**
 * Admin notice after cancel attempt.
 */
function venuestack_core_cancel_booking_admin_notice(): void {
	if ( ! isset( $_GET['venuestack_cancelled'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$ok = '1' === (string) wp_unslash( $_GET['venuestack_cancelled'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( $ok ) {
		echo '<div class="notice notice-success is-dismissible"><p>'
			. esc_html__( 'Booking cancelled. Linked WooCommerce order was cancelled when possible, and the customer was emailed.', 'venuestack-core' )
			. '</p></div>';
		return;
	}

	$msg = isset( $_GET['venuestack_cancel_msg'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		? sanitize_text_field( rawurldecode( wp_unslash( $_GET['venuestack_cancel_msg'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: __( 'Could not cancel booking.', 'venuestack-core' );

	echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
}
add_action( 'admin_notices', 'venuestack_core_cancel_booking_admin_notice' );

/**
 * Render the Booking details metabox.
 *
 * @param WP_Post $post Booking post.
 */
function venuestack_core_render_booking_details_metabox( WP_Post $post ): void {
	$booking_id = (int) $post->ID;
	$status     = (string) get_post_meta( $booking_id, 'status', true );
	$rows       = venuestack_core_get_booking_admin_rows( $booking_id );
	?>
	<table class="form-table venuestack-booking-details" role="presentation">
		<tbody>
			<?php foreach ( $rows as $row ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
					<td>
						<?php
						echo ! empty( $row['html'] )
							? $row['value'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built.
							: esc_html( $row['value'] );
						?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p class="description">
		<?php echo esc_html__( 'Guest, package, and payment details come from the linked WooCommerce order.', 'venuestack-core' ); ?>
	</p>
	<?php if ( venuestack_core_booking_can_cancel( $status ?: 'pending' ) ) : ?>
		<p>
			<a
				class="button button-secondary"
				style="color:#b32d2e;border-color:#b32d2e;"
				href="<?php echo esc_url( venuestack_core_get_cancel_booking_url( $booking_id ) ); ?>"
				onclick="return confirm('<?php echo esc_js( __( 'Cancel this booking and the linked WooCommerce order? The customer will be emailed.', 'venuestack-core' ) ); ?>');"
			>
				<?php echo esc_html__( 'Cancel booking', 'venuestack-core' ); ?>
			</a>
		</p>
	<?php endif; ?>
	<?php
}

/**
 * Row action: Cancel on classic booking list screens.
 *
 * @param array<string,string> $actions Actions.
 * @param WP_Post              $post    Post.
 * @return array<string,string>
 */
function venuestack_core_booking_row_actions( array $actions, WP_Post $post ): array {
	if ( 'venue_booking' !== $post->post_type ) {
		return $actions;
	}

	$status = (string) get_post_meta( $post->ID, 'status', true );
	if ( ! venuestack_core_booking_can_cancel( $status ?: 'pending' ) ) {
		return $actions;
	}

	$actions['venuestack_cancel'] = sprintf(
		'<a href="%1$s" style="color:#b32d2e;" onclick="return confirm(\'%3$s\');">%2$s</a>',
		esc_url( venuestack_core_get_cancel_booking_url( (int) $post->ID ) ),
		esc_html__( 'Cancel', 'venuestack-core' ),
		esc_js( __( 'Cancel this booking and the linked WooCommerce order?', 'venuestack-core' ) )
	);

	return $actions;
}
add_filter( 'post_row_actions', 'venuestack_core_booking_row_actions', 10, 2 );

/**
 * Customize venue_booking list columns.
 *
 * @param array<string,string> $columns Columns.
 * @return array<string,string>
 */
function venuestack_core_booking_list_columns( array $columns ): array {
	$new = array();

	if ( isset( $columns['cb'] ) ) {
		$new['cb'] = $columns['cb'];
	}

	$new['title']                = __( 'Booking', 'venuestack-core' );
	$new['venuestack_status']    = __( 'Status', 'venuestack-core' );
	$new['venuestack_space']     = __( 'Space', 'venuestack-core' );
	$new['venuestack_when']      = __( 'When', 'venuestack-core' );
	$new['venuestack_customer']  = __( 'Customer', 'venuestack-core' );
	$new['venuestack_order']     = __( 'Order', 'venuestack-core' );
	$new['date']                 = __( 'Created', 'venuestack-core' );

	return $new;
}
add_filter( 'manage_venue_booking_posts_columns', 'venuestack_core_booking_list_columns' );

/**
 * Render custom venue_booking list column cells.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function venuestack_core_booking_list_column_content( string $column, int $post_id ): void {
	switch ( $column ) {
		case 'venuestack_status':
			$status = (string) get_post_meta( $post_id, 'status', true );
			echo esc_html( venuestack_core_booking_status_label( $status ?: 'pending' ) );
			break;

		case 'venuestack_space':
			$space_id = (int) get_post_meta( $post_id, 'space_id', true );
			if ( $space_id < 1 ) {
				echo '—';
				break;
			}
			$title = get_the_title( $space_id );
			$link  = get_edit_post_link( $space_id, 'raw' );
			if ( $link ) {
				printf(
					'<a href="%s">%s</a>',
					esc_url( $link ),
					esc_html( $title ?: (string) $space_id )
				);
			} else {
				echo esc_html( $title ?: (string) $space_id );
			}
			break;

		case 'venuestack_when':
			$start = (int) get_post_meta( $post_id, 'start_datetime', true );
			if ( $start < 1 ) {
				echo '—';
				break;
			}
			echo esc_html( wp_date( 'M j, Y g:i a', $start ) );
			break;

		case 'venuestack_customer':
			$order_id = (int) get_post_meta( $post_id, 'wc_order_id', true );
			if ( $order_id < 1 || ! function_exists( 'wc_get_order' ) ) {
				echo '—';
				break;
			}
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				echo '—';
				break;
			}
			$name  = trim( $order->get_formatted_billing_full_name() );
			$email = $order->get_billing_email();
			if ( $name ) {
				echo esc_html( $name );
			} elseif ( $email ) {
				echo esc_html( $email );
			} else {
				echo '—';
			}
			break;

		case 'venuestack_order':
			$order_id = (int) get_post_meta( $post_id, 'wc_order_id', true );
			if ( $order_id < 1 || ! function_exists( 'wc_get_order' ) ) {
				echo '—';
				break;
			}
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				echo esc_html( '#' . $order_id );
				break;
			}
			printf(
				'<a href="%s">#%d</a>',
				esc_url( $order->get_edit_order_url() ),
				(int) $order->get_id()
			);
			break;
	}
}
add_action( 'manage_venue_booking_posts_custom_column', 'venuestack_core_booking_list_column_content', 10, 2 );

/**
 * Make Status and When sortable later-friendly (meta only; no complex joins).
 *
 * @param array<string,string> $columns Sortable columns.
 * @return array<string,string>
 */
function venuestack_core_booking_sortable_columns( array $columns ): array {
	$columns['venuestack_status'] = 'venuestack_status';
	$columns['venuestack_when']   = 'venuestack_when';
	return $columns;
}
add_filter( 'manage_edit-venue_booking_sortable_columns', 'venuestack_core_booking_sortable_columns' );

/**
 * Apply sorting for custom booking list columns.
 *
 * @param WP_Query $query Query.
 */
function venuestack_core_booking_list_orderby( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$post_type = $query->get( 'post_type' );
	if ( 'venue_booking' !== $post_type ) {
		return;
	}

	$orderby = $query->get( 'orderby' );
	if ( 'venuestack_status' === $orderby ) {
		$query->set( 'meta_key', 'status' );
		$query->set( 'orderby', 'meta_value' );
	} elseif ( 'venuestack_when' === $orderby ) {
		$query->set( 'meta_key', 'start_datetime' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
add_action( 'pre_get_posts', 'venuestack_core_booking_list_orderby' );
