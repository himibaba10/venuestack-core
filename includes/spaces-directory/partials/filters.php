<?php
/**
 * Print spaces directory filter chips + results meta.
 *
 * @package VenuestackCore
 *
 * @param array<int, array{slug:string,name:string}> $types     Space type terms.
 * @param array<int, array{slug:string,name:string}> $amenities Amenity terms.
 */
function venuestack_core_print_directory_filters( array $types, array $amenities ): void {
	?>
<div class="venuestack-directory-filters alignfull">
	<div class="venuestack-directory-filters__inner">
		<div class="venuestack-directory-filters__row">
			<p class="is-style-label has-muted-color has-text-color">
				<?php echo esc_html__( 'Space type', 'venuestack-core' ); ?>
			</p>
			<div
				class="venuestack-directory-filters__chips"
				role="group"
				aria-label="<?php echo esc_attr__( 'Filter by space type', 'venuestack-core' ); ?>"
			>
				<button
					type="button"
					class="venuestack-directory-chip"
					data-wp-on--click="actions.selectType"
					data-wp-bind--aria-pressed="state.isAllTypesActive"
					data-wp-class--is-active="state.isAllTypesActive"
				><?php echo esc_html__( 'All', 'venuestack-core' ); ?></button>
				<?php foreach ( $types as $type ) : ?>
					<?php
					$type_ctx = function_exists( 'wp_interactivity_data_wp_context' )
						? wp_interactivity_data_wp_context( array( 'filterType' => (string) $type['slug'] ) )
						: '';
					?>
					<button
						type="button"
						class="venuestack-directory-chip"
						<?php echo $type_ctx; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						data-wp-on--click="actions.selectType"
						data-wp-bind--aria-pressed="state.isTypeActive"
						data-wp-class--is-active="state.isTypeActive"
					><?php echo esc_html( $type['name'] ); ?></button>
				<?php endforeach; ?>
			</div>
		</div>

		<?php if ( $amenities ) : ?>
			<div class="venuestack-directory-filters__row">
				<p class="is-style-label has-muted-color has-text-color">
					<?php echo esc_html__( 'Amenities', 'venuestack-core' ); ?>
				</p>
				<div
					class="venuestack-directory-filters__chips"
					role="group"
					aria-label="<?php echo esc_attr__( 'Filter by amenity', 'venuestack-core' ); ?>"
				>
					<?php foreach ( $amenities as $amenity ) : ?>
						<?php
						$amenity_ctx = function_exists( 'wp_interactivity_data_wp_context' )
							? wp_interactivity_data_wp_context( array( 'filterAmenity' => (string) $amenity['slug'] ) )
							: '';
						?>
						<button
							type="button"
							class="venuestack-directory-chip is-amenity"
							<?php echo $amenity_ctx; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							data-wp-on--click="actions.selectAmenity"
							data-wp-bind--aria-pressed="state.isAmenityActive"
							data-wp-class--is-active="state.isAmenityActive"
						><?php echo esc_html( $amenity['name'] ); ?></button>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="venuestack-directory-filters__meta">
			<p class="is-style-body has-muted-color has-text-color">
				<span data-wp-text="state.resultsLabel"></span>
				<strong class="has-ink-color" data-wp-text="state.visible"></strong>
				<span data-wp-text="state.ofLabel"></span>
				<strong class="has-ink-color" data-wp-text="state.total"></strong>
			</p>
			<button
				type="button"
				class="venuestack-directory-clear is-style-label"
				data-wp-on--click="actions.clearFilters"
				data-wp-bind--hidden="state.hideClear"
			><?php echo esc_html__( 'Clear filters', 'venuestack-core' ); ?></button>
		</div>
	</div>
</div>
	<?php
}
