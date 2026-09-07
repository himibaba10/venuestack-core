<?php
/**
 * Booking notification emails (confirmed, cancelled, hold expired).
 *
 * HTML bodies live in templates/emails/ and can be overridden from the theme at:
 * yourtheme/venuestack-core/emails/{template}.php
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Format a UTC Unix timestamp for customer-facing email copy (site timezone).
 *
 * @param int $utc Unix timestamp (UTC).
 */
function venuestack_core_format_booking_email_datetime( int $utc ): string {
	if ( $utc < 1 ) {
		return '—';
	}

	return (string) wp_date( 'l, F j, Y g:i a', $utc );
}

/**
 * Brand colors for inline email styles (aligned with theme.json).
 *
 * @return array<string, string>
 */
function venuestack_core_email_brand_colors(): array {
	$brand = array(
		'ink'     => '#211D1B',
		'cream'   => '#F2ECE3',
		'surface' => '#F8F4ED',
		'stone'   => '#E4DACB',
		'accent'  => '#A6763D',
		'muted'   => '#857F78',
	);

	/**
	 * Filter VenueStack email brand colors.
	 *
	 * @param array<string, string> $brand Color map.
	 */
	return apply_filters( 'venuestack_email_brand_colors', $brand );
}

/**
 * Resolve an email template path (theme override wins).
 *
 * @param string $relative Relative path under emails/ (e.g. booking-confirmed.php).
 */
function venuestack_core_get_email_template_path( string $relative ): string {
	$relative = ltrim( str_replace( '\\', '/', $relative ), '/' );
	$theme    = trailingslashit( get_stylesheet_directory() ) . 'venuestack-core/emails/' . $relative;

	if ( is_readable( $theme ) ) {
		return $theme;
	}

	return VENUESTACK_CORE_PATH . 'templates/emails/' . $relative;
}

/**
 * Map email type → template filename.
 *
 * @param string $type Email type.
 */
function venuestack_core_booking_email_template_slug( string $type ): string {
	$map = array(
		'confirmed'    => 'booking-confirmed.php',
		'cancelled'    => 'booking-cancelled.php',
		'hold_expired' => 'booking-hold-expired.php',
	);

	return $map[ $type ] ?? '';
}

/**
 * Render a booking email HTML template.
 *
 * @param string               $type    Email type.
 * @param array<string, mixed> $context Template context.
 */
function venuestack_core_render_booking_email_html( string $type, array $context ): string {
	$slug = venuestack_core_booking_email_template_slug( $type );
	if ( '' === $slug ) {
		return '';
	}

	$path = venuestack_core_get_email_template_path( $slug );
	if ( ! is_readable( $path ) ) {
		return '';
	}

	$brand = venuestack_core_email_brand_colors();

	ob_start();
	// Templates expect $context, $brand, $type.
	include $path;
	return (string) ob_get_clean();
}

/**
 * Build shared template context for a booking email.
 *
 * @param int $booking_id Booking post ID.
 * @return array<string, mixed>|\WP_Error
 */
