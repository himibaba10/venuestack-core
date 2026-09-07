<?php
/**
 * Render callback: single-space booking panel (Interactivity).
 *
 * @package VenuestackCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

unset( $attributes, $content );

$space_id = 0;
if ( isset( $block->context['postId'] ) ) {
	$space_id = (int) $block->context['postId'];
}
if ( $space_id < 1 ) {
	$space_id = (int) get_the_ID();
}

if ( $space_id < 1 || 'venue_space' !== get_post_type( $space_id ) ) {
	return;
}

$hourly_rate = (float) get_post_meta( $space_id, 'hourly_rate', true );
$min_hours   = (int) get_post_meta( $space_id, 'minimum_booking_hours', true );
$max_cap     = (int) get_post_meta( $space_id, 'max_capacity', true );
$min_hours   = max( 1, $min_hours );

$timezone = wp_timezone_string();
$today    = wp_date( 'Y-m-d', null, wp_timezone() );

$is_logged_in = is_user_logged_in();
$after_auth   = get_permalink( $space_id ) ?: home_url( '/' );
$login_url    = wp_login_url( $after_auth );
$register_url = '';
if ( get_option( 'users_can_register' ) ) {
	$register_url = add_query_arg( 'redirect_to', $after_auth, wp_registration_url() );
}

$packages      = array();
$package_posts = get_posts(
	array(
		'post_type'              => 'event_package',
		'post_status'            => 'publish',
		'posts_per_page'         => 50,
		'orderby'                => 'title',
		'order'                  => 'ASC',
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
	)
);

foreach ( $package_posts as $package ) {
	$packages[] = array(
		'id'             => (int) $package->ID,
		'name'           => get_the_title( $package ),
		'price_per_head' => (float) get_post_meta( $package->ID, 'price_per_head', true ),
	);
}

$durations = array();
for ( $h = $min_hours; $h <= $min_hours + 8; $h++ ) {
	$durations[] = $h;
}

$busy_ranges = function_exists( 'venuestack_core_get_space_busy_ranges_for_panel' )
	? venuestack_core_get_space_busy_ranges_for_panel( $space_id, 120 )
	: array();

$user       = $is_logged_in ? wp_get_current_user() : null;
$first_name = $user instanceof WP_User ? (string) $user->first_name : '';
$last_name  = $user instanceof WP_User ? (string) $user->last_name : '';
$email      = $user instanceof WP_User ? (string) $user->user_email : '';

if ( function_exists( 'wp_interactivity_state' ) ) {
	wp_interactivity_state(
		'venuestack/booking-panel',
		array(
			'restUrl'     => esc_url_raw( rest_url( 'venuestack' ) ),
			'spaceId'     => $space_id,
			'timezone'    => $timezone,
			'hourlyRate'  => $hourly_rate,
			'minHours'    => $min_hours,
			'maxCapacity' => $max_cap,
			'packages'    => $packages,
			'busyRanges'  => $busy_ranges,
			'isLoggedIn'  => $is_logged_in,
			'isLoggedOut' => ! $is_logged_in,
			'isFormDisabled' => ! $is_logged_in,
			'isHoldDisabled' => ! $is_logged_in,
			'loginUrl'    => esc_url_raw( $login_url ),
			'registerUrl' => $register_url ? esc_url_raw( $register_url ) : '',
			'step'        => 'schedule',
			'date'        => $today,
			'time'        => '10:00',
			'hours'       => $min_hours,
			'headcount'   => $max_cap > 0 ? min( 20, $max_cap ) : 20,
			'packageId'   => 0,
			'busy'        => false,
			'error'       => '',
			'bookingId'   => 0,
			'holdToken'   => '',
			'expiresAt'   => 0,
			'secondsLeft' => 0,
			'firstName'   => $first_name,
			'lastName'    => $last_name,
			'email'       => $email,
			'phone'       => '',
		)
	);
}

$wrapper_classes = 'venuestack-booking-panel';
if ( ! $is_logged_in ) {
	$wrapper_classes .= ' is-locked';
}

$wrapper = get_block_wrapper_attributes(
	array(
		'class'               => $wrapper_classes,
		'data-wp-interactive' => 'venuestack/booking-panel',
		'data-wp-init'        => 'callbacks.init',
		'data-wp-class--is-locked' => 'state.isLoggedOut',
	)
);

$disabled_attr = $is_logged_in ? '' : ' disabled';
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div
		class="venuestack-booking-panel__login-gate"
		data-wp-bind--hidden="state.hideLoginGate"
		<?php echo $is_logged_in ? ' hidden' : ''; ?>
		role="status"
	>
		<p class="venuestack-booking-panel__login-gate-text is-style-body">
			<?php echo esc_html__( 'Log in to hold a date and confirm your booking.', 'venuestack-core' ); ?>
		</p>
		<p class="venuestack-booking-panel__login-gate-actions">
			<a class="venuestack-booking-panel__button" href="<?php echo esc_url( $login_url ); ?>">
				<?php echo esc_html__( 'Log in', 'venuestack-core' ); ?>
			</a>
			<?php if ( $register_url ) : ?>
				<a class="venuestack-booking-panel__link" href="<?php echo esc_url( $register_url ); ?>">
					<?php echo esc_html__( 'Create account', 'venuestack-core' ); ?>
				</a>
			<?php endif; ?>
		</p>
	</div>

	<p
		class="venuestack-booking-panel__error is-style-body"
		data-wp-bind--hidden="state.hideError"
		hidden
		role="alert"
		data-wp-text="state.error"
	></p>

	<div class="venuestack-booking-panel__step" data-wp-bind--hidden="state.hideSchedule">
		<div class="venuestack-booking-panel__grid">
			<label class="venuestack-booking-panel__field">
				<span class="is-style-label"><?php echo esc_html__( 'Date', 'venuestack-core' ); ?></span>
				<input
					type="text"
					class="venuestack-booking-panel__input venuestack-booking-panel__datepicker"
					readonly
					required
					autocomplete="off"
					placeholder="<?php echo esc_attr__( 'Select a date', 'venuestack-core' ); ?>"
					aria-label="<?php echo esc_attr__( 'Booking date', 'venuestack-core' ); ?>"
					data-wp-bind--disabled="state.isFormDisabled"
					<?php echo $disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				/>
			</label>

			<label class="venuestack-booking-panel__field">
				<span class="is-style-label"><?php echo esc_html__( 'Start time', 'venuestack-core' ); ?></span>
				<input
					type="text"
					class="venuestack-booking-panel__input venuestack-booking-panel__timepicker"
					readonly
					required
					autocomplete="off"
					placeholder="<?php echo esc_attr__( 'Select a time', 'venuestack-core' ); ?>"
					aria-label="<?php echo esc_attr__( 'Booking start time', 'venuestack-core' ); ?>"
					data-wp-bind--disabled="state.isFormDisabled"
					<?php echo $disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				/>
			</label>

			<label class="venuestack-booking-panel__field">
				<span class="is-style-label"><?php echo esc_html__( 'Duration', 'venuestack-core' ); ?></span>
				<select
					class="venuestack-booking-panel__input"
					data-wp-on--change="actions.setHours"
					data-wp-bind--value="state.hours"
					data-wp-bind--disabled="state.isFormDisabled"
					<?php echo $disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				>
					<?php foreach ( $durations as $hours ) : ?>
						<option value="<?php echo esc_attr( (string) $hours ); ?>" <?php selected( $hours, $min_hours ); ?>>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %d: number of hours */
									_n( '%d hour', '%d hours', $hours, 'venuestack-core' ),
									$hours
								)
							);
							?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>

			<label class="venuestack-booking-panel__field">
				<span class="is-style-label"><?php echo esc_html__( 'Guests', 'venuestack-core' ); ?></span>
				<input
					type="number"
					class="venuestack-booking-panel__input"
					min="1"
					<?php if ( $max_cap > 0 ) : ?>
						max="<?php echo esc_attr( (string) $max_cap ); ?>"
					<?php endif; ?>
					data-wp-bind--value="state.headcount"
					data-wp-on--change="actions.setHeadcount"
					data-wp-bind--disabled="state.isFormDisabled"
					<?php echo $disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				/>
			</label>

			<label class="venuestack-booking-panel__field venuestack-booking-panel__field--wide">
				<span class="is-style-label"><?php echo esc_html__( 'Package', 'venuestack-core' ); ?></span>
				<select
					class="venuestack-booking-panel__input"
					data-wp-on--change="actions.setPackage"
					data-wp-bind--value="state.packageId"
					data-wp-bind--disabled="state.isFormDisabled"
					<?php echo $disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				>
					<option value="0"><?php echo esc_html__( 'Space only', 'venuestack-core' ); ?></option>
					<?php foreach ( $packages as $package ) : ?>
						<option value="<?php echo esc_attr( (string) $package['id'] ); ?>">
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: package name, 2: price per head */
									__( '%1$s — $%2$s/guest', 'venuestack-core' ),
									$package['name'],
									number_format_i18n( $package['price_per_head'], 0 )
								)
							);
							?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>

		<div class="venuestack-booking-panel__summary">
			<p class="is-style-body">
				<?php echo esc_html__( 'Estimated total', 'venuestack-core' ); ?>
				<strong data-wp-text="state.estimateLabel"></strong>
			</p>
			<p class="venuestack-booking-panel__hint is-style-body">
				<?php echo esc_html__( 'Final price is confirmed server-side when you check out.', 'venuestack-core' ); ?>
			</p>
		</div>

		<button
			type="button"
			class="venuestack-booking-panel__button"
			data-wp-on--click="actions.createHold"
			data-wp-bind--disabled="state.isHoldDisabled"
			<?php echo $disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
			<span data-wp-bind--hidden="state.hideIdle"><?php echo esc_html__( 'Hold this slot', 'venuestack-core' ); ?></span>
			<span data-wp-bind--hidden="state.hideBusy" hidden><?php echo esc_html__( 'Holding…', 'venuestack-core' ); ?></span>
		</button>
	</div>

	<div class="venuestack-booking-panel__step" data-wp-bind--hidden="state.hideCheckout" hidden>
		<div class="venuestack-booking-panel__hold-banner">
			<p class="is-style-body">
				<?php echo esc_html__( 'Soft hold active', 'venuestack-core' ); ?>
				—
				<span data-wp-text="state.countdownLabel"></span>
			</p>
			<button
				type="button"
				class="venuestack-booking-panel__link"
				data-wp-on--click="actions.backToSchedule"
				data-wp-bind--disabled="state.isFormDisabled"
			><?php echo esc_html__( 'Change time', 'venuestack-core' ); ?></button>
		</div>

		<div class="venuestack-booking-panel__grid">
			<label class="venuestack-booking-panel__field">
				<span class="is-style-label"><?php echo esc_html__( 'First name', 'venuestack-core' ); ?></span>
				<input
					type="text"
					class="venuestack-booking-panel__input"
					autocomplete="given-name"
					required
					data-wp-bind--value="state.firstName"
					data-wp-on--input="actions.setFirstName"
					data-wp-bind--disabled="state.isFormDisabled"
					<?php echo $disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				/>
			</label>
			<label class="venuestack-booking-panel__field">
				<span class="is-style-label"><?php echo esc_html__( 'Last name', 'venuestack-core' ); ?></span>
				<input
					type="text"
					class="venuestack-booking-panel__input"
					autocomplete="family-name"
					required
					data-wp-bind--value="state.lastName"
					data-wp-on--input="actions.setLastName"
					data-wp-bind--disabled="state.isFormDisabled"
					<?php echo $disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				/>
			</label>
			<label class="venuestack-booking-panel__field">
				<span class="is-style-label"><?php echo esc_html__( 'Email', 'venuestack-core' ); ?></span>
				<input
					type="email"
					class="venuestack-booking-panel__input"
					autocomplete="email"
					required
					data-wp-bind--value="state.email"
					data-wp-on--input="actions.setEmail"
					data-wp-bind--disabled="state.isFormDisabled"
					<?php echo $disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				/>
			</label>
			<label class="venuestack-booking-panel__field">
				<span class="is-style-label"><?php echo esc_html__( 'Phone', 'venuestack-core' ); ?></span>
				<input
					type="tel"
					class="venuestack-booking-panel__input"
					autocomplete="tel"
					data-wp-bind--value="state.phone"
					data-wp-on--input="actions.setPhone"
					data-wp-bind--disabled="state.isFormDisabled"
					<?php echo $disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				/>
			</label>
		</div>

		<div class="venuestack-booking-panel__summary">
			<p class="is-style-body">
				<?php echo esc_html__( 'Estimated total', 'venuestack-core' ); ?>
				<strong data-wp-text="state.estimateLabel"></strong>
			</p>
		</div>

		<button
			type="button"
			class="venuestack-booking-panel__button"
			data-wp-on--click="actions.checkout"
			data-wp-bind--disabled="state.isHoldDisabled"
			<?php echo $disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
			<span data-wp-bind--hidden="state.hideIdle"><?php echo esc_html__( 'Confirm booking', 'venuestack-core' ); ?></span>
			<span data-wp-bind--hidden="state.hideBusy" hidden><?php echo esc_html__( 'Creating order…', 'venuestack-core' ); ?></span>
		</button>
	</div>
</div>
