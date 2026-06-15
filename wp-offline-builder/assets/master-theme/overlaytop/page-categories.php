<?php
/**
 * Template Name: Categories Hub
 *
 * @package Overlaytop
 */

get_header();
?>
<div class="container section">
	<header class="section__head">
		<div>
			<span class="eyebrow"><?php esc_html_e( 'Browse', 'overlaytop' ); ?></span>
			<h1 style="margin-top:.4rem"><?php the_title(); ?></h1>
			<p class="lede" style="margin-top:.6rem"><?php esc_html_e( 'Every Overlaytop guide, sorted by the part of the trip it saves you money on.', 'overlaytop' ); ?></p>
		</div>
	</header>

	<div class="cat-hub">
		<?php
		$ot_cats = get_categories( array( 'hide_empty' => false, 'orderby' => 'name' ) );
		foreach ( $ot_cats as $cat ) :
			$img = '';
			$q   = new WP_Query( array( 'category__in' => array( $cat->term_id ), 'posts_per_page' => 1, 'no_found_rows' => true ) );
			if ( $q->have_posts() ) {
				$q->the_post();
				$img = get_the_post_thumbnail_url( get_the_ID(), 'overlaytop_card' );
			}
			wp_reset_postdata();
			?>
			<a class="cat-card" href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>">
				<span class="cat-card__media">
					<?php if ( $img ) : ?><img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy"><?php endif; ?>
				</span>
				<span class="cat-card__body">
					<span class="cat-card__title"><?php echo esc_html( $cat->name ); ?></span>
					<span class="cat-card__desc"><?php echo esc_html( $cat->description ); ?></span>
					<span class="cat-card__count"><?php printf( esc_html( _n( '%d guide', '%d guides', $cat->count, 'overlaytop' ) ), (int) $cat->count ); ?></span>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
</div>
<?php
get_footer();
