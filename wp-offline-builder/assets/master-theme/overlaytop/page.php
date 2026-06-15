<?php
/**
 * Single page - static pages (About, Contact, legal/trust pages).
 * Reuses the article hero/body styling, minus post-only chrome
 * (category pill, byline, TOC, author box, related posts).
 *
 * @package Overlaytop
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'single single--page' ); ?>>
		<?php overlaytop_breadcrumbs(); ?>

		<header class="single-hero container">
			<h1 class="single-hero__title"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="single-hero__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="single-hero__media container">
				<?php the_post_thumbnail( 'overlaytop_hero', array( 'fetchpriority' => 'high' ) ); ?>
			</figure>
		<?php endif; ?>

		<div class="single__body container">
			<div class="single__content entry-content">
				<?php
				the_content();
				wp_link_pages(
					array(
						'before' => '<nav class="page-links">' . esc_html__( 'Pages:', 'overlaytop' ),
						'after'  => '</nav>',
					)
				);
				?>
			</div>
		</div>
	</article>
	<?php
endwhile;

get_footer();
