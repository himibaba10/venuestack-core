<?php
/**
 * Render: customer My Bookings list (view only).
 *
 * @package VenuestackCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

unset( $attributes, $content, $block );

$wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'venuestack-my-bookings',
	)
);

if ( ! is_user_logged_in() ) {
	$login_url = wp_login_url( venuestack_core_get_my_bookings_url() );
	?>
	<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="venuestack-my-bookings__gate" role="status">
			<p class="is-style-body venuestack-my-bookings__gate-text">
				<?php echo esc_html__( 'Log in to view your bookings.', 'venuestack-core' ); ?>
			</p>
			<p class="venuestack-my-bookings__gate-actions">
				<a class="venuestack-my-bookings__button" href="<?php echo esc_url( $login_url ); ?>">
					<?php echo esc_html__( 'Log in', 'venuestack-core' ); ?>
				</a>
			</p>
		</div>
	</div>
	<?php
	return;
}

$rows = venuestack_core_get_bookings_for_user( get_current_user_id() );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( array() === $rows ) : ?>
		<div class="venuestack-my-bookings__empty" role="status">
			<p class="is-style-body">
				<?php echo esc_html__( 'No bookings yet. Hold a space to see it here after checkout.', 'venuestack-core' ); ?>
			</p>
			<p class="venuestack-my-bookings__empty-actions">
				<a class="venuestack-my-bookings__button" href="<?php echo esc_url( home_url( '/spaces/' ) ); ?>">
					<?php echo esc_html__( 'Browse spaces', 'venuestack-core' ); ?>
				</a>
			</p>
		</div>
	<?php else : ?>
		<ul class="venuestack-my-bookings__list">
			<?php foreach ( $rows as $row ) : ?>
				<li class="venuestack-my-bookings__row">
					<div class="venuestack-my-bookings__row-main">
						<?php if ( ! empty( $row['space_url'] ) ) : ?>
							<a class="venuestack-my-bookings__space is-style-card" href="<?php echo esc_url( (string) $row['space_url'] ); ?>">
								<?php echo esc_html( (string) $row['space_title'] ); ?>
							</a>
						<?php else : ?>
							<span class="venuestack-my-bookings__space is-style-card">
								<?php echo esc_html( (string) $row['space_title'] ); ?>
							</span>
						<?php endif; ?>

						<span class="venuestack-my-bookings__status is-status-<?php echo esc_attr( sanitize_html_class( (string) $row['status'] ) ); ?>">
							<?php echo esc_html( (string) $row['status_label'] ); ?>
						</span>
					</div>

					<p class="venuestack-my-bookings__when is-style-body">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: start datetime, 2: end datetime */
								__( '%1$s – %2$s', 'venuestack-core' ),
								(string) $row['start_label'],
								(string) $row['end_label']
							)
						);
						?>
					</p>

					<p class="venuestack-my-bookings__order is-style-body">
						<?php if ( ! empty( $row['order_url'] ) ) : ?>
							<a href="<?php echo esc_url( (string) $row['order_url'] ); ?>">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: order number */
										__( 'Order #%s', 'venuestack-core' ),
										(string) $row['order_number']
									)
								);
								?>
							</a>
						<?php else : ?>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: order number */
									__( 'Order #%s', 'venuestack-core' ),
									(string) $row['order_number']
								)
							);
							?>
						<?php endif; ?>
					</p>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
