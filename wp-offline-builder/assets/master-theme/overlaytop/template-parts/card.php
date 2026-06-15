<?php
/**
 * Post card partial.
 *
 * @package Overlaytop
 */

$overlaytop_cats = get_the_category();
?>
<article <?php post_class( 'post-card' ); ?>>
	<a class="post-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail(
				'overlaytop_card',
				array(
					'loading' => 'lazy',
					'alt'     => the_title_attribute( array( 'echo' => false ) ),
				)
			);
		}
		?>
	</a>
	<div class="post-card__body">
		<?php if ( ! empty( $overlaytop_cats ) ) : ?>
			<a class="cat-pill" href="<?php echo esc_url( get_category_link( $overlaytop_cats[0]->term_id ) ); ?>">
				<?php echo esc_html( $overlaytop_cats[0]->name ); ?>
			</a>
		<?php endif; ?>
		<h3 class="post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<div class="post-card__meta">
			<span><?php echo esc_html( get_the_date() ); ?></span>
			<span aria-hidden="true">&middot;</span>
			<span><?php printf( esc_html__( '%d min read', 'overlaytop' ), (int) overlaytop_reading_time() ); ?></span>
		</div>
	</div>
</article>
