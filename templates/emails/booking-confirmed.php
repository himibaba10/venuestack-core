<?php
/**
 * Booking confirmed email (HTML).
 *
 * @var array<string, mixed> $context
 * @var array<string, string> $brand
 * @var string $type
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

$headline = __( 'Booking confirmed', 'venuestack-core' );
$intro    = __( 'Your venue booking is confirmed. Here are the details:', 'venuestack-core' );

include venuestack_core_get_email_template_path( 'layout.php' );
