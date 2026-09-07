<?php
/**
 * Booking hold expired email (HTML).
 *
 * @var array<string, mixed> $context
 * @var array<string, string> $brand
 * @var string $type
 *
 * @package VenuestackCore
 */

defined( 'ABSPATH' ) || exit;

$headline = __( 'Hold expired', 'venuestack-core' );
$intro    = __(
	'Your temporary booking hold expired before payment was completed, so the time slot was released.',
	'venuestack-core'
);

include venuestack_core_get_email_template_path( 'layout.php' );
