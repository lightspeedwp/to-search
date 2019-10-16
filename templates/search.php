<?php
/**
 * Accommodation Archive
 *
 * @package  tour-operator
 * @category search
 */

get_header(); ?>

	<?php lsx_content_wrap_before(); ?>

	<div id="primary" class="content-area <?php echo esc_attr( lsx_main_class() ); ?>">

		<?php lsx_content_before(); ?>

		<main id="main" class="site-main" role="main">

			<?php lsx_content_top(); ?>

			<?php
				global $lsx_to_archive;
				$lsx_to_archive = 1;

				$hidden_class = '';
				$show_loader  = false;
				if ( function_exists( 'FWP' ) ) {
					if ( isset( FWP()->ajax->is_preload ) && ( 1 === FWP()->ajax->is_preload || '1' === FWP()->ajax->is_preload || true === FWP()->ajax->is_preload ) ) {
						$show_loader = true;
					}
				}
			?>

			<?php if ( have_posts() ) : ?>

				<div class="row lsx-to-archive-items lsx-to-archive-template-<?php echo esc_attr( tour_operator()->archive_layout ); ?> lsx-to-archive-template-image-<?php echo esc_attr( tour_operator()->archive_list_layout_image_style ); ?> <?php echo esc_attr( $hidden_class ); ?>">
					<?php
					if ( true === $show_loader ) {
						?>
						<div class="facetwp-loading-wrapper"><div class="facetwp-loading"></div></div>
						<?php
					} else {
						while ( have_posts() ) :
							the_post();
							?>
							<div class="<?php echo esc_attr( lsx_to_archive_class( 'lsx-to-archive-item' ) ); ?>">
								<?php lsx_to_content( 'content', get_post_type() ); ?>
							</div>
							<?php
						endwhile;
					}
					?>
				</div>

			<?php else : ?>
				<?php
				if ( true === $show_loader ) {
					?>
					<div class="row lsx-to-archive-items lsx-to-archive-template-<?php echo esc_attr( tour_operator()->archive_layout ); ?> lsx-to-archive-template-image-<?php echo esc_attr( tour_operator()->archive_list_layout_image_style ); ?> <?php echo esc_attr( $hidden_class ); ?>">
						<div class="facetwp-loading-wrapper"><div class="facetwp-loading"></div></div>
					</div>
					<?php

				} else {
					get_template_part( 'partials/content', 'none' );
				}
				?>

			<?php endif; ?>

			<?php
				$lsx_to_archive = 0;
			?>

			<?php lsx_content_bottom(); ?>

		</main><!-- #main -->

		<?php lsx_content_after(); ?>

	</div><!-- #primary -->

	<?php lsx_content_wrap_after(); ?>

<?php get_sidebar(); ?>

<?php get_footer();
