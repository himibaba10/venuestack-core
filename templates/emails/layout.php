<?php
/**
 * Shared HTML chrome for VenueStack booking emails.
 *
 * Included from booking-*.php after those files set $headline / $intro.
 *
 * @package VenuestackCore
 *
 * @var array<string, mixed>  $context  Booking email context.
 * @var array<string, string> $brand    Inline brand colors.
 * @var string                $type     Email type slug.
 * @var string                $headline Email heading.
 * @var string                $intro    Lead paragraph.
 */

defined( 'ABSPATH' ) || exit;

$site_name = (string) ( $context['site_name'] ?? '' );
$site_url  = (string) ( $context['site_url'] ?? '' );
$name      = (string) ( $context['full_name'] ?? '' );
$space     = (string) ( $context['space_title'] ?? '' );
$start     = (string) ( $context['start_label'] ?? '' );
$end       = (string) ( $context['end_label'] ?? '' );
$order     = (string) ( $context['order_number'] ?? '' );

$ink     = esc_attr( $brand['ink'] ?? '#211D1B' );
$cream   = esc_attr( $brand['cream'] ?? '#F2ECE3' );
$surface = esc_attr( $brand['surface'] ?? '#F8F4ED' );
$stone   = esc_attr( $brand['stone'] ?? '#E4DACB' );
$accent  = esc_attr( $brand['accent'] ?? '#A6763D' );
$muted   = esc_attr( $brand['muted'] ?? '#857F78' );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php echo esc_html( $headline ); ?></title>
</head>
<body style="margin:0;padding:0;background-color:<?php echo $cream; ?>;">
	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:<?php echo $cream; ?>;padding:32px 16px;">
		<tr>
			<td align="center">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background-color:<?php echo $surface; ?>;border:1px solid <?php echo $stone; ?>;border-radius:4px;overflow:hidden;">
					<tr>
						<td style="background-color:<?php echo $ink; ?>;padding:20px 28px;">
							<p style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:22px;line-height:1.3;color:<?php echo $surface; ?>;">
								<?php echo esc_html( $site_name ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<td style="padding:28px;">
							<p style="margin:0 0 8px;font-family:Georgia,'Times New Roman',serif;font-size:24px;line-height:1.3;color:<?php echo $ink; ?>;">
								<?php echo esc_html( $headline ); ?>
							</p>
							<p style="margin:0 0 20px;font-family:system-ui,-apple-system,Segoe UI,sans-serif;font-size:15px;line-height:1.55;color:<?php echo $ink; ?>;">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: customer name */
										__( 'Hi %s,', 'venuestack-core' ),
										$name
									)
								);
								?>
							</p>
							<p style="margin:0 0 24px;font-family:system-ui,-apple-system,Segoe UI,sans-serif;font-size:15px;line-height:1.55;color:<?php echo $ink; ?>;">
								<?php echo esc_html( $intro ); ?>
							</p>

							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid <?php echo $stone; ?>;border-radius:4px;background-color:<?php echo $cream; ?>;">
								<tr>
									<td style="padding:16px 18px;font-family:system-ui,-apple-system,Segoe UI,sans-serif;font-size:14px;line-height:1.6;color:<?php echo $ink; ?>;">
										<p style="margin:0 0 10px;">
											<strong style="color:<?php echo $accent; ?>;"><?php echo esc_html__( 'Space', 'venuestack-core' ); ?></strong><br />
											<?php echo esc_html( $space ); ?>
										</p>
										<p style="margin:0 0 10px;">
											<strong style="color:<?php echo $accent; ?>;"><?php echo esc_html__( 'Starts', 'venuestack-core' ); ?></strong><br />
											<?php echo esc_html( $start ); ?>
										</p>
										<p style="margin:0 0 10px;">
											<strong style="color:<?php echo $accent; ?>;"><?php echo esc_html__( 'Ends', 'venuestack-core' ); ?></strong><br />
											<?php echo esc_html( $end ); ?>
										</p>
										<?php if ( '' !== $order && '0' !== $order ) : ?>
											<p style="margin:0;">
												<strong style="color:<?php echo $accent; ?>;"><?php echo esc_html__( 'Order', 'venuestack-core' ); ?></strong><br />
												#<?php echo esc_html( $order ); ?>
											</p>
										<?php endif; ?>
									</td>
								</tr>
							</table>

							<p style="margin:24px 0 0;font-family:system-ui,-apple-system,Segoe UI,sans-serif;font-size:15px;line-height:1.55;color:<?php echo $ink; ?>;">
								<?php echo esc_html__( 'Thanks,', 'venuestack-core' ); ?><br />
								<?php echo esc_html( $site_name ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<td style="padding:14px 28px 20px;border-top:1px solid <?php echo $stone; ?>;">
							<p style="margin:0;font-family:system-ui,-apple-system,Segoe UI,sans-serif;font-size:12px;line-height:1.5;color:<?php echo $muted; ?>;">
								<a href="<?php echo esc_url( $site_url ); ?>" style="color:<?php echo $muted; ?>;text-decoration:underline;">
									<?php echo esc_html( $site_url ); ?>
								</a>
							</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