function venuestack_core_get_booking_email_context( int $booking_id ) {
	$booking = get_post( $booking_id );
	if ( ! $booking instanceof WP_Post || 'venue_booking' !== $booking->post_type ) {
		return new WP_Error( 'venuestack_email_booking', __( 'Booking not found.', 'venuestack-core' ) );
	}

	$space_id  = (int) get_post_meta( $booking_id, 'space_id', true );
	$start_utc = (int) get_post_meta( $booking_id, 'start_datetime', true );
	$end_utc   = (int) get_post_meta( $booking_id, 'end_datetime', true );
	$order_id  = (int) get_post_meta( $booking_id, 'wc_order_id', true );
	$status    = (string) get_post_meta( $booking_id, 'status', true );

	$space_title = $space_id > 0 ? get_the_title( $space_id ) : '';
	if ( '' === $space_title ) {
		$space_title = __( 'Space', 'venuestack-core' );
	}

	$email      = '';
	$first_name = '';
	$full_name  = '';
	$order      = null;

	if ( $order_id > 0 && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( $order_id );
		if ( $order instanceof WC_Order ) {
			$email      = (string) $order->get_billing_email();
			$first_name = (string) $order->get_billing_first_name();
			$full_name  = (string) $order->get_formatted_billing_full_name();
		}
	}

	$email = sanitize_email( $email );
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'venuestack_email_recipient', __( 'No customer email on the linked order.', 'venuestack-core' ) );
	}

	if ( '' === $full_name ) {
		$full_name = $first_name ?: __( 'there', 'venuestack-core' );
	}

	return array(
		'booking_id'   => $booking_id,
		'status'       => $status,
		'space_id'     => $space_id,
		'space_title'  => $space_title,
		'start_label'  => venuestack_core_format_booking_email_datetime( $start_utc ),
		'end_label'    => venuestack_core_format_booking_email_datetime( $end_utc ),
		'order_id'     => $order_id,
		'order_number' => ( $order instanceof WC_Order ) ? $order->get_order_number() : (string) $order_id,
		'email'        => $email,
		'first_name'   => $first_name,
		'full_name'    => $full_name,
		'site_name'    => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		'site_url'     => home_url( '/' ),
	);
}

/**
 * Subject lines by email type.
 *
 * @param string               $type    confirmed|cancelled|hold_expired.
 * @param array<string, mixed> $context Template context.
 */
function venuestack_core_booking_email_subject( string $type, array $context ): string {
	$site  = (string) ( $context['site_name'] ?? get_bloginfo( 'name' ) );
	$space = (string) ( $context['space_title'] ?? '' );

	switch ( $type ) {
		case 'confirmed':
			return sprintf(
				/* translators: 1: site name, 2: space title */
				__( '[%1$s] Booking confirmed — %2$s', 'venuestack-core' ),
				$site,
				$space
			);
		case 'cancelled':
			return sprintf(
				/* translators: 1: site name, 2: space title */
				__( '[%1$s] Booking cancelled — %2$s', 'venuestack-core' ),
				$site,
				$space
			);
		case 'hold_expired':
			return sprintf(
				/* translators: 1: site name, 2: space title */
				__( '[%1$s] Booking hold expired — %2$s', 'venuestack-core' ),
				$site,
				$space
			);
		default:
			return sprintf(
				/* translators: %s: site name */
				__( '[%s] Booking update', 'venuestack-core' ),
				$site
			);
	}
}

/**
 * Plain-text fallback body by email type.
 *
 * @param string               $type    Email type.
 * @param array<string, mixed> $context Template context.
 */
function venuestack_core_booking_email_body_text( string $type, array $context ): string {
	$name  = (string) ( $context['full_name'] ?? '' );
	$space = (string) ( $context['space_title'] ?? '' );
	$start = (string) ( $context['start_label'] ?? '' );
	$end   = (string) ( $context['end_label'] ?? '' );
	$order = (string) ( $context['order_number'] ?? '' );
	$site  = (string) ( $context['site_name'] ?? '' );

	$lines = array(
		sprintf(
			/* translators: %s: customer name */
			__( 'Hi %s,', 'venuestack-core' ),
			$name
		),
		'',
	);

	switch ( $type ) {
		case 'confirmed':
			$lines[] = __( 'Your venue booking is confirmed. Here are the details:', 'venuestack-core' );
			break;
		case 'cancelled':
			$lines[] = __( 'Your venue booking has been cancelled.', 'venuestack-core' );
			break;
		case 'hold_expired':
			$lines[] = __( 'Your temporary booking hold expired before payment was completed, so the time slot was released.', 'venuestack-core' );
			break;
	}

	$lines[] = '';
	$lines[] = sprintf(
		/* translators: %s: space title */
		__( 'Space: %s', 'venuestack-core' ),
		$space
	);
	$lines[] = sprintf(
		/* translators: %s: datetime */
		__( 'Starts: %s', 'venuestack-core' ),
		$start
	);
	$lines[] = sprintf(
		/* translators: %s: datetime */
		__( 'Ends: %s', 'venuestack-core' ),
		$end
	);

	if ( '' !== $order && '0' !== $order ) {
		$lines[] = sprintf(
			/* translators: %s: order number */
			__( 'Order: #%s', 'venuestack-core' ),
			$order
		);
	}

	$lines[] = '';
	$lines[] = __( 'Thanks,', 'venuestack-core' );
	$lines[] = $site;

	return implode( "\n", $lines );
}

