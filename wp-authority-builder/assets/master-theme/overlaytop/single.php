<?php
/**
 * Single post — full editorial article anatomy.
 *
 * @package Overlaytop
 */

get_header();

while ( have_posts() ) :
	the_post();
	$ot_cats   = get_the_category();
	$ot_author = (int) get_the_author_meta( 'ID' );
	?>
	<article <?php post_class( 'single' ); ?>>
		<?php overlaytop_breadcrumbs(); ?>

		<header class="single-hero container">
			<?php if ( ! empty( $ot_cats ) ) : ?>
				<a class="cat-pill" href="<?php echo esc_url( get_category_link( $ot_cats[0]->term_id ) ); ?>"><?php echo esc_html( $ot_cats[0]->name ); ?></a>
			<?php endif; ?>
			<h1 class="single-hero__title"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="single-hero__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
			<div class="single-hero__byline">
				<?php echo get_avatar( $ot_author, 46 ); ?>
				<div>
					<a class="single-hero__author" rel="author" href="<?php echo esc_url( get_author_posts_url( $ot_author ) ); ?>">
						<?php printf( esc_html__( 'By %s', 'overlaytop' ), esc_html( get_the_author() ) ); ?>
					</a>
					<span class="single-hero__dates">
						<?php
						printf(
							/* translators: 1: updated date, 2: reading time minutes. */
							esc_html__( 'Updated %1$s &middot; %2$d min read', 'overlaytop' ),
							esc_html( get_the_modified_date() ),
							(int) overlaytop_reading_time()
						);
						?>
					</span>
				</div>
			</div>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="single-hero__media container">
				<?php the_post_thumbnail( 'overlaytop_hero', array( 'fetchpriority' => 'high' ) ); ?>
				<?php if ( get_the_post_thumbnail_caption() ) : ?>
					<figcaption><?php echo esc_html( get_the_post_thumbnail_caption() ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endif; ?>

		<div class="single__body container">
			<?php
			$ot_toc = overlaytop_get_toc();
			if ( $ot_toc ) {
				echo $ot_toc; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
			}
			?>
			<div class="single__content entry-content">
				<?php the_content(); ?>
			</div>
		</div>

		<?php
		overlaytop_author_box( $ot_author );
		overlaytop_related_posts();
		?>
	</article>
	<?php
endwhile;

get_footer();
