<?php
/**
 * Print spaces directory results grid.
 *
 * @package VenuestackCore
 *
 * @param WP_Post[] $spaces Published venue spaces.
 */
function venuestack_core_print_directory_results(array $spaces): void
{
	?>
	<div class="venuestack-directory-results alignfull has-ink-color has-plaster-background-color has-text-color has-background"
		style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--70)">
		<div class="venuestack-directory-grid"
			style="max-width:1200px;margin-inline:auto;padding-inline:var(--wp--preset--spacing--40)">
			<div class="venuestack-directory-query">
				<?php if ($spaces): ?>
					<ul class="wp-block-post-template is-layout-grid"
						style="list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:var(--wp--preset--spacing--40)">
						<?php foreach ($spaces as $space):
							echo venuestack_core_render_directory_space_card($space);
						endforeach; ?>
					</ul>
					<p class="venuestack-directory-empty is-style-lede has-muted-color has-text-color"
						data-wp-bind--hidden="state.hideEmpty" hidden>
						<?php echo esc_html__('No spaces match these filters.', 'venuestack-core'); ?>
					</p>
				<?php else: ?>
					<p class="is-style-lede has-muted-color has-text-color">
						<?php echo esc_html__('No spaces are published yet.', 'venuestack-core'); ?>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}