/**
 * Meta key used to mark an email type as already sent for a booking.
 *
 * @param string $type Email type.
 */
function venuestack_core_booking_email_sent_meta_key( string $type ): string {
	return 'email_' . sanitize_key( $type ) . '_sent';
}

/**
 * Send a booking notification email (idempotent per type).
 *
 * @param string $type       confirmed|cancelled|hold_expired.
 * @param int    $booking_id Booking post ID.
 * @return bool True when mail was accepted by wp_mail.
 */
function venuestack_core_send_booking_email( string $type, int $booking_id ): bool {
	$allowed = array( 'confirmed', 'cancelled', 'hold_expired' );
	if ( ! in_array( $type, $allowed, true ) || $booking_id < 1 ) {
		return false;
	}

	/**
	 * Filter whether a booking email should send.
	 *
	 * @param bool   $send       Whether to send.
	 * @param string $type       Email type.
	 * @param int    $booking_id Booking ID.
	 */
	if ( ! apply_filters( 'venuestack_send_booking_email', true, $type, $booking_id ) ) {
		return false;
	}

	$sent_key = venuestack_core_booking_email_sent_meta_key( $type );
	if ( (int) get_post_meta( $booking_id, $sent_key, true ) === 1 ) {
		return false;
	}

	$context = venuestack_core_get_booking_email_context( $booking_id );
	if ( is_wp_error( $context ) ) {
		return false;
	}

	$to      = (string) $context['email'];
	$subject = venuestack_core_booking_email_subject( $type, $context );
	$text    = venuestack_core_booking_email_body_text( $type, $context );
	$html    = venuestack_core_render_booking_email_html( $type, $context );

	if ( '' === $html ) {
		$html = '<pre style="font-family:system-ui,sans-serif;white-space:pre-wrap;">'
			. esc_html( $text )
			. '</pre>';
	}

	/**
	 * Filters booking email arguments before send.
	 *
	 * @param array{to:string,subject:string,text:string,html:string} $mail Mail payload.
	 * @param string                                                   $type Email type.
	 * @param array<string, mixed>                                     $context Context.
	 */
	$mail = apply_filters(
		'venuestack_booking_email',
		array(
			'to'      => $to,
			'subject' => $subject,
			'text'    => $text,
			'html'    => $html,
		),
		$type,
		$context
	);

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	$sent = wp_mail(
		(string) $mail['to'],
		(string) $mail['subject'],
		(string) $mail['html'],
		$headers
	);

	if ( $sent ) {
		update_post_meta( $booking_id, $sent_key, 1 );
	}

	return (bool) $sent;
}

/**
 * @param int $booking_id Booking post ID.
 */
function venuestack_core_send_booking_confirmed_email( int $booking_id ): bool {
	return venuestack_core_send_booking_email( 'confirmed', $booking_id );
}

/**
 * @param int $booking_id Booking post ID.
 */
function venuestack_core_send_booking_cancelled_email( int $booking_id ): bool {
	return venuestack_core_send_booking_email( 'cancelled', $booking_id );
}

/**
 * @param int $booking_id Booking post ID.
 */
function venuestack_core_send_booking_hold_expired_email( int $booking_id ): bool {
	return venuestack_core_send_booking_email( 'hold_expired', $booking_id );
}
