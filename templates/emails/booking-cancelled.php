<?php
/**
 * Booking cancelled email (HTML).
 *
 * @var array<string, mixed> $context
 * @var array<string, string> $brand
 * @var string $type
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

$headline = __( 'Booking cancelled', 'venuestack-core' );
$intro    = __( 'Your venue booking has been cancelled.', 'venuestack-core' );

include venuestack_core_get_email_template_path( 'layout.php' );
