<?php
/**
 * Hold tokens, rate limits, and named locks.
 *
 * @package VenuestackCore
 */

defined('ABSPATH') || exit;

/** Max soft-holds per IP within the rate window. */
const VENUESTACK_HOLD_RATE_LIMIT = 20;

/** Hold rate-limit window (seconds). */
const VENUESTACK_HOLD_RATE_WINDOW = 15 * MINUTE_IN_SECONDS;

/** Space / booking mutex lifetime in seconds. */
const VENUESTACK_MUTEX_TTL = 10;

/**
 * Acquire a short-lived named lock via add_option (atomic on unique name).
 */
function venuestack_core_acquire_named_lock(string $name, int $ttl): bool
{
	$option = 'venuestack_lock_' . $name;
	$now = time();
	$existing = get_option($option);

	if (is_array($existing) && !empty($existing['until']) && (int) $existing['until'] > $now) {
		return false;
	}

	if (false !== $existing) {
		delete_option($option);
	}

	return (bool) add_option(
		$option,
		array('until' => $now + $ttl),
		'',
		false
	);
}

/**
 * Release a named lock.
 */
function venuestack_core_release_named_lock(string $name): void
{
	delete_option('venuestack_lock_' . $name);
}

/**
 * Try to acquire a short-lived mutex for a space.
 */
function venuestack_core_acquire_space_lock(int $space_id): bool
{
	return venuestack_core_acquire_named_lock('space_' . $space_id, VENUESTACK_MUTEX_TTL);
}

/**
 * Release the space mutex.
 */
function venuestack_core_release_space_lock(int $space_id): void
{
	venuestack_core_release_named_lock('space_' . $space_id);
}

/**
 * Try to acquire a short-lived mutex for a booking checkout.
 */
function venuestack_core_acquire_booking_lock(int $booking_id): bool
{
	return venuestack_core_acquire_named_lock('booking_' . $booking_id, VENUESTACK_MUTEX_TTL);
}

/**
 * Release the booking checkout mutex.
 */
function venuestack_core_release_booking_lock(int $booking_id): void
{
	venuestack_core_release_named_lock('booking_' . $booking_id);
}

/**
 * Secret used to sign hold tokens (auto-generated once).
 */
function venuestack_core_hold_token_secret(): string
{
	$secret = get_option('venuestack_hold_token_secret', '');

	if (!is_string($secret) || strlen($secret) < 32) {
		$secret = wp_generate_password(64, true, true);
		update_option('venuestack_hold_token_secret', $secret, false);
	}

	return $secret;
}

/**
 * Create a signed hold token (booking_id|expires.hmac).
 */
function venuestack_core_create_hold_token(int $booking_id, int $expires_at): string
{
	$payload = "$booking_id|$expires_at";
	$sig = hash_hmac('sha256', $payload, venuestack_core_hold_token_secret());

	return "$payload.$sig";
}

/**
 * Verify hold token for a booking (signature + expiry + id match).
 */
function venuestack_core_verify_hold_token(int $booking_id, string $token): bool
{
	$token = trim($token);
	$parts = explode('.', $token, 2);

	if (2 !== count($parts)) {
		return false;
	}

	[$payload, $sig] = $parts;
	$payload_parts = explode('|', $payload, 2);

	if (2 !== count($payload_parts)) {
		return false;
	}

	[$id_raw, $expires_raw] = $payload_parts;
	$id = (int) $id_raw;
	$expires = (int) $expires_raw;

	if ($id !== $booking_id || $expires < time()) {
		return false;
	}

	$expected = hash_hmac('sha256', $payload, venuestack_core_hold_token_secret());

	return hash_equals($expected, $sig);
}

/**
 * Best-effort client IP for rate limiting.
 */
function venuestack_core_client_ip(): string
{
	if (!empty($_SERVER['REMOTE_ADDR'])) {
		return sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR']));
	}

	return '0.0.0.0';
}

/**
 * Enforce soft-hold creation rate limit per IP.
 *
 * @return true|\WP_Error
 */
function venuestack_core_rate_limit_holds()
{
	$ip = venuestack_core_client_ip();
	$key = 'venuestack_hold_rl_' . md5($ip);
	$now = time();
	$data = get_transient($key);

	if (!is_array($data) || empty($data['start']) || ($now - (int) $data['start']) >= VENUESTACK_HOLD_RATE_WINDOW) {
		$data = [
			'count' => 0,
			'start' => $now,
		];
	}

	if ((int) $data['count'] >= VENUESTACK_HOLD_RATE_LIMIT) {
		return new WP_Error(
			'venuestack_rate_limited',
			__('Too many hold attempts. Try again later.', 'venuestack-core'),
			['status' => 429]
		);
	}

	++$data['count'];
	$ttl = VENUESTACK_HOLD_RATE_WINDOW - ($now - (int) $data['start']);
	set_transient($key, $data, max(1, $ttl));

	return true;
}
