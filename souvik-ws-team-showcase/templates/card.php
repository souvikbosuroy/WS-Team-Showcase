<?php
/**
 * Card partial template.
 *
 * Available variables (set by class-widget.php before including this file):
 *
 * @var string $photo_url    Absolute image URL.
 * @var string $name         Member name.
 * @var string $designation  Job title.
 * @var string $department   Department slug / label.
 * @var string $bio          Short bio (may contain allowed HTML via wp_kses_post).
 * @var array  $socials      Array of [ 'platform' => string, 'url' => string|array ].
 * @var string $btn_label    CTA button text.
 * @var string $btn_url      CTA button URL.
 * @var array  $btn_url_raw  Raw button_url field (may be array with is_external).
 * @var bool   $lazy_load    Whether to add loading="lazy" to the image.
 * @var bool   $popup_enable Whether popup is enabled.
 * @var string $popup_trigger Popup trigger: 'card_click' or 'button'.
 * @var string $card_class   Pre-built card CSS classes.
 * @var bool   $img_zoom     Whether hover-zoom class should be applied.
 *
 * @package Souvik_WS_Team_Showcase
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

$open_trigger_attr = '';
if ( $popup_enable && 'card_click' === $popup_trigger ) {
	$open_trigger_attr = ' role="button" tabindex="0" data-souvik-ws-popup-open="true"';
}
?>
<article class="<?php echo esc_attr( $card_class ); ?>"
	data-dept="<?php echo esc_attr( $department ); ?>"
	data-index="<?php echo esc_attr( (string) $index ); ?>"
	<?php echo $open_trigger_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>

	<?php if ( $photo_url ) : ?>
	<div class="souvik-ws-team-card__image">
		<img
			src="<?php echo esc_url( $photo_url ); ?>"
			class="souvik-ws-team-card__img"
			alt="<?php echo esc_attr( $name ); ?>"
			<?php echo $lazy_load ? 'loading="lazy"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
	</div>
	<?php endif; ?>

	<div class="souvik-ws-team-card__body">

		<h3 class="souvik-ws-team-card__name"><?php echo esc_html( $name ); ?></h3>

		<?php if ( $designation ) : ?>
		<p class="souvik-ws-team-card__role"><?php echo esc_html( $designation ); ?></p>
		<?php endif; ?>

		<?php if ( $department ) : ?>
		<span class="souvik-ws-team-dept-badge"><?php echo esc_html( $department ); ?></span>
		<?php endif; ?>

		<?php if ( $bio ) : ?>
		<p class="souvik-ws-team-card__bio"><?php echo wp_kses_post( $bio ); ?></p>
		<?php endif; ?>

		<?php
		$has_socials = ! empty( $socials );
		$has_button  = ! empty( $btn_label );

		if ( $has_socials || $has_button ) :
		?>
		<div class="souvik-ws-team-card__bottom">
			<?php if ( $has_socials ) : ?>
			<ul class="souvik-ws-team-card__social" aria-label="<?php esc_attr_e( 'Social links', 'souvik-ws-team-showcase' ); ?>">
				<?php
				$social_icons = [
					'facebook'  => 'fab fa-facebook-f',
					'twitter'   => 'fab fa-x-twitter',
					'linkedin'  => 'fab fa-linkedin-in',
					'instagram' => 'fab fa-instagram',
					'youtube'   => 'fab fa-youtube',
					'github'    => 'fab fa-github',
					'dribbble'  => 'fab fa-dribbble',
					'behance'   => 'fab fa-behance',
				];



				foreach ( $socials as $social ) :
					$platform = sanitize_key( $social['platform'] ?? '' );
					$soc_url  = is_array( $social['url'] ?? '' )
						? ( $social['url']['url'] ?? '' )
						: (string) ( $social['url'] ?? '' );

					if ( ! $soc_url ) :
						continue;
					endif;

					$icon_src = $social['icon_source'] ?? 'default';
					$custom_icon = $social['custom_icon'] ?? [];
					$custom_image = $social['custom_image'] ?? [];
					$icon_html = '';

					if ( 'image' === $icon_src && ! empty( $custom_image['url'] ) ) {
						$icon_html = '<img src="' . esc_url( $custom_image['url'] ) . '" alt="' . esc_attr( $platform ) . '" class="souvik-ws-social-custom-img" aria-hidden="true">';
					} elseif ( 'icon' === $icon_src && ! empty( $custom_icon['value'] ) ) {
						ob_start();
						\Elementor\Icons_Manager::render_icon( $custom_icon, [ 'aria-hidden' => 'true' ] );
						$icon_html = ob_get_clean();
					} else {
						// Default: render real brand icons (Font Awesome) instead of placeholder letters.
						$fa_map = [
							'facebook'  => 'fab fa-facebook-f',
							'twitter'   => 'fab fa-x-twitter',
							'linkedin'  => 'fab fa-linkedin-in',
							'instagram' => 'fab fa-instagram',
							'youtube'   => 'fab fa-youtube',
							'github'    => 'fab fa-github',
							'dribbble'  => 'fab fa-dribbble',
							'behance'   => 'fab fa-behance',
						];

						$fa_class = $fa_map[ $platform ] ?? '';
						if ( $fa_class ) {
							$icon_html = '<i class="' . esc_attr( $fa_class ) . '" aria-hidden="true"></i>';
						} else {
							$icon_html = '<span aria-hidden="true">?</span>';
						}
					}

				?>
				<li>
					<a
						href="<?php echo esc_url( $soc_url ); ?>"
						class="souvik-ws-social-icon souvik-ws-social-icon--<?php echo esc_attr( $platform ); ?>"
						target="_blank"
						rel="noopener noreferrer"
						aria-label="<?php echo esc_attr( ucfirst( $platform ) ); ?>"
					>
						<?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				</li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>

			<?php if ( $has_button ) : ?>
			<div class="souvik-ws-team-card__btn-wrap">
				<?php
				$external    = is_array( $btn_url_raw ) && ! empty( $btn_url_raw['is_external'] );
				$nofollow    = is_array( $btn_url_raw ) && ! empty( $btn_url_raw['nofollow'] );
				$target_attr = $external ? ' target="_blank" rel="noopener' . ( $nofollow ? ' nofollow' : '' ) . '"' : '';
				$popup_attr  = ( $popup_enable && 'button' === $popup_trigger )
					? ' data-souvik-ws-popup-open="true"'
					: '';
				?>
				<a
					class="souvik-ws-team-card__btn"
					href="<?php echo esc_url( $btn_url ); ?>"
					<?php echo $target_attr . $popup_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				>
					<?php echo esc_html( $btn_label ); ?>
				</a>
			</div>
			<?php endif; ?>
		</div><!-- .souvik-ws-team-card__bottom -->
		<?php endif; ?>

	</div><!-- .souvik-ws-team-card__body -->

</article>
